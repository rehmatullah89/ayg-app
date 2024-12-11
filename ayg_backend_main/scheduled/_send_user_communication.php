<?php

if(defined("WORKER")
	|| defined("QUEUE")) {

	require_once 'dirpath.php';
	require_once $dirpath . 'lib/initiate.inc.php';
	require_once $dirpath . 'lib/errorhandlers_scheduled.php';
}


function send_sms_notification($userPhoneId, $message, $phoneNumberUS='', $override = false) {

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch User Phone object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$userPhone = parseExecuteQuery(array("objectId" => $userPhoneId), "UserPhones", "", "", [], 1);

	// if phone record was NOt found
	if(count_like_php5($userPhone) == 0) {

		return json_error_return_array("AS_1059", "", "SMS Notification failed (userPhoneId = " . $userPhoneId . "; Message = " . $message . "); UserPhones record was not found", 1, 1);
	}

	// If Notification is not allowed to be sent, Twilio Opt out
	if($userPhone->get('SMSNotificationsOptOut') == true) {

		json_error("AS_", "", "User SMS Opt out, skipping message send. userPhoneId = " . $userPhoneId . " - "  . $message, 1, 1);
		return "";
	}

	// If Notification is not allowed to be sent, return true response
	if($userPhone->get('SMSNotificationsEnabled') != true
		&& $override == false) {

		return "";
	}

	return send_sms_message_via_twilio($userPhone->get('phoneNumberFormatted'), $message, $userPhoneId);
}

function send_sms_notification_with_phone_number($phoneCountryCode, $phoneNumber, $message) {

	// in case it is testing phone, skip $phoneCountryCode

	if ($phoneNumber=='+48695671680'){
        $phoneCountryCode = '48';
        $phoneNumber = '695671680';
        // do not send message
        //return '';
	}

	return send_sms_message_via_twilio('+' . $phoneCountryCode . $phoneNumber, $message);
}

function send_sms_message_via_twilio($phoneNumber, $message, $userPhoneId='') {

	// Send message
	try {

		$client = new Twilio\Rest\Client($GLOBALS['env_TwilioSID'], $GLOBALS['env_TwilioToken']);
		$response = $client->messages->create(
			$phoneNumber,
			// '+' . $userPhone->get('phoneCountryCode') . $userPhone->get('phoneNumber'),
			[
				'from' => $GLOBALS['env_TwilioPhoneNumber'],
				'body' => $message
			]
		);
	}
	catch(Exception $ex) {

		if($ex->getCode() == 21610
			&& !empty($userPhoneId)) {

			$userPhone = parseExecuteQuery(array("objectId" => $userPhoneId), "UserPhones", "", "", [], 1);
			$userPhone->set("SMSNotificationsOptOut", true);
			$userPhone->set("SMSNotificationsEnabled", false);
			$userPhone->save();

			return json_error_return_array("AS_1095", "", "SMS Notification failed (phoneNumer = " . $phoneNumber . "; userPhoneId = " . $userPhoneId . "; Message = " . $message . "); User has Opted out of SMS. Disabling SMS notifications for user.", 1, 1);
		}

		// Delivery notification
		if($ex->getCode() == 21610
			&& empty($userPhoneId)) {

			return json_error_return_array("AS_1095", "", "SMS Notification failed (phoneNumer = " . $phoneNumber . "; Message = " . $message . "); Delivery User has Opted out of SMS. Inform to enable notifications.", 1, 1);
		}

		// if($ex->getCode() == 21408) {

		// 	$userPhone = parseExecuteQuery(array("objectId" => $userPhoneId), "UserPhones", "", "", [], 1);
		// 	$userPhone->set("SMSNotificationsEnabled", false);
		// 	$userPhone->save();

		// 	return json_error_return_array("AS_1095", "", "SMS Notification failed (phoneNumer = " . $phoneNumber . "; userPhoneId = " . $userPhoneId . "; Message = " . $message . "); User phone location not in approved twilio list. Disabling SMS notifications for user.", 1, 1);
		// }

		// Message didn't go, so error out
		return json_error_return_array("AS_1094", "", "SMS Notification failed (phoneNumer = " . $phoneNumber . "; userPhoneId = " . $userPhoneId . "; Message = " . $message . "); " . $ex->getCode() . ' - ' . $ex->getMessage(), 1, 1);
	}

	unset($client);

	if(!empty($response->errorCode)) {
		
		// Message didn't go, so error out
		return json_error_return_array("AS_1028", "", "SMS Notification failed (phoneNumer = " . $phoneNumber . "; userPhoneId = " . $userPhoneId . "; Message = " . $message . "); " . $response->errorCode . " - " . $response->errorMessage, 1, 1);
	}

	return "";	
}

