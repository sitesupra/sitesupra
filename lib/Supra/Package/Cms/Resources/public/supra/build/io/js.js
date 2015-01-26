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

/**
 * Extension to load CSS files
 */
YUI().add("supra.io-js", function (Y) {
	//Invoke strict mode
	"use strict";
	
	Supra.io.js = function (url, cfg) {
		cfg = cfg || {};
		cfg.on = cfg.on || {};
		cfg.deferred = cfg.deferred || new Supra.Deferred();
		
		var success_handler = function (data) {
				// Deferred
				cfg.deferred.resolveWith(cfg.context, [data, true]);
				
				// Backward compatibility
				if (Y.Lang.isFunction(cfg.on.complete)) {
					cfg.on.complete.apply(cfg.context, [data, true]);
				}
				if (Y.Lang.isFunction(cfg.on.success)) {
					cfg.on.success.apply(cfg.context, [data, true]);
				}
			},
			
			failure_handler = function (data) {
				// Deferred
				cfg.deferred.rejectWith(cfg.context, [data, false]);
				
				// Backward compatibility
				if (Y.Lang.isFunction(cfg.on.complete)) {
					cfg.on.complete.apply(cfg.context, [data, false]);
				}
				if (Y.Lang.isFunction(cfg.on.failure)) {
					cfg.on.failure.apply(cfg.context, [data, false]);
				}
			};
		
		var io = Y.Get.js(url, cfg, function (err, transaaction) {
			if (err && err.length) {
				failure_handler({'url': url, 'errors': err});
			} else {
				success_handler({'url': url, 'errors': null});
			}
		});
		
		// Add promise functionality to the transaction
		cfg.deferred.promise(io);
		
		return io;
	};
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ["supra.io"]});