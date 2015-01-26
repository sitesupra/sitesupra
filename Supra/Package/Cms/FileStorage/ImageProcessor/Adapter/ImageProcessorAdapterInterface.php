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

namespace Supra\Package\Cms\FileStorage\ImageProcessor\Adapter;

use Supra\Package\Cms\FileStorage\FileStorage;

interface ImageProcessorAdapterInterface
{
	/**
	 * Checks, whether image processing library is available for use
	 */
	public static function isAvailable();
	
	public function doResize($sourceName, $targetName, $width, $height, array $sourceDimensions);
	public function doCrop($sourceName, $targetName, $width, $height, $x, $y);
	public function doRotate($sourceName, $targetName, $degrees);

	public function setFileStorage(FileStorage $storage);

	/**
	 * @return FileStorage
	 */
	public function getFileStorage();
}