function __send_email_verify(&$message) {

	// $objUser = parseExecuteQuery(array("username" => createUsernameFromEmail($message["content"]["email"], $message["content"]["type"])), "_User", "", "", [], 1);
	$objUser = parseExecuteQuery(array("objectId" => $message["content"]["objectId"]), "_User", "", "", [], 1);

	$processEmailResponse = emailSend(
		$objUser->get("firstName") . ' ' . $objUser->get("lastName"), 
		createEmailFromUsername($objUser->get("username"),$objUser),
		"Welcome To AtYourGate! Confirm Your Email",
		"email_verify", 
		array(
			"greeting" => randomGreeting('',false,false),
			"first_name" => $objUser->get("firstName").",",
			"copyright_year" => date('Y'),
			"url" => $GLOBALS['env_SherpaExternalAPIURL'] . "/user/emailVerify/t/" . emailVerifyGenerateAndSaveToken($objUser->getObjectId())
		)
	);

	return $processEmailResponse;
}

function send_push_notification($userDeviceId, $oneSignalId, $message) {

	// Check if Push Notification is allowed from the user
	$userDevice = parseExecuteQuery(array("objectId" => $userDeviceId), "UserDevices", "", "", [], 1);

	if(count_like_php5($userDevice) > 0
		&& $userDevice->get('isPushNotificationEnabled') == true) {

		// send notification
	}
	// Skip sending
	else {

		// Log event
		json_error("AS_3014", "", "Push notification send failed, isPushNotificationEnabled is disabled; " . json_encode($message) . " - DeviceId: " . $userDeviceId, 1, 1);
		return "";
	}

	$data = [];
	foreach($message["data"] as $fieldName => $fieldValue) {

		$data[$fieldName] = $fieldValue;

	}

	$parameters = [
		'app_id' => $GLOBALS['env_OneSignalAppId'],
		'contents' => ['en' => $message["text"]],
		'data' => $data,
		'headings' => ['en' => $message["title"]], 
		'include_player_ids' => [$oneSignalId], 
		'content_available' => true,
		// 'ios_badgeType' => 'Increase',
		// 'ios_badgeCount' => 1,
		'collapse_id' => isset($message["id"]) ? $message["id"] : ""
	];

	$response = \Httpful\Request::post($GLOBALS['env_OneSignalNotificationsURL'])
	    ->sendsJson()
	    ->body(json_encode($parameters))
	    ->expectsJson()
        ->timeout(5)
	    ->sendIt();

	if(!isset($response->body->success)
		&& isset($response->body->errors)
		&& count_like_php5($response->body->errors) > 0) {

		$sendErrorCode = $response->code;

		// Check if the push notification id is no longer valid
		list($isOnesignalDeviceInvalid, $errorCode) = isOnesignalDeviceInvalid($oneSignalId);
		if($isOnesignalDeviceInvalid
			|| in_array("All included players are not subscribed", $response->body->errors)) {

			// mark the push notification flag as false so we don't send this again
			$userDevice->set("isPushNotificationEnabled", false);
			$userDevice->save();

			// Log event
			json_error("AS_3015", "", "Push notification send failed, one signal id is no longer valid (error_code=" . $errorCode . "). Marking user with isPushNotificationEnabled = false, DeviceId: " . $userDeviceId, 1, 1);
		}

		throw new Exception(json_encode(json_error_return_array("AS_1060", "", "Push notification send failed " . json_encode($response->body->errors) . json_encode($message) . "(error_code=" . $errorCode . ", " . $sendErrorCode . ") - DeviceId: " . $userDeviceId, 1)));
	}

	return "";
}

function __send_email_referral_reward_earn(&$message) {

	// Referrer
	$objUser = parseExecuteQuery(array("objectId" => $message["content"]["objectId"]), "_User", "", "", [], 1);
	$userReferral = parseExecuteQuery(array("user" => $objUser), "UserReferral", "", "", [], 1);

	// Referred
	$objUserReferred = parseExecuteQuery(array("objectId" => $message["content"]["objectIdReferred"]), "_User", "", "", [], 1);

	$processEmailResponse = emailSend(
		$objUser->get("firstName") . ' ' . $objUser->get("lastName"), 
		createEmailFromUsername($objUser->get("username"),$objUser),
		"Cha-Ching - You just earned " . $message["content"]["rewardInDollarsFormatted"] . "!", 
		"email_referral_reward_earn", 
		array(
			"first_name" => $objUser->get("firstName"),
			"reward_in_dollars_formatted" => $message["content"]["rewardInDollarsFormatted"],
			"reward_in_dollars_min_spend_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForReward']),
			"referral_offer_in_dollars_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']),
			"referral_offer_in_dollars_min_spend_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']),
			"referral_rules_url" => $GLOBALS['env_UserReferralRulesLink'],
			"first_name_referred" => ucfirst($objUserReferred->get("firstName")),
			"last_initial_referred" => ucfirst(substr($objUserReferred->get("lastName"), 0, 1)),
			"user_referral_code" => $userReferral->get('referralCode'),
			"copyright_year" => date('Y')
		)
	);

	return $processEmailResponse;
}

?>
