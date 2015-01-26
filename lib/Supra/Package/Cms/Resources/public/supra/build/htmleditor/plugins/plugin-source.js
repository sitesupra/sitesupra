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
YUI().add('supra.htmleditor-plugin-source', function (Y) {
	
	//Shortcut
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	var Loader = Manager.Loader;
	
	var defaultConfiguration = {
		/* Modes which plugin supports */
		modes: [Supra.HTMLEditor.MODE_SIMPLE, Supra.HTMLEditor.MODE_RICH]
	};
	
	Supra.HTMLEditor.addPlugin('source', defaultConfiguration, {
		
		showSourceEditor: function () {
			var htmleditor  = this.htmleditor,
				history     = htmleditor.getPlugin('history');
			
			history.pushTextState();
			
			htmleditor.resetSelection();
			htmleditor.fire('nodeChange', {});
			
			if (Manager.getAction('PageContentSettings')) {
				Manager.PageContentSettings.hide();
			}
			
			Manager.executeAction('PageSourceEditor', {
				'html': htmleditor.getHTML(),
				'callback': Y.bind(this.updateSource, this)
			});
		},
		
		/**
		 * Update source
		 * 
		 * @param {String} html HTML code
		 */
		updateSource: function (html) {
			var htmleditor = this.htmleditor,
				history = htmleditor.getPlugin('history');
			
			history.pushTextState();
			
			htmleditor.setHTML(html);
			htmleditor._changed();
			
			history.pushState();
		},
		
		/**
		 * Initialize plugin for editor,
		 * Called when editor instance is initialized
		 * 
		 * @param {Object} htmleditor HTMLEditor instance
		 * @constructor
		 */
		init: function (htmleditor, configuration) {
			var sourceeditor = Manager.getAction('PageSourceEditor'),
				toolbar = htmleditor.get('toolbar'),
				button = toolbar ? toolbar.getButton('source') : null;
			
			// Toolbar button
			button.set("visible", true);
			
			// Add command
			htmleditor.addCommand('source', Y.bind(this.showSourceEditor, this));
			
			if (button) {
				//When un-editable node is selected disable mediasidebar toolbar button
				htmleditor.on('editingAllowedChange', function (event) {
					button.set('disabled', !event.allowed);
				});
			}
			
			//On editor disable hide source editor
			htmleditor.on('disable', this.hideMediaSidebar, this);
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
	
}, YUI.version, {'requires': ['supra.manager', 'supra.htmleditor-base']});