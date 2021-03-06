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

namespace Supra\Package\Cms\Pages\Html;

use Supra\Package\Cms\Entity\Image;
use Supra\Package\Cms\FileStorage\FileStorage;
use Supra\Package\Cms\Html\HtmlTag;

class ImageTag extends HtmlTag
{
	/**
	 * @var Image
	 */
	protected $image;

	/**
	 * @var FileStorage
	 */
	protected $fileStorage;

	/**
	 * @var int image width in pixels
	 */
	protected $width;

	/**
	 * @var int image height in pixels
	 */
	protected $height;

	/**
	 * @var bool
	 */
	protected $crop = false;

	/**
	 * @TODO: is it wise to pass file storage here?
	 *
	 * @param Image $image
	 * @param FileStorage $fileStorage
	 */
	public function __construct(Image $image, FileStorage $fileStorage)
	{
		parent::__construct('img');

		$this->image = $image;
		$this->fileStorage = $fileStorage;
	}

	/**
	 * @param int $width
	 */
	public function setWidth($width)
	{
		$this->width = (int) $width;
		$this->setAttribute('width', $this->width);

		return $this;
	}

	/**
	 * @param int $height
	 */
	public function setHeight($height)
	{
		$this->height = (int) $height;
		$this->setAttribute('height', $this->height);

		return $this;
	}

	/**
	 * @param bool $crop
	 */
	public function setCrop($crop)
	{
		$this->crop = ($crop === true);
		return $this;
	}

	/**
	 * Gets the image path.
	 * Pass the $width and/or $height to get the resized image path.
	 *
	 * @return string
	 */
	public function getPath($width = null, $height = null)
	{
		return $this->getResizedImagePath($width, $height);
	}

	/**
	 * @return string
	 */
	public function getSrc()
	{
		return $this->getPath();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toHtml()
	{
		try {
			$this->setAttribute('src', $this->getResizedImagePath($this->width, $this->height));
		} catch (\Exception $e) {
			// @TODO: it's not nice to silently exit here.
			// should log raised exception at least.
			return '';
		}

		return parent::toHtml();
	}

	/**
	 * @param null|int $width
	 * @param null|int $height
	 * @return string
	 */
	private function getResizedImagePath($width, $height)
	{
		if ($width === null && $height === null) {
			$width = $this->image->getWidth();
			$height = $this->image->getHeight();
		} else if ($width === null) {
			$width = $this->image->getWidth();
		} else if ($height === null) {
			$height = $this->image->getHeight();
		}

		$wRatio = max($this->image->getWidth() / $width, 1);
		$hRatio = max($this->image->getHeight() / $height, 1);

		if ( ! $this->crop) {
			$wRatio = $hRatio = max($wRatio, $hRatio);
		}

		$width = round($this->image->getWidth() / $wRatio);
		$height = round($this->image->getHeight() / $hRatio);

		$imageSize = $this->fileStorage->createResizedImage(
					$this->image,
					$width,
					$height,
					$this->crop
		);

		return $this->fileStorage->getWebPath($this->image, $imageSize);
	}
}
