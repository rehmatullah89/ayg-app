<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use App\Tablet\Helpers\QueueMessageHelper;
use App\Tablet\Services\QueueServiceFactory;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

// execute_ping_slack_delivery();
// execute_check_slack_delivery_delays();

function execute_ping_slack_delivery()
{

    $deliveryCoveragePeriodOn = true;

    // Check Delivery coverage hours
    list($coverageStartTimestamp, $coverageStopTimestamp) = getDeliveryCoveragePeriod(time());


    if (!isActiveDeliveryCoveragePeriod($coverageStartTimestamp, $coverageStopTimestamp, time())) {

        // If Delivery's are not expected to be online
        $deliveryCoveragePeriodOn = false;
    }

    // Find all retailers
    $objectParseQueryPOSConfig = parseExecuteQuery(array("continousPingCheck" => true), "RetailerPOSConfig", "", "",
        array("retailer"));

    // Check if retailer is open
    $retailersOpen = false;

    foreach ($objectParseQueryPOSConfig as $obj) {

        list($isClosed, $errorMsg) = isRetailerClosed($obj->get('retailer'), 0, 0);

        // Is retailer not closed
        if ($isClosed == 0) {

            // If so, then we know at least one is open so check Delivery availability
            $retailersOpen = true;
            break;
        }
    }

    // If no retailers are open
    if ($retailersOpen == false) {

        // No need to check Delivery availability
        return;
    }

    // Find all Slack delivery that are active
    $zDeliverySlackUser = parseExecuteQuery(["isActive" => true], "zDeliverySlackUser");

    // If no delivery have been enabled during Delivery Coverage period
    if (count_like_php5($zDeliverySlackUser) == 0
        && $deliveryCoveragePeriodOn == true
    ) {

        // Slack it
        $slack = new SlackMessage($GLOBALS['env_SlackWH_deliveryPingFail'], 'env_SlackWH_deliveryPingFail');
        $slack->setText("Delivery User: No Delivery Online");

        $attachment = $slack->addAttachment();
        $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
        $attachment->addField("Error:", "No Delivery Online", false);

        try {

            $slack->send();
        } catch (Exception $ex) {

            throw new Exception($ex->getMessage());
        }
    }

    // Else Ping the Slack delivery
    foreach ($zDeliverySlackUser as $deliveryUser) {

        $slackUserId = $deliveryUser->get('slackUserId');

        // Sleep for 1 second to avoid Slack's flood detection
        sleep(1);

        list($ping, $errorMsg) = checkTabletStatus($slackUserId);

        $pingTimestamp = time();

        if ($GLOBALS['env_NoPingCheckForDeliveryUser'] == true) {

            // Set in Redis cache
            setSlackDeliveryPingTimestamp($deliveryUser->getObjectId(), $pingTimestamp);

            continue;
        }

        // If online, save lastSuccessfulPingTimestamp
        if ($ping == 1) {

            // Set in Redis cache
            setSlackDeliveryPingTimestamp($deliveryUser->getObjectId(), $pingTimestamp);

            // Log Successful Ping to RDS
            $queueService = QueueServiceFactory::create();

            $logDeliveryPingMessage = QueueMessageHelper::getLogDeliveryPingMessage($deliveryUser->get('slackUsername'),
                $pingTimestamp);
            $queueService->sendMessage($logDeliveryPingMessage, 0);
        } // Post on Slack if Delivery Coverage Period is on
        else {
            if ($deliveryCoveragePeriodOn == true) {

                // Sleep for 1 second to avoid Slack's flood detection
                sleep(1);

                $errorMsg = empty($errorMsg) ? "No specific message provided" : json_encode($errorMsg);

                $lastSuccessfulPingTimestamp = getSlackDeliveryPingTimestamp($deliveryUser->getObjectId());

                // Last ping was after Delivery Coverage started
                if ($lastSuccessfulPingTimestamp > $coverageStartTimestamp) {

                    $downForMins = round((time() - $lastSuccessfulPingTimestamp) / 60);
                } else {

                    $downForMins = round((time() - $coverageStartTimestamp) / 60);
                }

                $downForText = formatSecondsIntoHumanIntervals($downForMins * 60);

                $deliveryName = $deliveryUser->get("deliveryName");
                $slackUsername = $deliveryUser->get("slackUsername");
                $airportIataCode = $deliveryUser->get("airportIataCode");

                // Slack it
                $slack = new SlackMessage($GLOBALS['env_SlackWH_deliveryPingFail'], 'env_SlackWH_deliveryPingFail');
                $slack->setText("Delivery User: " . $deliveryName . ' - ' . $slackUsername . " (@" . $airportIataCode . ")");

                $attachment = $slack->addAttachment();
                $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
                $attachment->addField("Down for:", $downForText, false);
                $attachment->addField("Error:", $errorMsg, false);

                try {

                    $slack->send();
                } catch (Exception $ex) {

                    throw new Exception($ex->getMessage());
                }
            }
        }
    }
}

