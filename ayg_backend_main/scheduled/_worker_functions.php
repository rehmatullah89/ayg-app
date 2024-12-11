<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

// execute_dead_letter_queue();
// execute_delayed_order_confirmation();

function execute_dead_letter_queue() {
	
	try {

		$deadletterCount = $GLOBALS['workerDeadLetterQueue']->getQueueMessageCount();
	}
	catch (Exception $ex) {
	
		throw new Exception(json_encode(json_error_return_array("AS_1000", "", " Deadletter Queue Message pull failed! - " . $ex->getMessage(), 1)));
	}

	// If message found in Dead Letter queue, then slack it
	if(!is_null($deadletterCount)
		&& $deadletterCount > 0) {

		///////////////////////////////////////////////////////////////////////////////////////
		// Prepare for Slack post
		///////////////////////////////////////////////////////////////////////////////////////

		// 100 is max for SQS and IronMQ
		if($deadletterCount >= 100) {

			$deadletterCount = "100+";
		}
		
		// Slack it
		$slack = new SlackMessage($GLOBALS['env_SlackWH_deadletterAlerts'], 'env_SlackWH_deadletterAlerts');
		$slack->setText("Dead Letter Queue Messages found");
		
		$attachment = $slack->addAttachment();
		$attachment->addField("Count:", $deadletterCount, false);
		
		try {
			
			$slack->send();
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());				
			
			// $error_array = json_decode($ex->getMessage(), true);
			// return json_encode(json_error_return_array($error_array["error_code"], "", $error_array["error_message_log"] . " Dead letter alert failed to post on Slack! - ", 1, 1));
		}
	}
}

function execute_flight_api_calls_daily_count() {

	$nowTimestamp = time();
	$eodTimestamp = mktime(23, 59, 59, date("n", $nowTimestamp), date("j", $nowTimestamp), date("Y", $nowTimestamp));

	$zLogFlightAPICount = parseExecuteQuery(["__GTE__callTimestamp" => $eodTimestamp-24*60*60+1, "__LTE__callTimestamp" => $eodTimestamp], "zLogFlightAPI", "", "", [], 1, false, [], 'count');

	// If Daily count is greater than 1000
	if($zLogFlightAPICount >= 1000) {

		///////////////////////////////////////////////////////////////////////////////////////
		// Prepare for Slack post
		///////////////////////////////////////////////////////////////////////////////////////

		// Slack it
		$slack = new SlackMessage($GLOBALS['env_SlackWH_counterAlerts'], 'env_SlackWH_counterAlerts');
		$slack->setText("Flight API Daily Count");
		
		$attachment = $slack->addAttachment();
		$attachment->addField("Daily for:", date("Y-m-d", $eodTimestamp), true);
		$attachment->addField("Type:", 'Flight API', true);
		$attachment->addField("Count:", $zLogFlightAPICount, false);
		
		try {
			
			$slack->send();
		}
		catch (Exception $ex) {
			
			throw new Exception($ex->getMessage());
		}
	}	
}

function execute_delayed_refund_check() {

    $orderDelayedRefundList = parseExecuteQuery(array("isCompleted" => false, "__LTE__createdAt" => DateTime::createFromFormat("Y-m-d H:i:s", gmdate("Y-m-d H:i:s", (time()-6*60*60)))), "OrderDelayedRefund", "createdAt", "", ["order", "order.user", "order.retailer", "order.retailer.location"]);

	foreach($orderDelayedRefundList as $orderDelayedRefund) {

		///////////////////////////////////////////////////////////////////////////////////////
		// Prepare for Slack post
		///////////////////////////////////////////////////////////////////////////////////////

		$customerName = $orderDelayedRefund->get('order')->get('user')->get('firstName') . ' ' . $orderDelayedRefund->get('order')->get('user')->get('lastName');
		$retailerLocation = $orderDelayedRefund->get('order')->get('retailer')->get('location')->get('locationDisplayName');

        $orderObject=$orderDelayedRefund->get('order');

		$submissionDateTime = date("M j, g:i a", $orderObject->get('submitTimestamp'));
		$delayedByInMins = ((time()-$orderDelayedRefund->getCreatedAt()->getTimestamp())/60) . ' mins';

		// TAG
		// Slack it
		$slack = new SlackMessage($GLOBALS['env_SlackWH_orderProcErrors'], 'env_SlackWH_orderProcErrors');
		$slack->setText("Delayed Refund Pending!");
		
		$attachment = $slack->addAttachment();
		$attachment->addField("Order:", $orderDelayedRefund->get('order')->getObjectId() . ' - ' . $orderObject->get('orderSequenceId'), false);
		$attachment->addField("Customer:", $customerName, false);
		$attachment->addField("Retailer:", $orderDelayedRefund->get('order')->get('retailer')->get('retailerName') . " (" . $retailerLocation . ")", false);
		$attachment->addField("Submitted:", $submissionDateTime, false);
		$attachment->addField("Status:", orderStatusToPrint($orderDelayedRefund->get('order')), false);
		$attachment->addField("Delayed by:", $delayedByInMins, true);
		
		try {
			
			$slack->send();
		}
		catch (Exception $ex) {
			
			throw new Exception($ex->getMessage());				
		}
	}	
}

