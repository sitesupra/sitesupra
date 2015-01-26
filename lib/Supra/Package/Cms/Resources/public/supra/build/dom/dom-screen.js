/**
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
 */
YUI.add('supra.dom-screen', function(Y) {
	//Invoke strict mode
	"use strict";
	
	
	// Cache window size whenever possible
	// #16380 4. Reading window size triggers layout, while it's rarely neccessary
	var _getWinSize = Y.DOM._getWinSize,
		_winSizes = {},
		_winSizesInitialized = false;
	
	Y.DOM._getWinSizeReset = function () {
		// Clean cache
		_winSizes = {};
	};
	
	Y.DOM._getWinSize = function (node, doc) {
		if (!_winSizesInitialized) {
			_winSizesInitialized = true;
			Y.on('resize', Y.DOM._getWinSizeReset);
		}
		
		doc  = doc || (node) ? Y.DOM._getDoc(node) : Y.config.doc;
		
		var size = _winSizes[doc._yuid];
		
		if (size) {
			return size;
		} else {
			size = _winSizes[doc._yuid] = _getWinSize(node, doc);
			return size;
		}
	};
	

}, YUI.version ,{requires:['dom-core', 'dom-screen']});

YUI.Env.mods['dom-base'].details.requires.push('supra.dom-screen');
