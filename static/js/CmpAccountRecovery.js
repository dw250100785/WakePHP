$(function() {$.ongt(function() {

	$('.AccountRecoveryForm').each(function(i, form) {
		var $form = $(form);
		$form.ajaxFormController({
			success: function (result, statusText, xhr, $form) {
				if (result.success) {

					if (result.status != null) {
						if (result.status == 'sent') {
							$('.popupMsg', $form)
								.removeClass('denyMsg').addClass('allowMsg')
								.text(_('Check the mailbox ;-)'))
								.slideDown(300, function() {
									$(this).delay(2000).hide(1000, function() {
										$('.codeField', $form).slideDown(300);
									});
								});
						}
						else if (result.status == 'recovered') {
							$('button', $form).attr('disabled','1');
							$('.popupMsg', $form)
								.removeClass('denyMsg').addClass('allowMsg')
								.text(_('Access recovered. Use the password from the letter ;-)'))
								.slideDown(300, function() {$(this).delay(2000).hide(0, function() {
									var backUrl = $.urlParam('backurl');
									if (backUrl != null) {
										location.href = backUrl;
									} else {
										location.href = '/' +  $('html').attr('lang') + '/account/login';
									}
								});});
						}
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
		if (!$('.codeField', $form).is(':visible')) {
			$('input[name="code"]', $form).val('');
		}
		else if ($('input[name="code"]', $form).val() !== '') {
			$form.submit();
		}

	});
});});
