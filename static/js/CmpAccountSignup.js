$(function() {
	
	$('form.AccountSignupForm').each(function() {
		var $form = $(this);
		$form.find('input[name="location"]').autocomplete({source: ["Москва", "Находка", "Санкт-Петербург"]});
		$form.find('.generatePassword').easypassgen({
		'syllables':        2,
		'numbers':          Math.round(Math.random()),
		'specialchars':     Math.round(Math.random())
		});
		$form.find('.additionalFieldsButton').click(function() {
			$form.find('.additionalFields').show();
			$(this).hide();
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
			$('form.AccountSignupForm .CAPTCHA').html(_('Testing not required.'));
			if (result.success) {
				var backUrl = $.urlParam('backUrl');
				if (backUrl != null) {
					location.href = backUrl;
				}
				else {
					location.href = '/';
				}
			} else {
				for (var field in result.errors) {
				
					if (field == 'captcha') {
						var captchaDiv = $('form.AccountSignupForm .CAPTCHA');
						if (captchaDiv.parent().parent().is(':visible')) {
							captchaDiv.after('<div class="errorMessage errorMessage'+ucfirst(field)+'">'+_(result.errors[field])+'</div>');
						}
						else {
							captchaDiv.parent().parent().show();
						}
					} else {
						$form.find('input[name="'+field+'"]').after('<div class="errorMessage errorMessage'+ucfirst(field)+'">'+_(result.errors[field])+'</div>');
					}			
				}
				$('form.AccountSignupForm .CAPTCHA').captcha();
				$('.usernameAvailability').html('');
				$("form.AccountSignupForm input[name='username']").change();
			}
		}
	}).find('button[disabled]').removeAttr('disabled');
	
	
	$("form.AccountSignupForm input[name='username']").change(function() {
		if ($(this).val().length > 3) {
			$.queryController('Account/UsernameAvailablityCheck', function(data) {
				if (data.success) {
					$('form.AccountSignupForm .errorMessageUsername').remove();
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
