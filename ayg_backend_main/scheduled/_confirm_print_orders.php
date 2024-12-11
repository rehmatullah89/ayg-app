<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Httpful\Request;

// execute_confirm_print_orders($orderId);

function execute_confirm_print_orders($orderId) {

	// error_log("::ORDER_WORKER:: Inside Confirm Print Orders...");
	
	// $GLOBALS['sqs_client'] = getSQSClientObject();

	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
	}
	catch (Exception $ex) {

		return json_decode($ex->getMessage(), true);
	}

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch Order object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$orderObject = parseExecuteQuery(["objectId" => $orderId], "Order", "", "", ["retailer", "user", "sessionDevice", "sessionDevice.userDevice"], 1);

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Confirm Print Orders skipped as order was cancelled.", 3, 1);
		return "";
	}

	else if(!in_array($orderObject->get('status'), listStatusesForAwaitingConfirmation())) {
		
		// Order already confirmed
		$error_array = order_processing_error($orderObject, "AS_2007", "", "Print order is already confirmed!  orderId=(" . $orderId . ") -- ", 3, 1, "", 1);

		json_error($error_array["error_code"], "", $error_array["error_message_log"], $error_array["error_message_log"], 1, 1);

		return "";
	}

	$uniqueRetailerId = $orderObject->get('retailer')->get('uniqueId');
	$user = $orderObject->get('user');
	$googleJobId = $orderObject->get('orderPrintJobId');
	$submitTimestamp = $orderObject->get('submitTimestamp');
	///////////////////////////////////////////////////////////////////////////////////////


	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch POS Config
	///////////////////////////////////////////////////////////////////////////////////////
	$objectParseQueryPOSConfig = parseExecuteQuery(array("retailer" => $orderObject->get('retailer')), "RetailerPOSConfig", "", "", [], 1);
			
	// Get Google Print Id
	$googlePrinterId = $objectParseQueryPOSConfig->get('printerId');
	///////////////////////////////////////////////////////////////////////////////////////


	///////////////////////////////////////////////////////////////////////////////////////
	// Connect to Google Cloud Print
	///////////////////////////////////////////////////////////////////////////////////////
	try {
				
		$gcp = new GoogleCloudPrint();
		$token = $gcp->getAccessTokenByRefreshToken($GLOBALS['GCP_urlconfig']['refreshtoken_url'],http_build_query($GLOBALS['GCP_refreshTokenConfig']));

		if(empty($token)) {

			json_error("AS_515", "", "GooglePrint Token fetch failed for printerId (" . $googlePrinterId . ")", 1, 1);
			throw new Exception("GooglePrint Token fetch failed for printerId (" . $googlePrinterId . ")");
		}

		$gcp->setAuthToken($token);
	}
	catch (Exception $ex) {
			
		return order_processing_error($orderObject, "AS_317", "", "Google Print Access Token! OrderId: " . $orderId . ", Error(s): " . $ex->getMessage(), 1, 1);
	}
	///////////////////////////////////////////////////////////////////////////////////////
	

	///////////////////////////////////////////////////////////////////////////////////////
	// Check Print Status
	///////////////////////////////////////////////////////////////////////////////////////

	// Get Google Job Status
	try {
		
		$jobStatus = $gcp->jobStatus($googlePrinterId, $googleJobId);
	}
	catch (Exception $ex) {
		
		// Log it but don't exit
		return order_processing_error($orderObject, "AS_2002", "", "Job Status check failed! googlePrinterId=(" . $googlePrinterId . "), googleJobId=(" . $googleJobId . ") orderId=(" . $orderId . ") -- " . $ex->getMessage(), 3, 1, "", 1);
	}
	///////////////////////////////////////////////////////////////////////////////////////

	
	///////////////////////////////////////////////////////////////////////////////////////
	// If Printed, mark it as confirmed
	///////////////////////////////////////////////////////////////////////////////////////
	if(strcasecmp($jobStatus, "DONE")==0) {
		
		///////////////////////////////////////////////////////////////////////////////////////
		////////////////////// ORDER STATUS :: Accepted by Retailer ///////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////

		$response = orderStatusChange_ConfirmedByRetailer($orderObject);

		if(is_array($response)) {

			return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
		}

		///////////////////////////////////////////////////////////////////////////////////////

		$orderObject->save();


		////////////////////////////////////////////////////////////////////////////////////
		// SENDGRID Receipt -- Put on Queue to mark completion after pickup
		////////////////////////////////////////////////////////////////////////////////////
		try {

			$workerQueue->sendMessage(
					array("action" => "order_email_receipt", 
						  "content" => 
						  	array(
						  		"orderId" => $orderId,
				  			)
						)
					);
		}
		catch (Exception $ex) {

			$response = json_decode($ex->getMessage(), true);
			return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
		}
		//////////////////////////////////////////////////////////////////////////////////////////


		///////////////////////////////////////////////////////////////////////////////////////
		// If Pickup Order then put on SQS for auto completion after etaTimestamp
		///////////////////////////////////////////////////////////////////////////////////////

		if(strcasecmp($orderObject->get('fullfillmentType'), "p")==0) {

			////////////////////////////////////////////////////////////////////////////////////
			// Put on queue to mark completion after pickup
			////////////////////////////////////////////////////////////////////////////////////
			try {

				$workerQueue->sendMessage(
						array("action" => "order_pickup_mark_complete", 
							  "processAfter" => ["timestamp" => $orderObject->get('etaTimestamp')],
							  "content" => 
							  	array(
							  		"orderId" => $orderId,
							  		"etaTimestamp" => $orderObject->get('etaTimestamp')
					  			)
							),
							// DelaySeconds for 1 minute
							$workerQueue->getWaitTimeForDelay($orderObject->get('etaTimestamp'))
						);
			}
			catch (Exception $ex) {

				$response = json_decode($ex->getMessage(), true);
				return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
			}
			//////////////////////////////////////////////////////////////////////////////////////////
		}

		///////////////////////////////////////////////////////////////////////////////////////
		// If Delivery Order then put on SQS for delivery process to take over
		///////////////////////////////////////////////////////////////////////////////////////

		else {

			////////////////////////////////////////////////////////////////////////////////////
			// Put order on the queue for processing
			////////////////////////////////////////////////////////////////////////////////////
			try {

				$workerQueue->sendMessage(
						array("action" => "order_delivery_assign_delivery", 
							  "content" => 
							  	array(
							  		"orderId" => $orderId,
					  			)
							)
						);
			}
			catch (Exception $ex) {

				$response = json_decode($ex->getMessage(), true);
				return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
			}
			//////////////////////////////////////////////////////////////////////////////////////////
		}
		///////////////////////////////////////////////////////////////////////////////////////
	}
	///////////////////////////////////////////////////////////////////////////////////////


	///////////////////////////////////////////////////////////////////////////////////////
	// Delayed Print Handling
	///////////////////////////////////////////////////////////////////////////////////////
	// Else check if it has been more than X mins, if so send notifications to Slack channel
	// else {
		
	// 	// Log it but don't exit
	// 	return order_processing_error($orderObject, "AS_2002", "", "Job Status is delayed print! googlePrinterId=(" . $googlePrinterId . "), googleJobId=(" . $googleJobId . ") orderId=(" . $orderId . ") -- ", 3, 1, "", 1);
	// }

	return "";
}

?>