$(document).ready(function(){ 

	//password filed and its span overlay behavior
	$('.password-field').click(function(){
		$(this).children('.password-dummy').css('display','none');
		$(this).children('input').focus();
	});

	$('.password-field .input-text').blur(function(){
		if(!this.value){
			$(this).parent('.password-field').children('.password-dummy').css('display','block');
		}
	});

	//product description hint behavior
	$(document).click(function(){
		$(".event-types .hint").fadeOut('fast');
	});
	
	$('.event-types .for').click(function(e){
		$('.event-types .hint').fadeOut();
		if($(this).parent().children('.hint').css('display') == 'none'){
			$(this).parent().children('.hint').fadeIn('fast');
		}
		e.stopPropagation();
	});

	//get text-page headers and create contents links
	$('.text-page h1, .text-page h2, .text-page h3, .text-page h4, .text-page h5, .text-page h6').each(function(index){
		if(!$(this).hasClass('sections-header') && !$(this).hasClass('h-main')){
			if(!$(this)[0].id || $(this)[0].id == ''){
				$(this).attr('id','h'+index);
			}
			var hID = $(this)[0].id;
			var htext = $(this).text();
			var htagname = $(this)[0].nodeName.toLowerCase();
			$('.sections').html($('.sections').html()+'<a class="block '+htagname+'" href="#'+hID+'">'+htext+'</a>');
		}
	});

	
	//fix sections
	if($('.sections').length != 0){
		sectionsElement = $('.sections');
		sectionsY = sectionsElement.offset().top;
		sectionsX = sectionsElement.offset().left;
		sectionsWidth = sectionsElement.width();
		
		$(window).bind(
			'scroll resize',
			function(){
				sectionsLeft = $('.text-page').width()/100*75 + $('.page-content').offset().left + parseFloat($('.text-page').css('marginLeft')) +1;
				if($(window).scrollTop() >= sectionsY){
					sectionsElement.css('position','fixed');
					sectionsElement.css('top',sectionsY-283);
					sectionsElement.css('width',sectionsWidth);
					sectionsElement.css('left',sectionsLeft);
				} else {
					sectionsElement.css('position','absolute');
					sectionsElement.css('top','12em');
					sectionsElement.css('width','23%');
					sectionsElement.css('left','75%');
				}

			}
		);
	}
	
	//idea input focus
	if($('.idea-input') && $('.idea-input .input-area').attr('value')!=''){
		$('.idea-input .invitation').css('display','none');
	}
	$('.idea-input').click(function(){
		$('.idea-input .input-area').focus();
	});
	$('.idea-input .idea-submit').click(function(e){
		e.stopPropagation();
	});
	$('.input-area').focus(function(){
		$('.idea-input .invitation').css('display','none');
	});
	$('.idea-input .input-area').blur(function(){
		if($(this).attr('value') == ''){
			$('.idea-input .invitation').show();
		}
	});
	
	//custom inputs
	$('.control .more').click(function(){
		$(this).parent().find('.less').removeClass('less-disabled');
		var input = $(this).parent().parent().find('.field');
		var appendText = '';
		if(input.parent().parent().find('.field-append')){
			appendText = ' ' + input.parent().parent().find('.field-append').text();
			input.attr('value', input.attr('value').replace(appendText,''));
		}
		var value = parseInt(input.attr('value'))+1;
		if(isNaN(value) || value <= 1 ){
			value = 1;
			$(this).parent().find('.less').addClass('less-disabled');
		}
		input.attr('value', value + appendText);
	});
	$('.control .less').click(function(){
		var input = $(this).parent().parent().find('.field');
		var appendText = '';
		if(input.parent().parent().find('.field-append')){
			appendText = ' ' + input.parent().parent().find('.field-append').text();
			input.attr('value', input.attr('value').replace(appendText,''));
		}
		var value = parseInt(input.attr('value'))-1;
		if(isNaN(value) || value <= 1){
			value = 1;
			$(this).addClass('less-disabled');
		}
		input.attr('value', value + appendText);
	});
	
});