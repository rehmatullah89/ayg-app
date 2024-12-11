<?php

use App\Background\Repositories\PingLogMysqlRepository;
use App\Tablet\Repositories\RetailerPOSConfigParseRepository;

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Aws\Sqs\SqsClient;
use Httpful\Request;

function queue_pre__order_submission_process(&$message, &$workerQueue) {
	
	if(!$message["content"]["backOnQueue"]) {

		$orderObject = parseExecuteQuery(array("objectId" => $message["content"]["orderId"]), "Order", "", "", array("retailer", "retailer.retailerType"), 1);

		// Add to Parse
		countOrderTotals($orderObject->get("retailer")->get("airportIataCode"), $orderObject->get("retailer")->get("retailerType")->get("retailerType"), $orderObject->get("retailer")->get("uniqueId"));

		// Add Cache
		trendingRetailerMarkOrderSubmission($orderObject->get("retailer")->get("airportIataCode"), $orderObject->get("retailer")->get("retailerType")->get("retailerType"), $orderObject->get("retailer")->get("uniqueId"));

		// Mark that was picked up once
		$message["content"]["backOnQueue"] = true;

		// TODO:
		// Send order scheduled receipt
	}

	return "";
}

function queue__order_scheduled_process(&$message, &$workerQueue) {
	
	// Process Order
	$processFunctionResponse = processScheduledOrder(
		$message["content"]["orderId"]
	);

	return $processFunctionResponse;
}

function queue__order_submission_process(&$message, &$workerQueue) {
	
	// Process Order
	$processFunctionResponse = processOrder(
		$message["content"]["orderId"]
	);

	return $processFunctionResponse;
}

function queue__order_submission_process_dualconfig(&$message, &$workerQueue) {

    // Process Order
    $processFunctionResponse = processOrderDualConfig(
        $message["content"]["orderId"]
    );

    return $processFunctionResponse;
}

function queue__testtest(&$message, &$workerQueue) {
	var_dump('test');
}

function queue__log_website_download(&$message, &$workerQueue) {
	
    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
        //$pingLogRepository = new PingLogMysqlRepository($GLOBALS['logsPdoConnection']);
		$pingLogService = \App\Background\Services\LogServiceFactory::create();
        $status = $pingLogService->logWebsiteDownload(
            $message["content"]["objectId"]
        );
        unset($pingLogService);

        // If the load failed
        if(is_bool($status) && $status == false) {

   			return json_error_return_array("AS_3022", "", "Website download failed - " . $message["content"]["objectId"], 1);
        }
    }

	return "";
}

function queue__log_website_rating_click(&$message, &$workerQueue) {
	
    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {

		$pingLogService = \App\Background\Services\LogServiceFactory::create();
		$status = $pingLogService->logWebsiteRatingClick(
			$message["content"]["objectId"]
		);

        unset($pingLogService);

        // If the load failed
        if(is_bool($status) && $status == false) {

   			return json_error_return_array("AS_3022", "", "Website rating click failed - " . $message["content"]["objectId"], 1);
        }
    }

	return "";
}

function queue__order_status_via_sms(&$message, &$workerQueue) {
	
	$processFunctionResponse = send_sms_notification(
		$message["content"]["userPhoneId"],
		$message["content"]["message"]
	);

	return $processFunctionResponse;
}

function queue__order_status_via_push_notification(&$message, &$workerQueue) {

	try {
		
		$processFunctionResponse = send_push_notification(
			$message["content"]["userDeviceId"],
			$message["content"]["oneSignalId"],
			$message["content"]["message"]
		);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return json_error_return_array($response["error_code"], "", $response["error_message_log"], $response["error_severity"]);
	}

	return $processFunctionResponse;
}

function queue__reward_earn_via_sms(&$message, &$workerQueue) {

    $processFunctionResponse = send_sms_notification(
        $message["content"]["userPhoneId"],
        $message["content"]["message"]
    );

    return $processFunctionResponse;
}

function queue__send_sms_notification_with_phone_number(&$message, &$workerQueue) {

    $processFunctionResponse = send_sms_notification_with_phone_number(
        $message["content"]["phoneCountryCode"],
        $message["content"]["phoneNumber"],
        $message["content"]["message"]
    );

    return $processFunctionResponse;
}

function queue__reward_earn_via_push_notification(&$message, &$workerQueue) {

	try {
		
		$processFunctionResponse = send_push_notification(
			$message["content"]["userDeviceId"],
			$message["content"]["oneSignalId"],
			$message["content"]["message"]
		);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return json_error_return_array($response["error_code"], "", $response["error_message_log"], $response["error_severity"]);
	}

	return $processFunctionResponse;
}

