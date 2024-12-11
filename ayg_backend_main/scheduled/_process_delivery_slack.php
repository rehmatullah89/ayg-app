<?php

require_once 'dirpath.php';

require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/functions_orders.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;

function queue_fail__slackDelivery_FindDelivery($message)
{

    // Put another message back on queue with 120 second delay
    try {

        $workerQueue->putMessageBackonQueueWithDelay($message, $workerQueue->getWaitTimeForDelay(time() + 2 * 60));
    } catch (Exception $ex) {

        $response = json_decode($ex->getMessage(), true);
        return json_error_return_array($response["error_code"], "",
            "Assign Delivery order put back on queue failed " . $response["error_message_log"], 1);
    }

    return "";
}

function queue__slackOffsetOrderDelivery_PreNotification($message)
{
    $orderId = $message["content"]["orderId"];
    $fullfillmentTimeInSeconds = $message["content"]["fullfillmentTimeInSeconds"];
    $fullfillmentTimeInSecondsOverriden = $message["content"]["fullfillmentTimeInSecondsOverriden"];

    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array(
        "retailer",
        "etaTimestamp",
        "retailer.location",
        "retailer.retailerType",
        "deliveryLocation",
        "coupon",
        "coupon.applicableUser",
        "user"
    ), 1);

    // Set Airport Timezone
    $currentTimeZone = date_default_timezone_get();
    $airportTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'), $currentTimeZone);


    //SET SLACK VARIABLE VALUES
    $deliveryLocationName = ($orderObject->get('deliveryLocation') != "") ? $orderObject->get('deliveryLocation')->get('locationDisplayName') : ' ';
    $retailerLocation = $orderObject->get('retailer')->get('location')->get('airportIataCode') . ', ' . $orderObject->get('retailer')->get('location')->get('locationDisplayName');//gateDisplayName
    $quotedEstimatesObj = json_decode($orderObject->get('quotedEstimates'));
    $orderTotalAmount = (isset($quotedEstimatesObj->d) && isset($quotedEstimatesObj->d->TotalDisplay)) ? $quotedEstimatesObj->d->TotalDisplay : "N/A";


    $date = new DateTime();
    $orderDeliverTimeStamp = ($orderObject->get('submitTimestamp') + $fullfillmentTimeInSecondsOverriden);
    $orderDeliveryTime = $date->setTimestamp($orderDeliverTimeStamp)->setTimezone(new DateTimeZone($airportTimeZone));

    $date = new DateTime();
    $sendToRetailerTimeStamp = ($orderObject->get('submitTimestamp') + $fullfillmentTimeInSecondsOverriden) - $fullfillmentTimeInSeconds;
    $sendToRetailerTime = $date->setTimestamp($sendToRetailerTimeStamp)->setTimezone(new DateTimeZone($airportTimeZone));

    $date = new DateTime();
    $orderSubmittedAt = $date->setTimestamp($orderObject->get('submitTimestamp'))->setTimezone(new DateTimeZone($airportTimeZone));

    $orderDeliveryTime = (new DateTime('now', new DateTimeZone($airportTimeZone)))->setTimestamp($orderObject->get('etaTimestamp'));
    $sendToRetailerTime = (new DateTime('now', new DateTimeZone($airportTimeZone)))->setTimestamp((int)$orderObject->get('retailerETATimestamp')-(int)$orderObject->get('fullfillmentTimeInSeconds'));



    //slack notification
    $slack = createOrderNotificationSlackMessageByAirportIataCode($orderObject->get('retailer')->get('location')->get('airportIataCode'));
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'order-notification');
    $slack->setText("Scheduled Delivery Order Submitted");
    $attachment = $slack->addAttachment();
    $attachment->addField("Order ID:", $orderObject->get('orderSequenceId'), true);
    $attachment->addField("Retailer:",
        $orderObject->get('retailer')->get('retailerName') . " (" . $retailerLocation . ")", false);
    $attachment->addField("Submitted:", $orderSubmittedAt->format("M j, g:i a"), true);
    $attachment->addField("Send to Retailer At:", $sendToRetailerTime->format("M j, g:i a"), true);
    $attachment->addField("Delivery Time:", $orderDeliveryTime->format("M j, g:i a"), true);
    $attachment->addField("Delivery Location:", $deliveryLocationName, true);
    $attachment->addField("Customer:",
        $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName'), false);
    $attachment->addField("Platform:", getenv('env_EnvironmentDisplayCode'), true);
    $attachment->addField("Coupon:",
        ($orderObject->has('coupon') == true ? $orderObject->get('coupon')->get('couponCode') : ' '), true);
    $attachment->addField("Total:", $orderTotalAmount, true);

    try {
        // Post to order help channel
        $slack->send();
    } catch (Exception $ex) {
        throw $ex;
    }

    return "";
}

