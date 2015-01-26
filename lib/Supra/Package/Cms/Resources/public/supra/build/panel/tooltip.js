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
YUI.add('supra.tooltip', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Panel class
	 * 
	 * @extends Supra.Panel
	 * @param {Object} config Attribute values
	 */
	function Tooltip (config) {
		Tooltip.superclass.constructor.apply(this, arguments);
	}
	
	Tooltip.NAME = 'tooltip';
	Tooltip.CLASS_NAME = Tooltip.CSS_PREFIX = 'su-' + Tooltip.NAME;
	
	/*
	 * Tooltip attributes, default attribute values
	 */
	Tooltip.ATTRS = {
		/**
		 * Arrow visibility
		 */
		arrowVisible: {
			value: true,
			setter: '_setArrowVisible'
		},
		
		/**
		 * Text message
		 */
		textMessage: {
			value: null,
			setter: '_setTextMessage'
		},
		
		/**
		 * HTML message
		 */
		htmlMessage: {
			value: null,
			setter: '_setHTMLMessage'
		}
	};
	
	Y.extend(Tooltip, Supra.Panel, {
		
		/**
		 * Message node
		 */
		node_message: null,
		
		/**
		 * Escape and set tooltip message
		 * 
		 * @param {String} Tooltip mesasge
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setTextMessage: function (message) {
			if (message !== null && message !== undefined) {
				this.set('htmlMessage', Y.Escape.html(message || ''));
			}
			return message;
		},
		
		/**
		 * Set tooltip message
		 * 
		 * @param {String} Tooltip mesasge
		 * @return New value
		 * @type {String}
		 * @private
		 */
		_setHTMLMessage: function (message) {
			if (message) {
				var node = this.node_message;
				if (!node) {
					node = this.node_message = Y.Node.create('<p></p>');
					this.get('boundingBox').append(node);
				} else {
					node.removeClass('hidden');
				}
				node.set('innerHTML', message);
			} else {
				var node = this.get('boundingBox').one('P');
				if (this.node_message) {
					this.node_message.addClass('hidden');
				}
			}
			return message;
		},
		
		renderUI: function () {
			Tooltip.superclass.renderUI.apply(this, arguments);
			
			if (this.get('alignPosition')) {
				this._setAlignPosition(this.get('alignPosition'));
			}
			
			if (this.get('textMessage')) {
				this._setTextMessage(this.get('textMessage'));
			}
			
			if (this.get('htmlMessage')) {
				this._setHTMLMessage(this.get('htmlMessage'));
			}
		}
	});
	
	Supra.Tooltip = Tooltip;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.panel']});