<?php

use Socketlabs\SocketLabsClient;
use Socketlabs\Message\BasicMessage;
use Socketlabs\Message\EmailAddress;

function prepareMessage($emailSubject, $templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat, $emailToAddress, $emailToName){
    $message = new BasicMessage();
    $message->subject = $emailSubject;
    list($templateContentTextPlain, $templateContentHTML) = emailFetchTemplateContent($templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat);

    $message->htmlBody = $templateContentHTML;
    $message->plainTextBody = $templateContentTextPlain;

    $message->from = new EmailAddress($GLOBALS['env_EmailFromAddress'],$GLOBALS['env_EmailFromName']);
    $message->addToAddress($emailToAddress, $emailToName);
    return $message;
}

function emailSend($emailToName, $emailToAddress, $emailSubject, $templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat=[], $bccAddress='') {

    try {
        $serverId = $GLOBALS['env_SocketLabsClientServerId'];
        $injectionApiKey = $GLOBALS['env_SocketLabsClientInjectionApiKey'];

        $client = new SocketLabsClient($serverId, $injectionApiKey);
        $message = prepareMessage($emailSubject, $templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat, $emailToAddress, $emailToName);
        $response = $client->send($message);

        if (!empty($bccAddress)){
            $message = prepareMessage($emailSubject, $templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat, $bccAddress, $bccAddress);
            $response = $client->send($message);
        }

    } catch (Exception $exception){
        return json_error_return_array("AS_1025", "", "Email send via SocketLabsClient failed; " . $exception->getMessage());
    }

    if (!isset($response) || (!isset($response->result)) || $response->result!='Success'){
        return json_error_return_array("AS_1025", "", "Email send via SocketLabsClient failed; " . serialize($response));
    }


    return "";
}

/*
function emailSend($emailToName, $emailToAddress, $emailSubject, $templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat=[], $ccAddress='') {

	// From
	$emailFrom = new SendGrid\Email($GLOBALS['env_EmailFromName'], $GLOBALS['env_EmailFromAddress']);

	$mail = new SendGrid\Mail();
	$mail->setFrom($emailFrom);

	// Subject
	$mail->setSubject($emailSubject);

	// To
	$personalization = new SendGrid\Personalization();
	$email = new SendGrid\Email($emailToName, $emailToAddress);
	$personalization->addTo($email);

	if (!empty($ccAddress)){
        $ccEmail = new SendGrid\Email($ccAddress, $ccAddress);
	    $personalization->addCc($ccEmail);
    }

	// $personalization->setSubject("");
	$mail->addPersonalization($personalization);

	// Fetch Template
	list($templateContentTextPlain, $templateContentHTML) = emailFetchTemplateContent($templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat);

	// Body Text
	$content = new SendGrid\Content("text/plain", $templateContentTextPlain);
	$mail->addContent($content);

	// Body HTML
	$content = new SendGrid\Content("text/html", $templateContentHTML);
	$mail->addContent($content);

	$sg = new \SendGrid($GLOBALS['env_SendGridAPIKey']);
	$request_body = $mail;
	$response = $sg->client->mail()->send()->post($request_body);

	// Errors, anything that is not 2xx error code
	// https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/errors.html
	if (intval(substr(strval($response->statusCode()), 0, 1)) != 2) {

		return json_error_return_array("AS_1025", "", "Email send via sengrid failed; " . $response->statusCode() . " - " . $response->body() . " - " . json_encode($response->headers()), 1, 1);
	}

	return "";
}
*/

