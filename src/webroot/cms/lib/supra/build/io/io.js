//Invoke strict mode
"use strict";

YUI().add("supra.io", function (Y) {
	
	var ERROR_INVALID_RESPONSE = 'Error occured, please try again later';
	
	Supra.io = function (url, cfg, permissions, callback, context) {
		var io = null;
		
		//Clone args object to make sure it's unchanged
		var args = [url, cfg, permissions, callback, context];
		
		//Check optional arguments
		var normal = Supra.io.normalizeArguments(url, cfg, permissions, callback, context);
		url         = normal[0];
		cfg         = normal[1];
		permissions = normal[2];
		callback    = normal[3];
		context     = normal[4];
		
		//Success and failure methods are overwritten, save references to originals
		cfg.on._success = cfg.on.success;
		cfg.on._failure = cfg.on.failure;
		cfg.on._complete = cfg.on.complete;
		cfg.on.complete = null;
		cfg._data = cfg.data;
		cfg._url = url;
		
		//Add session id to data
		if (!('data' in cfg) || !Y.Lang.isObject(cfg.data)) {
			cfg._data = cfg.data = {};
		}
		
		var sid_name = SU.data.get('sessionName', null),
			sid_id = SU.data.get('sessionId', null);
			
		if (sid_name && sid_id) {
			cfg.data[sid_name] = sid_id;
		}
		
		//Add permissions to the request
		if (cfg.permissions) {
			cfg.data = Supra.mix({
				'_check-permissions': cfg.permissions
			}, cfg.data);
			
			//Make sure Supra.Permission.request doesn't do another request
			Supra.Permission.setIsLoading(cfg.permissions)
		}
		
		//Convert object into string compatible with PHP
		cfg.data = Supra.io.serializeIntoString(cfg.data);
		
		//Set callbacks
		cfg.on.success = function (transaction, response) {

			var response = Supra.io.parseResponse(url, cfg, response.responseText);
			return Supra.io.handleResponse(cfg, response);

		};
		cfg.on.failure = function (transaction, response) {

			if (response.status == 401) {
				//Authentication error, session expired
				Y.log('Session expired', 'info');
				
				var pre_filter_message = response.getResponseHeader('X-Authentication-Pre-Filter-Message');
				
				//If there is authentication message then this was login request
				//which shouldn't be queued
				if (!pre_filter_message) {
					Supra.io.loginRequestQueue.add(args);
				}
				
				return Supra.io.handleResponse(cfg, {
					'status': response.status,
					'success': false,
					'data': null,
					'error_message': pre_filter_message
				});
				
			} else {
				//Invalid response
				Y.log('Request to "' + url + '" failed', 'debug');
				
				return Supra.io.handleResponse(cfg, {
					'status': 0,
					'success': false,
					'data': null,
					'error_message': ERROR_INVALID_RESPONSE
				});
				
			}
		};
		
		io = Y.io(url, cfg);
		return io;
	};
	
	/**
	 * Normalize Supra.io arguments
	 * 
	 * @return Array with normalized arguments
	 * @type {Array}
	 * @private
	 */
	Supra.io.normalizeArguments = function (url, cfg, permissions, callback, context) {
		//Check optional arguments
		if (Y.Lang.isArray(cfg)) {
			//cfg argument missing
			context = callback;
			callback = permissions;
			permissions = cfg;
			cfg = {};
		} else if (Y.Lang.isFunction(cfg)) {
			//cfg and permissions arguments missing
			callback = cfg;
			context = permissions;
			cfg = {};
			permissions = null;
		} else if (Y.Lang.isFunction(permissions)) {
			//permissions argument missing
			context = callback;
			callback = permissions;
			permissions = null;
		} else if (Y.Lang.isObject(permissions) && !Y.Lang.isArray(permissions)) {
			//permissions and callback arguments missing
			context = permissions;
			callback = null;
			permissions = null;
		} else if (Y.Lang.isObject(callback)) {
			context = callback;
			callback = null;
		}
		
		//Normalize permissions
		if (!Y.Lang.isArray(permissions)) {
			permissions = null;
		}
		
		//Configuration
		if (!Y.Lang.isObject(cfg)) {
			cfg = {};
		}
		
		var cfg_default = {
			'type': 'json',
			'data': null,
			'permissions': permissions,
			'sync': false,
			'context': context,
			'suppress_errors': false,
			'on': {
				'success': callback,
				'failure': null,
				'complete': null
			}
		};
		
		//Save context and remove from config to avoid traversing them on Supra.mix
		context = cfg.context || cfg_default.context;
		cfg.context = cfg_default.context = null;
		
		cfg = Supra.mix(cfg_default, cfg, true);
		
		cfg.context = cfg_default.context = context;
		
		return [url, cfg, permissions, callback, context];
	};
	
	/**
	 * Parse response and check for correct format
	 * 
	 * @param {Object} cfg Request configuration
	 * @param {String} responseText Response text
	 * @return Parsed response
	 * @type {Object}
	 * @private
	 */
	Supra.io.parseResponse = function (url, cfg, responseText) {
		var data = null,
			response = {'status': false, 'data': null};
		
		//Localization, unless in configuration skipIntl is set
		if (responseText.indexOf('{#') !== -1 && (!cfg || !cfg.skipIntl)) {
			responseText = Supra.Intl.replace(responseText, 'json');
		}
		
		try {
			switch((cfg.type || '').toLowerCase()) {
				case 'json':
					data = Y.JSON.parse(responseText);
					Supra.mix(response, data);
					break;
				case 'jsonplain':
					data = Y.JSON.parse(responseText);
					Supra.mix(response, {'status': true, 'data': data});
					break;
				default:
					response = {'status': true, 'data': responseText};
					break;
			}
			
			if (!response.status && !response.error_message) {
				//Request didn't completed successfully and there is no message,
				//show default error message
				response.error_message = ERROR_INVALID_RESPONSE;
			}
			
		} catch (e) {
			Y.log('Unable to parse "' + url + '" request response: invalid JSON', 'debug');
			response.error_message = ERROR_INVALID_RESPONSE;
		}
		
		return response;
	};
	
	/**
	 * Handle response.
	 * Show error message, confirmation window and call success or failure callbacks
	 * 
	 * @param {Object} cfg Request configuration
	 * @param {Object} response Response object
	 * @private
	 */
	Supra.io.handleResponse = function (cfg, response) {
		//Show login form
		if (response.status == 401) {
			
			if (Supra.Manager) {
				Supra.Manager.executeAction('Login', response);
			}
			
			return;
		}
		
		//Show error message
		if (response.error_message) {
			this.handleErrorMessage(cfg, response);
		
		//Show warning messages
		} else if (response.warning_message) {
			this.handleWarningMessage(cfg, response);
		}
		
		//Show confirmation message
		if (response.confirmation_message) {
			return this.handleConfirmationMessage(cfg, response);
		}
		
		//Handle permissions
		if (response.permissions) {
			Supra.Permission.add(response.permissions, cfg.permissions);
		}
		
		//Missing callbacks, ignore
		if (!cfg || !cfg.on) return null;
		
		//Call callbacks
		var fn  = response.status ? cfg.on._success : cfg.on._failure,
			ret = null;
		
		if (Y.Lang.isFunction(cfg.on._complete)) {
			cfg.on._complete.apply(cfg.context, [response.data, response.status]);
		}
		
		if (Y.Lang.isFunction(fn)) {
			ret = fn.apply(cfg.context, [response.data, response.status]);
		}
		
		delete(cfg.permissions);
		delete(cfg._data);
		delete(cfg.data);
		delete(cfg.on._success);
		delete(cfg.on._failure);
		delete(cfg.on.success);
		delete(cfg.on.failure);
		delete(cfg.on._complete);
		delete(cfg.on.complete);
		
		return ret;
	};
	
	/**
	 * Handle error message parameter
	 * Show error message
	 * 
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleErrorMessage = function (cfg, response) {
		//No error or warning messages when "suppress_errors" parameter is set
		if (cfg.suppress_errors) return;
		
		SU.Manager.executeAction('Confirmation', {
		    'message': response.error_message,
		    'useMask': true,
		    'buttons': [
		        {'id': 'delete', 'label': 'Ok'}
		    ]
		});
	};
	
	/**
	 * Handle warning message parameter
	 * Show warning message
	 * 
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleWarningMessage = function (cfg, response) {
		//No error or warning messages when "suppress_errors" parameter is set
		if (cfg.suppress_errors) return;
		
		var message = response.warning_message,
			single = true;
		
		if (Y.Lang.isArray(message)) {
			if (message.length > 1) {
				single = false;
				message = '{#error.warnings#}<ul><li>' + message.join('</li><li>') + '</li></ul>';
			} else {
				message = message.shift();
			}
		}
		SU.Manager.executeAction('Confirmation', {
		    'message': message,
		    'align': single ? 'center' : 'left',
		    'useMask': true,
		    'buttons': [
		        {'id': 'delete', 'label': 'Ok'}
		    ]
		});
	};
	
	/**
	 * Handle confirmation message parameter
	 * Show confirmation message
	 * 
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleConfirmationMessage = function (cfg, response) {
		SU.Manager.executeAction('Confirmation', {
		    'message': response.confirmation_message.message,
		    'useMask': true,
		    'buttons': [
		    	{'id': 'yes', 'context': this, 'click': function () { this.handleConfirmationResult(1, cfg, response); }},
		    	{'id': 'no',  'context': this, 'click': function () { this.handleConfirmationResult(0, cfg, response); }}
		    ]
		});
	};
	
	/**
	 * On message confirm or deny send same request again and add answer to
	 * the data
	 * 
	 * @param {Number} answer Confirmation message answer
	 * @param {Object} request Request configuration
	 * @param {Object} response Request response
	 * @private
	 */
	Supra.io.handleConfirmationResult = function (answer, cfg, response) {
		var url = cfg._url;
		
		//Restore original values
		cfg.on.success  = cfg.on._success;
		cfg.on.failure  = cfg.on._failure;
		cfg.on.complete = cfg.on._complete;
		cfg.data        = cfg._data;
		
		delete(cfg.on._success);
		delete(cfg.on._failure);
		delete(cfg.on._complete);
		delete(cfg._data);
		delete(cfg._url);
		
		//Add answer to the request
		if (!('data' in cfg) || !Y.Lang.isObject(cfg.data)) {
			cfg.data = {};
		}
		cfg.data[response.confirmation_message.id] = answer;
		
		//Run request again
		Supra.io(url, cfg);
	};
	
	
	/**
	 * 
	 * @param {Object} obj
	 * @param {Object} prefix
	 */
	Supra.io.serialize = function (obj, prefix) {
		if (!Y.Lang.isObject(obj) && !Y.Lang.isArray(obj)) return obj;
		var o = {}, name = null;
		
		for(var i in obj) {
			if (obj.hasOwnProperty(i)) {
				name = (prefix ? prefix + '[' + encodeURIComponent(i) + ']' : encodeURIComponent(i));
				
				if (Y.Lang.isDate(obj[i])) {
					//Automatically format date to Y-m-d
					o[name] = encodeURIComponent(Y.DataType.Date.reformat(obj[i], 'raw', 'in_datetime'));
				} else if (Y.Lang.isObject(obj[i]) || Y.Lang.isArray(obj[i])) {
					Supra.mix(o, this.serialize(obj[i], name));
				} else {
					//Null or undefined shouldn't be sent to server-side, because they are received as strings
					o[name] = encodeURIComponent(obj[i] === null || obj[i] === undefined ? '' : obj[i]);
				}
			}
		}
		
		return o;
	};
	
	/**
	 * Serialize data into string
	 * 
	 * @param {Object} obj
	 * @return Serialized data
	 * @type {String}
	 */
	Supra.io.serializeIntoString = function (obj) {
		if (!Y.Lang.isObject(obj) && !Y.Lang.isArray(obj)) return obj;
		var obj = Supra.io.serialize(obj), o = [];
		
		for(var i in obj) {
			o[o.length] = i + '=' + obj[i];
		}
		
		return o.join('&');
	};
	
	/**
	 * Queue of requests which resulted in 401 responses
	 */
	Supra.io.loginRequestQueue = {
		'queue': [],
		
		/**
		 * Add request to the queue
		 * 
		 * @param {Array} args Request arguments
		 */
		'add': function (args) {
			this.queue.push(args);
		},
		
		/**
		 * Execute all requests from queue
		 */
		'run': function () {
			var queue = this.queue;
			this.queue = [];
			
			for(var i=0,ii=queue.length; i<ii; i++) {
				Supra.io.apply(Supra.io, queue[i]);
			}
		}
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["io", "json"]});