function queue__delivery_activated(&$message, &$workerQueue) {

	if($GLOBALS['env_deliveryUpAutoNotification'] == false) {

		return "";
	}
	
	$processFunctionResponse = setupDeliveryUpNotification(
		$message["content"]["airportIataCode"],
		$message["content"]["timestamp"],
		$GLOBALS['env_deliveryUpAutoNotificationLookbackTimeInMins']
	);

    $slack = createOrderNotificationSlackMessageByAirportIataCode($message["content"]["airportIataCode"]);
	//$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
	$slack->setText('Delivery Up Time - Users Notified - ' . $message["content"]["airportIataCode"]);

    $currentTimeZone = date_default_timezone_get();

    // Set Airport Timezone
    $airportTimeZone = fetchAirportTimeZone($message["content"]["airportIataCode"], $currentTimeZone);
    if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

        date_default_timezone_set($airportTimeZone);
    }
	
	$attachment = $slack->addAttachment();

	$deliveryUpTimestampFormatted = date("M-d G:i:s T", $message["content"]["timestamp"]);
	$deliveryUpLookbackFormatted = date("M-d G:i:s T", intval($message["content"]["timestamp"]-$GLOBALS['env_deliveryUpAutoNotificationLookbackTimeInMins']*60));

	$attachment->addField("Delivery Up time:", $deliveryUpTimestampFormatted, false);
	$attachment->addField("Look back till:", $deliveryUpLookbackFormatted, false);
	$attachment->addFieldSeparator();

    // Reset current timezone
    if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

        date_default_timezone_set($currentTimeZone);
    }

	// Notifications
	if(count_like_php5($processFunctionResponse["notified"]) > 0) {

		foreach($processFunctionResponse["notified"] as $userId => $notification) {

			$attachment->addField("Object Id:", $userId, true);
			$attachment->addField("Customer:", $notification["customerName"], true);
			$attachment->addField("Last Seen:", $notification["lastSeenTimestampFormatted"], false);
			$attachment->addField("Message:", $notification["message"], false);
			$attachment->addFieldSeparator();
		}
	}
	else {

		$attachment->addField("No Users Notified", "", false);
	}

	// Errors
	if(count_like_php5($processFunctionResponse["errors"]) > 0) {

		$attachment->addFieldSeparator();
		$attachment->addField("Errors:", "", false);
		$attachment->addFieldSeparator();

		foreach($processFunctionResponse["errors"] as $userId => $notification) {

			$attachment->addField("Object Id:", $userId, true);
			$attachment->addField("Customer:", $notification["customerName"], true);
			$attachment->addField("Last Seen:", $notification["lastSeenTimestampFormatted"], false);
			$attachment->addField("Error:", $notification["message"], false);
			$attachment->addFieldSeparator();
		}
	}

	try {
		
		$slack->send();
	}
	catch (Exception $ex) {
		
		json_error("AS_1014", "", "Slack post failed delivery up notifciations sent! (" . json_encode($processFunctionResponse) . ") -- " . $ex->getMessage(), 2, 1);
	}

	unset($slack);
	return "";
}

function queue__delivery_up_via_sms(&$message, &$workerQueue) {
	
	$processFunctionResponse = send_sms_notification(
		$message["content"]["userPhoneId"],
		$message["content"]["message"]
	);

	return $processFunctionResponse;
}

function queue__delivery_up_via_push_notification(&$message, &$workerQueue) {

	try {
		
		$processFunctionResponse = send_push_notification(
			$message["content"]["userDeviceId"],
			$message["content"]["oneSignalId"],
			$message["content"]["message"]
		);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return json_error_return_array($response["error_code"], "", $response["error_message_log"], $response["error_severity"]);
	}

	return $processFunctionResponse;
}

function queue__beta_activate_via_push_notification(&$message, &$workerQueue) {

	try {
		
		$processFunctionResponse = send_push_notification(
			$message["content"]["userDeviceId"],
			$message["content"]["oneSignalId"],
			$message["content"]["message"]
		);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return json_error_return_array($response["error_code"], "", $response["error_message_log"], $response["error_severity"]);
	}

	return $processFunctionResponse;
}

function queue__beta_activate_via_sms(&$message, &$workerQueue) {

	$processFunctionResponse = send_sms_notification(
		$message["content"]["userPhoneId"],
		$message["content"]["message"]
	);

	return $processFunctionResponse;
}

function queue__flight_notify_via_sms(&$message, &$workerQueue) {
	
	$processFunctionResponse = send_sms_notification(
		$message["content"]["userPhoneId"],
		$message["content"]["message"]
	);

	return $processFunctionResponse;
}

function queue__flight_notify_via_push_notification(&$message, &$workerQueue) {

	try {

		$processFunctionResponse = send_push_notification(
			$message["content"]["userDeviceId"],
			$message["content"]["oneSignalId"],
			$message["content"]["message"]
		);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return json_error_return_array($response["error_code"], "", $response["error_message_log"], $response["error_severity"]);
	}

	return $processFunctionResponse;
}

