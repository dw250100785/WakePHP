lang = $('html').attr('lang'),
$(function() {
	$.ongt(function() {
		var delim = ' - ';
		var title = document.title.split(delim);
		$.each(title, function(k, phrase) {
			title[k] = _(phrase)
		});
		document.title = title.join(delim);
	});
	
	if (jQuery.fn.capslock != null) {
		$(document).capslock({
			caps_lock_on: function() {$('.capslock').show();},
			caps_lock_off: function() {$('.capslock').hide();}
		});
	}
	$('input[name="location"]').autocomplete2Array(["Москва", "Находка", "Санкт-Петербург"],{
		delay:10,
		minChars:1,
		matchSubset:1,
		autoFill:true,
		maxItemsToShow:10
	}).bind('focus.clear', function() {
		$(this).val('');
		$(this).unbind('focus.clear');		
	});

	
/* logout */
$('.logoutButton').click(function() {
	$.queryController('Account/Logout',function (result, statusText, xhr, $form) {
		if (result.success) {
			location.reload();
		}
	});
});
	

});
/* core */
jQuery.fn.ajaxFormController = function(options) {
	if (!this.length) {
		return this;
	}
	return this.each(function() {
		var resultOptions = $.queryOptions($(this).attr('action'),null);
		delete resultOptions.contentType;
		delete resultOptions.data;
		for (var k in options) {
			resultOptions[k] = options[k];
		}
		$(this).ajaxForm(resultOptions);
	});
	return this;
};
jQuery.queryController = function(method, success, data, dataType) {
	$.ajax($.queryOptions(method, success, data, dataType));
};
$.queryOptions = function(method, success, data, dataType) {
	if (dataType == null) {
		dataType = 'json';
	}
	if (data == null) {data = {};}

	var options = {
		type: "POST",
		url: "/component/"+method+"/"+dataType,
		dataType: dataType,
		success: success
	};
	if (typeof data === 'array' || typeof data[0] != 'undefined') {
		data.push({'name': 'LC', 'value': lang});
		options.data = data;
	} else if (typeof data === 'object') {
		options.contentType = 'application/json';
		data.LC = lang;
		options.data = $.toJSON(data);
	}
	return options;
};

jQuery.urlParam = function(name){
	var results = new RegExp('[\\?&]' + name + '=([^&#]*)').exec(window.location.href);
	if (!results) { return null; }
	return jQuery.urldecode(results[1]) || null;
};

jQuery.xmlescape = function (string) {
	return $('<span>').text(string).html();
};

if (typeof WebSocket != 'undefined') {WebSocket.__swfLocation = "/files/websocket/WebSocketMain.swf";}


jQuery.urlencode = function(s) {
	if (typeof encodeURIComponent != 'undefined') {
		return encodeURIComponent(s).replace(new RegExp('\\+','g'), '%20');
	}
	return escape(s).replace(new RegExp('\\+','g'), '%20');
};
jQuery.urldecode = function(s) {
	return unescape(s).replace(new RegExp('\\+','g'),' ');
};
jQuery.fsize = function(x) {
	if (x >= 1024*1024*1024) {
		return (Math.floor(x/1024/1024/1024*100)/100)+' Gb';
	}
	if (x >= 1024*1024) {
		return (Math.floor(x/1024/1024*100)/100)+' Mb';
	}
	if (x >= 1024) {
		return (Math.floor(x/1024*100)/100)+' Kb';
	}
	return (x)+' B';
}

function ucfirst (str) {
    // Makes a string's first character uppercase  
    // 
    // version: 1009.2513
    // discuss at: http://phpjs.org/functions/ucfirst
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // +   improved by: Brett Zamir (http://brett-zamir.me)
    // *     example 1: ucfirst('kevin van zonneveld');
    // *     returns 1: 'Kevin van zonneveld'
    str += '';
    var f = str.charAt(0).toUpperCase();
    return f + str.substr(1);
}

/**
 * Function : dump()
 * Arguments: The data - array,hash(associative array),object
 *    The level - OPTIONAL
 * Returns  : The textual representation of the array.
 * This function was inspired by the print_r function of PHP.
 * This will accept some data as the argument and return a
 * text that will be a more readable version of the
 * array/hash/object that is given.
 * Docs: http://www.openjs.com/scripts/others/dump_function_php_print_r.php
 */
function dump(arr,level) {
	var dumped_text = "";
	if(!level) level = 0;

	//The padding given at the beginning of the line.
	var level_padding = "";
	for(var j=0;j<level+1;j++) level_padding += "    ";

	if(typeof(arr) == 'object') { //Array/Hashes/Objects 
		for(var item in arr) {
			var value = arr[item];

			if(typeof(value) == 'object') { //If it is an array,
				//dumped_text += level_padding + "'" + item + "' ...\n";
				//dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else { //Stings/Chars/Numbers etc.
		dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
	}
	return dumped_text;
}