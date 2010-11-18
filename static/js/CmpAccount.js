$(function() {

	$('.AccountLoginForm form').ajaxFormController({
		success: function (result, statusText, xhr, $form) {
			if (result.success) {
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
				
				$form.find('input[name="'+field+'"]').after('<div class="errorMessage">'+_(result.errors[field])+'</div>');;
			
			}
				
		}
	}});
	$('.AccountLoginForm form').find('button[disabled]').removeAttr('disabled');
	
	$('.logoutButton').click(function() {
		$.queryController('Account/Logout',function (result, statusText, xhr, $form) {
			if (result.success) {
				location.reload();
			}
		});
	});

});
