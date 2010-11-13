
$(function() {
	
	$('.placeholder').addClass('placeholderEditable');
	
	$.keyboard('ctrl',{strict: true, event: 'keydown'},function() {
	
		$('.placeholderEditable').addClass('placeholderEditableBordered');
		
	});
	$.keyboard('ctrl',{strict: true, event: 'keyup'},function() {
	
		$('.placeholderEditable').removeClass('placeholderEditableBordered');
		
	});
	
	var stack = new Array;
	var ev = function(o,type) {
		
				console.log(type);
		if (type == 'mouseenter') {
			stack.push(o.attr('id'));
		}
		else if (type == 'mouseleave') {
			var p = stack.indexOf(o.attr('id'));
			if (p != -1) {
				stack = stack.slice(0,p);
			}
		}		
		//if ($('.placeholderControls').size() > 0) {return;}
		
		$('div.placeholderControls').remove();
				
		var elSelector = '#'+stack[stack.length-1];
		var el = $(elSelector);
		if (el.size() == 0) {
			return;
		}
		var controls = $('<div class="placeholderControls">')
										.html('<img src="/images/modify.png">');
		el.prepend(controls);
		controls.position({
							my: "right top",
							at: "right top",
							of: elSelector,
							collision: 'flip flip'
		});
	};
	
	/*$( ".placeholderEditableBordered").draggable({
		helper: "ui-resizable-helper",
		stop: function(event, ui) {
		}
	});
	
	$( ".placeholderEditableBordered").droppable({
		tolerance: 'pointer',
		accept: ".placeholderEditable",
		drop: function(event, ui) {
			alert(o.id);
		}
	});*/
	$( ".placeholderControls").live("mouseenter mouseleave",function(event){
	
		$(this).parent().clearQueue();
		ev($(this).parent(),event.type);
	
	});
	$( ".placeholderEditableBordered").live("mouseenter mouseleave",function(event){
	
	 ev($(this),event.type);
	 
	});
	
	
});
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