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
YUI.add('linkmanager.sitemap-linkmanager-node', function (Y) {
	//Invoke strict mode
	"use strict";
	
	function LinkMapTreeNode (config) {
		LinkMapTreeNode.superclass.constructor.apply(this, [config]);
	}
	
	LinkMapTreeNode.NAME = 'tree-node';
	LinkMapTreeNode.CSS_PREFIX = LinkMapTreeNode.CLASS_NAME = 'su-' + LinkMapTreeNode.NAME;
	
	LinkMapTreeNode.ATTRS = {
		'defaultChildType': {
			'value': LinkMapTreeNode
		}
	};
	
	Y.extend(LinkMapTreeNode, Supra.TreeNode, {
		CONTENT_TEMPLATE: 	'<div class="tree-node">' +
			  				'	<div><span class="toggle hidden"></span><span class="remove"></span><span class="img"><img src="/public/cms/supra/img/tree/none.png" /></span> <label></label></div>' +
			  				'</div>' +
			  				'<ul class="tree-children">' +
			  				'</ul>',
		
		bindUI: function () {
			LinkMapTreeNode.superclass.bindUI.apply(this, arguments);
			
			this.get('boundingBox').one('span.remove').on('click', this.unsetSelectedState, this);
		},
		
		/**
		 * Unset selected state
		 */
		unsetSelectedState: function (evt) {
			this.getTree().set('selectedNode', null);
			evt.halt();
		}
	});
	
	Supra.LinkMapTreeNode = LinkMapTreeNode;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};

}, YUI.version, {'requires': ['supra.tree', 'supra.tree-node']});