function queue__slackDelivery_FindDelivery($message)
{

    $orderId = $message["content"]["orderId"];
    var_dump('find delivery triggered '.$orderId);


    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array(
        "retailer",
        "deliveryLocation",
        "retailer.location",
        "user",
        "deliveryLocation",
        "flightTrip",
        "flightTrip.flight"
    ), 1);

    // Is order cancelled, if so skip processing
    if (isOrderCancelled($orderObject)) {

        json_error("AS_3011", "", "Delivery - Find Delivery skipped as order was cancelled.", 3, 1);
        return "";
    } // Check if the order status is still In Progress
    else {
        if (!in_array($orderObject->get('status'), listStatusesForInProgress())) {

            return order_processing_error($orderObject, "AS_320", "",
                "Order Status Type not in Progress" . ' - FindDelivery - ' . " OrderId - " . $orderObject->getObjectId(),
                1, 1);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    ////////////////////// DELIVERY STATUS :: Find Delivery /////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    $response = deliveryStatusChange_FindDelivery($orderObject);

    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"],
            1);
    }
    ///////////////////////////////////////////////////////////////////////////////////////

    $orderObject->save();

    // Find Delivery availability
    list($foundDelivery, $deliverySlackUser) = getDeliveryAvailableForDelivery($orderObject->get('deliveryLocation'),
        $orderObject->get('requestedFullFillmentTimestamp'), true);

    // If no Delivery is found, put it back on queue with 2 mins delay
    if (!$foundDelivery) {

        // And notify on slack channel as Delivery ASSIGNMENT DELAY
        return order_processing_error($orderObject, "AS_326", "",
            "Delivery Assignment failed = " . $message["content"]["orderId"], 2, 1);
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    // Assign Delivery
    ///////////////////////////////////////////////////////////////////////////////////////

    // Send Slack message to Delivery

    // Get Order Summary
    list($orderSummaryArray, $retailerTotals) = getOrderSummary($orderObject, 1);

    $deliveryObjectId = $deliverySlackUser->getObjectId();
    $deliverySlackURL = $deliverySlackUser->get('slackURL');
    $deliveryName = $deliverySlackUser->get('deliveryName');


    // Generate slack message array
    $slack = new SlackMessage($deliverySlackURL, 'deliverySlack - ' . $deliveryName);
    $slack->setText("Order# " . $orderObject->get('orderSequenceId'));

    // Add Attachment
    $attachment = $slack->addAttachment();

    $attachment->setColorNew();

    $attachment->setAttribute("fallback", "Order# " . $orderObject->get('orderSequenceId'));
    $attachment->setAttribute("title", "");
    $attachment->setAttribute("text", "`ACTION REQUIRED`");
    $attachment->setAttribute("callback_id", "confirm_delivery__" . $orderObject->getObjectId());

    $attachment->addTimestamp();

    $attachment->addMarkdownAttribute("text");
    $attachment->addMarkdownAttribute("pretext");
    $attachment->addMarkdownAttribute("fields");

    // Add Buttons
    $buttonIndex = $attachment->addButtonPrimary("confirm_delivery_acceptance", "Accept Delivery", $deliveryObjectId);
    $attachment->addConfirmToButton($buttonIndex, "Confirm action", "Are you sure?", "Yes", "Nevermind");

    // Order type field
    // $attachment->addField("", "*" . $orderType . " Order*", false);
    $attachment->addFieldSeparator();

    // Order highlights
    $airporTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'),
        date_default_timezone_get());

    // Check if Airport Employee discount was applied
    if (!empty($orderSummaryArray["totals"]["AirEmployeeDiscount"])
        && $orderSummaryArray["totals"]["AirEmployeeDiscount"] > 0
    ) {

        $attachment->addField("VERIFY AIRPORT ID", "`Yes`", false);
        $attachment->addFieldSeparator();
    }

    // Check if Military discount was applied
    if (isset($orderSummaryArray["totals"]["MilitaryDiscount"])
        && !empty($orderSummaryArray["totals"]["MilitaryDiscount"])
        && $orderSummaryArray["totals"]["MilitaryDiscount"] > 0
    ) {

        $attachment->addField("VERIFY MILITARY PERSON", "`Yes`", false);
        $attachment->addFieldSeparator();
    }

    $userPhones = getLatestUserPhone($orderObject->get("user"));
    if (is_array($userPhones)) {
        $userPhoneString = 'phone not set';
    } else {
        $userPhoneString = $userPhones->get('phoneNumberFormatted');
    }
    $orderObject->get("retailer")->fetch();
    $orderObject->get("retailer")->get("location")->fetch();
    $orderObject->get("user")->fetch();
    $orderObject->get("deliveryLocation")->fetch();



    $orderObject->get("retailer")->fetch();
    $orderObject->get("retailer")->get("location")->fetch();
    $orderObject->get("user")->fetch();
    $orderObject->get("deliveryLocation")->fetch();

    $attachment->addField("Retailer",
        $orderObject->get("retailer")->get("retailerName") . " (" . $orderObject->get("retailer")->get("location")->get("gateDisplayName") . ")");
    $attachment->addField("Customer",
        $orderObject->get("user")->get("firstName") . " " . $orderObject->get("user")->get("lastName") . ", " . $userPhoneString);
    $attachment->addField("Delivery Location", $orderObject->get("deliveryLocation")->get("locationDisplayName"), true);
    $attachment->addField("Must pickup by", "`" . orderFormatDate($airporTimeZone,
            ($orderObject->get('etaTimestamp') - $orderObject->get('fullfillmentProcessTimeInSeconds')), 'time') . "`",
        true);
    $attachment->addField("Must deliver by",
        "`" . orderFormatDate($airporTimeZone, ($orderObject->get('etaTimestamp')), 'time') . "`", true);

    // Fetch flight data
    if (!empty($orderObject->get('flightTrip'))
        && !empty($orderObject->get('flightTrip')->get('flight'))
        && !empty($orderObject->get('flightTrip')->get('flight')->get('uniqueId'))
    ) {

        $flight = getFlightInfoCache($orderObject->get('flightTrip')->get('flight')->get('uniqueId'));

        if (!empty($flight)) {

            $attachment->addFieldSeparator();
            $attachment->addField(" ", '`Flight`', false);
            $attachment->addField("Number",
                $flight->get("info")->get("airlineIataCode") . ' ' . $flight->get("info")->get("flightNum"), true);
            $attachment->addField("To", $flight->get('arrival')->getAirportInfo()['airportIataCode'], true);
            $attachment->addField("Boarding Time", $flight->get('departure')->getBoardingTimestampFormatted(), true);
            $attachment->addField("Gate", $flight->get('departure')->getGateDisplayName(), true);
            $attachment->addFieldSeparator();
        }
    }

    $attachment->addField("Number of Items", $orderSummaryArray["internal"]["itemQuantityCount"], true);
    $attachment->addField("Order#", $orderObject->get('orderSequenceId'), true);

    if ($orderObject->has('deliveryInstructions')
        && !empty($orderObject->get('deliveryInstructions'))
    ) {

        $attachment->addField("Delivery Instructions", $orderObject->get('deliveryInstructions'), false);
    }

    $attachment->addFieldSeparator();

    foreach ($orderSummaryArray["items"] as $index => $item) {

        $itemDetails = [];
        $itemTitle = "";
        $qtyTitle = "";

        if ($index == 0) {

            $itemTitle = "Item(s)";
            $qtyTitle = "Qty";
        }

        $itemCategoryNames = $item["itemCategoryName"];

        if (!empty($item["itemSecondCategoryName"])) {

            $itemCategoryNames .= ", " . $item["itemSecondCategoryName"];
        }

        if (!empty($item["itemThirdCategoryName"])) {

            $itemCategoryNames .= ", " . $item["itemThirdCategoryName"];
        }

        // Add Item fields
        $attachment->addField($itemTitle, $item["itemName"], true);
        $attachment->addField($qtyTitle, $item["itemQuantity"], true);
        $attachment->addField('', '`Category`: ' . $itemCategoryNames, false);

        // Add modifiers
        if (isset($item["options"])) {
            foreach ($item["options"] as $option) {

                $itemDetails[] = "`+` " . $option["optionName"];
            }
        }

        // Add Special instructions
        if (!empty($item["itemComment"])) {

            $itemDetails[] = "`Special Instructions`: " . $item["itemComment"];
        }

        foreach ($itemDetails as $itemInfo) {

            $attachment->addField("", $itemInfo, false);
        }
    }
    // Connect to Slack and push order
    try {

        $slack->send();
    } catch (Exception $ex) {

        return order_processing_error($orderObject, "AS_877", "",
            "Delivery Slack Order push failed! OrderId: " . $orderId . "Error: " . json_encode($attachment->getAttachment()),
            1, 1);
    }


    ///////////////////////////////////////////////////////////////////////////////////////
    //////////////////// DELIVERY STATUS :: Assigned Delivery ///////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    // Add Delivery Assignment to the delivery user
    // Assign to Delivery in the table
    $zDeliverySlackOrderAssignments = new ParseObject('zDeliverySlackOrderAssignments');
    $zDeliverySlackOrderAssignments->set('order', $orderObject);
    $zDeliverySlackOrderAssignments->set('deliveryUser', $deliverySlackUser);
    $zDeliverySlackOrderAssignments->set('lastStatusUpdateTimestamp', time());
    $zDeliverySlackOrderAssignments->set('lastStatusDelivery', getOrderStatusDeliveryAssignedDelivery());
    $zDeliverySlackOrderAssignments->save();

    $response = deliveryStatusChange_AssignedDelivery($orderObject);

    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"],
            1);
    }
    ///////////////////////////////////////////////////////////////////////////////////////

    $orderObject->save();

    // Send message to Slack Delivery's phone
    $message = 'Delivery Assigment Alert: You have been assigned Order# ' . $orderObject->get('orderSequenceId') . ' for delivery. Please accept on Slack.';


    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueuePushAndSmsConsumerName']);
    $workerQueue->sendMessage(\App\Background\Helpers\QueueMessageHelper::getSendMessageBySMSMessage('1', $deliverySlackUser->get('SMSPhoneNumber'), $message));

    return '';
    //return send_sms_notification_with_phone_number('1', $deliverySlackUser->get('SMSPhoneNumber'), $message);
}