function execute_check_slack_delivery_delays()
{

    // Delivery - Assignment delays
    // 3 mins after order submitTimestamp
    $orderAssignmentDelays =
        parseExecuteQuery(
            [
                "__LTE__submitTimestamp" => (time() - 3 * 60),
                "fullfillmentType" => "d",
                "__LTE__statusDelivery" => getOrderStatusDeliveryFindDelivery(),
                "status" => listStatusesForPendingInProgress()
            ]
            ,
            "Order", "", "", ["retailer", "retailer.location"]);


    foreach ($orderAssignmentDelays as $orderObject) {
        // we are not notifing about orders that are not yet submitted
        if ($orderObject->get('status')==\App\Consumer\Entities\Order::STATUS_SCHEDULED){
            continue;
        }


        // Skip Awaiting retailer confirmation status
        // todo change it to background or create common namespace
        //if(in_array($orderObject->get('status'), listStatusesForAwaitingConfirmation()) || in_array($orderObject->get('statusDelivery'), [\App\Consumer\Entities\Order::STATUS_DELIVERY_NOT_PROCESSED,\App\Consumer\Entities\Order::STATUS_NOT_ORDERED])) {
        //	continue;
        //}

        if (in_array($orderObject->get('status'), listStatusesForAwaitingConfirmation())) {
            continue;
        }


        // Sleep for 2 seconds to avoid Slack's flood detection
        sleep(2);


        $timeWhenRetailerIsNotified = $orderObject->get('etaTimestamp') -$orderObject->get('fullfillmentTimeInSeconds');
        $delayInMins = round((time() - $timeWhenRetailerIsNotified) / 60);
        $delayType = "Delivery Assignment";

        // Add to Order Delay
        addUponOrderDelay($orderObject, $delayType, $delayInMins);

        notifyDeliveryDelayOnSlack($orderObject, $delayInMins . " mins", $delayType);
    }

    // Delivery - Acceptance delays
    // 5 mins after order submitTimestamp
    $orderAssignmentDelays = parseExecuteQuery([
        "__LTE__submitTimestamp" => (time() - 5 * 60),
        "fullfillmentType" => "d",
        "statusDelivery" => getOrderStatusDeliveryAssignedDelivery(),
        "status" => listStatusesForPendingInProgress()
    ], "Order",
        "", "", ["retailer", "retailer.location"],10000,true);


    foreach ($orderAssignmentDelays as $orderObject) {
        // we are not notifing about orders that are not yet submitted
        if ($orderObject->get('status')==\App\Consumer\Entities\Order::STATUS_SCHEDULED){
            continue;
        }



        // Skip offset order status
        //if(in_array($orderObject->get('statusDelivery'), [\App\Consumer\Entities\Order::STATUS_DELIVERY_NOT_PROCESSED,\App\Consumer\Entities\Order::STATUS_NOT_ORDERED])) {
        //continue;
        //}

        // Sleep for 2 seconds to avoid Slack's flood detection
        sleep(2);

        $timeWhenRetailerIsNotified = $orderObject->get('etaTimestamp') -$orderObject->get('fullfillmentTimeInSeconds');
        $delayInMins = round((time() - $timeWhenRetailerIsNotified) / 60);
        $delayType = "Delivery Assignment";

        // Add to Order Delay
        addUponOrderDelay($orderObject, $delayType, $delayInMins);

        notifyDeliveryDelayOnSlack($orderObject, $delayInMins . " mins", $delayType);
    }

    // Delivery - Pickup delays
    // beyond eta minus process time
    $orderPickupDelays = parseExecuteQuery([
        "__LTE__retailerETATimestamp" => (time()),
        "fullfillmentType" => "d",
        "statusDelivery" => getOrderStatusDeliveryAssignedOrAcceptedOrArrivedByDelivery(),
        "status" => listStatusesForPendingInProgress()
    ], "Order",
        "", "", ["retailer", "retailer.location"]);


    foreach ($orderPickupDelays as $orderObject) {
        // we are not notifing about orders that are not yet submitted
        if ($orderObject->get('status')==\App\Consumer\Entities\Order::STATUS_SCHEDULED){
            continue;
        }


        // Skip offset order status
        //if(in_array($orderObject->get('statusDelivery'), [\App\Consumer\Entities\Order::STATUS_DELIVERY_NOT_PROCESSED,\App\Consumer\Entities\Order::STATUS_NOT_ORDERED])) {
        //		continue;
        //	}

        // Sleep for 2 seconds to avoid Slack's flood detection
        sleep(2);

        $delayInMins = round((time() - $orderObject->get("retailerETATimestamp")) / 60);
        $delayType = "Delivery Pickup";

        // Add to Order Delay
        addUponOrderDelay($orderObject, $delayType, $delayInMins);

        notifyDeliveryDelayOnSlack($orderObject, $delayInMins . " mins", $delayType);
        // notifyDeliveryDelayOnSlack($orderObject, round((time()-$orderObject->get("retailerETATimestamp"))/60) . " mins", "Pickup");
    }

    // Delivery - Delivery delays
    // beyond eta time
    $orderDeliveryDelays = parseExecuteQuery([
        "__LTE__etaTimestamp" => (time()),
        "fullfillmentType" => "d",
        "statusDelivery" => [
            getOrderStatusDeliveryPickedupByDelivery(),
            getOrderStatusDeliveryAtDeliveryLocationDelivery()
        ],
        "status" => listStatusesForPendingInProgress()
    ], "Order",
        "", "", ["retailer", "retailer.location"]);


    foreach ($orderDeliveryDelays as $orderObject) {


        // Skip offset order status
        //if(in_array($orderObject->get('statusDelivery'), [\App\Consumer\Entities\Order::STATUS_DELIVERY_NOT_PROCESSED,\App\Consumer\Entities\Order::STATUS_NOT_ORDERED])) {
        //		continue;
        //}

        // Sleep for 2 seconds to avoid Slack's flood detection
        sleep(2);

        $delayInMins = round((time() - $orderObject->get("etaTimestamp")) / 60);
        $delayType = "Delivery";

        // Add to Order Delay
        addUponOrderDelay($orderObject, $delayType, $delayInMins);

        notifyDeliveryDelayOnSlack($orderObject, $delayInMins . " mins", $delayType);
        // notifyDeliveryDelayOnSlack($orderObject, round((time()-$orderObject->get("etaTimestamp"))/60) . " mins", "Delivery");
    }
}