function queue__order_confirm_accepted_by_retailer(&$message, &$workerQueue) {

	// Find order
	$orderObject = parseExecuteQuery(array("objectId" => $message["content"]["orderId"]), "Order", "", "", array("retailer", "retailer.location", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	if(count_like_php5($orderObject) > 0) {

		// Find POS Config
		$confirmType = getPOSType($orderObject->get('retailer'));
				
		// For Print retailer
		if(strcasecmp($confirmType, 'PRINT')==0) {

			$processFunctionResponse = execute_confirm_print_orders(
				$message["content"]["orderId"]
			);
		}

		// For POS retailer
		else if(strcasecmp($confirmType, 'POS')==0) {

			$processFunctionResponse = execute_confirm_pos_orders(
				$message["content"]["orderId"]
			);
		}

		// For Tablet retailer - Confirmed by retailer action
		// else if(strcasecmp($confirmType, 'TABLET')==0) {

		// 	$processFunctionResponse = execute_confirm_tablet_orders(
		// 		$message["content"]["orderId"]
		// 	);
		// }

		// If an error array was returned
		if(is_array($processFunctionResponse)) {

			// Put message on display
			json_error($processFunctionResponse["error_code"], "", $processFunctionResponse["error_message_log"], $processFunctionResponse["processFunctionResponse"], 1);

			// Put another message back on queue with 120 second delay
			try {

				$workerQueue->putMessageBackonQueueWithDelay($message, $workerQueue->getWaitTimeForDelay(time()+2*60));
			}
			catch (Exception $ex) {

				$response = json_decode($ex->getMessage(), true);
				return json_error_return_array($response["error_code"], "", "Confirm order put back on queue failed " . $response["error_message_log"], 1);
			}

			// Let messsage be deleted
			return "";
		}
		else {

			return "";
		}
	}

	// Order not found
	return json_error_return_array("AS_1029", "", "Confirm by Retailer order failed as order not found = " . $message["content"]["orderId"], 3);
}

function queue__order_ops_cancel_request(&$message, &$workerQueue) {

	$processFunctionResponse = cancel_order_by_ops(
		$message["content"]["orderId"],
		json_decode($message["content"]["cancelOptions"], true)
	);

	return $processFunctionResponse;	
}

function queue__order_ops_complete(&$message, &$workerQueue) {

	list($processFunctionResponse, $errorMsg) = complete_order_admin(
		$message["content"]["orderId"]
	);

	if(empty($errorMsg)) {

		$errorMsg = "Completed";
	}

	////////////////////////////////////////////////////////////////////////////////////
	// Slack response
	////////////////////////////////////////////////////////////////////////////////////
    $slack = createOrderNotificationSlackMessageBySequenceId($message["content"]["orderId"]);
	//$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
	$slack->setText('Order Mark Complete Admin Override');
	
	$attachment = $slack->addAttachment();
	$attachment->addField("Order Id:", $message["content"]["orderId"], true);
	$attachment->addField("Comments:", $errorMsg, false);
	
	try {
		
		$slack->send();
	}
	catch (Exception $ex) {
		
		json_error("AS_1014", "", "Slack post failed informing Order Admin mark complete status! orderId=(" . $message["content"]["orderId"] . ") -- " . $ex->getMessage(), 2, 1);
	}

	unset($slack);
	return "";
}

function queue__order_ops_cancel_admin_request(&$message, &$workerQueue) {

	list($processFunctionResponse, $errorMsg) = cancel_order_by_ops_admin(
		$message["content"]["orderId"],
		json_decode($message["content"]["cancelOptions"], true)
	);

	////////////////////////////////////////////////////////////////////////////////////
	// Slack if an error occurred
	////////////////////////////////////////////////////////////////////////////////////
	if($processFunctionResponse == false) {

		// it is using object ID, not sequence ID
        $slack = createOrderNotificationSlackMessage($message["content"]["orderId"]);
		//$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
		$slack->setText('Order Cancel Admin Override Failed!');
		
		$attachment = $slack->addAttachment();
		$attachment->addField("Order Id:", $message["content"]["orderId"], true);
		$attachment->addField("Comments:", $errorMsg, false);
		
		try {
			
			$slack->send();
		}
		catch (Exception $ex) {
			
			json_error("AS_1014", "", "Slack post failed informing Order Admin cancel failed status! orderId=(" . $message["content"]["orderId"] . ") -- " . $ex->getMessage(), 2, 1);
		}
	}

	unset($slack);
	return "";
}

function queue__order_pickup_mark_complete(&$message, &$workerQueue) {

    $processFunctionResponse = markPickupOrderComplete(
        $message["content"]["orderId"]
    );

    return $processFunctionResponse;
}

function queue__send_notification_order_pickup_accepted(&$message, &$workerQueue) {

    $processFunctionResponse = sendNotificationPickupOrderAccepted(
        $message["content"]["orderId"]
    );

    return $processFunctionResponse;
}

function queue__referral_reward_earn_qualify(&$message, &$workerQueue) {

	$processFunctionResponse = referral_reward_earn_qualify(
		$message["content"]["orderId"]
	);

	return $processFunctionResponse;	
}

function queue__referral_reward_process(&$message, &$workerQueue) {

	$processFunctionResponse = referral_reward_process(
		$message["content"]["orderId"],
		$message["content"]["userWhoReferred"],
		$message["content"]["overrideReward"],
		$message["content"]["overrideRewardReason"]
	);

	return $processFunctionResponse;	
}

function queue__order_email_receipt(&$message, &$workerQueue) {

	// Send Order receipt
	$processEmailResponse = send_order_receipt($message["content"]["orderId"]);

	return $processEmailResponse;
}

function queue__email_verify(&$message, &$workerQueue) {

	$processEmailResponse = __send_email_verify($message);

	return $processEmailResponse;
}

function queue__reward_earn_via_email(&$message, &$workerQueue) {

	// Send Reward Earn Email
	$processEmailResponse = __send_email_referral_reward_earn($message);

	return $processEmailResponse;
}

function queue__email_verify_on_signup(&$message, &$workerQueue) {

	// $objUser = parseExecuteQuery(array("username" => createUsernameFromEmail($message["content"]["email"], $message["content"]["type"])), "_User", "", "", [], 1);
	$objUser = parseExecuteQuery(array("objectId" => $message["content"]["objectId"]), "_User", "", "", [], 1);
	$processEmailResponse = __send_email_verify($message);

	if(is_array($processEmailResponse)) {

		return $processEmailResponse;
	}

	// Find promo used (there might be a delay in posting if the user doesn't enter code within 2 mins of signin up)

	$promoUsed = "";
    // Fetch all User Credits for the user
    // $userCredits = parseExecuteQuery(["user" => $objUser, "reasonForCredit" => "Signup Promo Code"], "UserCredits", "", "", ["signupCoupon"], 1);
    $userCredits = parseExecuteQuery(["user" => $objUser, "reasonForCreditCode" => getUserCreditReasonCode('GeneralSignupPromo')], "UserCredits", "", "", ["signupCoupon"], 1);
    if(count_like_php5($userCredits) > 0 && $userCredits->has("signupCoupon")) {

    	$promoUsed = $userCredits->get("signupCoupon")->get("couponCode");
    }
    else {

	    // Fetch all User Coupons for the user
	    $userCoupons = parseExecuteQuery(["addedOnStep" => "signup", "user" => $objUser], "UserCoupons", "", "", ["coupon"], 1);

	    if(count_like_php5($userCoupons) > 0 && $userCoupons->has("coupon")) {

	    	$promoUsed = $userCoupons->get("coupon")->get("couponCode");
	    }
    }

    $referralUserName = "";
    // $userCredit = parseExecuteQuery(["reasonForCredit" => "Signup Referral Code", "user" => $objUser], "UserCredits", "", "createdAt", ["userReferral.user"], 1);
    $userCredit = parseExecuteQuery(["reasonForCreditCode" => getUserCreditReasonCode('ReferralSignup'), "user" => $objUser], "UserCredits", "", "createdAt", ["userReferral.user"], 1);

	if(count_like_php5($userCredit) > 0
		&& !empty($userCredit->get("userReferral"))) {

		$referralUserName = $userCredit->get("userReferral")->get("user")->get('firstName') . ' ' . $userCredit->get("userReferral")->get("user")->get('lastName');
	}
    ////////////////////////////////////////////////

    $sessionDevice = parseExecuteQuery(array("user" => $objUser), "SessionDevices", "", "updatedAt", [], 1);

    $nearAirportIataCode = "";
    $locationCity = $locationState = $locationCountry = "";

    list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationForSession($sessionDevice);

    if(empty($nearAirportIataCode)) {

    	$locationDisplay = $locationState . ", " . $locationCountry;
    }
    else {

    	$locationDisplay = $nearAirportIataCode . " (" . $locationState . ", " . $locationCountry . ")";
    }

	// Post to Slack Channel
	$submissionDateTime = date("M j, g:i a", time());

	// Slack it
	$slack = new SlackMessage($GLOBALS['env_SlackWH_newUserSignup'], 'env_SlackWH_newUserSignup');
	$slack->setText("New User Signup" . " ($submissionDateTime)");
	
	$attachment = $slack->addAttachment();
	$attachment->addField("Name:", $objUser->get("firstName") . " " . $objUser->get("lastName"), false);
	$attachment->addField("Email:", createEmailFromUsername($objUser->get("username"),$objUser), false);
	$attachment->addField("Airport / Location:", $locationDisplay, false);
	$attachment->addField("Location Source:", $locationSource, false);
	$attachment->addField("App:", $message["content"]["app"], true);

	if(!empty($promoUsed)) {

		$attachment->addField("Promo:", $promoUsed, true);
	}
	else if(!empty($referralUserName)) {

		$attachment->addField("Referral By:", $referralUserName, true);
	}
	else {

		$attachment->addField("Promo / Referral:", 'none', true);
	}
	
	try {
		
		$slack->send();
	}
	catch (Exception $ex) {
		
		return json_error_return_array("AS_1016", "", "Slack post for New Signup failed email = " . $message["content"]["email"] ." - " . $ex->getMessage(), 3, 1);
	}

	unset($slack);
	return $processEmailResponse;
}

function queue__email_forgot_password(&$message, &$workerQueue) {

	$objUser = parseExecuteQuery(array("username" => createUsernameFromEmail($message["content"]["email"], $message["content"]["type"])), "_User", "", "", [], 1);

	$processEmailResponse = emailSend(
		$objUser->get("firstName") . ' ' . $objUser->get("lastName"), 
		$message["content"]["email"], 
		"Password Reset Instructions", 
		$message["action"],
		array(
			"greeting" => randomGreeting('',false,false),
			"first_name" => $objUser->get("firstName") . ",",
			"url" => "#",
			"token" => forgotGenerateAndSaveToken($message["content"]["email"]),
			"copyright_year" => date('Y')
		)
	);

	return $processEmailResponse;
}

function queue__email_password_reset_confirmation(&$message, &$workerQueue) {

	$objUser = parseExecuteQuery(array("username" => createUsernameFromEmail($message["content"]["email"], $message["content"]["type"])), "_User", "", "", [], 1);

	$processEmailResponse = emailSend(
		$objUser->get("firstName") . ' ' . $objUser->get("lastName"), 
		$message["content"]["email"], 
		"Password Reset Confirmation", 
		$message["action"],
		array(
			"greeting" => randomGreeting('',false,false),
			"first_name" => $objUser->get("firstName") . ",",
			"copyright_year" => date('Y')
		)
	);

	return $processEmailResponse;
}

function queue__onesignal_create_device(&$message, &$workerQueue) {

	$processFunctionResponse = create_onesignal_device(
		$message["content"]["userDeviceId"]
	);

	return $processFunctionResponse;
}

function queue__flight_new_addition(&$message, &$workerQueue) {
	
	// Process New Flight Additions
	$processFunctionResponse = statusFlightCheckWithoutNotifications(
		$message["content"]
	);

	return $processFunctionResponse;
}

function queue__flight_status_check(&$message, &$workerQueue) {
	
	// Check status
	$processFunctionResponse = statusFlightCheckWithNotifications(
		$message["content"]
	);

	return $processFunctionResponse;
}

/*
function queue__flight_status_marker(&$message, &$workerQueue) {
	
	// Check status
	$processFunctionResponse = statusFlightMarkerNotification(
		$message["content"]
	);

	return $processFunctionResponse;
}
*/

function queue__order_delivery_assign_delivery(&$message, &$workerQueue) {

	/////////////////////////////////////////////////////////////
	// Auto processing
	/////////////////////////////////////////////////////////////
	// $processFunctionResponse = queue__tempDelivery_FindDelivery($message);

	/////////////////////////////////////////////////////////////
	// Slack-based delivery
	/////////////////////////////////////////////////////////////
	$processFunctionResponse = queue__slackDelivery_FindDelivery($message);

	return $processFunctionResponse;	
}

function queue__flight_log_api_call($message) {

	$processFunctionResponse = logFlightAPICalls($message["content"]);

	return $processFunctionResponse;	
}

function queue__order_retailer_help_request($message) {

	$processFunctionResponse = retailerRequestingHelpFromTablet($message["content"]["orderId"]);

	return $processFunctionResponse;	
}

function queue__retailer_early_close(&$message, &$workerQueue) {
	
	// Process Order
	$processFunctionResponse = retailerEarlyClose(
		$message["content"]["retailerUniqueId"],
		$message["content"]["closeLevel"],
		$message["content"]["closeForSecs"]
	);

	return $processFunctionResponse;
}

function queue__order_ops_partial_refund_request(&$message, &$workerQueue) {
	// Process Refund
	$options = json_decode($message["content"]["options"], true);

	list($status, $error_message) = refundPartialOrder(
		$options
	);

	////////////////////////////////////////////////////////////////////////////////////
	// Slack message for Ops know to refund the order
	////////////////////////////////////////////////////////////////////////////////////
    $slack = createOrderNotificationSlackMessageBySequenceId(intval($message["content"]["orderId"]));
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
	$slack->setText('Order Partial Refund');
	
	$attachment = $slack->addAttachment();
	$attachment->addField("Order Id:", $options["orderSequenceId"], true);
	$attachment->addField("Type:", $options["refundType"], true);
	$attachment->addField("Reason:", $options["reason"], true);
	$attachment->addField("In Cents:", $options["inCents"], true);

	if($status == true) {

		$attachment->addField("Status:", "Refunded", false);
	}
	else {

		$attachment->addField("Status:", "Failed!", false);
		$attachment->addField("Comments:", $error_message, false);
	}
	
	try {
		
		$slack->send();
	}
	catch (Exception $ex) {
		
		json_error("AS_1014", "", "Slack post failed informing Order refund status! orderId=(" . $options["orderSequenceId"] . ") -- " . $ex->getMessage(), 2, 1);
	}

	unset($slack);
	return "";
}

function queue__order_refund_to_source_post_settle(&$message, &$workerQueue) {
	
	// Process Refund
    $orderDelayedRefund = parseExecuteQuery(["objectId" => $message["content"]["orderDelayedRefundObjectId"]], "OrderDelayedRefund", "", "", ["order"], 1) ;

	$response = refundOrderToSource($orderDelayedRefund->get('order')->get('orderSequenceId'), $orderDelayedRefund->get('order')->get('paymentId'), $orderDelayedRefund->get('amount'), $orderDelayedRefund->getObjectId());
    
	$orderDelayedRefund->set("attempt", $orderDelayedRefund->get("attempt")+1);
	$orderDelayedRefund->save();

    // Did not process
    if(is_array($response)) {

        json_error($response["error_code"], "", "Delayed Refund queue message failed " . $response["error_message_log"], 2, 1);
		return "";
	}

	// Else
	$orderDelayedRefund->set("isCompleted", true);
	$orderDelayedRefund->save();

	////////////////////////////////////////////////////////////////////////////////////
	// Slack message for Ops know to refund the order
	////////////////////////////////////////////////////////////////////////////////////
    $slack = createOrderNotificationSlackMessage($orderDelayedRefund->get('order')->getObjectId());
	//$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
	$slack->setText('Order Partial Refund');
	
	$attachment = $slack->addAttachment();
	$attachment->addField("Order Id:", $orderDelayedRefund->get('order')->get('orderSequenceId'), true);
	$attachment->addField("In Cents:", $orderDelayedRefund->get('amount')*100, true);
	$attachment->addField("Status:", "Delayed Refund Completed", false);
	
	try {
		
		$slack->send();
	}
	catch (Exception $ex) {
		
		json_error("AS_1014", "", "Slack post failed informing Order refund status! orderId=(" . $message["content"]["orderSequenceId"] . ") -- " . $ex->getMessage(), 2, 1);
	}

	unset($slack);
	return "";
}

function queue__log_user_checkin(&$message, &$workerQueue) {

	// Log in MySQL
    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
        $checkInService = \App\Background\Services\LogServiceFactory::create();
		$checkInService->logUserCheckin(
            $message["content"]["objectId"],
            $message["content"]["sessionObjectId"]
            // TAG
        );
		unset($checkInService);
    }
}

function queue__log_retailer_ping(&$message, &$workerQueue) {

	// Check if the retailer is a Dual Config retailer
	if(isRetailerDualConfig($message["content"]["retailerUniqueId"])) {


		setRetailerDualConfigPingTimestamp($message["content"]["retailerUniqueId"], time());
	}
	else {
		RetailerPOSConfigParseRepository::setLastSuccessfulPingTimestampByRetailersStatic([$message["content"]["retailerUniqueId"]], time());
	}

	// Log in MySQL
    if ($GLOBALS['logsPdoConnection'] instanceof PDO) {

        $pingLogService = \App\Background\Services\LogServiceFactory::create();
        $pingLogService->logRetailerPing(
            $message["content"]["retailerUniqueId"],
            $message["content"]["time"]
        );
		unset($pingLogService);
    }
}

function queue__log_delivery_ping(&$message, &$workerQueue) {

    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
        $pingLogService = \App\Background\Services\LogServiceFactory::create();
        $pingLogService->logDeliveryPing(
            $message["content"]["slackUsername"],
            $message["content"]["time"]
        );
		unset($pingLogService);
    }
}

