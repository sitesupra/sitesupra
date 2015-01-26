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
 * Url generator
 */
YUI.add('supra.url', function(Y){
	"use strict";

	var Url = Supra.Url = {
		routes: {},

		generate: function (route, params) {
			params = params || {};

			if (!this.routes[route]) {
				throw Error('Route "' + route + '" is not defined');
			}

			var routeParams = this.routes[route];

			var path = [];

			for (var i = 0; i < routeParams.tokens.length; i ++) {
				var token = routeParams.tokens[i];

				//token type?
				switch (token[0]) {
					case 'text':
						path.push(token[1]);
						break;
					case 'variable':
						var value = undefined;
						if (token[3] in params) {
							value = params[token[3]];
						} else {
							if (token[3] in routeParams.defaults) {
								value = routeParams.defaults[token[3]];
							}
						}

						if (value === undefined) {
							throw Error('Can not resolve value for route parameter "'+token[3]+'"');
						}

						//todo: check requirements
						//empty / nullable path elements are not appended to prevent uri's like /foo///bar
						if (value) {
							path.push(value);
						}
						break;
					default:
						throw Error('Sorry, only "text" tokens are supported now, "'+token[0]+'" given');
						break;
				}
			}

			path.reverse();

			return path.join('/');
		},

		setRoutes: function (routes) {
			this.routes = routes;
		}
	};

	delete(this.fn); this.fn = function () {};
}, YUI.version);
