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

namespace Supra\Package\Cms\Pages\Block;

use Supra\Package\Cms\Pages\BlockController;
use Supra\Package\Cms\Pages\Request\PageRequestEdit;

class UnknownBlockController extends BlockController
{
	const className = __CLASS__;

	public function doExecute()
	{
		if (! $this->getRequest() instanceof PageRequestEdit) {
			// do nothing if requested not through CMS.
			return null;
		}

		$this->getResponse()->output(sprintf(
				"<p><span>Block [%s] is unknown.<br/>Maybe it was removed?</span></p>",
				$this->block->getComponentClass()
		));
	}
}