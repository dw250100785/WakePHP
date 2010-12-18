$(function() {
	
	$('form.AccountProfileForm').each(function() {
		var $form = $(this);
		$form.find('input[name="location"]').autocomplete({source: ["Москва", "Находка", "Санкт-Петербург"]});
		$form.find('.buttonGeneratePassword').click(function() {
			$form.find('.containerGeneratedPassword').easypassgen({
				'syllables':        2,
				'numbers':          Math.round(Math.random()),
				'specialchars':     Math.round(Math.random())
			}).parent().show();
			return false;
		});
		
		$form.find('input[name="birthdate"]').datepicker({
				changeMonth: true,
				changeYear: true,
				yearRange: "-100:-5"
		});

		$form.find('.containerGeneratedPassword').click(function() {
			$form.find('input[name="password"]').val($(this).text());
		});
		$form.find('.additionalFieldsButton').click(function() {
			$form.find('.additionalFields').show();
			$(this).hide();
			return false;
		});
	}).ajaxFormController({
		beforeSubmit: function(arr, $form, options) {
			$.each(arr, function(index, el) {
				if ((el.name == 'password') && (el.value == '')) {
					el.value = $form.find('.generatePassword').text();
				}
			});
		},
		success: function (result, statusText, xhr, $form) {
			$form.find('.errorMessage').remove();
			if (result.success) {
				location.href = '/' + $('html').attr('lang') + '/account/confirm';
			} else {
				var hasCaptchaError = false;
				var captchaDiv = $('form.AccountProfileForm .CAPTCHA');
				
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
						$form.find('input[name="'+field+'"]').after('<div class="errorMessage errorMessage'+ucfirst(field)+'">'+_(result.errors[field])+'</div>');
					}			
				}
				if (hasCaptchaError) {
					captchaDiv.captcha();
				} else {
					//captchaDiv.parent().parent().hide();
					captchaDiv.captcha();
					// @TODO
				}
				$('.usernameAvailability').html('');
				$("form.AccountProfileForm input[name='username']").change();
			}
		}
	}).find('button[disabled]').removeAttr('disabled');
	
	
	$("form.AccountProfileForm input[name='username']").change(function() {
		if ($(this).val().length > 3) {
			$.queryController('Account/UsernameAvailablityCheck', function(data) {
				if (data.success) {
					$('form.AccountProfileForm .errorMessageUsername').remove();
					var div = $('.usernameAvailability');
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
