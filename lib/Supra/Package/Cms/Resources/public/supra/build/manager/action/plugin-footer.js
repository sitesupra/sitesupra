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
YUI.add('supra.manager-action-plugin-footer', function (Y) {
	//Invoke strict mode
	"use strict";
	
	var Action = Supra.Manager.Action;
	
	function PluginFooter () {
		PluginFooter.superclass.constructor.apply(this, arguments);
	};
	
	PluginFooter.NAME = 'PluginFooter';
	
	Y.extend(PluginFooter, Action.PluginBase, {
		
		initialize: function () {
			
			if (!this.placeholders) {
				Y.log('Can\'t find container to create Form for Action ' + this.host.NAME + '. Please make sure there is a template', 'error');
				return;
			}
			
			//Find container
			var node = this.host.one('div.footer');
			
			//Add widget
			if (node) {
				var config = {
					'srcNode': node
				};
				this.addWidget(new Supra.Footer(config));
			}
		},
		
		render: function () {
			PluginFooter.superclass.render.apply(this, arguments);
			
			//Find panel
			var panel = this.host.getPluginWidgets('PluginPanel', true);
			panel = panel.length ? panel[0] : null;
			
			//Find form
			var form = this.host.getPluginWidgets('PluginForm', true);
			form = form.length ? form[0] : null;
			
			//Close button should close form
			var cancel = this.instances.footer.getButton('cancel');
			if (cancel && panel) {
				cancel.on('click', panel.hide, panel);
			}
		},
		
		execute: function () {
			PluginFooter.superclass.execute.apply(this, arguments);
			
			// @TODO reset form values
		}
		
	});
	
	Action.PluginFooter = PluginFooter;
	
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-action-plugin-base', 'supra.footer']});