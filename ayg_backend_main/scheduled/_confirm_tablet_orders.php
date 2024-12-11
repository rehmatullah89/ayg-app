<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Httpful\Request;

// execute_confirm_tablet_orders($orderId);

// function execute_confirm_tablet_orders($orderId) {

// 	/*
// 	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        // $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
// 	}
// 	catch (Exception $ex) {

// 		return json_decode($ex->getMessage(), true);
// 	}
// 	*/

// 	///////////////////////////////////////////////////////////////////////////////////////
// 	// Fetch Order object and get initial info
// 	///////////////////////////////////////////////////////////////////////////////////////
// 	$orderObject = parseExecuteQuery(["objectId" => $orderId], "Order", "", "", ["retailer", "user", "sessionDevice", "sessionDevice.userDevice"], 1);

// 	///////////////////////////////////////////////////////////////////////////////////////
// 	// Delayed Confirmation
// 	///////////////////////////////////////////////////////////////////////////////////////
// 	// If order is still in confirmation state
// 	if(in_array($orderObject->get('status'), listStatusesForAwaitingConfirmation())) {
		
// 		// Log it but don't exit
// 		return order_processing_error($orderObject, "AS_2002", "", "Tablet order status is delayed confirmation!  orderId=(" . $orderId . ") -- ", 3, 1, "", 1);
// 	}

// 	// It is confirmed
// 	/*
// 	else {
// 	}
// 	///////////////////////////////////////////////////////////////////////////////////////
// 	*/

// 	return "";
// }

?>