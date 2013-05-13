$(function () {

	$('form.AccountFinishSignupForm').each(function (i, form) {
		var $form = $(form);
		$form.ajaxFormController({success: function (result, statusText, xhr, $form) {
			if (result.success)
			{
				if (result.status!=null)
				{
					if (result.status=='sent')
					{
						$('.popupMsg', $form)
							.removeClass('denyMsg').addClass('allowMsg')
							.text(_('Verification request has been sent to your mailbox.'))
							.slideDown(300, function () {
								           $(this).delay(6000).hide(1000, function () {
									           $('.codeField', $form).slideDown(300);
								           });
							           });
					}
					else if (result.status=='verified')
					{
						$('button', $form).attr('disabled', '1');
						$('.popupMsg', $form)
							.removeClass('denyMsg').addClass('allowMsg')
							.text(_('Thank you, email has been verified.'))
							.slideDown(300).delay(3000).hide(0, function () {
								                                 var backUrl = $.urlParam('backurl');
								                                 if (backUrl!=null)
								                                 {
									                                 location.href = backUrl;
								                                 }
								                                 else
								                                 {
									                                 location.href = '/'+$('html').attr('lang')+'/';
								                                 }
							                                 });
					}
				}
			}
			else
			{
				$('.errorMessage', $form).remove();
				for (var field in result.errors)
				{
					$('input[name="'+field+'"]', $form).after('<div class="errorMessage">'+_(result.errors[field])+'</div>');
				}
			}
		}
		                         });
		$('button[disabled]', $form).removeAttr('disabled');
	});
});