function queue__slackDelivery_AssignedDelivery(&$message)
{

    $orderId = $message["content"]["orderId"];

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "",
        array("retailer", "deliveryLocation", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

    // Is order cancelled, if so skip processing
    if (isOrderCancelled($orderObject)) {

        json_error("AS_3011", "", "Delivery - Assigned Delivery skipped as order was cancelled.", 3, 1);
        return "";
    } // Check if the order status is still In Progress
    else {
        if (!in_array($orderObject->get('status'), listStatusesForInProgress())) {

            return order_processing_error($orderObject, "AS_320", "",
                "Order Status Type not in Progress" . ' - AssignedDelivery - ' . " OrderId - " . $orderObject->getObjectId(),
                1, 1);
        }
    }

    $deliveryUserAssignment = parseExecuteQuery(array("order" => $orderObject), "zDeliverySlackOrderAssignments", "",
        "", ["deliveryUser"], 1);

    // Check delivery user was found for the order
    if (count_like_php5($deliveryUserAssignment) == 0) {

        return order_processing_error($orderObject, "AS_320", "",
            "Delivery Assignment not found for order " . " OrderId - " . $orderObject->getObjectId(), 1, 1);
    }

    // No message sent for this status

    // $message = $deliveryUserAssignment->get("deliveryUser")->get("DeliveryName") . ", will be delivering your order to " . $orderObject->get("deliveryLocation")->get("gateDisplayName") . ".";

    // $response = sendOrderNotification($orderObject, $message);

    // if(is_array($response)) {

    // 	return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
    // }

    return "";
}

function queue__slackDelivery_ArrivedDelivery(&$message)
{

    $orderId = $message["content"]["orderId"];

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "",
        array("retailer", "deliveryLocation", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

    // Is order cancelled, if so skip processing
    if (isOrderCancelled($orderObject)) {

        json_error("AS_3011", "", "Delivery - Arrived Delivery skipped as order was cancelled.", 3, 1);
        return "";
    } // Check if the order status is still In Progress
    else {
        if (!in_array($orderObject->get('status'), listStatusesForInProgress())) {

            return order_processing_error($orderObject, "AS_320", "",
                "Order Status Type not in Progress" . ' - ArrivedDelivery - ' . " OrderId - " . $orderObject->getObjectId(),
                1, 1);
        }
    }

    $deliveryUserAssignment = parseExecuteQuery(array("order" => $orderObject), "zDeliverySlackOrderAssignments", "",
        "", ["deliveryUser"], 1);

    // Check delivery user was found for the order
    if (count_like_php5($deliveryUserAssignment) == 0) {

        return order_processing_error($orderObject, "AS_320", "",
            "Delivery Assignment not found for order " . " OrderId - " . $orderObject->getObjectId(), 1, 1);
    }

    // $message = $deliveryUserAssignment->get("deliveryUser")->get("DeliveryName") . " has arrived at " . $orderObject->get('retailer')->get('retailerName') . " to pick up your order.";

    $message = "we have arrived at " . $orderObject->get('retailer')->get('retailerName') . " to pick up your order.";

    $response = sendOrderNotification($orderObject, $message);

    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"],
            1);
    }

    return "";
}

