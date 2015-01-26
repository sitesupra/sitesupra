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


YUI.add('website.tree-node-permissions', function(Y) {
	
	function TreeNodePermissions (config) {
		TreeNodePermissions.superclass.constructor.apply(this, arguments);
		
		this.init.apply(this, arguments);
	}
	
	TreeNodePermissions.NAME = 'tree-node-draggable';
	TreeNodePermissions.ATTRS = {
		/**
		 * Inside node, before and after can be dropped 
		 * other nodes
		 */
		'isDropTarget': {
			value: false
		},
		
		/**
		 * Tree node shouldn't be selectable
		 */
		'selectable': {
			value: false
		},
		
		/**
		 * Child class
		 */
		'defaultChildType': {  
            value: TreeNodePermissions
        }
	};
	
	Y.extend(TreeNodePermissions, Supra.TreeNodeDraggable, {
		ROOT_TYPE: TreeNodePermissions,
		
		renderUI: function () {
			//Overwrite attribute values
			this.set('isDropTarget', false);
			this.set('selectable', false);
			
			TreeNodePermissions.superclass.renderUI.apply(this, arguments);
		}
	});
	
	
	Supra.TreeNodePermissions = TreeNodePermissions;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tree-node-draggable']});