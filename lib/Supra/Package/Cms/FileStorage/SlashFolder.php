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

namespace Supra\Package\Cms\FileStorage;

use Supra\Package\Cms\Entity\Folder;

/**
 * This class represent synthetic root folder and is used only 
 * for authorization. Do not use this for anything else!
 */
final class SlashFolder extends Folder
{
	const DUMMY_ROOT_ID = 'slash';
	const DUMMY_ROOT_NAME = '/';
	
	public function __construct()
	{
		parent::__construct();
		$this->id = self::DUMMY_ROOT_ID;
		$this->fileName = self::DUMMY_ROOT_NAME;
	}
	
	public function getAuthorizationAncestors()
	{
		return array();
	}
}
