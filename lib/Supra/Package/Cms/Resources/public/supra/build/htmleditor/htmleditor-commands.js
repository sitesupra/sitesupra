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
YUI().add('supra.htmleditor-commands', function (Y) {
	//Invoke strict mode
	"use strict";
	
	Y.mix(Supra.HTMLEditor.prototype, {
		
		/**
		 * Plugin instances
		 */
		commands: {},
		
		/**
		 * Add command
		 * 
		 * @param {String} id
		 * @param {Function} callback
		 */
		addCommand: function (id, callback) {
			if (!(id in this.commands)) {
				this.commands[id] = [];
			}
			
			this.commands[id].push(callback);
		},
		
		/**
		 * Execute command
		 * 
		 * @param {String} action
		 */
		exec: function (command, data) {
			var disabled = this.get('disabled');
			if (disabled || !this.editingAllowed) return;
			
			if (command in this.commands) {
				var commands = this.commands[command],
					i=commands.length-1;
					
				for(; i >= 0; i--) {
					if (commands[i](data, command) === true) {
						//New node may have been added
						if (!this.refresh()) {
							//Or maybe only style changed
							this.fire('selectionChange');
							this.fire('nodeChange');
						};
						
						return true;
					}
				}
			}
			
			return false;
		}
	});

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {'requires': ['supra.htmleditor-base']});