function notifyDeliveryDelayOnSlack($order, $downForMins, $typeOfDelay)
{

    // Slack it
    $slack = createOrderInvPrintDelaySlackMessageByAirportIataCode($order->get('retailer')->get('location')->get('airportIataCode'));
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderInvPrintDelay'], 'env_SlackWH_orderInvPrintDelay');
    $slack->setText("Delayed Delivery Order: " . $order->get("orderSequenceId"));

    $attachment = $slack->addAttachment();
    $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
    $attachment->addField("Order:", $order->get("orderSequenceId"), false);
    $attachment->addField("Delay type:", $typeOfDelay, true);
    $attachment->addField("Delayed by:", $downForMins, true);

    try {

        $slack->send();

    } catch (Exception $ex) {

        throw new Exception($ex->getMessage());
    }
}

function isActiveDeliveryCoveragePeriod($coverageStartTimestamp, $coverageStopTimestamp, $timestamp)
{

    if ($coverageStartTimestamp > $timestamp) {

        // Start time hasn't started yet
        return false;
    } else {
        if ($timestamp > $coverageStopTimestamp) {

            // Delivery coverage stop time has already passed
            return false;
        }
    }

    return true;
}

function getDeliveryCoveragePeriod($timestamp)
{

    // Day of Week, with Sunday = 1
    $deliveryCoveragePeriod = parseExecuteQuery(["dayOfWeek" => strval(date("w", $timestamp) + 1)],
        "zDeliveryCoveragePeriod", "", "", [], 1);

    // Timestamp from when the coverage starts and stos
    $coverageStartTimestamp = strtotime("Today 12:00 am") + $deliveryCoveragePeriod->get("secsSinceMidnightStart");
    $coverageStopTimestamp = strtotime("Today 12:00 am") + $deliveryCoveragePeriod->get("secsSinceMidnightEnd");

    return [$coverageStartTimestamp, $coverageStopTimestamp];
}

?>
