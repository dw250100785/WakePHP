$(function() {
	
	$('.placeholder').addClass('placeholderEditable');
	
	$.keyboard('ctrl',{strict: true, event: 'keydown'},function() {
	
		$('.placeholderEditable').addClass('placeholderEditableBordered');
		
	});
	$.keyboard('ctrl',{strict: true, event: 'keyup'},function() {
	
		$('.placeholderEditable').removeClass('placeholderEditableBordered');
		
	});
	
	
	$( ".placeholderEditable").draggable({
		helper: "ui-resizable-helper",
		stop: function(event, ui) {
		}
	});
	
	$( ".placeholderEditable").droppable({
		tolerance: 'pointer',
		accept: ".placeholderEditable",
		drop: function(event, ui) {
			alert($(this).id);
		}
	});
	
	
});
