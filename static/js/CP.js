
$(function() {
		
	$('.block').addClass('blockEditable');
	
	$.keyboard('ctrl',{strict: true, event: 'keydown'},function() {
	
		$('.blockEditable').addClass('blockEditableBordered');
		
	});
	$.keyboard('ctrl',{strict: true, event: 'keyup'},function() {
	
		$('.blockEditable').removeClass('blockEditableBordered');
		
	});	
	
	$("body").append('<ul id="myMenu" class="contextMenu"> \
			<li class="edit"><a href="#edit">Edit</a></li> \
			<li class="quit separator"><a href="#quit">Quit</a></li> \
		</ul>');

	$(".blockEditable").contextMenu({

		menu: 'myMenu'
	},
	function(action, el, pos) {
		if (action == 'edit') {

				$(el).tinymce({
			// Location of TinyMCE script
			script_url : '/js/tiny_mce/tiny_mce.js',

			// General options
			theme : "advanced",
			save_onsavecallback: function(ed)	{
				ed.setProgressState(1); // Show progress
				var blockId = ed.id;
				var template = ed.getBody().innerHTML;
				
				$.ajax({
					type: "POST",
					url: "/q/ACP/saveBlock",
					dataType: 'json',
					data: {
						"id": blockId,
						"template": template
					},
					success: function(data){
						var ed = $('#'+data.id).tinymce();
						ed.setProgressState(0); // Hide progress
					}
				});
				
				return true;
			},
			setup : function(ed) {
				ed.onInit.add(function(ed) {
          $(el).tinymce().setProgressState(1); // Show progress while the source is loading
				
					$.ajax({
						type: "POST",
						url: "/q/ACP/getBlockSource",
						data: {
							"id": $(el).attr('id')
						},
						dataType: "json",
						success: function(data){
							var ed = $('#'+data._id).tinymce();
							ed.setContent(data.template);
							ed.setProgressState(0); // Hide progress
						}
					});
				});
			},
			plugins : "safari,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",

			// Theme options
			theme_advanced_buttons1 : "save,newdocument,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,fontselect,fontsizeselect",
			theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,help,code,|,insertdate,inserttime,preview,|,forecolor,backcolor",
			theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen",
			theme_advanced_buttons4 : "insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,del,ins,attribs,|,visualchars,nonbreaking,template,pagebreak",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "left",
			theme_advanced_statusbar_location : "bottom",
			theme_advanced_resizing : true,

			// Example content CSS (should be your site CSS)
			content_css : "css/content.css",

			// Drop lists for link/image/media/template dialogs
			template_external_list_url : "lists/template_list.js",
			external_link_list_url : "lists/link_list.js",
			external_image_list_url : "lists/image_list.js",
			media_external_list_url : "lists/media_list.js",

			// Replace values for the template plugin
			template_replace_values : {
				username : "Some User",
				staffid : "991234"
			}
				});
		}

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
