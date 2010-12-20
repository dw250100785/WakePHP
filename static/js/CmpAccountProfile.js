$(function() {
	
	$('form.AccountProfileForm').each(function(i, form) {
		var $form = $(form);
		$('.buttonGeneratePassword', $form).click(function() {
			$('.containerGeneratedPassword', $form).easypassgen({
				'syllables':        2,
				'numbers':          Math.round(Math.random()),
				'specialchars':     Math.round(Math.random())
			}).parent().show();
			return false;
		});
		
		$('input[name="birthdate"]', $form).datepicker({
				changeMonth: true,
				changeYear: true,
				yearRange: "-100:-5"
		});

		$('.containerGeneratedPassword', $form).click(function() {
			$('input[name="password"]', $form).val($(this).text());
		});
		$('.additionalFieldsButton', $form).click(function() {
			$('.additionalFields', $form).show();
			$(this).hide();
			return false;
		});
		
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
					$('.popupMsg', $form).text(_('Thanks! We will remember ;-)'))
						.slideDown(300, function() {$(this).delay(2000).hide(1000);});
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

	});

});
