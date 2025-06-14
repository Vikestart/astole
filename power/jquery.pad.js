$('a:not([href="#"]):not([target="_blank"])').click(function() {
	$('#page_loading').fadeIn('fast');
});
$(window).on('load', function() {
	// Feedback contact form
	$(document).ready(function($) {
		$('#page_loading').fadeOut('fast');
		$('#feedbackform_show').click(function() {
			$("#topmenu > a").hide();
			$('#topmenu_back').show();
			$('#homelinks').hide();
			$('#resourceoverview').addClass('resourceoverview_hidden');
			$('#resourcemenu').addClass('resourcemenu_hidden');
			$('#feedbackform').show();
		});
		$('#changelog_show').click(function() {
			$("#topmenu > a").hide();
			$('#topmenu_back').show();
			$('#homelinks').hide();
			$('#resourceoverview').addClass('resourceoverview_hidden');
			$('#resourcemenu').addClass('resourcemenu_hidden');
			$('#changelog').show();
		});
		$('#topmenu_back').click(function() {
			$("#topmenu > a").show();
			$('#topmenu_back').hide();
			$('#feedbackform, #changelog').hide();
			$('#homelinks').show();
			$('#resourceoverview').removeClass('resourceoverview_hidden');
			$('#resourcemenu').removeClass('resourcemenu_hidden');
		});
		function validateEmail($email) {
			var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
			return emailReg.test( $email );
		}
		$('#feedbackform_submit').click(function() {
			var name = $('#feedbackform_name').val();
			var email = $('#feedbackform_email').val();
			var message = $('#feedbackform_message').val();
			$('#returnmessage').empty(); // To empty previous error/success message.
			// Checking for blank fields.
			if (name == '' || email == '' || message == '') {
				alert('Vennligst fyll ut hele skjemaet.');
			} else if (!validateEmail(email)) {
				alert('Vennligst oppgi en gyldig e-postadresse!');
			} else {
				// Returns successful data submission message when the entered information is stored in database.
				$.post('contact_form.php', {
					name1: name,
					email1: email,
					message1: message
				}, function(data) {
					$('#returnmessage').append('Din forespørsel har blitt mottatt. Du vil få tilbakemelding fortløpende.'); // Append returned message to message paragraph.
					if (data == 'OK') {
						$('#feedbackform')[0].reset(); // To reset form fields on success.
					}
					$('#feedbackform_description, #feedbackform_submit').hide();
					$('#feedbackform input, #feedbackform textarea').hide().attr('disabled','true');
				});
			}
		});
	});
});