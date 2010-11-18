$.ongt(function() {
		
	$("body")
	.append($('<ul id="I18nContextMenu" class="contextMenu">').html('\
			<li class="edit"><a href="#edit" class="i18n">Edit translation</a></li> \
			<li class="quit separator"><a href="#quit" class="i18n">Quit</a></li> \
		</ul>').i18n());
		
	$(".i18n").contextMenu({

		menu: 'I18nContextMenu'
	},
	function(action, el, pos) {
		if (action == 'edit') {
		
		}
	});
});