function queue__log_delivery_activated(&$message, &$workerQueue) {

    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
		$pingLogService = \App\Background\Services\LogServiceFactory::create();
		$pingLogService->logDeliveryActivated(
            $message["content"]["slackUsername"],
            $message["content"]["time"]
        );
		unset($pingLogService);
    }
}

function queue__log_delivery_deactivated(&$message, &$workerQueue) {

    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
		$pingLogService = \App\Background\Services\LogServiceFactory::create();
		$pingLogService->logDeliveryDeactivated(
            $message["content"]["slackUsername"],
            $message["content"]["time"]
        );
		unset($pingLogService);
    }
}

function queue__log_retailer_connect_failure(&$message, &$workerQueue) {

    if ($GLOBALS['logsPdoConnection'] instanceof PDO) {

    	// Fetch Retailer Unique Id from the session token
    	$sessionDeviceObject = parseExecuteQuery(array("sessionTokenRecall" => decryptStringInMotion($message["content"]["sessionTokenEnc"])), "SessionDevices", "", "", ["user"], 1);

    	if(count_like_php5($sessionDeviceObject) == 0) {

    		json_error("AS_037", "", "Retailer Failure Connect failed, no session token found in SessionDevices " . json_encode($message), 3, 1);

    		return "";
    	}

    	// Admin Retailer, so skip the log
    	if($sessionDeviceObject->get("user")->get("retailerUserType") == 2) {

    		json_error("AS_038", "", "Retailer Failure Connect failed, log skipped for Ops user device", 3, 1);
    		return "";
    	}

    	$retailerTabletObject = parseExecuteQuery(array("tabletUser" => $sessionDeviceObject->get("user")), "RetailerTabletUsers", "", "", ["retailer"], 1);

    	if(count_like_php5($retailerTabletObject) == 0) {

    		json_error("AS_037", "", "Retailer Failure Connect failed, no retailer found with provided session token " . json_encode($message), 3, 1);

    		return "";
    	}

    	if ($retailerTabletObject->get("retailer") || $retailerTabletObject->get("retailer")===null){
    		return "";
		}
        $retailerUniqueId = $retailerTabletObject->get("retailer")->get("uniqueId");

		$pingLogService = \App\Background\Services\LogServiceFactory::create();
		$pingLogService->logRetailerConnectFailure(
            $retailerUniqueId,
            $message["content"]["time"]
        );
		unset($pingLogService);
    }
}

