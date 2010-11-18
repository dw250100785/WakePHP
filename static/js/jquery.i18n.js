/**
 * i18n for jQuery
 * 
 * Copyright (c) 2010 kakserpom <kak.serpom.po.yaitsam@gmail.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details. 
 *
 * @license http://www.gnu.org/licenses/gpl.html 
 * @project jquery.i18n
 
 */
;(function($) {
$.fn.i18n = function() {
	$(this).find('.i18n').each(function(index) {
		var clone = $(this).clone();
		var argValues = [];
		clone.find('.i18nArg').each(function() {
			argValues.push($(this).outerHTML());
			$(this).replaceWith('%s');
		});
		$(this).html($.vsprintf(_(clone.html()),argValues));
	});
	return this;
}
$.ongt = function(cb) {$('body').bind('onreadygettext',cb);};
$.fn.outerHTML = function() {return $('<div></div>').append( this.clone() ).html();}
$(function() {$.ongt(function(e) {$(this).i18n();});});
})(jQuery);
