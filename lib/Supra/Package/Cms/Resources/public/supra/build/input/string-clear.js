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
//Invoke strict mode
"use strict";

YUI().add('supra.input-string-clear', function (Y) {
	
	/**
	 * Plugin for String input to clear content on icon click
	 */
	function InputStringClear () {
		InputStringClear.superclass.constructor.apply(this, arguments);
	};
	
	InputStringClear.NAME = 'InputStringClear';
	InputStringClear.NS = 'clear';
	
	Y.extend(InputStringClear, Y.Plugin.Base, {
		
		/**
		 * Clear icon/button
		 * 
		 * @type {Object}
		 * @private 
		 */
		nodeClear: null,
		
		/**
		 * Attach to event listeners, etc.
		 * 
		 * @constructor
		 * @private
		 */
		'initializer': function () {
			this.nodeClear = Y.Node.create('<a class="clear"></a>');
			this.nodeClear.on('click', this.clearInputValue, this);
			
			var host = this.get('host'),
				node = host.get('inputNode');
			
			if (node) {
				this.appendClearNode();
			} else {
				this.inputNodeEvt = host.after('inputNodeChange', this.appendClearNode, this);
			}
		},
		
		/**
		 * Add clear node to the input
		 * 
		 * @private
		 */
		'appendClearNode': function () {
			var host = this.get('host'),
				node = host.get('inputNode');
			
			if (node) {
				this.get('host').get('inputNode').insert(this.nodeClear, 'after');
			}
		},
		
		/**
		 * Clear input value
		 * 
		 * @private
		 */
		'clearInputValue': function () {
			this.get('host').set('value', '');
		}
	});
	
	Supra.Input.String.Clear = InputStringClear;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.form', 'plugin']});