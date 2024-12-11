<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Httpful\Request;

// execute_confirm_pos_orders($orderId);

function execute_confirm_pos_orders($orderId) {

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
	///////////////////////////////////////////////////////////////////////////////////////

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Confirm POS Orders skipped as order was cancelled.", 3, 1);
		return "";
	}

	else if(!in_array($orderObject->get('status'), listStatusesForAwaitingConfirmation())) {
		
		// Order already confirmed
		$error_array = order_processing_error($orderObject, "AS_2007", "", "POS order is already confirmed!  orderId=(" . $orderId . ") -- ", 3, 1, "", 1);

		json_error($error_array["error_code"], "", $error_array["error_message_log"], $error_array["error_message_log"], 1, 1);

		return "";
	}


	///////////////////////////////////////////////////////////////////////////////////////
	////////////////////// ORDER STATUS :: Accepted by Retailer ///////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////

	$response = orderStatusChange_ConfirmedByRetailer($orderObject);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}

	///////////////////////////////////////////////////////////////////////////////////////

	// Manually set; will be replaced by a process of identifying Tablet POS response
	$confirmed = 1;

	if($confirmed == 1) {

		////////////////////////////////////////////////////////////////////////////////////
		// SENDGRID Receipt -- Put on SQS queue to mark completion after pickup
		////////////////////////////////////////////////////////////////////////////////////
		try {

			$workerQueue->sendMessage(
					array("action" => "order_email_receipt", 
						  "content" => 
						  	array(
						  		"orderId" => $orderObject->getObjectId(),
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
			// Put on SQS queue to mark completion after pickup
			////////////////////////////////////////////////////////////////////////////////////
			try {

				$workerQueue->sendMessage(
						array("action" => "order_pickup_mark_complete", 
							  "processAfter" => ["timestamp" => $orderObject->get('etaTimestamp')],
							  "content" => 
							  	array(
							  		"orderId" => $orderObject->getObjectId(),
							  		"etaTimestamp" => $orderObject->get('etaTimestamp')
					  			)
							),
							// DelaySeconds
							$workerQueue->getWaitTimeForDelay($orderObject->get('etaTimestamp'))
						);
			}
			catch (Exception $ex) {

				$response = json_decode($ex->getMessage(), true);
				return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
			}
		}
		///////////////////////////////////////////////////////////////////////////////////////


		///////////////////////////////////////////////////////////////////////////////////////
		// If Delivery Order then put on Queue for delivery process to take over
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
						  		"orderId" => $orderObject->getObjectId(),
				  			)
						)
						);
			}
			catch (Exception $ex) {

				$response = json_decode($ex->getMessage(), true);
				return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
			}
		}
	}

	///////////////////////////////////////////////////////////////////////////////////////
	// Delayed POS Confirmation
	///////////////////////////////////////////////////////////////////////////////////////
	// else {

	// 	///////////////////////////////////////////////////////////////////////////////////////
	// 	// TODO with Andriod app
	// 	///////////////////////////////////////////////////////////////////////////////////////
	// }

	return "";
}

?>