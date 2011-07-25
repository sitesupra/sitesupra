//Invoke strict mode
"use strict";

YUI.add('website.sitemap-tree-newpage', function (Y) {
	
	
	var TREENODE_DATA = {
		'title': 'New page',
		'template': '',
		'icon': 'page',
		'path': 'new-page',
		'parent': null,
		'published': false,
		'scheduled': false
	};
	
	
	/**
	 * New page tree plugin allows adding new page using drag & drop
	 */	
	function NewPagePlugin (config) {
		NewPagePlugin.superclass.constructor.apply(this, arguments);
	}

	// When plugged into a tree instance, the plugin will be 
	// available on the "state" property.
	NewPagePlugin.NS = 'newpage';
	
	NewPagePlugin.ATTRS = {
	};
	
	// Extend Plugin.Base
	Y.extend(NewPagePlugin, Y.Plugin.Base, {
		
		/**
		 * Tree node instance
		 * @type {Object}
		 */
		treenode: null,
		
		/**
		 * New page index in tree
		 * @type {Number}
		 */
		new_page_index: null,
		
		
		createTreeNode: function (proxy, node) {
			var data = SU.mix({}, TREENODE_DATA);
			var treenode = new SU.SitemapTreeNode({
				'data': data,
				'label': data.title,
				'icon': data.icon
			});
			
			treenode.render(document.body);
			treenode.get('boundingBox').remove();
			
			treenode._tree = this.get('host');
			
			var dd = this.dd = new Y.DD.Drag({
				'node': node ? node : treenode.get('boundingBox').one('div.tree-node'),
				'dragMode': 'point',
				'target': false
			}).plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,			// Don't move original node at the end of drag
				cloneNode: true
			});
			
			dd.set('treeNode', treenode);
			
			if (dd.target) {
				dd.target.set('treeNode', treenode);
			}
			
			//When starting drag all children must be locked to prevent
			//parent drop inside children
			dd.on('drag:afterMouseDown', treenode._afterMouseDown);
			
			//Set special style to proxy node
			dd.on('drag:start', treenode._dragStart);
			
			// When we leave drop target hide marker
			dd.on('drag:exit', treenode._dragExit);
			
			// When we move mouse over drop target update marker
			dd.on('drag:over', treenode._dragOver);
			
			dd.on('drag:end', this._dragEnd, this);
			this.treenode = treenode;
			
			return treenode;
		},
		
		/**
		 * Constructor
		 */
		initializer: function (config) {
			var host = config.host;
			var node = config.dragNode;
			var treenode = this.createTreeNode(true, node);
		},
		
		/**
		 * 
		 * @param {Object} e
		 */
		_dragEnd: function(e){
			var self = this.treenode,
				tree = this.get('host');
			
			if (self.drop_target) {
				var target = self.drop_target
				var drag_data = TREENODE_DATA;
				var drop_data = target.get('data');
				var position = self.marker_position;
				
				//Fire drop event
				var event = tree.fire('drop', {'drag': drag_data, 'drop': drop_data, 'position': position});
				
				//If event was not prevented, then create node
				if (event) this.addChild(position, target);
			}
			
			//Hide marker and cleanup data
			self.setMarker(null);
			
			//Unlock children to allow them being draged
			self.unlockChildren();
			
			//Make sure node is not actually moved
			e.preventDefault();
		},
		
		onNewPageDataLoad: function (data) {
			var page_data = SU.mix({}, TREENODE_DATA, data),
				parent_node = this.get('host').getNodeById(page_data.parent),
				parent_data = parent_node.get('data');
			
			//Set fullpath value
			page_data.fullpath = (parent_data.fullpath || '') + '/' + page_data.path;
			
			//Add to parent
			if (!parent_data.children) parent_data.children = [];
			parent_data.children.push(page_data);
			
			//Set into data
			var data_indexed = this.get('host').getIndexedData();
			data_indexed[page_data.id] = page_data;
			
			//Create node
			parent_node.add({
				'label': page_data.title,
				'icon': page_data.icon,
				'data': page_data
			}, this.new_page_index);
		},
		
		addChild: function (position, target, callback, context) {
			var drop_data = target.get('data');
			var pagedata = SU.mix({}, TREENODE_DATA, {
				'parent': (position == 'inside' ? drop_data.id : drop_data.parent),
				'template': (position == 'inside' ? drop_data.template : target.get('parent').get('data').template)
			});
			
			this.new_page_index = (position == 'inside' ? target.size() + 1 : (position == 'after' ? target.get('index') + 1 : target.get('index')));
			
			SU.Manager.Page.createPage(pagedata, function () {
				this.onNewPageDataLoad.apply(this, arguments);
				if (Y.Lang.isFunction(callback)) callback.apply(context, arguments);
			}, this);
		}
		
	});
	
	Supra.Tree.NewPagePlugin = NewPagePlugin;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.tree-dragable']});
