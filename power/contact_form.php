<?php
// Fetching Values from URL.
$name = $_POST['name1'];
$email = $_POST['email1'];
$message = $_POST['message1'];
$email = filter_var($email, FILTER_SANITIZE_EMAIL); // Sanitizing E-mail.
// After sanitization Validation is performed
if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
	$subject = '#' . date('mds') . ' Forespørsel Power Support Pad';
	// To send HTML mail, the Content-type header must be set.
	$headers = 'MIME-Version: 1.0' . "\r\n";
	$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
	$headers .= 'From: ' . $email. "\r\n"; // Sender's Email
	$headers .= 'Cc: ' . $email. "\r\n"; // Carbon copy to Sender
	$template = 'Hei, ' . $name . '.<br/>'
	. '<br/>Takk for forespørselen!<br/><br/>'
	. '<strong>Navn:</strong> ' . $name . '<br/>'
	. '<strong>E-post:</strong> ' . $email . '<br/>'
	. '<strong>Beskjed:</strong> ' . $message . '<br/><br/>'
	. 'Dette er en bekreftelse på at din forespørsel har blitt mottatt.'
	. '<br/>'
	. 'Du vil få en tilbakemelding fortløpende.';
	$sendmessage = '<div style="color:#000;font-size:18px;">' . $template . '</div>';
	// Message lines should not exceed 70 characters (PHP rule), so wrap it.
	$sendmessage = wordwrap($sendmessage, 70);
	// Send mail by PHP Mail Function.
	mail("aleksander.stole@power.no", '=?utf-8?B?'.base64_encode($subject).'?=', $sendmessage, $headers);
	echo "OK";
} else {
	echo "<span>* ugyldig e-postadresse *</span>";
}