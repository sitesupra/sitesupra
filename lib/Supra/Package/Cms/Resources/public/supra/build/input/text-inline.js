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
YUI.add("supra.input-inline-text", function (Y) {
	//Invoke strict mode
	"use strict";
	
	/**
	 * Helper functions for escaping/unescaping strings
	 */
	var HTML_CHARS = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#x27;',
        '/': '&#x2F;',
        '`': '&#x60;',
        ' ': '&nbsp;'
    }
	
	var HTML_CHARS_INVERSE = {};
	var HTML_CHARS_REGEXP = '';
	
	for (var i in HTML_CHARS) {
		HTML_CHARS_INVERSE[HTML_CHARS[i].toLowerCase()] = i;
		
		if (i != ' ') {
			// We escaping characters leave whitespace as is
			HTML_CHARS_REGEXP += '\\' + i;
		}
	}
	
	HTML_CHARS_REGEXP = new RegExp('[' + HTML_CHARS_REGEXP + ']', 'g');
	
	/**
	 * Escape HTML character for safe use in HTML
	 * Used in 'value' attribute setter / when setting value
	 */
	function escapeHtml (chr) {
		return HTML_CHARS[chr] || chr;
	}
	
	/**
	 * Unescape HTML character for string
	 * Used in 'value' and 'saveValue' attribute getter / when getting value
	 */
	function unescapeHtml (ent) {
		return HTML_CHARS_INVERSE[ent.toLowerCase()] || ent;
	}
	
	
	
	/**
	 * Inline text input widget
	 */
	function Input (config) {
		Input.superclass.constructor.apply(this, arguments);
		this.init.apply(this, arguments);
	}
	
	// Input is inline
	Input.IS_INLINE = true;
	
	// Input is inside form
	Input.IS_CONTAINED = false;
	
	Input.NAME = "input-text-inline";
	Input.CLASS_NAME = Input.CSS_PREFIX = 'su-' + Input.NAME;
	
	Y.extend(Input, Supra.Input.InlineString, {
		
		EDITOR_MODE: Supra.HTMLEditor.MODE_TEXT,
		
		_getValue: function (value) {
			if (this.htmleditor) {
				value = this.htmleditor.getHTML();
				
				value = value.replace(/\n/g, '');
				value = value.replace(/<br\s*\/?>/ig, '\n');
				value = value.replace(/<[^>]+>/g, '');
				value = value.replace(/&.*?;/g, unescapeHtml);
			}
			
			return value;
		},
		
		_getSaveValue: function (value) {
			if (this.htmleditor) {
				value = this.htmleditor.getProcessedHTML();
				
				// Remove all tags
				value = value.replace(/\n/g, '');
				value = value.replace(/<\/?br\s*>/ig, '\n');
				value = value.replace(/<[^>]+>/g, '');
				// Unescape characters
				value = value.replace(/&.*?;/g, unescapeHtml);
			}
			
			return value;
		},
		
		_setValue: function (value) {
			value = value || '';
			value = value.replace(HTML_CHARS_REGEXP, escapeHtml);
			value = value.replace(/\n/g, '<br />');
			if (this.htmleditor) {
				this.htmleditor.setHTML(value);
			}
			
			return value;
		}
		
	});
	
	Input.lipsum = function () {
		return Supra.Lipsum.sentence({'count': 4, 'variation': 1});
	};
	
	Supra.Input.InlineText = Input;
	
	//Since this widget has Supra namespace, it doesn't need to be bound to each YUI instance
	//Make sure this constructor function is called only once
	delete(this.fn); this.fn = function () {};
	
}, YUI.version, {requires:["supra.input-inline-string"]});
