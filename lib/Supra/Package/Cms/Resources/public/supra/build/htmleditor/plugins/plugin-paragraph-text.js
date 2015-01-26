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
YUI().add('supra.htmleditor-plugin-paragraph-text', function (Y) {
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_TEXT, Supra.HTMLEditor.MODE_BASIC]
	};
	
	Supra.HTMLEditor.addPlugin('paragraph-text', defaultConfiguration, {
		
		/**
		 * Prevent return key
		 */
		_onReturnKey: function (event) {
			if (!event.stopped && event.keyCode == 13 && !event.altKey && !event.ctrlKey) {
				// Insert BR
				var editor = this.htmleditor,
					maxlength = editor.get('maxLength');
				
				if (!maxlength || maxlength > editor.getContentCharacterCount()) {
					editor.get('doc').execCommand('insertlinebreak', null);
				}
                event.halt();
			}
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			htmleditor.on('keyDown', Y.bind(this._onReturnKey, this));
		},
		
		/**
		 * Clean up after plugin
		 * Called when editor instance is destroyed
		 */
		destroy: function () {}
		
	});
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});