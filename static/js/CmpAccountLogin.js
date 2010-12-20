$(function() {

	$('.AccountLoginForm form').each(function(i, form) {
		var $form = $(form);
		$form.ajaxFormController({
			success: function (result, statusText, xhr, $form) {
				if (result.success) {
					if ((result.needConfirm != null) && result.needConfirm) {
						location.href = '/' +  $('html').attr('lang') + '/account/confirm';
						return;
					}
					var backUrl = $.urlParam('backurl');
					if (backUrl != null) {
						location.href = backUrl;
					} else {
						location.href = '/' +  $('html').attr('lang') + '/';
					}
				}	else {
					$('.errorMessage', $form).remove();
					for (var field in result.errors) {
						$('input[name="'+field+'"]', $form).after('<div class="errorMessage">'+_(result.errors[field])+'</div>');
					}
				}
			}
		});
		$('button[disabled]', $form).removeAttr('disabled');
		$('.forgotPasswordButton', $form).click(function() {
			var $input = $('input[name="username"]', $form);
			var backUrl = $.urlParam('backurl');
			var qs = [];
			if (($input.size() > 0) && ($input.val() !== '')) {
				qs.push('email=' + $.urlencode($input.val()));
			}
			if (backUrl != null) {
				qs.push('backurl=' + $.urlencode(backUrl));
			}
			if (qs.length  > 0) {
				location.href = $(this).attr('href') + '?'+qs.join('&');
				return false;
			}
		});
	});
});