function queue__slackDelivery_PickedupByDelivery(&$message)
{

    $orderId = $message["content"]["orderId"];

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "",
        array("retailer", "deliveryLocation", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

    // Is order cancelled, if so skip processing
    if (isOrderCancelled($orderObject)) {

        json_error("AS_3011", "", "Delivery - Picked by Delivery skipped as order was cancelled.", 3, 1);
        return "";
    } // Check if the order status is still In Progress
    else {
        if (!in_array($orderObject->get('status'), listStatusesForInProgress())) {

            return order_processing_error($orderObject, "AS_320", "",
                "Order Status Type not in Progress" . ' - Pickedup - ' . " OrderId - " . $orderObject->getObjectId(), 1,
                1);
        }
    }

    $deliveryUserAssignment = parseExecuteQuery(array("order" => $orderObject), "zDeliverySlackOrderAssignments", "",
        "", ["deliveryUser"], 1);

    // Check delivery user was found for the order
    if (count_like_php5($deliveryUserAssignment) == 0) {

        return order_processing_error($orderObject, "AS_320", "",
            "Delivery Assignment not found for order " . " OrderId - " . $orderObject->getObjectId(), 1, 1);
    }

    // $message = $deliveryUserAssignment->get("deliveryUser")->get("DeliveryName") . " has picked up your order and will meet you shortly at " . $orderObject->get('deliveryLocation')->get('gateDisplayName') . ".";

    $message = "we have picked up your order and will meet you shortly at " . $orderObject->get('deliveryLocation')->get('gateDisplayName') . ".";

    $response = sendOrderNotification($orderObject, $message);

    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"],
            1);
    }

    return "";
}