function queue__log_retailer_login(&$message, &$workerQueue) {

    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
		$pingLogService = \App\Background\Services\LogServiceFactory::create();
		$pingLogService->logRetailerLogin(
            $message["content"]["retailerUniqueId"],
            $message["content"]["time"]
        );
		unset($pingLogService);
    }
}

function queue__log_retailer_logout(&$message, &$workerQueue) {

    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
		$pingLogService = \App\Background\Services\LogServiceFactory::create();
		$pingLogService->logRetailerLogout(
            $message["content"]["retailerUniqueId"],
            $message["content"]["time"]
        );
		unset($pingLogService);
    }
}

function queue__log_partner_action_change_password_failed($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_file_list($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_login($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_access_attempt($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_access_attempt_success($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_access_disabled($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_change_password($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_logout($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_partner_action_file_download($message, $workerQueue) {

	return log_partner_action($message);
}

function queue__log_user_action_install($message, $workerQueue) {
	if (!isset($message["content"]["id"]) || $message["content"]["id"]===null){
	//var_dump('empty content id');
			return '';
	}

//var_dump(["objectId" => $message["content"]["id"]]);
	$zLogInstall = parseExecuteQuery(["objectId" => $message["content"]["id"]], "zLogInstall", "", "", [], 1);

	list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationByIP($zLogInstall->get('IPAddress'));

	$message["content"]["location"]["nearAirportIataCode"] = $nearAirportIataCode;
	$message["content"]["location"]["state"] = $locationState;
	$message["content"]["location"]["country"] = $locationCountry;
	$message["content"]["location"]["locationSource"] = $locationSource;
	$message["content"]["data"] = json_encode(["referral" => $zLogInstall->get('referral')]);

	$message["content"]["customerName"] = "";
	$message["content"]["objectId"] = $message["content"]["id"] . '-install';

	return log_user_action($message);
}

function queue__log_user_action_signup_begin($message, $workerQueue) {

	// get location information
	$cacheResponse = getCache($message["content"]["id"], 1);

	list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationByIP($cacheResponse["IPAddress"]);

	$message["content"]["data"] = json_encode(["email" => $cacheResponse["email"]]);
	$message["content"]["location"]["nearAirportIataCode"] = $nearAirportIataCode;
	$message["content"]["location"]["state"] = $locationState;
	$message["content"]["location"]["country"] = $locationCountry;
	$message["content"]["location"]["locationSource"] = $locationSource;

	$message["content"]["customerName"] = "";

	return log_user_action($message);
}

function queue__log_user_action_phone_add_failed($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_blocked_phone_add($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_duplicate_phone_add($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_profile_update($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_referral_info($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_checkin($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_invalid_session($message, $workerQueue) {

	// TAG
	$message["content"]["data"] = "";
	$message["content"]["location"]["nearAirportIataCode"] = "";
	$message["content"]["location"]["state"] = "";
	$message["content"]["location"]["country"] = "";
	$message["content"]["location"]["locationSource"] = "unknown";

	$message["content"]["customerName"] = "";

	// TAG
	return log_user_action($message);
}

function queue__log_user_action_logout($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_retailer_menu($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	// TAG
	return log_user_action($message);
}

function queue__log_user_action_retailer_list($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_add_flight($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_add_cart($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_delete_item_cart($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_checkout_start($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_checkout_cart($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_cart_coupon_applied($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_cart_coupon_failed($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_signup_coupon_failed($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_checkout_warning($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_checkout_payment_failed($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_checkout_complete($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_payment_add($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_payment_add_begin($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_payment_list($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__log_user_action_payment_delete($message, $workerQueue) {

	list($message["content"]["location"], $message["content"]["customerName"]) = getLocationForLogForUser($message["content"]["objectId"]);

	return log_user_action($message);
}

function queue__flight_gate_change_order_impact($message, $workerQueue) {

	$processFunctionResponse = flight_gate_change_order_impact(
		$message["content"]["flightId"],
		$message["content"]["typeOfChange"]
	);

	return $processFunctionResponse;	
}

function queue__shutdown_request_9001($message, $workerQueue) {
	
	return true;
}

// TAG
function queue__override_adjustment_for_delivery_set(&$message, &$workerQueue) {

	$details = getOverrideAdjustmentForDeliveryRequest($message["content"]["delivery_notification_set_id"]);


	// TAG
	//////////////////////////////////////////
    // 2 Hour hard cap on expiry of override
    // 1 Hour hard cap on expiry of override
    $validTillNextHours = 1;
	//////////////////////////////////////////

	$details["adjustment_minutes"] = intval($details["adjustment_minutes"]);

	list($locationsByTerminalConcourse, $namesByTerminalConcourse) = getLocationIdsAndNamesByTerminalConcoursePairing($details["airportIataCode"]);

	if(empty($locationsByTerminalConcourse)) {
		// TAG

		return "";
	}

	$validTillTimestamp = $details["requested_at_timestamp"]+($validTillNextHours*60*60);

	$overridesSet = [];
	if(strcasecmp($details["adjustment_direction"], 'd')==0) {

		$details["adjustment_minutes"] = -1*$details["adjustment_minutes"];
	}

    $bulkCacheBuild = [];
    $bulkCacheBuildExpire = [];

	foreach($locationsByTerminalConcourse as $key => $locationIdList) {

		if(!in_array($key, $details["terminalconcourses"])) {

			continue;
		}

		foreach($locationIdList as $locationId) {

			foreach($details["retailerIds"] as $uniqueId) {

				$cacheKeyIndex = $uniqueId . '__' . $locationId;
				$bulkCacheBuild[$cacheKeyIndex] = $details["adjustment_minutes"]*60;
				$bulkCacheBuildExpire[$cacheKeyIndex] = $validTillTimestamp;

				$retailerInfo = getRetailerInfo($uniqueId);

				if(!isset($overridesSet[$namesByTerminalConcourse[$key]])
					|| !in_array($retailerInfo["retailerName"] . ' (' . $retailerInfo["location"]["locationDisplayName"] . ')', $overridesSet[$namesByTerminalConcourse[$key]])) {

					$overridesSet[$namesByTerminalConcourse[$key]][] = $retailerInfo["retailerName"] . ' (' . $retailerInfo["location"]["locationDisplayName"] . ')';
				}
			}
		}
	}

	setCacheBulkOverrideAdjustmentForDeliveryTimeInSeconds($details["airportIataCode"], $bulkCacheBuild, $bulkCacheBuildExpire);


    $slack = createOrderNotificationSlackMessageByAirportIataCode($details["airportIataCode"]);
	//$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
	$slack->setText('Delivery Override Adjustment - Set');

	$attachment = $slack->addAttachment();
	$attachment->addField("Airport:", $details["airportIataCode"], true);

	if($details["adjustment_minutes"]==0) {

		$attachment->addField("Adjustment:", 'Removed adjustment', false);
	}
	else if(strcasecmp($details["adjustment_direction"], 'd')==0) {

		$attachment->addField("Expires:", date("g:i A", $validTillTimestamp), true);
		$attachment->addField("Adjustment:", 'decrease by ' . abs($details["adjustment_minutes"]) . ' mins', false);
	}
	else {

		$attachment->addField("Expires:", date("g:i A", $validTillTimestamp), true);
		$attachment->addField("Adjustment:", 'increase by ' . abs($details["adjustment_minutes"]) . ' mins', false);
	}

	foreach($overridesSet as $toLocationDisplay => $info) {

		$attachment->addField("From Retailer(s):", implode(", ", $info), false);
		$attachment->addField("To Terminal and Concourse:", $toLocationDisplay, false);
		$attachment->addFieldSeparator();
	}

	try {
		
		$slack->send();
	}
	catch (Exception $ex) {

        //json_error("AS_1014", "", "Slack post failed delivery override adjustment update! (" . json_encode($processFunctionResponse) . ") -- " . $ex->getMessage(), 2, 1);
        json_error("AS_1014", "", "Slack post failed delivery override adjustment update! (" . json_encode($details) . ") -- " . $ex->getMessage(), 2, 1);
	}

	unset($slack);

	// Set message on queue
	// Set it for validTillNextHours + 1 minute
	// When received it will check expire hash, identify any that was expired in last 2 mins
	// Slack the list of Airport, Terminal-Concourse, Retailer Name => ord-channel
	// TAG
	if($details["adjustment_minutes"] != 0) {

	    try {

	        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

	        $workerQueue->sendMessage(
	                array("action" => "override_adjustment_for_delivery_expiry_notification",
	                      "content" =>
	                        array(
	                        	"airportIataCode" => $details["airportIataCode"],
	                        	"validTillTimestamp" => $validTillTimestamp,
	                            "timestamp" => time()
	                        )
	                    ),($validTillTimestamp-time()+60)
	                );
	    }
	    catch (Exception $ex) {

			$response = json_decode($ex->getMessage(), true);
			json_error($response["error_code"], "", "Queue message to notify delivery time expiry failed " . $response["error_message_log"], 1, 1);
		}
	}
}

function queue__override_adjustment_for_delivery_expiry_notification(&$message, &$workerQueue) {

	$airportIataCode = $message["content"]["airportIataCode"];
	$validTillTimestamp = intval($message["content"]["validTillTimestamp"]);

	list($locationsByTerminalConcourse, $namesByTerminalConcourse) = getLocationIdsAndNamesByTerminalConcoursePairing($airportIataCode);

	// TAG
	list($hashKeys, $hashKeyExpires) = getAllCacheOverrideAdjustmentForDelivery(
		$airportIataCode
	);

	$expiredOverrides = [];
	foreach($hashKeyExpires as $key => $expireTimestamp) {

		if($expireTimestamp == $validTillTimestamp
			&& $hashKeys[$key] != 0) {

			list($uniqueId, $locationId) = explode("__", $key);

			$locationInfo = getTerminalGateMapByLocationId($airportIataCode, $locationId);
			$keyTerminalConcourse = $locationInfo->get("terminal") . '-' . $locationInfo->get('concourse');

			$retailerInfo = getRetailerInfo($uniqueId);

			if(!isset($expiredOverrides[$namesByTerminalConcourse[$keyTerminalConcourse]])
				|| !in_array($retailerInfo["retailerName"] . ' (' . $retailerInfo["location"]["locationDisplayName"] . ')', $expiredOverrides[$namesByTerminalConcourse[$keyTerminalConcourse]])) {

				$expiredOverrides[$namesByTerminalConcourse[$keyTerminalConcourse]][] = $retailerInfo["retailerName"] . ' (' . $retailerInfo["location"]["locationDisplayName"] . ')';
			}
		}
	}

	if(empty($expiredOverrides)
		|| count_like_php5($expiredOverrides)==0) {

		return "";
	}

    $slack = createOrderNotificationSlackMessageByAirportIataCode($airportIataCode);
	//$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
	$slack->setText('Delivery Override Adjustment - Expired');

	$attachment = $slack->addAttachment();
	$attachment->addField("Airport:", $airportIataCode, false);

	foreach($expiredOverrides as $toLocationDisplay => $info) {

		$attachment->addField("From Retailer(s):", implode(", ", $info), false);
		$attachment->addField("To Terminal and Concourse:", $toLocationDisplay, false);
		$attachment->addFieldSeparator();
	}

	try {
		
		$slack->send();
	}
	catch (Exception $ex) {
		
		json_error("AS_1014", "", "Slack post failed delivery override adjustment update! (" . json_encode($message) . ") -- " . $ex->getMessage(), 2, 1);
	}

	unset($slack);
	return "";
}

function queue__log_delivery_status_active(&$message, &$workerQueue) {
	if ($GLOBALS['logsPdoConnection'] instanceof PDO)
	{
		$logService = \App\Background\Services\LogServiceFactory::create();
		$logService->logDeliveryStatusChangedToActive(
			$message["content"]["airportIataCode"],
			$message["content"]["action"],
			(int)$message["content"]["timeStamp"]
		);
		unset($logService);
	}
}

function queue__log_delivery_status_inactive(&$message, &$workerQueue) {
	if ($GLOBALS['logsPdoConnection'] instanceof PDO)
	{
		$logService = \App\Background\Services\LogServiceFactory::create();
		$logService->logDeliveryStatusChangedToInactive(
			$message["content"]["airportIataCode"],
			$message["content"]["action"],
            (int)$message["content"]["timeStamp"]
		);
		unset($logService);
	}
}

function queue__log_order_delivery_statuses(&$message, &$workerQueue) {
    if ($GLOBALS['logsPdoConnection'] instanceof PDO)
    {
        $logService = \App\Background\Services\LogServiceFactory::create();
        $logService->logOrderDeliveryStatus(
            $message["content"]["airportIataCode"],
            $message["content"]["action"],
            $message["content"]["timestamp"],
            $message["content"]["orderSequenceId"]
        );
        unset($logService);
    }
}

?>
