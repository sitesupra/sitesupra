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
YUI.add('supra.manager-loader-actions', function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Set common action base paths
	 * 
	 * Define here all actions which are reusable between 'managers' to allow them loading
	 * without specifying path each time
	 */
	Supra.Manager.Loader.setActionBasePaths({
		'Header': '/content-manager',
		'Notification': '/content-manager',
		'SiteMap': '/content-manager',
		'SiteMapRecycle': '/content-manager',
		'PageToolbar': '/content-manager',
		'PageButtons': '/content-manager',
		'EditorToolbar': '/content-manager',
		'Page': '/content-manager',
		'PageSettings': '/content-manager',
		'PageContentSettings': '/content-manager',
		'PageSourceEditor': '/content-manager',
		'LayoutContainers': '/content-manager',
		'Confirmation': '/content-manager',
		'LinkManager': '/content-manager',
		
		'MediaLibrary': '/media-library',
		'MediaSidebar': '/media-library',
		'IconSidebar': '/media-library',
		
		'Login': '/login',
		'MyPassword': '/login',
		
		'UserAvatar': '/internal-user-manager',
		
		'Applications': '/dashboard',
		'BrowserSupport': '/dashboard',
		
		'ComboSidebar': '/content-manager',
		'Crud': '/crud-manager-2'
	});

	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires: ['supra.manager-loader']});