function queue__slackDelivery_AtDeliveryLocationByDelivery(&$message)
{

    $orderId = $message["content"]["orderId"];

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "",
        array("retailer", "deliveryLocation", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

    // Is order cancelled, if so skip processing
    if (isOrderCancelled($orderObject)) {

        json_error("AS_3011", "", "Delivery - At Delivery Location skipped as order was cancelled.", 3, 1);
        return "";
    } // Check if the order status is still In Progress
    else {
        if (!in_array($orderObject->get('status'), listStatusesForInProgress())) {

            return order_processing_error($orderObject, "AS_320", "",
                "Order Status Type not in Progress" . ' - AtDeliveryLocation - ' . " OrderId - " . $orderObject->getObjectId(),
                1, 1);
        }
    }

    $deliveryUserAssignment = parseExecuteQuery(array("order" => $orderObject), "zDeliverySlackOrderAssignments", "",
        "", ["deliveryUser"], 1);

    // Check delivery user was found for the order
    if (count_like_php5($deliveryUserAssignment) == 0) {

        return order_processing_error($orderObject, "AS_320", "",
            "Delivery Assignment not found for order " . " OrderId - " . $orderObject->getObjectId(), 1, 1);
    }

    if (strcasecmp($deliveryUserAssignment->get("deliveryUser")->get("gender"), 'm') == 0) {

        $genderPronoun = 'him';
    } else {

        $genderPronoun = 'her';
    }

    // $message = $deliveryUserAssignment->get("deliveryUser")->get("deliveryName") . ", has arrived at " . $orderObject->get('deliveryLocation')->get('gateDisplayName') . ". We wear the fablous purple Airport Sherpa attire. Please look for " . $genderPronoun . " to accept delivery.";

    //$message = "we are approaching " . $orderObject->get('deliveryLocation')->get('gateDisplayName') . ". Please look for us in the bright blue uniform to accept your delivery.";
    $message = "we are approaching " . $orderObject->get('deliveryLocation')->get('gateDisplayName') . ". Please look for us in our AtYourGate uniform to accept your delivery.";

    $response = sendOrderNotification($orderObject, $message);

    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"],
            1);
    }

    return "";
}

function queue__slackDelivery_DeliveredByDelivery(&$message)
{

    $orderId = $message["content"]["orderId"];

    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "",
        array("retailer", "deliveryLocation", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

    return orderCompleteMethods($orderObject);
}

?>
