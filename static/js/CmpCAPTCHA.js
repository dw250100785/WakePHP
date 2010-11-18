$(function() {

	$('.CAPTCHA').each(function() {
		$(this).attr('id','xxxxxxxx'.replace(/[xy]/g, function(c) {var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8); return v.toString(16);}).toUpperCase());
		var el = $(this);
		$.getScript('http://www.google.com/recaptcha/api/js/recaptcha_ajax.js', function(data, textStatus)
		{
			Recaptcha.create(AWconfig.CAPTCHApublickey,el.attr('id'),
			{
				theme: "red",
				callback: Recaptcha.focus_response_field
			});
		});
	});
});
