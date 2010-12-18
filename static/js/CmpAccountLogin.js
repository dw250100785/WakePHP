$(function() {

	$('.AccountLoginForm form').ajaxFormController({
		success: function (result, statusText, xhr, $form) {
			if (result.success) {
				if ((result.needConfirm != null) && result.needConfirm) {
					location.href = '/' +  $('html').attr('lang') + '/account/confirm';
					return;
				}
				var backUrl = $.urlParam('backUrl');
				if (backUrl != null) {
				location.href = backUrl;
			}
			else {
				location.reload();
			}
		} else {
			
			$form.find('.errorMessage').remove();
			for (var field in result.errors) {
				
				$form.find('input[name="'+field+'"]').after('<div class="errorMessage">'+_(result.errors[field])+'</div>');
			
			}
				
		}
	}});
	$('.AccountLoginForm form').find('button[disabled]').removeAttr('disabled');
		
});
