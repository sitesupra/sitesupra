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
YUI.add('supra.manager-action-plugin-panel', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Action = Supra.Manager.Action;
	
	function PluginPanel () {
		PluginPanel.superclass.constructor.apply(this, arguments);
		this.children = {};
	};
	
	PluginPanel.NAME = 'PluginPanel';
	
	Y.extend(PluginPanel, Action.PluginBase, {
		
		initialize: function () {
			if (!this.placeholders) {
				Y.log('Can\'t find container to create Panel for Action ' + this.host.NAME + '. Please make sure there is a template', 'error');
				return;
			}
			
			//Panel
			var node = this.host.one('DIV');
			if (node) {
				
				var config = {
					'srcNode': node,
					'xy': [20, 65],
					'visible': false
				};
				
				this.addWidget(new Supra.Panel(config));
			}
			
			//Propagate events to Action
			//When panel is hidden/shown do the same for action and vice versa
			var instances = this.instances;
			for(var i in instances) {
				this.host.bindAttributes(instances[i], {'visible': 'visible'});
			}
		},
		
		/**
		 * Render UI
		 */
		render: function () {
			PluginPanel.superclass.render.apply(this, arguments);
			
			//Initially all panels should be are hidden
			var instances = this.instances;
			for(var i in instances) instances[i].hide();
		},
		
		execute: function () {
			PluginPanel.superclass.execute.apply(this, arguments);
			
			//Hide panels
			var instances = this.instances;
			for(var i in instances) if (!instances[i].get('visible')) instances[i].show();
		}
		
	});
	
	Action.PluginPanel = PluginPanel;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base', 'supra.panel']});