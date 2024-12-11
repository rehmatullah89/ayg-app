<?php

require_once 'dirpath.php';

require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/functions_orders.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;

function queue__tempDelivery_FindDelivery(&$message) {

	$orderId = $message["content"]["orderId"];

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch Order object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Delivery - Find delivery skipped as order was cancelled.", 3, 1);
		return "";
	}

	// Check if the order status is still In Progress
	else if(!in_array($orderObject->get('status'), listStatusesForInProgress())) {

		return order_processing_error($orderObject, "AS_320", "", "Order Status Type not in Progress" .' - FindDelivery - ' . " OrderId - " . $orderObject->getObjectId(), 1, 1);
	}


	///////////////////////////////////////////////////////////////////////////////////////
	////////////////////// DELIVERY STATUS :: Find delivery /////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////

	$response = deliveryStatusChange_FindDelivery($orderObject);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	///////////////////////////////////////////////////////////////////////////////////////


	$orderObject->save();


	////////////////////////////////////////////////////////////////////////////////////
	// Put on queue for 1-3 mins to mimic delivery assigning
	////////////////////////////////////////////////////////////////////////////////////
	// Put order on the queue for POS confirm
	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

		$workerQueue->sendMessage(
				array("action" => "tempDelivery_AssignedDelivery", 
					  "content" => 
					  	array(
					  		"orderId" => $orderId,
			  			)
					),
					// DelaySeconds for 1-3 minutes
					rand(1,3) * 60
				);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	//////////////////////////////////////////////////////////////////////////////////////////

	return "";
}

function queue__tempDelivery_AssignedDelivery(&$message) {

	$orderId = $message["content"]["orderId"];

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch Order object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Delivery - Assigned delivery skipped as order was cancelled.", 3, 1);
		return "";
	}

	// Check if the order status is still In Progress
	else if(!in_array($orderObject->get('status'), listStatusesForInProgress())) {

		return order_processing_error($orderObject, "AS_320", "", "Order Status Type not in Progress" .' - AssignedDelivery - ' . " OrderId - " . $orderObject->getObjectId(), 1, 1);
	}


	///////////////////////////////////////////////////////////////////////////////////////
	//////////////////// DELIVERY STATUS :: Assigned delivery ///////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////

	$response = deliveryStatusChange_AssignedDelivery($orderObject);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	///////////////////////////////////////////////////////////////////////////////////////


	$orderObject->save();


	////////////////////////////////////////////////////////////////////////////////////
	// Put on queue for less than fullfillmentProcessTimeInSeconds before etaTimestamp to mimic delivery arriving
	////////////////////////////////////////////////////////////////////////////////////
	// Put order on the queue for POS confirm
	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

		$workerQueue->sendMessage(
				array("action" => "tempDelivery_ArrivedDelivery", 
					  "processAfter" => ["timestamp" => timestampDeliveryPickupByForPickup($orderObject)],
					  "content" => 
					  	array(
					  		"orderId" => $orderId,
			  			)
					),
					// DelaySeconds
					$workerQueue->getWaitTimeForDelay(timestampDeliveryPickupByForPickup($orderObject))
				);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	//////////////////////////////////////////////////////////////////////////////////////////

	return "";
}

function queue__tempDelivery_ArrivedDelivery(&$message) {

	$orderId = $message["content"]["orderId"];

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch Order object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Delivery - Arrived delivery skipped as order was cancelled.", 3, 1);
		return "";
	}

	// Check if the order status is still In Progress
	else if(!in_array($orderObject->get('status'), listStatusesForInProgress())) {

		return order_processing_error($orderObject, "AS_320", "", "Order Status Type not in Progress" .' - ArrivedDelivery - ' . " OrderId - " . $orderObject->getObjectId(), 1, 1);
	}


	///////////////////////////////////////////////////////////////////////////////////////
	//////////////////// DELIVERY STATUS :: Arrived delivery ////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////

	$response = deliveryStatusChange_ArrivedDelivery($orderObject);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	///////////////////////////////////////////////////////////////////////////////////////


	$orderObject->save();


	////////////////////////////////////////////////////////////////////////////////////
	// Put on queue with delay time to mimic picking up of order
	////////////////////////////////////////////////////////////////////////////////////
	// Put order on the queue for POS confirm
	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

		$workerQueue->sendMessage(
				array("action" => "tempDelivery_PickedByDelivery", 
					  "content" => 
					  	array(
					  		"orderId" => $orderId,
			  			)
					),
					// DelaySeconds
					rand(3,5) * 60
				);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	//////////////////////////////////////////////////////////////////////////////////////////

	return "";
}

function queue__tempDelivery_PickedByDelivery(&$message) {

	$orderId = $message["content"]["orderId"];

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch Order object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Delivery - Picked by delivery skipped as order was cancelled.", 3, 1);
		return "";
	}

	// Check if the order status is still In Progress
	else if(!in_array($orderObject->get('status'), listStatusesForInProgress())) {

		return order_processing_error($orderObject, "AS_320", "", "Order Status Type not in Progress" .' - PickedUpDelivery - ' . " OrderId - " . $orderObject->getObjectId(), 1, 1);
	}


	///////////////////////////////////////////////////////////////////////////////////////
	//////////////////// DELIVERY STATUS :: Picked by delivery //////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////

	$response = deliveryStatusChange_PickedupByDelivery($orderObject);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}

	$message = "Your order has been picked up for delivery from " . $orderObject->get('retailer')->get('retailerName') . ".";

	$response = sendOrderNotification($orderObject, $message);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}


	////////////////////////////////////////////////////////////////////////////////////
	// Put on queue with delay to walk over, so any time on or before ETA, minus random 0 to 3 mins
	////////////////////////////////////////////////////////////////////////////////////
	// Put order on the queue for POS confirm
	$processAfterTimestamp = ($orderObject->get('etaTimestamp')-(rand(0,3)*60));
	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

		$workerQueue->sendMessage(
				array("action" => "tempDelivery_DeliveredByDelivery", 
					  "processAfter" => ["timestamp" => $processAfterTimestamp],
					  "content" => 
					  	array(
					  		"orderId" => $orderId,
			  			)
					),
					// DelaySeconds - Time left before delivery needs to be made minus random 1 to 3 mins
					$workerQueue->getWaitTimeForDelay($processAfterTimestamp)
				);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	//////////////////////////////////////////////////////////////////////////////////////////


	$orderObject->save();

	return "";
}

function queue__tempDelivery_DeliveredByDelivery(&$message) {

	$orderId = $message["content"]["orderId"];

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch Order object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Delivery - Deliverd by delivery skipped as order was cancelled.", 3, 1);
		return "";
	}

	// Check if the order status is still In Progress
	else if(!in_array($orderObject->get('status'), listStatusesForInProgress())) {

		return order_processing_error($orderObject, "AS_320", "", "Order Status Type not in Progress" .' - Delivered - ' . " OrderId - " . $orderObject->getObjectId(), 1, 1);
	}


	///////////////////////////////////////////////////////////////////////////////////////
	/////////////////////////// ORDER STATUS :: Completed /////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////

	$response = orderStatusChange_Completed($orderObject);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}
	///////////////////////////////////////////////////////////////////////////////////////

	// Delivery status must be set second

	///////////////////////////////////////////////////////////////////////////////////////
	///////////////// DELIVERY STATUS :: Delivered by delivery //////////////////////////////
	///////////////////////////////////////////////////////////////////////////////////////

	$response = deliveryStatusChange_DeliveredyByDelivery($orderObject);

	if(is_array($response)) {

		return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
	}

	$orderObject->save();

	return "";
}
