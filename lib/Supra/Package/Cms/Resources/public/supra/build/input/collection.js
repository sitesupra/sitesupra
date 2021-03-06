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
YUI.add('supra.input-collection', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * List of input groups with controls to add or remove
	 * groups
	 */
	function Collection (config) {
		Collection.superclass.constructor.apply(this, arguments);
	}
	
	// Collection is not inline
	Collection.IS_INLINE = false;
	
	// Collection is inside form
	Collection.IS_CONTAINED = true;
	
	Collection.NAME = 'input-collection';
	Collection.CLASS_NAME = Collection.CSS_PREFIX = 'su-' + Collection.NAME;
	
	Collection.ATTRS = {
		// Properties for each item
		// Although name is plural, this attribute contains configuration
		// for single property
		'properties': {
			value: null
		},
		
		// Render widget into separate slide and add
		// button to the place where this widget should be
		'separateSlide': {
			value: true
		},
		
		// Add more button label
		'labelAdd': {
			value: 'Add more'
		},
		
		// Remove button label
		'labelRemove': {
			value: 'Remove'
		},
		
		// Item number label
		'labelItem': {
			value: '#%s'
		},
		
		// Button label to use instead of "Label"
		'labelButton': {
			value: ''
		},
		
		// Button icon to use
		'icon': {
			value: null
		},
		
		// Minimal item count
		'minCount': {
			value: 0
		},
		
		// Maximal item count
		'maxCount': {
			value: 0
		},
		
		// Default value
		'defaultValue': {
			value: []
		}
	};
	
	Y.extend(Collection, Supra.Input.Proto, {
		
		INPUT_TEMPLATE: '<input type="hidden" value="" />',
		
		HEADING_TEMPLATE: '<h3></h3>',
		
		/**
		 * Item count
		 * @type {Number}
		 * @protected
		 */
		_count: 0,
		
		/**
		 * Slide content node
		 * @type {Object}
		 * @protected
		 */
		_slideContent: null,
		
		/**
		 * Button to open slide
		 * @type {Object}
		 * @protected
		 */
		_slideButton: null,
		
		/**
		 * Slide name
		 * @type {String}
		 * @protected
		 */
		_slideId: null,
		
		/**
		 * "Add more" button
		 * @type {Object}
		 * @protected
		 */
		_addButton: null,
		
		/**
		 * List of item nodes
		 * @type {Object}
		 * @protected
		 */
		_nodes: null,
		
		/**
		 * List of item widgets and nodes
		 * @type {Array}
		 * @protected
		 */
		_widgets: null,
		
		/**
		 * Value is beeing updated by setter
		 * Don't trigger event
		 * @type {Boolean}
		 * @protected
		 */
		_silentValueUpdate: false,
		
		/**
		 * Values has been rendered
		 * @type {Boolean}
		 * @protected
		 */
		_valuesRendered: false,
		
		/**
		 * Focused list index
		 * @type {Number}
		 * @protected
		 */
		_focusedItemIndex: 0,
		
		
		
		/**
		 * On desctruction life cycle clean up
		 * 
		 * @protected
		 */
		destructor: function () {
			var count = this._count,
				i = count-1;
			
			for (; i >= 0; i--) {
				this._removeItem(i);
			}
			
			if (this.get('separateSlide') && this._slideId) {
				var slideshow = this.getSlideshow();
				slideshow.removeSlide(this._slideId);
			}
			
			this._slideContent = null;
			this._slideId = null;
			this._widgets = [];
			this._nodes = [];
			this._count = 0;
			
			this._fireResizeEvent();
		},
		
		/**
		 * Life cycle method, render input
		 * 
		 * @protected
		 */
		renderUI: function () {
			this._count = 0;
			this._nodes = [];
			this._widgets = [];
			
			// Create items?
			if (this.get('separateSlide')) {
				var slideshow = this.getSlideshow();
				if (!slideshow) {
					this.set('separateSlide', false);
					Y.log('Unable to create new slide for Supra.Input.Collection "' + this.get('id') + '", because slideshow can\'t be detected');
				} else {
					// Don't create description, we have a button
					this.DESCRIPTION_TEMPLATE = null;
				}
			}
			
			Collection.superclass.renderUI.apply(this, arguments);
			
			// Create slide or render data
			if (!this.get('separateSlide')) {
				this._createAllItems();
			} else {
				this._renderSlide();
				this._createAllItems();
			}
			
			// New item button
			this.renderUINewItemButton();
			
			// Set inital value
			var value = this.get('value');
			if (value && value.length) {
				this._setValue('value', value);
			}
		},
		
		/**
		 * Render "Add more" button
		 * 
		 * @protected
		 */
		renderUINewItemButton: function () {
			// New item button
			var button = this._addButton = new Supra.Button({
				'label': this.get('labelAdd'),
				'style': 'small-gray'
			});
			
			button.addClass(button.getClassName('fill'));
			
			if (!this.get('separateSlide')) {
				button.render(this.get('contentBox'));
			} else {
				button.render(this._slideContent);
			}
		},
		
		/**
		 * Life cycle method, attach event listeners
		 * 
		 * @protected
		 */
		bindUI: function () {
			Collection.superclass.bindUI.apply(this, arguments);
			
			// When slide is opened for first time create inputs
			if (this.get('separateSlide')) {
				// On button click open slide
				this._slideButton.on('click', this._openSlide, this);
				
				// Disabled change
				this.on('disabledChange', function (event) {
					this._slideButton.set('disabled', event.newVal);
				}, this);
			}
			
			// Add new item on "Add more" click
			if (this._addButton) {
				this._addButton.on('click', this.addItem, this);
			}
			
			// Change event
			this.on('valueChange', this._afterValueChange, this);
		},
		
		
		/*
		 * ------------------------- Items --------------------------------
		 */
		
		
		/**
		 * Recreate items from data
		 * 
		 * @protected
		 */
		_createAllItems: function () {
			var data = this.get('value'),
				i = 0,
				ii = data.length;
			
			for (; i<ii; i++) {
				this._addItem(data[i]);
			}
			
			this._valuesRendered = true;
			this._fireResizeEvent();
		},
		
		/**
		 * Add new item
		 * 
		 * @param {Object} data Item default input values
		 * @param {Boolean} animate Animate UI
		 * @protected
		 */
		_addItem: function (data, animate) {
			var property = this.get('properties'),
				
				form  = this.getForm(),
				node = Y.Node.create('<div class="' + this.getClassName('group') + '"></div>'),
				index = this._count,
				
				widgets = {'input': null, 'nodeHeading': null, 'buttonRemove': null},
				config,
				input = null,
				
				heading = null,
				button = null,
				
				container  = null,
				inputElement;
			
			// Create container node
			if (this.get('separateSlide')) {
				container = this._slideContent;
			} else {
				container = this.get('contentBox');
			}
			
			container.append(node);
			
			if (this._addButton) {
				container.append(this._addButton.get('boundingBox'));
			}
			
			// Create heading
			if (this.HEADING_TEMPLATE) {
				heading = widgets.nodeHeading = Y.Node.create(this.HEADING_TEMPLATE);
				heading.set('text', this.get('labelItem').replace('%s', index + 1));
				node.append(heading);
			}
			
			// Create inputs
			if (property) {
				// Add input
				config = Supra.mix({}, property, {
					'id': property.id + '_' + Y.guid(),
					'name': String(index),
					'value': data,
					'parent': this,
					'containerNode': node,
					'disabled': this.get('disabled'),
					
					// For 'Set' input
					'separateSlide': false
				});
				
				this.fire('before:item:add', {'data': data, 'index': index});
				input = form.factoryField(config);
				
				if (input) {
					if (!Supra.Input.isContained(config.type)) {
						input.render();
					} else {
						input.render(node);
					}
					
					input.after('valueChange', this._fireChangeEvent, this);
					input.on('focus', this._onInputFocus, this);
					input.on('input', this._fireInputEvent, this, property.id);
					
					widgets.input = input;
				}
			}
			
			// "Remove" button
			button = widgets.buttonRemove = new Supra.Button({
				'label': this.get('labelRemove'),
				'style': 'small-red'
			});
			button.addClass(button.getClassName('fill'));
			button.render(node);
			button.on('click', this._removeTargetItem, this, node);
			
			this._count++;
			this._nodes.push(node);
			this._widgets.push(widgets);
			
			if (animate) {
				this._animateIn(node);
			}
			
			this.fire('item:add', {'data': data, 'element': node, 'index': index});
		},
		
		/**
		 * Remove item
		 * 
		 * @param {Number} index Item index
		 * @param {Boolean} animate Animate UI
		 * @protected
		 */
		_removeItem: function (index, animate) {
			var nodes = this._nodes,
				widgets = this._widgets,
				count = this._count,
				
				form = this.getForm(),
				properties = this.get('properties'),
				
				widget = widgets[index],
				input = null,
				key = null,
				
				node = null,
				value;
			
			if (index >=0 && index < count) {
				node = nodes[index];
				value = widget.input ? widget.input.get('value') : null;
				
				if (animate) {
					this._animateOut(node, widget);
				} else {
					// Destroy inputs
					if (widget.input) {
						form.fire('input:remove', {
							'config': properties,
							'input': widget.input
						});
						
						widget.input.destroy(true);
					}
					if (widget.buttonRemove) {
						widget.buttonRemove.destroy(true);
					}
					
					node.remove(true);
				}
				
				widgets.splice(index, 1);
				nodes.splice(index, 1);
				this._count--;
				
				// Update all other item headings
				var i = index,
					ii = count - 1;
				
				for (; i<ii; i++) {
					if (widgets[i].nodeHeading) {
						widgets[i].nodeHeading.set('innerHTML', this.get('labelItem').replace('%s', i + 1));
					}
					
					if (widgets[i].input) {
						widgets[i].input.set('name', String(i));
					}
				}
				
				this.fire('item:remove', {'data': value, 'element': node});
			}
		},
		
		/**
		 * Update child element value
		 */
		_updateItem: function (index, data) {
			
		},
		
		/**
		 * Remove item in which "Remove" button was clicked
		 * 
		 * @param {Object} event Event facade object
		 * @param {Object} node Item node which needs to be removed
		 * @protected
		 */
		_removeTargetItem: function (event, node) {
			var index = this._getItemIndex(node);
			this.removeItem(index);
		},
		
		addItem: function () {
			this._addItem(undefined, true);
			this._fireChangeEvent();
			this.fire('add', undefined);
		},
		
		removeItem: function (index) {
			this._removeItem(index, true);
			this._fireChangeEvent();
			this.fire('remove', index);
		},
		
		/**
		 * Returns item count
		 * 
		 * @returns {Number} Item count
		 */
		size: function () {
			return this._count;
		},
		
		/**
		 * Returns item index by node
		 * 
		 * @param {Object} node Item container node
		 * @returns {Number} Item index
		 * @protected
		 */
		_getItemIndex: function (node) {
			var index = 0,
				selector = '.' + this.getClassName('group');
			
			node = node.closest(selector);
			
			while (node) {
				node = node.previous(selector);
				if (node) {
					index++;
				}
			}
			
			return index;
		},
		
		/**
		 * On input focus save item index
		 * 
		 * @param {Object} event Event facade object
		 * @protected
		 */
		_onInputFocus: function (event) {
			this._focusedItemIndex = this._getItemIndex(event.target.get('srcNode'));
		},
		
		/**
		 * Returns input for item
		 * 
		 * @param {Number} index Item index
		 * @returns {Object|Nul} Input widget
		 */
		getInput: function (index) {
			var property = this.get('properties'),
				widgets  = this._widgets;
			
			if (property && this._widgets[index]) {
				return this._widgets[index].input;
			}
			
			return null;
		},
		
		/**
		 * Returns inputs for all items
		 *
		 * @returns {Array} Inputs for all items
		 */
		getInputs: function () {
			var property = this.get('properties'),
				widgets  = this._widgets,
				inputs   = [],
				i        = 0,
				ii;
			
			if (property && widgets) {
				for (ii=widgets.length; i<ii; i++) {
					inputs.push(widgets[i].input);
				}
			}
			
			return inputs;
		},
		
		/**
		 * Returns all inputs, including Collection and Set child inputs
		 * If key is 'array', then array is returned, otherwise object by input names
		 * 
		 * @returns {Array|Object} Inputs
		 */
		getAllInputs: function (key) {
			var property = this.get('properties'),
				widgets  = this._widgets,
				inputs   = [],
				i        = 0,
				ii,
				obj;
			
			if (property && widgets) {
				if (key === 'array') {
					obj = [];
					
					for (ii=widgets.length; i<ii; i++) {
						if (widgets[i].input) {
							obj.push(widgets[i].input);
							
							if (widgets[i].input.getAllInputs) {
								obj = obj.concat(widgets[i].input.getAllInputs(key));
							}
						}
					}
				} else {
					obj = {};
					
					for (ii=widgets.length; i<ii; i++) {
						if (widgets[i].input) {
							obj[widgets[i].input.getHierarhicalName()] = widgets[i].input;
							
							if (widgets[i].input.getAllInputs) {
								Supra.mix(obj, widgets[i].input.getAllInputs(key));
							}
						}
					}
				}
			} else {
				if (key === 'array') {
					obj = [];
				} else {
					obj = {};
				}
			}
			
			return obj;
		},
		
		
		/*
		 * ---------------------------------------- ANIMATIONS ----------------------------------------
		 */
		
		
		/**
		 * Fade and slide in node
		 * 
		 * @param {Object} node Slide node which will be animated
		 * @protected
		 */
		_animateIn: function (node) {
			var height = node.get('offsetHeight');
			
			node.setStyles({
				'overflow': 'hidden',
				'height': '0px',
				'opacity': 0
			});
			node.transition({
				'height': height + 'px',
				'opacity': 1,
				'duration': 0.35
			}, Y.bind(function () {
				node.removeAttribute('style');
				this._fireResizeEvent();
			}, this));
		},
		
		_animateOut: function (node, widgets) {
			node.setStyles({
				'overflow': 'hidden',
				'margin': 0,
				'padding': 0
			});
			node.transition({
				'height': '0px',
				'opacity': 0,
				'duration': 0.35
			}, Y.bind(function () {
				//Destroy inputs
				if (widgets.input) {
					widgets.input.destroy(true);
				}
				if (widgets.buttonRemove) {
					widgets.buttonRemove.destroy(true);
				}
				
				// Remove node
				node.remove(true);
				
				// Trigger resize for scrollbar size and position
				this._fireResizeEvent();
			}, this));
		},
		
		
		/*
		 * ---------------------------------------- SLIDESHOW ----------------------------------------
		 */
		
		
		/**
		 * Add slide to the slideshow
		 * 
		 * @protected
		 */
		_renderSlide: function () {
			var label = this.get('label'),
				labelButton = this.get('labelButton'),
				icon = this.get('icon'),
				
				slideshow = this.getSlideshow(),
				slide_id = this.get('id') + '_' + Y.guid(),
				slide = slideshow.addSlide({
					'id': slide_id,
					'title': label || labelButton
				});
			
			this._slideContent = slide.one('.su-slide-content');
			this._slideId = slide_id;
			
			// Button
			var button = new Supra.Button({
				'style': icon ? 'icon' : 'small',
				'label': labelButton || label,
				'icon': icon
			});
			
			button.addClass('button-section');
			button.render(this.get('contentBox'));
			
			if (this.get('disabled')) {
				button.set('disabled', true);
			}
			
			this._slideButton = button;
		},
		
		_openSlide: function () {
			var slideshow = this.getSlideshow();
			slideshow.set('slide', this._slideId);
		},
		
		/**
		 * Fire resize event
		 * 
		 * @param {Object} node Node which content changed
		 * @protected
		 */
		_fireResizeEvent: function () {
			var container = null;
			
			if (this.get('separateSlide')) {
				container = this._slideContent;
			} else {
				container = this.get('contentBox');
			}
			
			if (container) {
				container = container.closest('.su-scrollable-content');
				
				if (container) {
					Supra.immediate(container, function () {
						this.fire('contentresize');
					});
				}
			}
		},
		
		
		/*
		 * ------------------------- Attributes -----------------------------
		 */
		
		
		/**
		 * Trigger value change events
		 * 
		 * @protected
		 */
		_fireChangeEvent: function () {
			if (this._silentValueUpdate) return;
			this._silentValueUpdate = true;
			this.set('value', this.get('value'));
			this._silentValueUpdate = false;
		},
		
		_fireInputEvent: function (event, property) {
			var index = this._focusedItemIndex;
			
			this.fire('input', {
				'value': event.value,
				'index': index,
				'property': property
			});
		},
		
		/**
		 * Value attribute setter
		 * 
		 * @param {Object} value New value
		 * @returns {Object} New value
		 * @protected
		 */
		_setValue: function (value) {
			value = value || [];
			
			// If we are updating just 'value' (this._silentValueUpdate) then
			// don't change UI.
			// If inputs hasn't been rendered then we can't set value
			if (!this.get('rendered') || this._silentValueUpdate || !this._valuesRendered) return value;
			this._silentValueUpdate = true;
			
			var property = this.get('properties'),
				widgets = this._widgets,
				i,
				old_count,
				count;
			
			if (Supra.Input.isInline(property)) {
				// Need to craete/re-create all inline inputs
				count = this._count;
				i = count-1;
				
				for (; i >= 0; i--) {
					this._removeItem(i);
				}
				
				// Add new values
				count = value.length;
				i = 0;
				
				for (; i<count; i++) {
					this._addItem(value[i]);
				}
				
				// @TODO We should recreate only if inputs are not valid anymore!
			} else {
				// 
				old_count = this._count;
				count = value.length;
				i = 0;
				
				for (; i < count; i++) {
					if (widgets[i] && widgets[i].input) {
						// Update item
						widgets[i].input.set('value', value[i], {'silent': true});
						
						if (widgets[i].input.isInstanceOf('input-set')) {
							// Force property check
							widgets[i].input.set('properties', widgets[i].input.get('properties'));
						}
					} else {
						// Add new item
						this._addItem(value[i]);
					}
				}
				
				// Remove unneeded items
				i = old_count - 1;
				for (; i >= count; i--) {
					this._removeItem(i);
				}
			}
			
			this._silentValueUpdate = false;
			this._fireChangeEvent();
			
			if (value) {
				// Value is missing only when resetting form, not need to
				// fire resize event in that case
				this._fireResizeEvent();
			}
			
			return value;
		},
		
		/**
		 * Value attribute getter
		 * 
		 * @returns {Object} Value
		 * @protected
		 */
		_getValue: function (value) {
			// If inputs hasn't been rendered then we can't get values from
			// inputs which doesn't exist
			if (!this.get('rendered') || !this._valuesRendered) {
				return value;
			}
			
			var data = [],
				i = 0,
				ii = this._count,
				widgets = this._widgets;
			
			if (this.get('properties')) {
				for (; i<ii; i++) {
					if (widgets[i].input) {
						data.push(widgets[i].input.get('value'));
					}
				}
			}
			
			return data;
		},
		
		_getSaveValue: function () {
			// If inputs hasn't been rendered then we can't get values from
			// inputs which doesn't exist
			if (!this.get('rendered') || !this._valuesRendered) {
				return this.get('value');
			}
			
			var data = [],
				i = 0,
				ii = this._count,
				widgets = this._widgets,
				property = this.get('properties');
			
			if (property) {
				for (; i<ii; i++) {
					if (widgets[i].input) {
						data.push(widgets[i].input.get('saveValue'));
					} else {
						data.push(Supra.Input.getDefaultValue(property.type));
					}
				}
			}
			
			if (data.length) {
				return data;
			} else {
				// Empty arrays can't be sent through ajax, so we convert
				// them into empty string, which can be sent
				return '';
			}
		},
		
		_afterValueChange: function (evt) {
			this.fire('change', {'value': evt.newVal});
		},
		
		/**
		 * Disable all child inputs
		 */
		_setDisabled: function (disabled) {
			Collection.superclass._setDisabled.apply(this, arguments);
			if (!this.get('rendered')) return disabled;
			
			var i = 0,
				ii = this._count,
				widgets = this._widgets;
			
			if (this.get('properties')) {
				for (; i<ii; i++) {
					widgets[i].input.set('disabled', disabled);
				}
			}
			
			return disabled;
		}
		
	});
	
	Supra.Input.Collection = Collection;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});
