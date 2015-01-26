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

YUI().add('website.sitemap-tree-node-app', function (Y) {
	
	//Shortcuts
	var Action = Supra.Manager.getAction('SiteMap');
	
	
	/**
	 * Application tree node
	 */
	function TreeNodeApp(config) {
		TreeNodeApp.superclass.constructor.apply(this, arguments);
	}
	
	TreeNodeApp.NAME = 'TreeNodeApp';
	TreeNodeApp.CSS_PREFIX = 'su-tree-node';
	TreeNodeApp.ATTRS = {
		'application_id': {
			'value': null
		}
	};
	
	Y.extend(TreeNodeApp, Action.TreeNode, {
		/**
		 * Render UI
		 * 
		 * @private
		 */
		'renderUI': function () {
			TreeNodeApp.superclass.renderUI.apply(this, arguments);
			
			this.set('application_id', this.get('data').application_id);
			
			//Application specific classname
			this.get('boundingBox').addClass(this.getClassName(this.get('data').application_id));
		},
		
		/**
		 * Instead of expanding children show list popup
		 * if there is any
		 * 
		 * @private
		 */
		'_setExpandedExpand': function () {
			var returnValue = TreeNodeApp.superclass._setExpandedExpand.apply(this, arguments);
			
			var children = this.children(),
				i = 0,
				size = children.length;
			
			for(; i<size; i++) {
				if (children[i].isInstanceOf('TreeNodeList')) {
					children[i].expand(); break;
				}
			}
			
			return returnValue;
		}
	});
	
	
	Action.TreeNodeApp = TreeNodeApp;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['website.sitemap-tree-node']});