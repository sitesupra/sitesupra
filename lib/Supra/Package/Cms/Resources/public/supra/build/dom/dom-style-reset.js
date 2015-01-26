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
YUI.add('supra.dom-style-reset', function(Y) {
	//Invoke strict modet
	"use strict";
	
	function callBefore (o, fn, call) {
		var original = o[fn];
		o[fn] = function () { call.apply(this, arguments); return original.apply(this, arguments); };
	};
	
	// On transitions cssText is used, so we need to reset cache manually
	callBefore(Y.Transition.prototype, '_runNative', function () {
		Y.DOM.resetStyleCache(this._node);
	});
	
	callBefore(Y.TransitionNative.prototype, '_runNative', function () {
		Y.DOM.resetStyleCache(this._node);
	});
	
}, YUI.version ,{requires:['transition']});
