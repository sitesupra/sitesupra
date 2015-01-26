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
YUI.add('supra.input-link', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		DEFAULT_LABEL_SET = '{#form.set_link#}';
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	Input.NAME = 'input-link';
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		'labelSet': {
			'value': DEFAULT_LABEL_SET,
			'validator': Y.Lang.isString
		},
		'mode': {
			'value': 'link'
		},
		'groupsSelectable': {
			'value': false
		},
		
		// Link manager tree request URI, optional
		'treeRequestURI': {
			'value': null
		}

	};
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Button is used instead of input
		 */
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		/**
		 * Button node
		 * @type {Object}
		 * @private
		 */
		button: null,
		
		/**
		 * Right container action settings to it restore after link
		 * manager is closed
		 * @type {Object}
		 * @private
		 */
		restore_action: null,
		
		/**
		 * Open link manager for redirect
		 */
		openLinkManager: function () {
			var value = this.get('value');
			
			//Save previous right layout container action to restore
			//it after 
			this.restore_action = null;
			if (Manager.Loader.isLoaded('LayoutRightContainer')) {
				
				var action_name = Manager.LayoutRightContainer.getActiveAction();
				if (action_name && Manager.Loader.isLoaded(action_name)) {
					var action = Manager.getAction(action_name);
					
					if (action_name == 'PageContentSettings') {
						this.restore_action = {
							'action': action,
							'args': [action.form, action.options]
						};
					} else if (action_name == 'PageSettings') {
						this.restore_action = {
							'action': action,
							'args': [true]
						};
					}
					
				}
			}
			
			Manager.executeAction('LinkManager', value, {
				'mode': this.get('mode'),
				'treeRequestURI':    this.get('treeRequestURI'),
				'hideToolbar': true,
				'selectable': {
					'group_pages': this.get('groupsSelectable')
				}
			}, this.onLinkManagerClose, this);
		},
		
		/**
		 * Update value on change
		 *
		 * @param {Object} data
		 */
		onLinkManagerClose: function (data) {
			this.set('value', data);
			
			if (this.restore_action) {
				var conf = this.restore_action;
				conf.action.execute.apply(conf.action, conf.args);
			}
		},
		
		renderUI: function () {
			//Create button
			this.button = new Supra.Button({'label': this.get('labelSet')});
			this.button.render(this.get('contentBox'));
			this.button.on('click', this.openLinkManager, this);
			
			Input.superclass.renderUI.apply(this, arguments);
			
			//Insert button before input
			this.get('inputNode').insert(this.button.get('boundingBox'), 'before');
		},
		
		_setValue: function (data) {
			if (!data || (!data.href && !data.page_id && !data.file_id)) {
				data = '';
			}
			
			var title = (data && data.href ? data.title || data.href : Supra.Intl.replace(this.get('labelSet')));
			this.button.set('label', title);
			
			return data;
		},
		
		_getValue: function (data) {
			if (!data || (!data.href && !data.page_id && !data.file_id)) {
				return '';
			} else {
				return data;
			}
		},
		
		_afterValueChange: function (evt) {
			if (evt.prevVal != evt.newVal) {
				this.fire('change', {'value': evt.newVal});
			}
		},
		
		_setLabelSet: function (label) {
			if (typeof label !== 'string') {
				label = this.get('labelSet');
			}
			if (typeof label !== 'string') {
				label = DEFAULT_LABEL_SET;
			}
			return label;
		}
		
	});
	
	Supra.Input.Link = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
