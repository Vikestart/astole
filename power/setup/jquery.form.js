$(window).on('load', function() {
	// Loading mechanisms
	$(document).ready(function($){
		$('#form_loading').fadeOut('fast', function() {
			$('#form').fadeIn('fast', function() {
				$('#form .input[data-autofocus]:visible:first').focus();
				$('#form_button_next').removeClass('form_button_hidden');
			});
		});
	});
	// Forms dropdown
	$(document).on('click', '#main', function(e) {
		if (!$(e.target).is('#topresources > div, #topresources > div *')) {
			$('#topresources > div').removeClass('otherforms_open');
			$('#topresources > div > div').hide();
			$('#articlecode').text('');
			$('#articleprice').text('');
			$('#articleselect').val('0');
		}
	});
	$('#header_inner').on('click', '#topresources > div', function(e) {
		if (e.target === this) {
			$('#topresources > div').removeClass('otherforms_open');
			$('#topresources > div > div').hide();
			$(this).toggleClass('otherforms_open').find('div').toggle();
		}
	});
	// Article selection
	$('#topresources').on('change', '#articleselect', function() {
		var articlecode = $(this).val();
		var articleprice = $(this).find('option:selected').attr('data-price');
		$('#articlecode').text(articlecode);
		$('#articleprice').text(articleprice);
	});
	// Blur select field on change
	$('#form').on('change', 'select', function() {
		$(this).next().focus();
	});
	// Content editable field max length
	$('span[contenteditable="true"][data-maxlength], div[contenteditable="true"][data-maxlength]').on('keydown', function (event) {
		 var cntMaxLength = parseInt($(this).attr('data-maxlength'));
		 if ($(this).text().length >= cntMaxLength && event.keyCode != 8 && event.keyCode != 46) {
			 event.preventDefault();
		 }
	});
	function focusAndPlaceCaretAtEnd(el) {
		el.focus();
		if (typeof window.getSelection != "undefined"
				&& typeof document.createRange != "undefined") {
			var range = document.createRange();
			range.selectNodeContents(el);
			range.collapse(false);
			var sel = window.getSelection();
			sel.removeAllRanges();
			sel.addRange(range);
		} else if (typeof document.body.createTextRange != "undefined") {
			var textRange = document.body.createTextRange();
			textRange.moveToElementText(el);
			textRange.collapse(false);
			textRange.select();
		}
	}
	$('span[contenteditable="true"][data-maxlength], div[contenteditable="true"][data-maxlength]').on('paste', function (event) {
		var cntMaxLength = parseInt($(this).attr('data-maxlength'));
		var cntClipboard = event.originalEvent.clipboardData.getData('Text');
		if ( ($(this).text().length + cntClipboard.length) > cntMaxLength && event.keyCode != 8 && event.keyCode != 46) {
			event.preventDefault();
		}
		var pastedElement = $(this);
		var pastedElementDOM = $(this)[0];
		setTimeout(function() {
			pastedElement.find('*').contents().unwrap();
			focusAndPlaceCaretAtEnd(pastedElementDOM);
		}, 0);
		validateform();
	});
	// Content editable field max newlines
	$('div[contenteditable="true"]').on('keydown', function (event) {
		 var divheight = $(this).outerHeight();
		 var charCode = (event.which) ? event.which : event.keyCode;
		 if ( (charCode == 13) && (divheight >= 94) ) {
			 event.preventDefault();
		 }
	});
	// Integer fields
	$('span[contenteditable="true"][data-type="number"], div[contenteditable="true"][data-type="number"]').on('keydown', function (event) {
		var charCode = (event.which) ? event.which : event.keyCode;
		if ( ((charCode <= 47 || charCode >= 58) && (charCode <= 95 || charCode >= 106) && charCode != 8 && charCode != 46) && (charCode != 17 && charCode != 86) ) {
			event.preventDefault();
		}
	});
	// Validate and toggle the NEXT/Print button
	function isValidEmailAddress(emailAddress) {
		var pattern = /^\b[A-Z0-9._-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b$/i;
		return pattern.test(emailAddress);
	};
	$('#form').on('click change', 'input[type="checkbox"], input[type="radio"], select', function() {
		if ( (!$(this).val() == '') && ($(this).val() != 0) )  {
			$(this).addClass('approved');
		} else {
			$(this).removeClass('approved');
		}
		validateform();
	});
	$('#form').on('keyup paste', '.input', function(event) {
		$(this).find('br').remove();
		var charCode = (event.which) ? event.which : event.keyCode;
		if ($(this).attr('data-name') == 'elguide') {
			var elguide_int = $(this).text();
			var elguide_5 = String(elguide_int).charAt(4);
			var elguide_int_5 = Number(elguide_5);
		}
		var minlength = 3;
		if ($(this).attr('data-minlength')) {
			minlength = $(this).attr('data-minlength');
		}
		if ( ( (!$(this).text() == '') && ($(this).text().length >= minlength) ) && ( ($(this).attr('data-type') != 'fullname') || ($(this).text().match(/^(([a-zæøåéèü']{2,20})(\s|-))+([a-zæøåéèü']{2,20})$/i)) ) && ( ($(this).attr('data-type') != 'firstname') || ($(this).text().match(/^(([a-zæøåéèü']{2,20})(\s|-))*([a-zæøåéèü']{2,20})$/i)) ) && ( ($(this).attr('data-name') != 'phone') || ( ($.isNumeric($(this).text())) && ($(this).text().length == 8) && (!$(this).text().match(/(.)(.*\1){6}/i)) ) ) && ( ($(this).attr('data-name') != 'elguide') || ( ($(this).text().length >= 11) && (!$(this).text().match(/(.)(.*\1){6}/i)) && ($(this).text().match(/^[0-9]{4}.+$/i)) && ( ($(this).text().match('^11')) || ($(this).text().match('^13')) || ($(this).text().match('^14')) || ($(this).text().match('^15')) || ($(this).text().match('^16')) || ($(this).text().match('^20')) ) ) ) && ( ($(this).attr('data-type') != 'email') || (isValidEmailAddress($(this).text())) ) )  {
			$(this).addClass('approved');
		} else {
			$(this).removeClass('approved');
		}
		if ( (!$(this).hasClass('input_required')) && ($(this).text().length > 0) && ($(this).attr('data-type') == 'email') && (!isValidEmailAddress($(this).text())) ) {
			$(this).addClass('incomplete');
		} else if ( ($(this).hasClass('approved')) || ($(this).text().length == 0) ) {
			$(this).removeClass('incomplete');
		}
		var minlength = $(this).attr('data-minlength');
		if ( (!$(this).hasClass('input_required')) && ($(this).text().length > 0) && ($(this).attr('data-minlength')) && ($(this).text().length < minlength) ) {
			$(this).addClass('incomplete');
		} else if ( (!$(this).hasClass('input_required')) && ($(this).text().length > 0) && ($(this).text().length < 3) ) {
			$(this).addClass('incomplete');
		} else if ( ($(this).hasClass('approved')) || ($(this).text().length == 0) ) {
			$(this).removeClass('incomplete');
		}
		/*if (charCode == 8) {
			var pastedElement = $(this);
			var pastedElementDOM = $(this)[0];
			setTimeout(function() {
				pastedElement.find('*').contents().unwrap();
				focusAndPlaceCaretAtEnd(pastedElementDOM);
			}, 0);
		}*/
		validateform();
	});
	function validateform() {
		var visiblefancyselects = $('ul.fancyselect:not(#formselection):visible').length;
		if ( ($('.input.input_required:visible').filter(':not(.approved)').length > 0) || ($('select[required]:visible option:selected[value="0"]').length > 0) || ( ($('#form_basics').is(':visible')) && ($('input[type="radio"][name="device"]:checked').length < 1) ) || ( ($('#form_basics').is(':visible')) && ($('input[type="radio"][name="os"]:checked').length < 1) ) || ($('label.label_required:visible').filter(':not(.checked)').length > 0) ) {
			$('#navbtn_proceed:not(.navbtn_disabled):not(.navbtn_lockdown)').removeClass('navbtn_shifting').addClass('navbtn_disabled');
			$('#navbtn_proceed:not(.navbtn_lockdown) .navbtn_next, #navbtn_proceed:not(.navbtn_lockdown) .navbtn_unlocking').hide();
			$('#navbtn_proceed:not(.navbtn_lockdown) .navbtn_locked').show();
			$('#form_printbutton').hide();
			$('#form_requirednotice').show();
			$('#content').addClass('blockprint');
		} else if ($('.input:visible').filter('.incomplete').length > 0) {
			$('#navbtn_proceed:not(.navbtn_disabled):not(.navbtn_lockdown)').removeClass('navbtn_shifting').addClass('navbtn_disabled');
			$('#navbtn_proceed:not(.navbtn_lockdown) .navbtn_next, #navbtn_proceed:not(.navbtn_lockdown) .navbtn_unlocking').hide();
			$('#navbtn_proceed:not(.navbtn_lockdown) .navbtn_locked').show();
			$('#form_printbutton').hide();
			$('#form_requirednotice').show();
			$('#content').addClass('blockprint');
		} else {
			$('#navbtn_proceed.navbtn_disabled').removeClass('navbtn_disabled').addClass('navbtn_shifting');
			$('#navbtn_proceed.navbtn_shifting:not(.navbtn_lockdown) .navbtn_locked').hide();
			$('#navbtn_proceed.navbtn_shifting:not(.navbtn_lockdown) .navbtn_unlocking').show();
			setTimeout(function() {
				$('#navbtn_proceed.navbtn_shifting:not(.navbtn_lockdown) .navbtn_unlocking').hide();
				$('#navbtn_proceed.navbtn_shifting:not(.navbtn_lockdown) .navbtn_next').show();
				$('#navbtn_proceed.navbtn_shifting').removeClass('navbtn_shifting');
			}, 500);
			/*$('#form_button_next:not(.form_button_print)').removeClass('form_button_disabled').removeClass('form_button_incomplete').text('Neste steg');
			$('#form_button_next.form_button_print').removeClass('form_button_disabled').removeClass('form_button_incomplete').text('Skriv ut');*/
			$('#form_requirednotice').hide();
			$('#form_printbutton').show();
			$('#content').removeClass('blockprint');
		}
	}
	// Adjust the behaviour of the TAB key
	$(function() {

		// gather all inputs of selected types
		var inputs = $('.input, input, textarea, select, button, ul.fancyselect li, ul.fancychecklist li'), inputTo;

		// refocus autofocus element when pressing TAB if no elements have focus
		$(document).on('keydown', function(e) {
			if ( $('.input:focus, input:focus, textarea:focus, select:focus, button:focus, ul.fancyselect li:focus, ul.fancychecklist li:focus, h1[tabindex]:focus').length == 0 ) {
				if (e.keyCode == 9 || e.which == 9) {
					e.preventDefault();
					$('.input[data-autofocus]').focus();
				}
			}
		});
		if ( $('input:focus').length > 0 ) {  return; }

		// bind on keydown
		inputs.on('keydown click', function(e) {

			inputs = $('.input, input, textarea, select, button, ul.fancyselect li, ul.fancychecklist li').filter(':not(.disabledoption):visible'), inputTo;

			// if we pressed the tab
			if (e.keyCode == 9 || e.which == 9) {
				// prevent default tab action
				e.preventDefault();

				//if popup window is not visible
				if ( (!$('.overlaywindow:visible').length) && (!$('#preloadscreen_login').is(':visible')) && (!$('#authenticationscreen').is(':visible')) && (!$('#preloadscreen_home').is(':visible')) ) {

					if (e.shiftKey) {
						// get previous input based on the current input
						inputTo = inputs.get(inputs.index(this) - 1);
					} else {
						// get next input based on the current input
						inputTo = inputs.get(inputs.index(this) + 1);
					}

					// move focus to inputTo, otherwise focus first input
					if (inputTo) {
						inputTo.focus();
					} else {
						inputs[0].focus();
					}

				}

			}
		});

	});
	// Prevent ENTER key from creating new line, press next button instead
	$('.input').keypress(function(event){
		if (event.keyCode === 10 || event.keyCode === 13) {
			event.preventDefault();
			$('#navbtn_proceed:not(.navbtn_disabled):not(.navbtn_shifting)').trigger('click');
		}
	});
	// Conditional siblings 1
	$('select[data-togglemain]').change(function() {
		if ( ($(this).val() != 0) && ($(this).val() != 'new') && ($(this).val() != 'new_mail') && ($(this).val() != 'later') ) {
			$(this).siblings('.input[data-toggledmain], input[data-toggledmain], select[data-toggledmain]').removeClass('input_hidden input_handwrite');
			$(this).siblings('.input[data-type="password"]').text('').removeClass('approved input_placeholder2').addClass('input_required');
		} else if ( ($(this).val() == 'new') || ($(this).val() == 'new_mail') ) {
			$(this).siblings('.input[data-type="email"]').addClass('input_hidden input_handwrite').text('').removeClass('approved');
			$(this).siblings('.input[data-type="password"]').text('').addClass('input_placeholder2').removeClass('approved input_hidden input_required');
		} else {
			$(this).siblings('.input[data-toggledmain], .input[data-toggledaccount], .input[data-toggledaccount_new]').addClass('input_hidden').removeClass('approved');
			$(this).siblings('select[data-toggledmain], select[data-toggledaccount], select[data-toggledaccount_new]').addClass('input_hidden').removeClass('approved');
			$(this).siblings('.input[data-toggledmain], .input[data-toggledaccount], .input[data-toggledaccount_new]').text('');
			$(this).siblings('select[data-toggledmain], select[data-toggledaccount], select[data-toggledaccount_new]').val(0);
			$(this).siblings('.input.input_handwrite').removeClass('input_handwrite');
			$(this).siblings('.input[data-type="password"]').text('').removeClass('approved input_placeholder2').addClass('input_required');
		}
	});
	// Conditional siblings Existing/New Account
	$('select[data-toggleaccount]').change(function() {
		if ($(this).val() == 'existing') {
			$(this).siblings('.input[data-toggledaccount], select[data-toggledaccount]').removeClass('input_hidden');
		} else {
			$(this).siblings('.input[data-toggledaccount], select[data-toggledaccount]').addClass('input_hidden').removeClass('approved');
			$(this).siblings('.input[data-toggledaccount]').text('');
			$(this).siblings('select[data-toggledaccount]').val(0);
		}
		if ( ($(this).val() == 'new') || ($(this).val() == 'new_mail') ) {
			$(this).siblings('.input[data-toggledaccount_new], select[data-toggledaccount_new]').removeClass('input_hidden');
		} else {
			$(this).siblings('.input[data-toggledaccount_new], select[data-toggledaccount_new]').addClass('input_hidden').removeClass('approved');
		}
		if ( ($(this).val() == 0) || ($(this).val() == 'new') || ($(this).val() == 'new_mail') || ($(this).val() == 'later') ) {
			$(this).siblings('.input[data-toggledaccount_new]').text('');
			$(this).siblings('select[data-toggledaccount_new]').val(0);
		}
	});
	// Radio and check boxes effects
	$('input[type="radio"]').change(function() {
		$(this).parent('label').siblings('label.label_required').removeClass('label_required');
		$(this).parent('label').siblings('label.checked').removeClass('checked');
		$(this).parent('label').toggleClass('checked');
	});
	$('input[type="checkbox"]').change(function() {
		$(this).parent('label').toggleClass('checked');
	});
	// Show operating system radio buttons
	$('input[type="radio"][name="device"]').change(function() {
		$('input[type="radio"][name="os"]').parent('label').removeClass('checked').addClass('label_required').hide();
		$('input[type="radio"][name="os"]:checked').prop('checked', false);
		$('#operatingsystem').removeClass('fieldset_disabled');
		$('#operatingsystem #operatingsystem_placeholder').fadeOut(300);
		$('#devicetype label:not(.checked)').addClass('label_disabled');
		if ($('input[name="device"][value="Datamaskin"]').is(':checked')) {
			$('#operatingsystem.os_onerow').removeClass('os_onerow');
			$('input[type="radio"][name="os"][value="Acer"]').parent('label').fadeIn(400);
			$('input[type="radio"][name="os"][value="Apple"]').parent('label').delay(80).fadeIn(400);
			$('input[type="radio"][name="os"][value="ASUS"]').parent('label').delay(160).fadeIn(400);
			$('input[type="radio"][name="os"][value="Cepter"]').parent('label').delay(240).fadeIn(400);
			$('input[type="radio"][name="os"][value="Dell"]').parent('label').delay(320).fadeIn(400);
			$('input[type="radio"][name="os"][value="HP"]').parent('label').delay(400).fadeIn(400);
			$('input[type="radio"][name="os"][value="Huawei"]').parent('label').delay(480).fadeIn(400);
			$('input[type="radio"][name="os"][value="Lenovo"]').parent('label').delay(560).fadeIn(400);
			$('input[type="radio"][name="os"][value="Medion"]').parent('label').delay(640).fadeIn(400);
			$('input[type="radio"][name="os"][value="Microsoft"]').parent('label').delay(820).fadeIn(400);
			$('input[type="radio"][name="os"][value="MSI"]').parent('label').delay(900).fadeIn(400);
			$('input[type="radio"][name="os"][value="Toshiba"]').parent('label').delay(980).fadeIn(400);
			setTimeout(function() { $('#operatingsystem').addClass('os_enlarged') }, 1180);
			setTimeout(function() { $('#operatingsystem').removeClass('os_enlarged'); $('#devicetype label.label_disabled').removeClass('label_disabled') }, 1430);
		} else if ($('input[name="device"][value="Smarttelefon"]').is(':checked')) {
			$('#operatingsystem.os_onerow').removeClass('os_onerow');
			$('input[type="radio"][name="os"][value="Alcatel"]').parent('label').fadeIn(400);
			$('input[type="radio"][name="os"][value="Apple"]').parent('label').delay(80).fadeIn(400);
			$('input[type="radio"][name="os"][value="ASUS"]').parent('label').delay(160).fadeIn(400);
			$('input[type="radio"][name="os"][value="CAT"]').parent('label').delay(240).fadeIn(400);
			$('input[type="radio"][name="os"][value="Doro"]').parent('label').delay(320).fadeIn(400);
			$('input[type="radio"][name="os"][value="Honor"]').parent('label').delay(400).fadeIn(400);
			$('input[type="radio"][name="os"][value="HTC"]').parent('label').delay(480).fadeIn(400);
			$('input[type="radio"][name="os"][value="Huawei"]').parent('label').delay(560).fadeIn(400);
			$('input[type="radio"][name="os"][value="LG"]').parent('label').delay(640).fadeIn(400);
			$('input[type="radio"][name="os"][value="Motorola"]').parent('label').delay(720).fadeIn(400);
			$('input[type="radio"][name="os"][value="Nokia"]').parent('label').delay(800).fadeIn(400);
			$('input[type="radio"][name="os"][value="Samsung"]').parent('label').delay(880).fadeIn(400);
			$('input[type="radio"][name="os"][value="Sony"]').parent('label').delay(960).fadeIn(400);
			$('input[type="radio"][name="os"][value="ZTE"]').parent('label').delay(1040).fadeIn(400);
			setTimeout(function() { $('#operatingsystem').addClass('os_enlarged') }, 1240);
			setTimeout(function() { $('#operatingsystem').removeClass('os_enlarged'); $('#devicetype label.label_disabled').removeClass('label_disabled') }, 1490);
		} else if ($('input[name="device"][value="Knappetelefon"]').is(':checked')) {
			$('#operatingsystem:not(.os_onerow)').addClass('os_onerow');
			$('input[type="radio"][name="os"][value="Alcatel"]').parent('label').fadeIn(400);
			$('input[type="radio"][name="os"][value="Cat"]').parent('label').delay(80).fadeIn(400);
			$('input[type="radio"][name="os"][value="Doro"]').parent('label').delay(160).fadeIn(400);
			$('input[type="radio"][name="os"][value="Nokia"]').parent('label').delay(240).fadeIn(400);
			setTimeout(function() { $('#operatingsystem').addClass('os_enlarged') }, 440);
			setTimeout(function() { $('#operatingsystem').removeClass('os_enlarged'); $('#devicetype label.label_disabled').removeClass('label_disabled') }, 690);
		} if ($('input[name="device"][value="Nettbrett"]').is(':checked')) {
			$('#operatingsystem:not(.os_onerow)').addClass('os_onerow');
			$('input[type="radio"][name="os"][value="Apple"]').parent('label').fadeIn(400);
			$('input[type="radio"][name="os"][value="ASUS"]').parent('label').delay(80).fadeIn(400);
			$('input[type="radio"][name="os"][value="Huawei"]').parent('label').delay(160).fadeIn(400);
			$('input[type="radio"][name="os"][value="Kurio"]').parent('label').delay(240).fadeIn(400);
			$('input[type="radio"][name="os"][value="Samsung"]').parent('label').delay(360).fadeIn(400);
			setTimeout(function() { $('#operatingsystem').addClass('os_enlarged') }, 560);
			setTimeout(function() { $('#operatingsystem').removeClass('os_enlarged'); $('#devicetype label.label_disabled').removeClass('label_disabled') }, 810);
		}
		$('#timelimit').addClass('fieldset_faded');
		$('input[type="radio"][name="time"]').parent('label').removeClass('checked').addClass('label_required').hide();
		$('input[type="radio"][name="time"]:checked').prop('checked', false);
		$('#timelimit .fieldset_faded_placeholder').show();
	});
	// Proceed to BASICS step
	$('#navbtn_bar').on('click', '#navbtn_proceed.navbtn_employee:not(.navbtn_shifting):not(.navbtn_disabled):not(.navbtn_lockdown)', function() {
		$('#navbtn_proceed').addClass('navbtn_lockdown').removeClass('navbtn_employee');
		$('#navbtn_proceed .navbtn_next, #navbtn_proceed .navbtn_unlocking').hide();
		$('#navbtn_proceed .navbtn_locked').show();
		$(window).scrollTop(0);
		$('#employee').text($('#form_employee span[data-name="employee"]').text());
		$('#form_employee').fadeOut(function() {
			$('#navbtn_proceed').addClass('navbtn_marginleft');
			$('#form_stepindicator_employee').removeClass('activestep').addClass('completedstep');
			$('#form_stepindicator_basics').addClass('activestep');
			$('#form_basics').fadeIn(function() {
				$('#navbtn_retreat').removeClass('navbtn_hidden navbtn_disabled').addClass('navbtn_basics');
				$('#navbtn_proceed').removeClass('navbtn_lockdown navbtn_marginleft').addClass('navbtn_disabled navbtn_basics');
				validateform();
				$('.input[data-autofocus]:visible').focus();
			});
		});
		$('#privacydisclaimer, #formstatistics, #formnews, #formdonation').fadeOut();
	});
	// Go back to EMPLOYEE step
	$('#navbtn_bar').on('click', '#navbtn_retreat.navbtn_basics:not(.navbtn_shifting):not(.navbtn_disabled):not(.navbtn_lockdown)', function() {
		$(window).scrollTop(0);
		$('#navbtn_retreat').addClass('navbtn_disabled navbtn_hidden').removeClass('navbtn_basics');
		$('#navbtn_proceed').addClass('navbtn_lockdown').removeClass('navbtn_basics');
		$('#navbtn_proceed .navbtn_next, #navbtn_proceed .navbtn_unlocking').hide();
		$('#navbtn_proceed .navbtn_locked').show();
		$('#form_basics').fadeOut(function() {
			$('#form_stepindicator_basics').removeClass('activestep');
			$('#form_stepindicator_employee').removeClass('completedstep').addClass('activestep');
			$('#form_employee span[data-name="employee"]').text('').removeClass('approved');
			$('#form_employee').fadeIn(function() {
				$('#navbtn_proceed').removeClass('navbtn_lockdown').addClass('navbtn_disabled navbtn_employee');
				validateform();
				$('.input[data-autofocus]:visible').focus();
			});
			$('#privacydisclaimer, #formstatistics, #formnews, #formdonation').fadeIn();
		});
	});
	// Proceed to ADVANCED step
	$('#navbtn_bar').on('click', '#navbtn_proceed.navbtn_basics:not(.navbtn_shifting):not(.navbtn_disabled):not(.navbtn_lockdown)', function() {
		$('#navbtn_retreat').addClass('navbtn_disabled').removeClass('navbtn_basics');
		$('#navbtn_proceed').addClass('navbtn_lockdown').removeClass('navbtn_basics');
		$('#navbtn_proceed .navbtn_next, #navbtn_proceed .navbtn_unlocking').hide();
		$('#navbtn_proceed .navbtn_locked').show();
		$(window).scrollTop(0);
		$('#copyright').fadeOut();
		$('#form_basics').fadeOut(function() {
			$('#form_stepindicator_basics').removeClass('activestep').addClass('completedstep');
			$('#form_stepindicator_advanced').addClass('activestep');
			var username = $('.input[data-name="fullname"]').text().split(' ')[0];
			if ( ($('input[name="device"][value="Datamaskin"]').is(':checked')) && (!$('input[name="os"][value="Apple"]').is(':checked')) ) {
				$('#form_advanced fieldset[name="login"]').show();
				$('#form_advanced .input[data-name="username"]').text(username).addClass('approved');
				$('#form_advanced .input[data-name="password"]').removeClass('input_hidden');
				$('#form_advanced fieldset[name="emailsetup"]').show();
				$('#form_advanced fieldset[name="office"]').show();
				$('#form_advanced fieldset[name="antivirus"]').show();
				$('#form_advanced fieldset[name="transfer"]').show();
				$('#form_advanced fieldset[name="misc"]').show();
			} else if ( ($('input[name="device"][value="Datamaskin"]').is(':checked')) && ($('input[name="os"][value="Apple"]').is(':checked')) ) {
				$('#form_advanced fieldset[name="login"]').show();
				$('#form_advanced .input[data-name="username"]').text(username).addClass('approved');
				$('#form_advanced .input[data-name="password_mac"]').removeClass('input_hidden');
				$('#form_advanced fieldset[name="emailsetup"]').show();
				$('#form_advanced fieldset[name="appleid"]').show();
				$('#form_advanced fieldset[name="office"]').show();
				$('#form_advanced fieldset[name="antivirus"]').show();
				$('#form_advanced fieldset[name="transfer"]').show();
				$('#form_advanced fieldset[name="misc"]').show();
			} else if ( ( ($('input[name="device"][value="Smarttelefon"]').is(':checked')) || ($('input[name="device"][value="Nettbrett"]').is(':checked')) ) && ($('input[name="os"][value="Apple"]').is(':checked')) ) {
				$('#form_advanced fieldset[name="pincodes"]').show();
				if ($('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val() == 'screenlocktype_pattern') {
					$('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val('screenlocktype_none').removeClass('approved');
				} else if ($('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val() == 'screenlocktype_pin') {
					$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
					$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"].input_hidden').removeClass('input_hidden');
				} else {
					$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"]:not(.input_hidden)').addClass('input_hidden');
					$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
				}
				$('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"] option[value="screenlocktype_pattern"]').hide();
				$('#form_advanced fieldset[name="emailsetup"]').show();
				$('#form_advanced fieldset[name="appleid"]').show();
				$('#form_advanced fieldset[name="antivirus"]').show();
				$('#form_advanced fieldset[name="antivirus"] #antivirusunits option.ibasrecovery').hide();
				$('#form_advanced fieldset[name="transfer"]').show();
				$('#form_advanced fieldset[name="misc"]').show();
			} else if ( ($('input[name="device"][value="Smarttelefon"]').is(':checked')) || ($('input[name="device"][value="Nettbrett"]').is(':checked')) ) {
				$('#form_advanced fieldset[name="pincodes"]').show();
				if ($('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val() == 'screenlocktype_pattern') {
					$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"]:not(.input_hidden)').addClass('input_hidden');
					$('#form_advanced fieldset[name="pincodes"] #patternHolder7.input_hidden').removeClass('input_hidden');
				} else if ($('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val() == 'screenlocktype_pin') {
					$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
					$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"].input_hidden').removeClass('input_hidden');
				} else {
					$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"]:not(.input_hidden)').addClass('input_hidden');
					$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
				}
				$('#form_advanced fieldset[name="emailsetup"]').show();
				$('#form_advanced fieldset[name="google"]').show();
				$('#form_advanced fieldset[name="antivirus"]').show();
				$('#form_advanced fieldset[name="antivirus"] #antivirusunits option.ibasrecovery').hide();
				$('#form_advanced fieldset[name="transfer"]').show();
				$('#form_advanced fieldset[name="misc"]').show();
				if ($('input[name="os"][value="Huawei"]').is(':checked')) {
					$('#form_advanced fieldset[name="huawei"]').show();
				}
				if ($('input[name="os"][value="Honor"]').is(':checked')) {
					$('#form_advanced fieldset[name="huawei"]').show();
				}
				if ($('input[name="os"][value="Samsung"]').is(':checked')) {
					$('#form_advanced fieldset[name="samsung"]').show();
				}
				if ($('input[name="os"][value="ASUS"]').is(':checked')) {
					$('#form_advanced fieldset[name="asus"]').show();
				}
			} else if ($('input[name="device"][value="Knappetelefon"]').is(':checked')) {
				$('#form_advanced fieldset[name="pincodes"]').show();
				if ($('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val() == 'screenlocktype_pattern') {
					$('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val('screenlocktype_none').removeClass('approved');
				} else if ($('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').val() == 'screenlocktype_pin') {
					$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
					$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"].input_hidden').removeClass('input_hidden');
				} else {
					$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"]:not(.input_hidden)').addClass('input_hidden');
					$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
				}
				$('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"] option[value="screenlocktype_pattern"]').hide();
				$('#form_advanced fieldset[name="transfer"]').show();
				$('#form_advanced fieldset[name="misc"]').show();
			}
			$('#form_advanced').fadeIn(function(){
				$('#navbtn_retreat').removeClass('navbtn_disabled').addClass('navbtn_advanced');
				$('#navbtn_proceed').removeClass('navbtn_lockdown').addClass('navbtn_disabled navbtn_advanced');
				validateform();
				$('.input[data-autofocus]:visible').focus();
			});
		});
	});
	// Go back to BASICS step
	$('#navbtn_bar').on('click', '#navbtn_retreat.navbtn_advanced:not(.navbtn_shifting):not(.navbtn_disabled):not(.navbtn_lockdown)', function() {
		$(window).scrollTop(0);
		$('#navbtn_retreat').addClass('navbtn_disabled').removeClass('navbtn_advanced');
		$('#navbtn_proceed').addClass('navbtn_lockdown').removeClass('navbtn_advanced');
		$('#navbtn_proceed .navbtn_next, #navbtn_proceed .navbtn_unlocking').hide();
		$('#navbtn_proceed .navbtn_locked').show();
		$('#form_advanced').fadeOut(function() {
			$('#form_stepindicator_advanced').removeClass('activestep');
			$('#form_stepindicator_basics').removeClass('completedstep').addClass('activestep');
			$('#form_advanced fieldset').hide();
			$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
			$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"]:not(.input_hidden)').addClass('input_hidden');
			$('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"] option[value="screenlocktype_pattern"]').show();
			$('#form_advanced .input[data-name="password"]').addClass('input_hidden');
			$('#form_advanced .input[data-name="password_mac"]').addClass('input_hidden');
			$('#form_advanced fieldset[name="antivirus"] #antivirusunits option:hidden').show();
			$('#form_basics').fadeIn(function(){
				$('#navbtn_retreat').removeClass('navbtn_disabled').addClass('navbtn_basics');
				$('#navbtn_proceed').removeClass('navbtn_lockdown').addClass('navbtn_disabled navbtn_basics');
				validateform();
				$('.input[data-autofocus]:visible').focus();
			});
		});
	});
	// Pin code or pattern lock or no lock
	$('#form_advanced fieldset[name="pincodes"] select[name="pinorpattern"]').change(function() {
		if ($(this).val() == 'screenlocktype_pattern') {
			$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"]:not(.input_hidden)').addClass('input_hidden');
			$('#form_advanced fieldset[name="pincodes"] #patternHolder7.input_hidden').removeClass('input_hidden');
		} else if ($(this).val() == 'screenlocktype_pin') {
			$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
			$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"].input_hidden').removeClass('input_hidden');
		} else {
			$('#form_advanced fieldset[name="pincodes"] span[data-name="lockpin"]:not(.input_hidden)').addClass('input_hidden');
			$('#form_advanced fieldset[name="pincodes"] #patternHolder7:not(.input_hidden)').addClass('input_hidden');
		}
	});
	// Transfer options
	$('fieldset[name="transfer"] input[type="radio"][name="transferextent"]').change(function() {
		$('fieldset[name="transfer"] input[type="checkbox"]:checked').parent('label').removeClass('checked').hide();
		$('fieldset[name="transfer"] input[type="checkbox"]:checked').parent('label').removeClass('checked');
		$('fieldset[name="transfer"] input[type="checkbox"]:checked').prop('checked', false);
		if ($('input[name="transferextent"][value="Utvalg"]').is(':checked')) {
			$('#transfer.transfer_onerow').removeClass('transfer_onerow');
			$('#transfercontents').show();
			$('fieldset[name="transfer"] input[type="checkbox"]').parent('label').delay(200).fadeIn(400);
			$('#form_advanced fieldset[name="transfer"] span[data-name="password_oldunit"].input_hidden').removeClass('input_hidden');
			$('#form_advanced fieldset[name="transfer"] span[data-name="password_oldunit"]:empty').focus();
		} else if ($('input[name="transferextent"][value="Alt"]').is(':checked')) {
			$('#transfer:not(.transfer_onerow)').addClass('transfer_onerow');
			$('#transfercontents').hide();
			$('fieldset[name="transfer"] input[type="checkbox"]').parent('label').hide();
			$('fieldset[name="transfer"] input[type="checkbox"]').parent('label').addClass('label_required');
			$('#form_advanced fieldset[name="transfer"] span[data-name="password_oldunit"].input_hidden').removeClass('input_hidden');
			$('#form_advanced fieldset[name="transfer"] span[data-name="password_oldunit"]:empty').focus();
		} else {
			$('#transfer:not(.transfer_onerow)').addClass('transfer_onerow');
			$('#transfercontents').hide();
			$('fieldset[name="transfer"] input[type="checkbox"]').parent('label').hide();
			$('fieldset[name="transfer"] input[type="checkbox"]').parent('label').addClass('label_required');
			$('#form_advanced fieldset[name="transfer"] span[data-name="password_oldunit"]:not(.input_hidden)').addClass('input_hidden');
			$('#form_advanced fieldset[name="transfer"] span[data-name="password_oldunit"]:not(:empty)').text('').removeClass('approved');
		}
	});
	$('fieldset[name="transfer"] input[type="checkbox"][name="transferdetails"]').change(function() {
		if ($('fieldset[name="transfer"] input[type="checkbox"]:checked').length > 0) {
			$('fieldset[name="transfer"] input[type="checkbox"]').parent('label').removeClass('label_required');
		} else {
			$('fieldset[name="transfer"] input[type="checkbox"]').parent('label').addClass('label_required');
		}
		if ($('fieldset[name="transfer"] input[type="checkbox"]:not(:checked)').length < 1) {
			$('input[name="transferextent"][value="Alt"]').click();
			$('#form_advanced fieldset[name="transfer"] span[data-name="password_oldunit"]:empty').focus();
		}
	});
	// Anti-virus: Hide device count selection if customer already has license
	$('#form_advanced fieldset[name="antivirus"] select[name="antivirusoption"]').change(function() {
		if ( ($(this).val() == 'existingantivirus') || ($(this).val() == '0') ) {
			$('#form_advanced fieldset[name="antivirus"] select[name="antivirusunits"]').addClass('input_hidden');
		} else {
			$('#form_advanced fieldset[name="antivirus"] select[name="antivirusunits"]').removeClass('input_hidden');
		}
	});
	// Make email required if any services are selected (office, anti-virus, etc.)
	$('#form_advanced select').change(function() {
		activatedservices = false;
		$('#form_advanced select[data-toggleaccount]').each(function() {
			if ($(this).val() == 'new') {
				activatedservices = true;
			}
		});
		if (activatedservices) {
			$('select#emailaccount').prop('required', true);
			$('fieldset#emailsetup').addClass('fieldset_required');
			$('p#emaildescription').text('Når vi skal opprette nye kontoer for kunden er det nødvendig med en e-postadresse å ta utgangspunkt i.');
		} else {
			$('select#emailaccount').prop('required', false);
			$('fieldset#emailsetup').removeClass('fieldset_required');
			$('p#emaildescription').text('Vi kan legge til epost-kontoen til kunden i Outlook eller et annet epost-program.');
		}
	});
	// Reveal and requirie placeholder BIRTHDAY field if not filled out and new accounts are to be made
	$('#form_advanced select').change(function() {
		createnewaccount = false;
		$('#form_advanced select').each(function() {
			if ($(this).val() == 'new') {
				createnewaccount = true;
			}
		});
		if ( (createnewaccount) && ($('.input[data-name="bdate"]').text().trim().length == 0) ) {
			$('#extrarequired:hidden').show();
			$('.input.input_hidden[data-name="placeholder_bdate"]').removeClass('input_hidden');
			$('.input[data-name="bdate"]').addClass('input_required');
		} else {
			$('#extrarequired:visible').hide();
			$('.input[data-name="placeholder_bdate"]:not(.input_hidden)').addClass('input_hidden');
			$('.input[data-name="bdate"]').removeClass('input_required');
		}
	});
	// Reveal warning about "Info kommer senere" for accounts
	$('#form_advanced select').change(function() {
		lateraccountinfo = false;
		$('#form_advanced select').each(function() {
			if ($(this).val() == 'later') {
				lateraccountinfo = true;
			}
		});
		if (lateraccountinfo) {
			$('#lateraccountwarning:hidden').show();
			$("html, body").animate({ scrollTop: $("#lateraccountwarning:not(.triggeredwarning)").offset().top }, 500);
			$('#lateraccountwarning').addClass('triggeredwarning');
		} else {
			$('#lateraccountwarning:visible').hide();
			$('#lateraccountwarning').removeClass('triggeredwarning');
		}
	});
	// Proceed to TIME step
	$('#navbtn_bar').on('click', '#navbtn_proceed.navbtn_advanced:not(.navbtn_shifting):not(.navbtn_disabled):not(.navbtn_lockdown)', function() {
		$('#navbtn_retreat').addClass('navbtn_disabled').removeClass('navbtn_advanced');
		$('#navbtn_proceed').addClass('navbtn_lockdown').removeClass('navbtn_advanced');
		$('#navbtn_proceed .navbtn_next, #navbtn_proceed .navbtn_unlocking').hide();
		$('#navbtn_proceed .navbtn_locked').show();
		$(window).scrollTop(0);
		$('#copyright').fadeOut();
		$('#form_advanced').fadeOut(function() {
			$('#form_stepindicator_advanced').removeClass('activestep').addClass('completedstep');
			$('#form_stepindicator_time').addClass('activestep');
			$('#form_time').fadeIn(function(){
				$('#navbtn_retreat').removeClass('navbtn_disabled').addClass('navbtn_time');
				$('#navbtn_proceed').removeClass('navbtn_lockdown').addClass('navbtn_disabled navbtn_time');
				validateform();
				$('.input[data-autofocus]:visible').focus();
			});
		});
	});
	// Go back to ADVANCED step
	$('#navbtn_bar').on('click', '#navbtn_retreat.navbtn_time:not(.navbtn_shifting):not(.navbtn_disabled):not(.navbtn_lockdown)', function() {
		$(window).scrollTop(0);
		$('#navbtn_retreat').addClass('navbtn_disabled').removeClass('navbtn_time');
		$('#navbtn_proceed').addClass('navbtn_lockdown').removeClass('navbtn_time');
		$('#navbtn_proceed .navbtn_next, #navbtn_proceed .navbtn_unlocking').hide();
		$('#navbtn_proceed .navbtn_locked').show();
		$('#form_time').fadeOut(function() {
			$('#form_stepindicator_time').removeClass('activestep');
			$('#form_stepindicator_advanced').removeClass('completedstep').addClass('activestep');
			$('#form_advanced').fadeIn(function(){
				$('#navbtn_retreat').removeClass('navbtn_disabled').addClass('navbtn_advanced');
				$('#navbtn_proceed').removeClass('navbtn_lockdown').addClass('navbtn_disabled navbtn_advanced');
				validateform();
				$('.input[data-autofocus]:visible').focus();
			});
		});
	});
	// Proceed to PRINT MODE
	$('#navbtn_bar').on('click', '#navbtn_proceed.navbtn_time:not(.navbtn_shifting):not(.navbtn_disabled):not(.navbtn_lockdown)', function() {
		$(window).scrollTop(0);
		$('#navbtn_retreat').addClass('navbtn_disabled');
		$('#navbtn_proceed').addClass('navbtn_lockdown').removeClass('navbtn_advanced');
		$('#navbtn_proceed .navbtn_next, #navbtn_proceed .navbtn_unlocking').hide();
		$('#navbtn_proceed .navbtn_locked').show();
		$('#form_stepindicator').fadeOut();
		$('#navbtn_bar').fadeOut();
		$('#loading_title').text('Konverterer til utskriftsformat');
		$('#form_loading').addClass('fullscreenloading');
		$('#form_loading').fadeIn(function(){
			var timestring = new Date();
			var dateoptions = { year: 'numeric', month: '2-digit', day: '2-digit' };
			var timeoptions = { hour: '2-digit', minute: '2-digit' };
			var begindate = timestring.toLocaleDateString('no-NO', dateoptions);
			var begintime = timestring.toLocaleTimeString('no-NO', timeoptions);
			$('#createdtime').text(begindate + ', kl. ' + begintime);
			$('#form #form_basics fieldset#devicetype').hide();
			$('#form #form_basics fieldset#operatingsystem').hide();
			$('fieldset[name="transfer"] input[type="radio"][name="transferextent"][value="Utvalgt"]').parent('label').hide();
			$('#form_time').fadeOut();
			$('#form_basics, #form_advanced').fadeIn();
			$('.bottommessage:visible').hide();
			if ( ($('.input[data-name="bdate"]').text().trim().length == 0) && ($('.input[data-name="placeholder_bdate"]').text().trim().length == 8) ) {
				var placeholder_bdate = $('.input[data-name="placeholder_bdate"]').text();
				$('.input[data-name="bdate"]').text(placeholder_bdate).addClass('approved');
			}
			var timelimit = $('input[type="radio"][name="time"]:checked').attr('data-text');
			$('#timelimit').text($('#form_time span[data-name="timelimit"]').text());
			setTimeout(function() {
				$('html').addClass('print');
				$('#form_loading').fadeOut();
				$('#form_loading').fadeOut(function(){
					window.print();
				});
			}, 1800);
		});
	});
	// Manual print button
	$('#form_notifications').on('click', '#form_printbutton', function() {
		window.print();
	});
});