function execute_delayed_order_confirmation() {

	// Notify if Delayed for more these mins in printing
	$notifyIfDelayedbyMins = 1;
	
	// Calculate timestamp for Delay mins ago
	$timeXMinsAgo = time() - ($notifyIfDelayedbyMins * 60);

	$order = parseExecuteQuery(["__LTE__submitTimestamp" => $timeXMinsAgo, "status" => listStatusesForSubmittedOrAwaitingConfirmation()], "Order", "", "submitTimestamp", ["retailer", "retailer.location", "user", "sessionDevice", "sessionDevice.userDevice"]);

	// If Submit Timestamp of Order is less than Delay timestamp, message on Slack
	$posTabletDelayedOrders = [];
	foreach($order as $orderObject) {

		// Skip offset order status
		// @todo - change \App\Consumer\Entities\Order to background or create common
		// @todo - checked if that is needed
		//if(strcasecmp($orderObject->get('fullfillmentType'), "d") == 0 && in_array($orderObject->get('statusDelivery'), [\App\Consumer\Entities\Order::STATUS_DELIVERY_NOT_PROCESSED,\App\Consumer\Entities\Order::STATUS_NOT_ORDERED])) {
		//	continue;
		//}

		// we are not notifing about orders that are not yet submitted
		if ($orderObject->get('status')==\App\Consumer\Entities\Order::STATUS_SCHEDULED){
			continue;
		}


		// Fetch Customer Name
		$customerName = $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName');

		//$delayedByInMins = round((time()-$orderObject->get('submitTimestamp'))/60) . ' mins';

        $timeWhenRetailerIsNotified = $orderObject->get('etaTimestamp') -$orderObject->get('fullfillmentTimeInSeconds');
        $delayedByInMins = round((time() - $timeWhenRetailerIsNotified) / 60) . ' mins';

		$delayType = "Retailer Confirmation";

		// Add to Order Delay
		addUponOrderDelay($orderObject, $delayType, $delayedByInMins);

		///////////////////////////////////////////////////////////////////////////////////////
		// Send on Slack
		///////////////////////////////////////////////////////////////////////////////////////

		$submissionDateTime = date("M j, g:i a", $orderObject->get('submitTimestamp'));
		$retailerLocation = $orderObject->get('retailer')->get('location')->get('airportIataCode') . ' ' . $orderObject->get('retailer')->get('location')->get('gateDisplayName');
		$confirmType = getPOSType($orderObject->get('retailer'));

		// If Tablet order and status is awaiting confirmation
		// Create a list so we can buzz the tablet
		if(strcasecmp($confirmType, 'Tablet')==0
			&& in_array($orderObject->get('status'), listStatusesForAwaitingConfirmation())) {

			$posTabletDelayedOrders[$orderObject->get('retailer')->getObjectId()]['retailer'] = $orderObject->get('retailer');
			$posTabletDelayedOrders[$orderObject->get('retailer')->getObjectId()]['orderCount'] = isset($posTabletDelayedOrders[$orderObject->get('retailer')->getObjectId()]['orderCount']) ? $posTabletDelayedOrders[$orderObject->get('retailer')->getObjectId()]['orderCount'] + 1 : 1;
		}

		// Slack it
		//$slack = new SlackMessage($GLOBALS['env_SlackWH_orderInvPrintDelay'], 'env_SlackWH_orderInvPrintDelay');
        $slack = createOrderInvPrintDelaySlackMessageByAirportIataCode($orderObject->get('retailer')->get('location')->get('airportIataCode'));
		$slack->setText("Delayed Order Confirmation from Retailer!");
		
		$attachment = $slack->addAttachment();
		$attachment->addField("Order:", $orderObject->getObjectId() . ' - ' . $orderObject->get('orderSequenceId'), false);
		$attachment->addField("Customer:", $customerName, false);
		$attachment->addField("Retailer:", $orderObject->get('retailer')->get('retailerName') . " (" . $retailerLocation . ")", false);
		$attachment->addField("Submitted:", $submissionDateTime, false);
		$attachment->addField("Status:", orderStatusToPrint($orderObject), false);
		$attachment->addField("Confirm Type:", $confirmType, true);
		$attachment->addField("Delayed by:", $delayedByInMins, true);
		
		try {
			
			$slack->send();
		}
		catch (Exception $ex) {
			
			throw new Exception($ex->getMessage());				
			// json_error("AS_2003", "", "Slack post failed informing Delay in Order Retailer Acceptance! orderId=(" . $orderId . ") -- " . $ex->getMessage(), 2, 1);
		}
		///////////////////////////////////////////////////////////////////////////////////////
	}

	// Message the POS Tablet retailers to accept orders
	if(count_like_php5($posTabletDelayedOrders) > 0) {

		// Keeps track of MobiLock ids that have been pinged (applicable when same tablet is used for multiple retailers)
		$pingedMobiLockIds = [];
		foreach($posTabletDelayedOrders as $retailer) {

			// Fetch POS Config
			$objectParseQueryPOSConfig = parseExecuteQuery(["retailer" => $retailer['retailer']], "RetailerPOSConfig", "", "", ["retailer"], 1);

			if(!empty($objectParseQueryPOSConfig->get('tabletMobilockId'))
				&& !in_array($objectParseQueryPOSConfig->get('tabletMobilockId'), $pingedMobiLockIds)) {

				$pingedMobiLockIds[] = $tabletMobilockId = $objectParseQueryPOSConfig->get('tabletMobilockId');
			
				// Buzz failed
			    if(!sendBuzzToPOSTablet($tabletMobilockId, 'execute_delayed_order_confirmation')) {

			    	throw new Exception (json_encode(json_error_return_array("AS_1054", "", "Buzz to retailer (" . $retailer['retailer']->get('uniqueId') . ") failed for order delay notification", 1, 1)));
			    }
			}
		}
	}	
}

?>
