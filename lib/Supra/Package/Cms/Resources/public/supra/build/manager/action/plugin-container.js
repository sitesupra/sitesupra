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
 * Manager Action plugin to automatically hide container when
 * action 'visible' attribute changes
 */
YUI.add('supra.manager-action-plugin-container', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Action = Supra.Manager.Action;
	
	function PluginContainer () {
		PluginContainer.superclass.constructor.apply(this, arguments);
		this.children = {};
	};
	
	PluginContainer.NAME = 'PluginContainer';
	
	Y.extend(PluginContainer, Action.PluginBase, {
		
		initialize: function () {
			//On visibility change show/hide container
			this.host.on('visibleChange', function (evt) {
				var node = this.one();
				if (node && evt.newVal != evt.prevVal) {
					node.toggleClass('hidden', !evt.newVal);
					
					if (evt.newVal) {
						this.fire('show');
					} else {
						this.fire('hide');
					}
				}
			});
		}
		
	});
	
	Action.PluginContainer = PluginContainer;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base']});