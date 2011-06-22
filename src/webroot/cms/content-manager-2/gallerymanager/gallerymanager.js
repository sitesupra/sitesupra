SU('dd-delegate', 'dd-drop-plugin', 'dd-constrain', 'dd-proxy', function (Y) {

	//Shortcut
	var Manager = SU.Manager;
	var Action = Manager.Action;
	
	//Default properties
	var DEFAULT_PROPERTIES = [
		/* Default properties if none is set in configuration */
		{'id': 'title', 'type': 'String', 'label': 'Title', 'value': ''}
	];
	
	//Add as child, when EditorToolbar will be hidden GalleryManager will be hidden also (page editing is closed)
	Manager.getAction('EditorToolbar').addChildAction('GalleryManager');
	
	//Create Action class
	new Action({
		
		/**
		 * Unique action name
		 * @type {String}
		 * @private
		 */
		NAME: 'GalleryManager',
		
		/**
		 * Page manager has stylesheet, include it
		 * @type {Boolean}
		 * @private
		 */
		HAS_STYLESHEET: true,
		
		/**
		 * Image preview size
		 * @type {String}
		 * @private
		 */
		PREVIEW_SIZE: '60x60',
		
		/**
		 * Gallery data
		 * @type {Object}
		 * @private
		 */
		data: {},
		
		/**
		 * Callback function
		 * @type {Function}
		 * @private
		 */
		callback: null,
		
		/**
		 * Last drag X position
		 * @type {Number}
		 * @private
		 */
		lastDragX: 0,
		dragGoingUp: false,
		
		/**
		 * Settings form
		 * @type {Object}
		 * @private
		 */
		settings_form: null,
		
		/**
		 * Selected image data
		 * @type {Object}
		 * @private
		 */
		selected_image_data: null,
		
		/**
		 * Initialize
		 * @private
		 */
		initialize: function () {
			
			
			//On visibility change update container class and disable/enable toolbar
			this.on('visibleChange', function (evt) {
				if (evt.newVal) {
					this.getContainer().removeClass('hidden');
				} else {
					this.getContainer().addClass('hidden');
					this.callback = null;
					this.data = null;
				}
				
				if (this.settings_form && this.settings_form.get('visible')) {
					Manager.PageContentSettings.hide();
				}
				Manager.getAction('EditorToolbar').set('disabled', evt.newVal);
			}, this);
		},
		
		/**
		 * Generate settings form
		 */
		createSettingsForm: function () {
			//Get form placeholder
			var content = Manager.getAction('PageContentSettings').getContainer();
			if (!content) return;
			
			//Properties form
			var form_config = {
				'inputs': Supra.data.get(['supra.gallerymanager', 'properties'], DEFAULT_PROPERTIES)
			};
			
			var form = new Supra.Form(form_config);
				form.render(content);
				form.get('boundingBox').addClass('yui3-form-properties');
				form.hide();
			
			//Form heading
			var heading = Y.Node.create('<h2>Gallery image properties</h2>');
			form.get('contentBox').insert(heading, 'before');
			
			//Buttons
			var buttons = Y.Node.create('<div class="yui3-form-buttons"></div>');
			form.get('contentBox').insert(buttons, 'before');
			
			//Save button
			var btn = new Supra.Button({'label': 'Apply', 'style': 'mid-blue'});
				btn.render(buttons).on('click', this.settingsFormApply, this);
			
			//Close button
			var btn = new Supra.Button({'label': 'Close', 'style': 'mid'});
				btn.render(buttons).on('click', this.settingsFormCancel, this);
			
			//Delete button
			var btn = new Supra.Button({'label': 'Delete', 'style': 'mid-red'});
				btn.render(form.get('contentBox'));
				btn.addClass('yui3-button-delete');
				btn.on('click', this.removeSelectedImage, this);
			
			this.settings_form = form;
			return form;
		},
		
		/**
		 * Remove selected image
		 */
		removeSelectedImage: function () {
			var images = this.data.images,
				selected = this.selected_image_data;
			
			for(var i=0,ii=images.length; i<ii; i++) {
				if (images[i] === selected) {
					this.getContainer('.list').one('img[alt="' + selected.id + '"]').ancestor('li').remove();
					this.data.images.splice(i,1);
					this.settingsFormCancel();
					return this;
				}
			}
			
			Y.log('GalleryManager image which was supposed to be selected is not in image list');
		},
		
		/**
		 * Render widgets
		 * @private
		 */
		render: function () {
			//Buttons
			var buttons = this.getContainer('.yui3-form-buttons');
			
			//Apply button
			var btn = new Supra.Button({'label': 'Apply', 'style': 'mid-blue'});
				btn.render(buttons).on('click', this.applyChanges, this);
			
			//Close button
			var btn = new Supra.Button({'label': 'Close', 'style': 'mid'});
				btn.render(buttons).on('click', this.cancelChanges, this);
			
			this.bindDragDrop();
			
			//Position sync with other actions
			this.plug(SU.PluginLayout, {
				'offset': [10, 10, 10, 10]	//Default offset from page viewport
			});
			
			var layoutTopContainer = Manager.getAction('LayoutTopContainer'),
				layoutLeftContainer = Manager.getAction('LayoutLeftContainer'),
				layoutRightContainer = Manager.getAction('LayoutRightContainer');
			
			//Top bar 
			this.layout.addOffset(layoutTopContainer, layoutTopContainer.getContainer(), 'top', 10);
			this.layout.addOffset(layoutLeftContainer, layoutLeftContainer.getContainer(), 'left', 10);
			this.layout.addOffset(layoutRightContainer, layoutRightContainer.getContainer(), 'right', 10);
		},
		
		/**
		 * Bind drag & drop event listeners
		 */
		bindDragDrop: function () {
			var fnDragDrag = Y.bind(this.onDragDrag, this),
				fnDragStart = Y.bind(this.onDragStart, this),
				fnDropOver = Y.bind(this.onDropOver, this);
			
			var del = this.dragDelegate = new Y.DD.Delegate({
				container: '#galleryManagerList',
				nodes: 'li',
				target: {},
				dragConfig: {
					haltDown: false
				}
			});
			del.dd.plug(Y.Plugin.DDProxy, {
				moveOnEnd: false,
				cloneNode: true
			});

			del.on('drag:drag', fnDragDrag);
			del.on('drag:start', fnDragStart);
			del.on('drag:over', fnDropOver);
			
			//On list click check if actually item was clicked
			Y.one('#galleryManagerList').on('click', function (evt) {
				var target = evt.target;
				if (!target.test('LI')) target = target.ancestor('LI');
				
				if (target) {
					this.showImageSettings(target.getData('imageId'));
				}
			}, this);
		},
		
		/**
		 * Handle drag:drag event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDragDrag: function (evt) {
			var x = evt.target.lastXY[0];
			
			this.dragGoingUp = (x < this.lastDragX);
		    this.lastDragX = x;
		},
		
		/**
		 * Handle drag:start event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDragStart: function (evt) {
			//Get our drag object
	        var drag = evt.target;
			
	        //Set some styles here
	        drag.get('dragNode').addClass('gallery-item-proxy');
		},
		
		/**
		 * Handle drop:over event
		 * 
		 * @param {Object} evt Event
		 * @private
		 */
		onDropOver: function (evt) {
			//Get a reference to our drag and drop nodes
		    var drag = evt.drag.get('node'),
		        drop = evt.drop.get('node');
			
		    //Are we dropping on a li node?
		    if (drop.get('tagName').toLowerCase() === 'li' && drop.hasClass('gallery-item')) {
			    //Are we not going up?
		        if (!this.dragGoingUp) {
		            drop = drop.get('nextSibling');
		        }
				if (!this.dragGoingUp && !drop) {
			        evt.drop.get('node').get('parentNode').append(drag);
				} else {
			        evt.drop.get('node').get('parentNode').insertBefore(drag, drop);
				}
				
		        //Resize this nodes shim, so we can drop on it later.
		        evt.drop.sizeShim();
		    }
		},
		
		/**
		 * Show image settings/properties form
		 * 
		 * @param {Object} data Image data
		 */
		showImageSettings: function (image_id) {
			//If form exists and is already opened, save all values
			if (this.settings_form && this.settings_form.get('visible')) {
				this.settingsFormApply(true);
			}
			
			var data = this.data,
				image_data = null;
			
			for(var i=0,ii=data.images.length; i<ii; i++) {
				if (data.images[i].id == image_id) {
					image_data = data.images[i];
					break;
				}
			}
			
			if (image_data) {
				Manager.getAction('PageContentSettings').execute(this.settings_form || this.createSettingsForm());
				this.selected_image_data = image_data;
				
				this.settings_form.resetValues()
							  .setValues(image_data, 'id');
			}
		},
		
		/**
		 * Hide properties form
		 */
		settingsFormApply: function (dont_hide) {
			if (this.settings_form.get('visible')) {
				var image_data = Supra.mix(this.selected_image_data, this.settings_form.getValues('id')),
					data = this.data;
				
				for(var i=0,ii=data.images.length; i<ii; i++) {
					if (data.images[i].id == image_data.id) {
						data.images[i] = image_data;
						break;
					}
				}
				
				if (dont_hide !== true) {
					this.settingsFormCancel();
				}
			}
		},
		
		settingsFormCancel: function () {
			if (this.settings_form.get('visible')) {
				Manager.PageContentSettings.hide();
				this.selected_image_data = null;
			}
		},
		
		/**
		 * Render image list
		 * 
		 * @param {Object} data Data
		 * @private
		 */
		renderData: function (data) {
			var list = this.getContainer('.list'),
				images = this.data.images,
				preview_size = this.PREVIEW_SIZE,
				src,
				item;
			
			//Remove old data
			list.all('LI').remove();
			
			//Add new items
			for(var i=0,ii=images.length; i<ii; i++) {
				src = null;
				for(var k=0,kk=images[i].sizes.length; k<kk; k++) {
					if (images[i].sizes[k].id == preview_size) {
						src = images[i].sizes[k].external_path;
						break;
					}
				}
				
				if (src) {
					item = Y.Node.create('<li class="yui3-dd-drop gallery-item"><img src="' + src + '" alt="' + images[i].id + '" /></li>');
					item.setData('imageId', images[i].id);
					list.append(item);
				}
			}
			
			this.dragDelegate.syncTargets();
		},
		
		/**
		 * Apply changes
		 * 
		 * @private
		 */
		applyChanges: function () {
			var items = this.getContainer('.list').all('li'),
				order = {},
				data = this.data,
				images = [];
			
			//Get image order
			items.each(function (item, index) {
				order[this.getData('imageId')] = index;
			});
			
			//Sort images array
			data.images.sort(function (a, b) {
				var oa = order[a.id],
					ob = order[b.id];
				
				return oa > ob;
			});
			
			if (this.callback) {
				this.callback(data, true);
			}
			
			this.hide();
		},
		
		/**
		 * Cancel changes
		 * 
		 * @private
		 */
		cancelChanges: function () {
			if (this.callback) {
				this.callback(this.data, false);
			}
			
			this.hide();
		},
		
		/**
		 * Execute action
		 * 
		 * @param {Object} data Gallery data
		 * @param {Function} callback Callback function
		 */
		execute: function (data, callback) {
			this.data = data;
			this.callback = callback;
			this.renderData(data);
			this.show();
		}
	});
	
});