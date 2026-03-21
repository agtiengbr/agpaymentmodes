$(function(){
	var message_saved = false;

	function saveMessage(textarea, message)
	{
		$.ajax({
			url: agpaymentmodes_url_save_message,
			type: 'POST',
			dataType: 'JSON',
			data: {
				message: message
			},
			success: function(data){
				$(textarea).parent().find('.btn-primary').removeAttr('disabled');

				if (typeof data.success !== 'undefined' && data.success) {
					message_saved = true;

					$(textarea).parent().find('.btn-primary span').addClass('hidden');
					$(textarea).parent().find('.saved').removeClass('hidden');
				} else {
					$(textarea).parent().find('.error').removeClass('hidden');
					$(textarea).parent().find('.text-error-unexpected').removeClass('hidden');

					$(textarea).parent().find('.btn-primary span').addClass('hidden');
					$(textarea).parent().find('.save').removeClass('hidden');
				}
			},
			error: function(){
				$(textarea).parent().find('.btn-primary').removeAttr('disabled');

				$(textarea).parent().find('.error').removeClass('hidden');
				$(textarea).parent().find('.text-error-unexpected').removeClass('hidden');

				$(textarea).parent().find('.btn-primary span').addClass('hidden');
				$(textarea).parent().find('.save').removeClass('hidden');
			}
		})
	}

	$('.agpaymentmodes-message-save').click(function(){
		var id = $('[name=payment-option]:checked').attr('id');
		var textarea = $('#' + id + '-additional-information .agpaymentmodes textarea');
		var val = $(textarea).val();

		if (textarea.length) {
			if (val) {
				$(this).attr('disabled', 'disabled');

				$(textarea).parent().find('.error').addClass('hidden');
				$(textarea).parent().find('.error .text-error').addClass('hidden');

				$(textarea).parent().find('.btn-primary span').addClass('hidden');
				$(textarea).parent().find('.saving').removeClass('hidden');

				saveMessage(textarea, val);
			} else {
				$(textarea).parent().find('.error').removeClass('hidden');
				$(textarea).parent().find('.text-error-required').removeClass('hidden');

			}
		}		
	});

	$('#payment-confirmation button').click(function(){
		var id = $('[name=payment-option]:checked').attr('id');
		var textarea = $('#' + id + '-additional-information .agpaymentmodes textarea');
		var val = $(textarea).val();		

		if (textarea.length) {
			if (!message_saved) {
				$(textarea).parent().find('.error').removeClass('hidden');
				$(textarea).parent().find('.text-error-required').removeClass('hidden');
				return false;
			}
		}

		return true;
	});
});