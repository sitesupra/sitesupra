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
YUI.add('supra.tree-draggable', function(Y) {
	//Invoke strict mode
	"use strict";
	
	function TreeDraggable (config) {
		TreeDraggable.superclass.constructor.apply(this, arguments);
	}
	
	TreeDraggable.NAME = 'tree-draggable';
	TreeDraggable.CLASS_NAME = TreeDraggable.CSS_PREFIX = 'su-' + TreeDraggable.NAME;
	
	TreeDraggable.ATTRS = {
		/**
		 * Default children class
		 * @type {Function}
		 */
		'defaultChildType': {  
            value: Supra.TreeNodeDraggable
		},
		
		/**
		 * Node to which all drag proxies should be added to
		 * @type {Object}
		 */
		'dragProxyParent': {
			value: null
		}
	};
	
	Y.extend(TreeDraggable, Supra.Tree, {
		_renderTreeUIChild: function (data, i) {
			var isDraggable = (data && 'isDraggable' in data ? data.isDraggable : true);
			var isDropTarget = (data && 'isDropTarget' in data ? data.isDropTarget : true);
			this.add({'data': data, 'label': data.title, 'icon': data.icon, 'isDropTarget': isDropTarget, 'isDraggable': isDraggable}, i);
		}
	});
	
	Supra.TreeDraggable = TreeDraggable;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.tree', 'supra.tree-node-draggable']});
