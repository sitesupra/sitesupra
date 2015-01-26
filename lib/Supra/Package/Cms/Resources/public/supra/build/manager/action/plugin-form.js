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
YUI.add('supra.manager-action-plugin-form', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Action = Supra.Manager.Action;
	
	function PluginForm () {
		PluginForm.superclass.constructor.apply(this, arguments);
	};
	
	PluginForm.NAME = 'PluginForm';
	
	Y.extend(PluginForm, Action.PluginBase, {
		
		initialize: function () {
			
			if (!this.placeholders) {
				Y.log('Can\'t find container to create Form for Action ' + this.host.NAME + '. Please make sure there is a template', 'error');
				return;
			}
			
			//Find container node
			var node = this.host.one('form');
			if (!node) {
				//Use panels content box if form is not found
				var panel = this.plugins.getPlugin('PluginPanel');
				if (panel) node = panel.getWidget('panel').get('contentBox');
			}
			if (!node) {
				//Use first found item
				node = this.host.one();
			}
			
			//Create form
			var form = new Supra.Form({ 'srcNode': node });
			this.addWidget(form);
			
			form.setURLLoad(this.host.getDataPath());
		},
		
		render: function () {
			PluginForm.superclass.render.apply(this, arguments);
		},
		
		execute: function () {
			PluginForm.superclass.execute.apply(this, arguments);
			
			// @TODO reset form values; auto-load form data
		}
		
	});
	
	Action.PluginForm = PluginForm;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base', 'supra.input']});