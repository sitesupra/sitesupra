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

/**
 * Plugin to link button and input so that button represents input value
 */
YUI.add('supra.button-plugin-input', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Button = Supra.Button;
	
	/**
	 * Folder rename plugin
	 * Saves item properties when they change
	 */
	function Plugin (config) {
		Plugin.superclass.constructor.apply(this, arguments);
	}
	
	Plugin.NAME = 'input';
	Plugin.NS = 'input';
	
	Plugin.ATTRS = {
		
		/**
		 * Default label when input doesn't return any
		 * @type {String}
		 */
		'defaultLabel': {
			value: ''
		},
		
		/**
		 * Input which value is binded with this 
		 * @type {Object}
		 */
		'input': {
			value: null
		},
		
		/**
		 * Formatter function
		 * @type {Function}
		 */
		'formatter': {
			value: null
		}
	};
	
	Y.extend(Plugin, Y.Plugin.Base, {
		
		/**
		 * Initialize plugin
		 */
		initializer: function () {
			var input = this.get('input'),
				button = this.get('host');
			
			if (input.isInstanceOf('input')) {
				if (input.getValueData) {
					// We can get detailed data from input
					input.after('change', this.onSelectChange, this);
					input.after('valuesChange', this.onSelectChange, this);
				} else {
					// We don't know how to handle this, try guessing
					input.after('change', this.onInputChange, this);
				}
			}
		},
		
		/**
		 * Select input changed, update button UI
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		onSelectChange: function (event) {
			var button = this.get('host'),
				input  = this.get('input'),
				value  = event.value,
				data   = null,
				type   = typeof value;
			
			if (type === 'null' || type === 'undefined') {
				value = input.get('value');
				type  = typeof value;
			}
			
			data = input.getValueData(value);
			
			if (data) {
				this.syncUI({
					'label': data.title || this.get('defaultLabel'),
					'icon': data.icon,
					'button': button,
					'input': input
				});
			}
		},
		
		/**
		 * Select input changed, update button UI
		 * 
		 * @param {Object} event Event facade object
		 * @private
		 */
		onInputChange: function (event) {
			var button = this.get('host'),
				input  = this.get('input'),
				value  = event.value,
				type   = typeof value;
			
			if (type === 'null' || type === 'undefined') {
				value = input.get('value');
				type  = typeof value;
			}
			
			if (type === 'null' || type === 'undefined') {
				value = '';
			} else if (type !== 'string' && type !== 'number') {
				if (value.title) {
					// File or image
					value = value.title;
				} else {
					value = '';
				}
			}
			
			this.syncUI({
				'label': value || this.get('defaultLabel'),
				'button': button,
				'input': input
			});
		},
		
		/**
		 * Update button UI
		 * 
		 * @param {Object} data Data for button
		 */
		syncUI: function (data) {
			var formatter = this.get('formatter');
			
			if (!Y.Lang.isFunction(formatter)) {
				formatter = this.defaultFormatter;
			}
			
			formatter(data);
		},
		
		/**
		 * Default formatter
		 * 
		 * @param {Object} data Data for button
		 */
		defaultFormatter: function (data) {
			if ('label' in data) {
				data.button.set('label', data.label);
			}
			if ('icon' in data) {
				data.button.set('icon',  data.icon || '');
			}
		}
		
	});
	
	
	Supra.Button.PluginInput = Plugin;
	
	//Since this Widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['plugin']});