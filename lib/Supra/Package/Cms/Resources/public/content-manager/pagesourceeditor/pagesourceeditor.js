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
Supra(function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	var Action = Manager.Action;
	
	//Add as child, when EditorToolbar will be hidden PageSourceEditor will be hidden also (page editing is closed)
	Manager.getAction('EditorToolbar').addChildAction('PageSourceEditor');
	
	//Includes
	var includes = [
		'{sourceeditor}includes/editor.js'
	];
	
	//Create Action class
	new Action(Supra.Manager.Action.PluginContainer, {
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'PageSourceEditor',
		
		/**
		 * Load stylesheet
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Load template
		 * @type {Boolean}
		 * @private
		 */
		HAS_TEMPLATE: true,
		
		
		
		
		/**
		 * Source which should be edited
		 * @type {String}
		 * @private
		 */
		html: '',
		
		/**
		 * Callback function
		 * @type {Function}
		 * @private
		 */
		callback: null,
		
		/**
		 * Editor instance
		 */
		editor: null,
		
		
		/**
		 * Initialize
		 */
		initialize: function () {
			var incl = includes,
				path = this.getActionPath(),
				args = [];
			
			//Change path	
			for(var id in incl) {
				args.push(incl[id].replace('{sourceeditor}', path));
			}
			
			//Load modules
			Y.Get.script(args, {
				'onSuccess': function () {
					//Create classes
					Supra('supra.source-editor', Y.bind(function () {
						this.ready();
					}, this));
				},
				attributes: {
					'async': 'async',	//Load asynchronously
					'defer': 'defer'	//For browsers that doesn't support async
				},
				'context': this
			});
		},
		
		/**
		 * When all dependancies are loaded show editor
		 */
		ready: function () {
			//Remove loading class
			this.one('.su-source-editor-content').removeClass('loading');
			
			//Create editor
			this.editor = new Manager.PageSourceEditor.Editor({
				'srcNode': this.one('textarea')
			});
			
			this.editor.render();
			this.editor.setHTML(this.html);
			this.editor.focus();
		},
		
		/**
		 * Initialize
		 * @private
		 */
		render: function () {
			Manager.getAction('PageButtons').addActionButtons(this.NAME, [{
				'id': 'done',
				'context': this,
				'callback': this.hide
			}]);
		},
		
		/**
		 * Hide action
		 */
		hide: function () {
			if (!this.get('visible')) return;
			
			//Callback
			if (this.callback && this.editor) {
				this.callback(this.editor.getHTML());
			}
			
			//Hide action
			Action.Base.prototype.hide.apply(this, arguments);
			
			//Hide buttons
			Manager.getAction('EditorToolbar').set('visible', true);
			Manager.getAction('PageButtons').unsetActiveAction(this.NAME);
		},
		
		/**
		 * Execute action
		 */
		execute: function (data) {
			this.html = data.html || '';
			this.callback = data.callback;
			
			//Show buttons
			Manager.getAction('EditorToolbar').set('visible', false, {'silent': true});
			Manager.getAction('PageButtons').setActiveAction(this.NAME);
			
			//Show content
			this.show();
			
			//Set content
			if (this.editor) {
				this.editor.setHTML(this.html);
				this.editor.focus();
			}
		}
	});
	
});