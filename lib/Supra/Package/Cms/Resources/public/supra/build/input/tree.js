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
YUI.add('supra.input-tree', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager,
		DEFAULT_LABEL_SET = '{#form.set_tree#}';
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
	}
	
	Input.NAME = 'input-tree';
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Input.ATTRS = {
		'labelSet': {
			'value': DEFAULT_LABEL_SET,
			'validator': Y.Lang.isString
		},
		'mode': {
			'value': 'tree'
		},
		'groupsSelectable': {
			'value': false
		},
		
		'sourceId': {
			'value': ''
		}
	};
	
	// Input is inline
	Input.IS_INLINE = false;
	
	// Input is inside form
	Input.IS_CONTAINED = true;
	
	// Input supports notifications
	Input.SUPPORTS_NOTIFICATIONS = false;
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Link, {
		
		openLinkManager: function () {
			// Update request URI
			var requestUri = Supra.CRUD.getDataPath('sourcedata') + '?sourceId=' + this.get('sourceId');
			this.set('treeRequestURI', requestUri);
			
			// Open link manager
			return Input.superclass.openLinkManager.apply(this, arguments);
		}
		
	});
	
	Supra.Input.Tree = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-link']});
