$(function() {
	
	$('form.AccountSignupForm').each(function(i,	form) {

		$form = $(form);
		$('.generatePassword', $form).easypassgen({
		'syllables':        2,
		'numbers':          Math.round(Math.random()),
		'specialchars':     Math.round(Math.random())
		});
		$('.additionalFieldsButton', $form).click(function() {
			$('.additionalFields', $form).show();
			$(this).hide();
			return false;
		});
		
		$('button[disabled]', $form).removeAttr('disabled');
	
	
		$("input[name='username']", $form).change(function() {
			if ($(this).val().length > 3) {
				$.queryController('Account/UsernameAvailablityCheck', function(data) {
					if (data.success) {
						$('.errorMessageUsername', $form).remove();
						var div = $('.usernameAvailability', $form);
						if (data.error == null) {
							div.html(_('Username available.'));
							div.removeClass('denyMsg');
							div.addClass('allowMsg');
						}
						else {
							div.html(_(data.error));
							div.removeClass('allowMsg');
							div.addClass('denyMsg');
						}
					}
				}, {username: $(this).val()}
				);
			}
		}).change();
		
		$form.ajaxFormController({
			beforeSubmit: function(arr, $form, options) {
				$.each(arr, function(index, el) {
					if ((el.name == 'password') && (el.value == '')) {
						el.value = $('.generatePassword', $form).text();
					}
				});
			},
			success: function (result, statusText, xhr, $form) {
				$('.errorMessage', $form).remove();
				if (result.success) {
					location.href = '/' + $('html').attr('lang') + '/account/confirm';
				} else {
					var hasCaptchaError = false;
					var captchaDiv = $('.CAPTCHA', $form);
					
					for (var field in result.errors) {
					
						if (field == 'captcha') {
							hasCaptchaError = true;
							if (captchaDiv.parent().parent().is(':visible')) {
								captchaDiv.after('<div class="errorMessage errorMessage'+ucfirst(field)+'">'+_(result.errors[field])+'</div>');
							}
							else {
								captchaDiv.parent().parent().show();
								captchaDiv.captcha();
							}
						} else {
							$('input[name="'+field+'"]', $form).after('<div class="errorMessage errorMessage'+ucfirst(field)+'">'+_(result.errors[field])+'</div>');
						}			
					}
					if (hasCaptchaError) {
						captchaDiv.captcha();
					} else {
						//captchaDiv.parent().parent().hide();
						captchaDiv.captcha();
						// @TODO
					}
					$('.usernameAvailability', $form).html('');
					$("input[name='username']", $form).change();
				}
			}
		});
	
	});
});
