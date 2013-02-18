YUI.add('supra.input-map-inline', function (Y) {
	//Invoke strict mode
	"use strict";
	
	//Shortcuts
	var Manager = Supra.Manager;
	
	//Default value
	var DEFAULT_VALUE = {
		'latitude': 0,
		'longitude': 0
	};
	
	
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = false;
	
	Input.NAME = 'input-map-inline';
	Input.CLASS_NAME = Y.ClassNameManager.getClassName(Input.NAME);
	Input.ATTRS = {
		// Image node which is edited
		'targetNode': {
			value: null,
			setter: 'createMap'
		},
		'doc': {
			value: null
		},
		'win': {
			value: null
		}
	};
	
	Input.HTML_PARSER = {};
	
	Y.extend(Input, Supra.Input.Proto, {
		/**
		 * Constants
		 */
		INPUT_TEMPLATE: '',
		LABEL_TEMPLATE: '',
		CONTENT_TEMPLATE: '',
		
		/**
		 * Map instance
		 * @type {Object}
		 * @private
		 */
		map: null,
		
		/**
		 * Map marker
		 * @type {Object}
		 * @private
		 */
		marker: null,
		
		/**
		 * Location cache
		 * @type {Array}
		 * @private
		 */
		value: null,
		
		/**
		 * Map node
		 * @type {HTMLElement}
		 * @private
		 */
		node: null,
		
		/**
		 * Silent value change
		 * @type {Boolean}
		 * @private
		 */
		silent: false,
		
		/**
		 * Dragend event listener
		 * @type {Boolean}
		 * @private
		 */
		_dragendListener: null,
		
		
		renderUI: function () {
			Input.superclass.renderUI.apply(this, arguments);
			
			//Bind to context
			this._afterValueChange = Y.bind(this._afterValueChange, this);
			
			//MapManager.prepare(this.createMap, this);
			this.createMap(this.get('targetNode'));
		},
		
		bindUI: function () {
			Input.superclass.bindUI.apply(this, arguments);
			
			// FIXME Using form is dirty, should be using startEditing / stopEditing instead
			var form = this.getForm();
			if (form) {
				form.on('visibleChange', function (e) {
					if (e.newVal != e.prevVal) {
						if (e.newVal) {
							this.bindMapEvents();
						} else {
							this.unbindMapEvents();
						}
					}
				}, this);
			}
		},
		
		/**
		 * Start editing
		 * Enable marker drag and drop
		 */
		bindMapEvents: function () {
			if (this.marker) {
				var global = this.get('win');
				
				if (this._dragendListener) {
					// Remove in case if old reference
					global.google.maps.event.removeListener(this._dragendListener);
				}
				
				this.marker.set('draggable', true);
				this._dragendListener = global.google.maps.event.addListener(this.marker, 'dragend', this._afterValueChange);
			}
		},
		
		/**
		 * Stop editing
		 * Disable marker drag and drop
		 */
		unbindMapEvents: function () {
			if (this.marker) {
				var global = this.get('win');
				
				this.marker.set('draggable', false);
				global.google.maps.event.removeListener(this._dragendListener);
				this._dragendListener = null;
			}
		},
		
		/**
		 * Create map
		 * 
		 * @param {Object} targetNode Element inside which to create map
		 */
		createMap: function (targetNode) {
			if (targetNode && targetNode !== this.get('targetNode')) {
				MapManager.prepare(this.get('doc'), this.get('win'), function () {
					this._createMap(targetNode);
				}, this);
			}
			return targetNode;
		},
		
		_createMap: function (targetNode) {
			var global = this.get('win'),
				doc    = this.get('doc'),
				value  = this.get('value'),
				cont   = targetNode,
				node   = null,
				map    = null,
				marker = null,
				latlng = null,
				options = null,
				
				g_node = null,
				g_instance = null;
			
			if (!cont) return;
			node = cont.getDOMNode();
			cont.addClass('supra-map');
			
			if (global.jQuery) {
				g_node = global.jQuery(node);
				g_instance = g_node.data('map');
				
				if (g_instance && g_instance.map) {
					// We can get existing map instance
					this.map = g_instance.map;
					this.marker = g_instance.marker;
					this.startEditing();
					return;
				}
			}
			
			cont.empty(); // in case there already is a map
			
			latlng = new global.google.maps.LatLng(value.latitude, value.longitude);
			options = {
				zoom: 8,
				center: latlng,
				streetViewControl: false,
				mapTypeId: global.google.maps.MapTypeId.ROADMAP
			};
			
			map = this.map = new global.google.maps.Map(node, options);
			
			//Add marker
			if (!latlng) {
				latlng = map.getCenter();
			}
			marker = this.marker = new global.google.maps.Marker({'position': latlng, 'map': map, 'draggable': true});
			
			//On marker drag trigger change event
			this.startEditing();
		},
		
		
		/* -------------------------------------- ATTRIBUTES -------------------------------------- */
		
		
		/**
		 * Disabled attribute change
		 * 
		 * @param {Boolean} disabled
		 * @returns {Boolean} New attribute value
		 * @private
		 */
		/*_setDisabled: function (disabled) {
			if (disabled === true) {
				this.stopEditing();
			} else if (disabled === false){
				this.startEditing();
			}
			
			return false;
		},*/
		
		/**
		 * Value setter
		 * 
		 * @param {Object} value Object with latitude and longitude properties
		 * @returns New attribute value
		 * @private
		 */
		_setValue: function (value) {
			if (this.silent) return value;
			
			var latlng = null,
				map = this.map,
				marker = this.marker,
				global = this.get('win');
			
			value = Supra.mix({}, DEFAULT_VALUE, this.get('defaultValue'), value);
			
			if (map && marker) {
				latlng = new global.google.maps.LatLng(value.latitude, value.longitude);
				map.setCenter(latlng);
				marker.setPosition(latlng);
			}
			
			return value;
		},
		
		/**
		 * Value getter
		 * 
		 * @returns {Object} Object with latitude and longitude properties
		 * @private
		 */
		_getValue: function (value) {
			var value = Supra.mix({}, DEFAULT_VALUE, this.get('defaultValue'), value),
				point = null,
				map = this.map,
				marker = this.marker;
			
			if (map && marker) {
				point = marker.getPosition();
				value.latitude = point.lat();
				value.longitude = point.lng();
			}
			
			return value;
		},
		
		/**
		 * After value change trigger event
		 */
		_afterValueChange: function () {
			var value = this.get('value');
			
			this.silent = true;
			this.set('value', value);
			this.silent = false;
			
			this.fire('change', {'value': value});
		}
		
	});
	
	Supra.Input.InlineMap = Input;
	
	
	/**
	 * Handle loading Google Maps API and call callback when done
	 */
	var MapManager = Supra.Input.MapManager = {
		
		/**
		 * Property name which is set on document
		 */
		DOCUMENT_PROPERTY: 'supraGoogleMapsGuid',
		
		/**
		 * Function prefix which is set on window
		 */
		WINDOW_FUNCTION: 'supraGoogleMapsReady',
		
		/**
		 * Unique ID counter
		 */
		guid: 1,
		
		/**
		 * Maps
		 */
		maps: {},
		
		/**
		 * List of event listeners
		 * @type {Array}
		 */
		listeners: [],
		
		/**
		 * 
		 */
		prepare: function (doc, win, callback, context) {
			var guid = doc[this.DOCUMENT_PROPERTY],
				map  = null;
			
			if (!guid) {
				guid = this.create(doc, win);
			}
			
			map = this.maps[guid];
			
			if (map.loaded && callback) {
				callback.call(context || window);
			} else if (map.loading && callback) {
				map.listeners.push({'fn': callback, 'obj': context || window});
			}
		},
		
		create: function (doc, win) {
			// Create callback
			var guid = this.guid++,
				fn   = this.WINDOW_FUNCTION + guid,
				self = this;
			
			doc[this.DOCUMENT_PROPERTY] = guid;
			win[fn] = function () { self.ready(guid); };
			
			// Create object
			this.maps[guid] = {
				'loaded': false,
				'loading': true,
				'listeners': []
			};
			
			if (win.google && (win.google.maps || win.google.load)) {
				if (win.google.maps) {
					// Google Maps loaded
					this.maps[guid].loading = false;
					this.maps[guid].loaded = true;
				} else {
					// Google API loaded, load Maps
					win.google.load("maps", "3",  {callback: win[fn], other_params:"sensor=false"});
				}
			} else {
				// Load Google Maps
				var script = document.createElement('script');
					script.type = 'text/javascript';
					script.src  = document.location.protocol + '//maps.googleapis.com/maps/api/js?sensor=false&callback=' + fn;
				
				doc.body.appendChild(script);
			}
			
			return guid;
		},
		
		ready: function (guid) {
			var map = this.maps[guid],
				listeners = map.listeners,
				i = 0,
				ii = listeners.length;
			
			map.listeners = [];
			map.loading = false;
			map.loaded = true;
			
			for(; i<ii; i++) {
				listeners[i].fn.call(listeners[i].obj);
			}
		}
	};
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:['supra.input-proto']});