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
YUI().add('supra.htmleditor-traverse', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Retrieves a nodeList based on the given CSS selector.
		 * 
		 * @param {String} selector The CSS selector to test against.
		 * @return A NodeList instance for the matching HTMLCollection/Array.
		 * @type {Object}
		 */
		all: function (selector) {
			return this.get('srcNode').all(selector);
		},
		
		/**
		 * Retrieves a Node instance of nodes based on the given CSS selector. 
		 * 
		 * @param {String} selector The CSS selector to test against.
		 * @return A Node instance for the matching HTMLElement.
		 * @type {Object}
		 */
		one: function (selector) {
			return this.get('srcNode').one(selector);
		}
		
	});


	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});