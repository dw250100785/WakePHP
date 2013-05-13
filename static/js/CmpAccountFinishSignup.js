$(function () {

	$('form.AccountFinishSignupForm').each(function (i, form) {
		var $form = $(form);
		$form.ajaxFormController({success: function (result, statusText, xhr, $form) {
			if (result.success)
			{
				var backUrl = $.urlParam('backurl');
				if (backUrl!=null)
				{
					location.href = backUrl;
				}
				else
				{
					location.href = '/'+$('html').attr('lang')+'/';
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
