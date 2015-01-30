<?php

/*
 * Copyright (C) SiteSupra SIA, Riga, Latvia, 2015
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 */

namespace Supra\Package\Cms\Pages\Editable\Filter;

use Supra\Core\DependencyInjection\ContainerAware;
use Supra\Core\DependencyInjection\ContainerInterface;
use Supra\Package\Cms\Editable\Filter\FilterInterface;
use Supra\Package\Cms\Entity\BlockProperty;
use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\Entity\ReferencedElement\ImageReferencedElement;
use Supra\Package\Cms\FileStorage\Exception\FileStorageException;
use Supra\Package\Cms\FileStorage\Exception\RuntimeException;
use Supra\Package\Cms\Pages\Editable\BlockPropertyAware;

class GalleryFilter implements FilterInterface, BlockPropertyAware, ContainerAware
{
	/**
	 * @var ContainerInterface
	 */
	protected $container;

	/**
	 * @var BlockProperty
	 */
	protected $blockProperty;

	/**
	 * {@inheritDoc}
	 * @return string
	 */
	public function filter($content, array $options = array())
	{
		$itemTemplate = ! empty($options['itemTemplate']) ? (string) $options['itemTemplate'] : '';
		$wrapperTemplate = ! empty($options['wrapperTemplate']) ? (string) $options['wrapperTemplate'] : '';

		$output = '';

		$fileStorage = $this->container['cms.file_storage'];
		/* @var $fileStorage \Supra\Package\Cms\FileStorage\FileStorage */

		foreach ($this->blockProperty->getMetadata() as $metadata) {
			/* @var $metadata \Supra\Package\Cms\Entity\BlockPropertyMetadata */

			$element = $metadata->getReferencedElement();

			if (! $element instanceof ImageReferencedElement
					|| $element->getSizeName() === null) {

				continue;
			}

			$image = $fileStorage->findImage($element->getImageId());

			if ($image === null) {
				continue;
			}

			$previewSize = $image->getImageSize($element->getSizeName());

			if ($previewSize === null) {
				continue;
			}

			$previewUrl = $fileStorage->getWebPath($image, $previewSize);

			$crop = isset($options['fullSizeCrop']) ? (bool) $options['fullSizeCrop'] : true;

			$fullSizeWidth = ! empty($options['fullSizeMaxWidth']) ? (int) $options['fullSizeMaxWidth'] : null;
			$fullSizeHeight = ! empty($options['fullSizeMaxHeight']) ? (int) $options['fullSizeMaxWidth'] : null;

			try {

				list($width, $height) = $this->getFullSizeDimensions($image, $fullSizeWidth, $fullSizeHeight, $crop);
				$fullSizeName = $fileStorage->createResizedImage($image, $width, $height, $crop);

			} catch (FileStorageException $e) {
				$this->container->getLogger()->warn($e->getMessage());
				continue;
			}

			$fullSize = $image->getImageSize($fullSizeName);
			$fullSizeUrl = $fileStorage->getWebPath($image, $fullSize);

			$itemData = array(
				'image' 		=> '<img src="' . $previewUrl . '" alt="' . $element->getAlternateText() . '" />',
				'imageUrl' 		=> $previewUrl,
				'title' 		=> $element->getTitle(),
				'description' 	=> $element->getDescription(),

				'fullSizeUrl' 	=> $fullSizeUrl,
				'fullSizeWidth' => $fullSize->getWidth(),
				'fullSizeHeight' => $fullSize->getHeight(),
			);

			$output .= preg_replace_callback(
				'/{{\s*(image|title|description|fullSizeUrl|fullSizeWidth|fullSizeHeight)\s*}}/',
				function ($matches) use ($itemData) {
					return $itemData[$matches[1]];
				},
				$itemTemplate
			);
		}

		return preg_replace('/{{\s*items\s*}}/', $output, $wrapperTemplate);
	}

	/**
	 * {@inheritDoc}
	 */
	public function setBlockProperty(BlockProperty $blockProperty)
	{
		$this->blockProperty = $blockProperty;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setContainer(ContainerInterface $container)
	{
		$this->container = $container;
	}

	/**
	 * @param Image $image
	 * @param int|null $targetWidth
	 * @param int|null $targetHeight
	 * @param bool $crop
	 * @return array
	 */
	private function getFullSizeDimensions(Image $image, $targetWidth = null, $targetHeight = null, $crop)
	{
		if ($targetWidth === null && $targetHeight === null) {
			$width = $image->getWidth();
			$height = $image->getHeight();
		} else if ($targetWidth === null) {
			$width = $image->getWidth();
			$height = $targetHeight;
		} else if ($targetHeight === null) {
			$width = $targetWidth;
			$height = $image->getHeight();
		}

		$wRatio = max($image->getWidth() / $width, 1);
		$hRatio = max($image->getHeight() / $height, 1);

		if (! $crop) {
			$wRatio = $hRatio = max($wRatio, $hRatio);
		}

		$width = round($image->getWidth() / $wRatio);
		$height = round($image->getHeight() / $hRatio);

		return array($width, $height);
	}
}
