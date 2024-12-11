<?php

require_once 'dirpath.php';

require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Httpful\Request;

Braintree_Configuration::environment($env_BraintreeEnvironment);
Braintree_Configuration::merchantId($env_BraintreeMerchantId);
Braintree_Configuration::publicKey($env_BraintreePublicKey);
Braintree_Configuration::privateKey($env_BraintreePrivateKey);

function processScheduledOrder($orderId)
{

    try {

        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
    } catch (Exception $ex) {

        return json_decode($ex->getMessage(), true);
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId, "__NE__interimOrderStatus" => -1), "Order", "", "",
        array(
            "retailer",
            "retailer.location",
            "user",
            "sessionDevice",
            "sessionDevice.userDevice",
            "deliveryLocation",
            "coupon",
            "coupon.applicableUser",
            "flightTrip.flight"
        ), 1);

    if (count_like_php5($orderObject) == 0) {

        // Warning, but no blocking error (processFlag = 1)
        return order_processing_error("", "AS_321", "",
            "Order could not be processed! Order Id: " . $orderId . " not found", 3, 1, "", 1);
    } // If interimOrderStatus == -1
    else {
        if ($orderObject->get('interimOrderStatus') == -1) {

            // Warning, but no blocking error (processFlag = 1)
            return order_processing_error("", "AS_321", "",
                "Order could not be processed, still in pending status! Order Id: " . $orderId . " not found", 3, 1, "",
                1);
        } // If not in submitted or awaiting confirmation state, return true
        else {
            if (!in_array($orderObject->get('status'), listStatusesForScheduled())) {

                json_error("AS_3020", "",
                    "Order Processing skipped as order was not in scheduled conf state. Order Id: " . $orderId, 3, 1);
                return "";
            } // Is order cancelled, if so skip processing
            else {
                if (isOrderCancelled($orderObject)) {

                    json_error("AS_3011", "", "Order Processing skipped as order was cancelled. Order Id: " . $orderId,
                        3, 1);
                    return "";
                }
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Verify no pending errors for this order
    //////////////////////////////////////////////////////////////////////////////////////////////////////

    $response = verifyPendingOrderErrors($orderObject);
    try {

        $workerQueue->sendMessage(
            array(
                "action" => "order_submission_process",
                "content" =>
                    array(
                        "orderId" => $orderId,
                        // Indicate this has been put back on queue
                        "backOnQueue" => false,
                    )
            ),
            // This states by when we need to place the order to hit the requested fullfillment timestamp
            5
        );
    } catch (Exception $ex) {

        $response = json_decode($ex->getMessage(), true);
        json_error($response["error_code"], "",
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), 1);
    }

    orderStatusChange_Submitted($orderObject);

    $orderObject->save();
}

function processOrder($orderId)
{

    // TODO: Implement order scheduling using requestedFullfillmentTimestamp

    try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
    } catch (Exception $ex) {

        return json_decode($ex->getMessage(), true);
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId, "__NE__interimOrderStatus" => -1), "Order", "", "",
        array(
            "retailer",
            "retailer.location",
            "user",
            "sessionDevice",
            "sessionDevice.userDevice",
            "deliveryLocation",
            "coupon",
            "coupon.applicableUser",
            "flightTrip.flight"
        ), 1);

    if (count_like_php5($orderObject) == 0) {

        // Warning, but no blocking error (processFlag = 1)
        return order_processing_error("", "AS_321", "",
            "Order could not be processed! Order Id: " . $orderId . " not found", 3, 1, "", 1);
    } // If interimOrderStatus == -1
    else {
        if ($orderObject->get('interimOrderStatus') == -1) {

            // Warning, but no blocking error (processFlag = 1)
            return order_processing_error("", "AS_321", "",
                "Order could not be processed, still in pending status! Order Id: " . $orderId . " not found", 3, 1, "",
                1);
        } // If not in submitted or awaiting confirmation state, return true
        else {
            if (!in_array($orderObject->get('status'), listStatusesForSubmittedOrAwaitingConfirmation())) {

                json_error("AS_3020", "",
                    "Order Processing skipped as order was not in submitted or awating conf state. Order Id: " . $orderId,
                    3, 1);
                return "";
            } // Is order cancelled, if so skip processing
            else {
                if (isOrderCancelled($orderObject)) {

                    json_error("AS_3011", "", "Order Processing skipped as order was cancelled. Order Id: " . $orderId,
                        3, 1);
                    return "";
                }
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Verify no pending errors for this order
    //////////////////////////////////////////////////////////////////////////////////////////////////////

    $response = verifyPendingOrderErrors($orderObject);

    $interimOrderStatus = empty($orderObject->get('interimOrderStatus')) ? 0 : $orderObject->get('interimOrderStatus');
    $braintreeTransactionId = $orderObject->get('paymentId');
    $omnivoreTicketId = $orderObject->get('orderPOSId');
    $submitTimestamp = $orderObject->get('submitTimestamp');
    $etaTimestamp = $orderObject->get('etaTimestamp');
    $retailerUniqueId = $orderObject->get('retailer')->get('uniqueId');

    // Fetch Customer Name
    $customerName = $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName');

    // Get Order Summary
    list($orderSummaryArray, $retailerTotals) = getOrderSummary($orderObject, 1);
    $orderTotalForPayment = $orderSummaryArray["totals"]["Total"];

    // Fetch Order Totals
    $totalsWithFees = json_decode($orderObject->get("totalsWithFees"), true);
    $orderTotal = $totalsWithFees["Total"];
    //////////////////////////////////////////////////////////////////////////////////////////////////////


    // Error while verifying pending errors
    // Or there is a pending error for this order hence don't process
    if (!empty($response)) {

        return $response;
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////


    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Submission Attempt verification
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    $orderObject->increment('submissionAttempt');
    $orderObject->save();
    $submissionAttempt = $orderObject->get('submissionAttempt');

    if ($submissionAttempt != 2) {

        decrementSubmissionAttempt($orderObject);
        return order_processing_error($orderObject, "AS_301", "",
            "Order could not be processed! Order Id: " . $orderId . ", Duplicate Order Process run caught! test1", 1,
            1);
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////


    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Ping Retailer
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Check if the POS system is up for the Retailer
    $retailer = $orderObject->get('retailer');
    list($ping, $isClosed, $error, $pingStatusDescription) = pingRetailer($retailer, $orderObject);
    if ($ping == false) {

        decrementSubmissionAttempt($orderObject);
        return order_processing_error($orderObject, "AS_302", "",
            "Retailer POS is DOWN. Orders not being accepted right now. OrderId - " . $orderObject->getObjectId() . " " . $error,
            1, 1);
    }
    // Closed Retailer
    // else if($ping == 2) {

    // 	decrementSubmissionAttempt($orderObject);
    // 	return order_processing_error($orderObject, "AS_302", "", "Retailer is Closed. Orders not being accepted right now. OrderId - " . $orderObject->getObjectId() . " " . $error, 1, 1);
    // }
    //////////////////////////////////////////////////////////////////////////////////////////////////////


    // Add Status of Order being confirmed
    // addOrderStatus($orderObject, 98, new ParseObject("OrderStatus"));


    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Fetch POS Config
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Fetch POS Config data
    $objectParseQueryPOSConfig = parseExecuteQuery(array("retailer" => $orderObject->get('retailer')),
        "RetailerPOSConfig", "", "", ["dualPartnerConfig"], 1);

    // If Config is NOT found, let caller know
    if (count_like_php5($objectParseQueryPOSConfig) == 0) {

        decrementSubmissionAttempt($orderObject);
        return order_processing_error($orderObject, "AS_303", "",
            "Retailer not found! POS Config not found for the uniqueRetailerId (" . $orderObject->get('retailer')->get('uniqueId') . ") OrderId - " . $orderObject->getObjectId(),
            1, 1);
    } // Get the Config
    else {

        $googlePrinterId = $objectParseQueryPOSConfig->get('printerId');
        $tabletId = $objectParseQueryPOSConfig->get('tabletId');
        $tabletMobilockId = $objectParseQueryPOSConfig->get('tabletMobilockId');
        $tabletSlackURL = $objectParseQueryPOSConfig->get('tabletSlackURL');
        $omnivoreLocationId = $objectParseQueryPOSConfig->get('locationId');
        $omnivoreEmployeeId = $objectParseQueryPOSConfig->get('employeeId');
        $omnivoreOrderTypeId = $objectParseQueryPOSConfig->get('orderTypeId');
        $omnivoreRevenueCenterId = $objectParseQueryPOSConfig->get('revenueCenterId');
        $omnivoreTenderTypeId = $objectParseQueryPOSConfig->get('tenderTypeId');
        $omnivorePlaceHolderItemId = $objectParseQueryPOSConfig->get('placeHolderItemId');
        $omnivorePlaceHolderItemPriceLevelId = $objectParseQueryPOSConfig->get('placeHolderItemPriceLevelId');
        $etaPickupSLAMins = $objectParseQueryPOSConfig->get('pickupSLAInMins');
        $pushOrdersToPOS = $objectParseQueryPOSConfig->get('pushOrdersToPOS');
        $dualPartnerConfig = $objectParseQueryPOSConfig->get('dualPartnerConfig');
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////////////////////////////
    // New Order Notification on Slack
    ///////////////////////////////////////////////////////////////////////////////////////


    if ($interimOrderStatus < 1) {

        $response = newOrderNotification($orderObject);

        // Error while notifying
        if (!empty($response)) {

            return $response;
        }

        $orderObject->set("interimOrderStatus", 1);
        $orderObject->save();
    }
    ////////////////////////////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////////////////////////////
    // Create & Save PDF
    ///////////////////////////////////////////////////////////////////////////////////////
    if ($interimOrderStatus < 2) {

        $retailerName = "";

        // Fetch Retailer Name associated with the Order
        $retailerName = $orderObject->get('retailer')->get('retailerName');

        list($pdfURL, $pdfFileName) = createOrderTicketPDF($orderObject, $customerName, $retailerName, $submitTimestamp,
            $etaTimestamp, $orderSummaryArray, $retailerTotals);
        $orderObject->set("invoicePDFURL", $pdfFileName);

        $orderObject->set("interimOrderStatus", 2);
        $orderObject->save();
    }
    ///////////////////////////////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////////////////////////////
    // Payment Settlement
    ///////////////////////////////////////////////////////////////////////////////////////
    if ($interimOrderStatus < 3) {

        if ($orderTotalForPayment > 0) {

            try {

                $paymentSettle = Braintree_Transaction::submitForSettlement($braintreeTransactionId);
            } catch (Exception $ex) {

                decrementSubmissionAttempt($orderObject);
                return order_processing_error($orderObject, "AS_305", "",
                    "Payment Processing failed. Braintree Error: " . $ex->getMessage() . " OrderId - " . $orderObject->getObjectId(),
                    1, 1);
            }

            // Payment Processing failed
            if (!$paymentSettle->success && count_like_php5($paymentSettle->errors->deepAll()) > 0) {

                decrementSubmissionAttempt($orderObject);
                return order_processing_error($orderObject, "AS_307", "",
                    "Payment Processing failed. Braintree Error: " . braintreeErrorCollect($paymentSettle) . " OrderId - " . $orderObject->getObjectId(),
                    1, 1);
            }

            // Check if the payment was NOT submitted_for_settlement or NOT settling
            if (strcasecmp($paymentSettle->transaction->status, "submitted_for_settlement") != 0
                && strcasecmp($paymentSettle->transaction->status, "settling") != 0
            ) {

                $message = "";
                if (isset($paymentSettle->message)) {

                    $message = $paymentSettle->message;
                }

                decrementSubmissionAttempt($orderObject);
                return order_processing_error($orderObject, "AS_308", "",
                    "Payment Settlement failed. Trans_id: " . $braintreeTransactionId . "Status: " . $paymentSettle->transaction->status . ", message: " . $message . " OrderId - " . $orderObject->getObjectId(),
                    1, 1);
            }
        }

        ///////////////////////////////////////////////////////////////////////////////////////
        //////////////////////// ORDER STATUS :: Payment Accepted /////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////

        orderStatusChange_PaymentAccepted($orderObject);

        ///////////////////////////////////////////////////////////////////////////////////////

        $orderObject->set("interimOrderStatus", 3);
        $orderObject->save();
    }
    ///////////////////////////////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////////////////////////////
    // Verify if Testing is on - No Real Order Push
    ///////////////////////////////////////////////////////////////////////////////////////
    // If the Push Orders flag is set to false, then just mark order as processed
    if ($pushOrdersToPOS == false) {

        ///////////////////////////////////////////////////////////////////////////////////////
        //////////////////////// ORDER STATUS :: Process Through Completion ///////////////////
        ///////////////////////////////////////////////////////////////////////////////////////

        orderStatusChange_PushedToRetailer($orderObject);


        //



        $response = orderStatusChange_ConfirmedByRetailer($orderObject);

        if (is_array($response)) {

            return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
                $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(),
                $response["error_severity"], 1);
        }

        $response = orderStatusChange_Completed($orderObject);

        if (is_array($response)) {

            return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
                $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(),
                $response["error_severity"], 1);
        }

        ///////////////////////////////////////////////////////////////////////////////////////

        $orderObject->save();

        return "";
    }


    ///////////////////////////////////////////////////////////////////////////////////////
    // Process Order
    ///////////////////////////////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////// GOOGLE CLOUD PRINT ORDER /////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    // If Printer Retailer
    if (!empty($googlePrinterId)) {

        if ($interimOrderStatus < 4) {

            // Connect to Google Cloud Print and generate access token
            try {

                $gcp = new GoogleCloudPrint();
                $token = $gcp->getAccessTokenByRefreshToken($GLOBALS['GCP_urlconfig']['refreshtoken_url'],
                    http_build_query($GLOBALS['GCP_refreshTokenConfig']));

                if (empty($token)) {

                    json_error("AS_515", "", "GooglePrint Token fetch failed for printerId (" . $googlePrinterId . ")",
                        1, 1);
                    throw new Exception("GooglePrint Token fetch failed for printerId (" . $googlePrinterId . ")");
                }

                $gcp->setAuthToken($token);
            } catch (Exception $ex) {

                decrementSubmissionAttempt($orderObject);
                return order_processing_error($orderObject, "AS_317", "",
                    "Google Print Access Token! OrderId: " . $orderId . ", Error(s): " . $ex->getMessage(), 1, 1);
            }

            // Check if the pdfURL is empty; aka this is a reprocess order and the URL was generated in the last run, so just get it from DB
            // PDF is created in Step 1 of this process
            if (empty($pdfURL)) {

                $pdfURL = $orderObject->get('invoicePDFURL');
            }

            // Send document to the printer
            $googleResponseArray = array();
            try {

                $googleResponseArray = $gcp->sendPrintToPrinter($googlePrinterId,
                    $orderId . " (" . $orderSummaryArray["internal"]["orderIdDisplay"] . ")", $pdfURL,
                    "application/pdf");
            } catch (Exception $ex) {

                decrementSubmissionAttempt($orderObject);
                return order_processing_error($orderObject, "AS_318", "",
                    "Google Print Failed! OrderId: " . $orderId . ", Error(s): " . $ex->getMessage(), 1, 1);
            }

            // Print Failed
            if ($googleResponseArray['status'] == false) {

                decrementSubmissionAttempt($orderObject);
                return order_processing_error($orderObject, "AS_306", "",
                    "Google Print Failed! OrderId: " . $orderId . "Error code: " . $googleResponseArray['errorcode'] . " Message:" . $googleResponseArray['errormessage'],
                    1, 1);
            }

            // Save Printer Job Id: orderPrintJobId
            $orderObject->set("orderPrintJobId", $googleResponseArray['id']);


            ///////////////////////////////////////////////////////////////////////////////////////
            /////////////////////// ORDER STATUS :: Pushed to Retailer ////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////

            orderStatusChange_PushedToRetailer($orderObject);

            ///////////////////////////////////////////////////////////////////////////////////////


            ////////////////////////////////////////////////////////////////////////////////////
            // Put on Queue to confirm printing after 60 seconds
            ////////////////////////////////////////////////////////////////////////////////////
            // Put order on the queue for print confirm
            try {

                $workerQueue->sendMessage(
                    array(
                        "action" => "order_confirm_accepted_by_retailer",
                        "content" =>
                            array(
                                "orderId" => $orderId,
                            )
                    ),
                    // DelaySeconds for 1 minute
                    60
                );
            } catch (Exception $ex) {

                $response = json_decode($ex->getMessage(), true);
                return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
                    $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(),
                    $response["error_severity"], 1);
            }
            //////////////////////////////////////////////////////////////////////////////////////////


            $orderObject->set("interimOrderStatus", 4);
            $orderObject->save();


            ////////////////////////////////////////////////////////////////////////////////////
            // Send Message to Retailer's POS Slack Channel
            ////////////////////////////////////////////////////////////////////////////////////
            $response = sendTabletSlackNotification($orderObject, $tabletSlackURL);

            // Error while notifying
            if (!empty($response)) {

                return $response;
            }
            ////////////////////////////////////////////////////////////////////////////////////
        }
    }
    ///////////////////////////////////////////////////////////////////////////////////////


    ///////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////// POS RETAILER ORDER ////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////
    // If POS Retailer
    else {
        if (!empty($omnivoreLocationId)) {

            // Micros 16 character limit
            $orderComment = substr("AS-" . $orderObject->get('orderSequenceId'), 0, 15);
            $orderObject->set("comment", $orderComment);

            // Create an Omnivore ticket
            $omnivoreTicket["employee"] = $omnivoreEmployeeId;
            $omnivoreTicket["order_type"] = $omnivoreOrderTypeId;
            $omnivoreTicket["revenue_center"] = $omnivoreRevenueCenterId;
            $omnivoreTicket["guest_count"] = 1;
            $omnivoreTicket["name"] = $orderComment;

            // It is automatically set to true when payment is posted
            // $omnivoreTicket["auto_send"] = false;
            $omnivoreTicket["auto_send"] = true;

            ////////////////
            // Ghost item's Comment
            $orderCommentGhostItem = "";
            $omnivoreTicketDetailsArray = array();
            if (!empty($omnivorePlaceHolderItemId)) {

                $orderCommentGhostItem = substr($orderObject->get('user')->get('firstName'), 0, 14);
                $omnivoreTicketDetailsArray[] = array(
                    "menu_item" => $omnivorePlaceHolderItemId,
                    "quantity" => 1,
                    "price_level" => $omnivorePlaceHolderItemPriceLevelId,
                    "comment" => $orderCommentGhostItem
                );
            }
            ////////////////

            // Iterate through Cart items and add them to the Ticket array
            foreach ($orderSummaryArray["items"] as $orderItems) {

                $itemArray = array();

                // Fetch Item Info
                $objectParseTemp = parseExecuteQuery(array("uniqueId" => $orderItems["itemId"]), "RetailerItems", "",
                    "", [], 1);
                if (count_like_php5($objectParseTemp) == 0) {

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_309", "",
                        "Order Item not found (" . $orderItems["itemId"] . ")" . " OrderId - " . $orderObject->getObjectId(),
                        1, 1);
                }

                $itemArray["menu_item"] = $objectParseTemp->get('itemId');
                $itemArray["quantity"] = $orderItems["itemQuantity"];
                $itemArray["price_per_unit"] = $orderItems["itemPrice"];
                // $itemArray["price_level"] = $objectParseTemp->get('priceLevelId');
                $itemArray["comment"] = $orderItems["itemComment"];

                if (isset($orderItems["options"])) {
                    foreach ($orderItems["options"] as $orderOptions) {

                        $modifiersArray = array();

                        // Fetch Option Info
                        $objectParseTemp = parseExecuteQuery(array("uniqueId" => $orderOptions["optionId"]),
                            "RetailerItemModifierOptions", "", "", [], 1);
                        if (count_like_php5($objectParseTemp) == 0) {

                            decrementSubmissionAttempt($orderObject);
                            return order_processing_error($orderObject, "AS_310", "",
                                "Order Item Option not found (" . $orderOptions["optionId"] . ")" . " OrderId - " . $orderObject->getObjectId(),
                                1, 1);
                        }

                        $modifiersArray["modifier"] = $objectParseTemp->get('optionId');
                        $modifiersArray["quantity"] = $orderOptions["optionQuantity"];
                        $modifiersArray["price_per_unit"] = $orderOptions["pricePerUnit"];
                        // $modifiersArray["price_level"] = $objectParseTemp->get('priceLevelId');
                        $modifiersArray["comment"] = "";

                        $itemArray["modifiers"][] = $modifiersArray;
                    }
                }

                $omnivoreTicketDetailsArray[] = $itemArray;
            }

            // #1 Step - Create; Only do it the POS Ticket Id is not filed in
            if (empty($omnivoreTicketId)
                && $interimOrderStatus < 4
            ) {

                $urlTicketCreate = $omnivoreLocationId . "/tickets";

                ///////////////////////////////////////////////////////////////////////////////////////
                //////////////////////// POST TO OMNIVORE - Create Ticket /////////////////////////////
                ///////////////////////////////////////////////////////////////////////////////////////

                try {

                    $omnivoreTicketObject = postToOmnivore($urlTicketCreate, $omnivoreTicket);
                } catch (Exception $ex) {

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_322", "",
                        "Omnviore connection failed! " . json_encode($omnivoreTicket) . " OrderId - " . $orderObject->getObjectId() . " - " . $ex->getMessage(),
                        1, 1);
                }

                // Get Ticket Id assigned by POS
                if (isset($omnivoreTicketObject->body->id)) {

                    $omnivoreTicketId = $omnivoreTicketObject->body->id;
                } // Error Occurred
                else {

                    $errorAppend = "";
                    if (isset($omnivoreTicketObject->body->errors)
                        && count_like_php5($omnivoreTicketObject->body->errors) > 0
                    ) {

                        $errorAppend = json_encode($omnivoreTicketObject->body->errors);
                    }

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_311", "",
                        "Unknown POS error occurred. Create Ticket failed ($errorAppend): " . json_encode($omnivoreTicket) . " OrderId - " . $orderObject->getObjectId(),
                        1, 1);
                }

                // Save POS Ticket Id
                $orderObject->set("orderPOSId", $omnivoreTicketId);

                $orderObject->set("interimOrderStatus", 4);
                $orderObject->save();
            }

            // #2 - Submit Order with Ticket Details
            if ($interimOrderStatus < 5) {

                $urlTicketSubmit = $omnivoreLocationId . "/tickets/" . $omnivoreTicketId . "/items";

                ///////////////////////////////////////////////////////////////////////////////////////
                /////////////////////// POST TO OMNIVORE - Send Items List ////////////////////////////
                ///////////////////////////////////////////////////////////////////////////////////////

                // unset($omnivoreTicketDetailsArray[0]["modifiers"][0]["price_per_unit"]);

                // decrementSubmissionAttempt($orderObject);
                // echo($GLOBALS['env_OmnivoreAPIURLPrefix'] . $urlTicketSubmit);
                // echo(json_encode($omnivoreTicketDetailsArray));
                // exit;
                try {

                    $omnivoreTicketDetailsObject = postToOmnivore($urlTicketSubmit, $omnivoreTicketDetailsArray);
                } catch (Exception $ex) {

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_323", "",
                        "Omnviore connection failed! " . json_encode($omnivoreTicketDetailsArray) . " OrderId - " . $orderObject->getObjectId() . " - " . $ex->getMessage(),
                        1, 1);
                }

                // decrementSubmissionAttempt($orderObject);
                // print_r($omnivoreTicketDetailsObject);exit;

                if (isset($omnivoreTicketDetailsObject->body->errors)
                    && count_like_php5($omnivoreTicketDetailsObject->body->errors) > 0
                ) {

                    $errorAppend = json_encode($omnivoreTicketDetailsObject->body->errors);

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_312", "",
                        "Unknown Error occurred! Order Entry for Ticket failed ($errorAppend): " . json_encode($omnivoreTicketDetailsArray) . " OrderId - " . $orderObject->getObjectId(),
                        1, 1);
                }

                ///////////////////////////////////////////////////////////////////////////////////////
                /////////////////////// ORDER STATUS :: Pushed to Retailer ////////////////////////////
                ///////////////////////////////////////////////////////////////////////////////////////

                orderStatusChange_PushedToRetailer($orderObject);

                ///////////////////////////////////////////////////////////////////////////////////////

                $orderObject->set("interimOrderStatus", 5);
                $orderObject->save();
            }

            // Order successful; get Totals
            if ($interimOrderStatus < 6) {

                $urlTicketPosted = $omnivoreLocationId . "/tickets/" . $omnivoreTicketId;

                try {

                    $omnivoreTicketPostedObject = getFromOmnivore($urlTicketPosted);
                } catch (Exception $ex) {

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_324", "",
                        "Omnviore connection failed! " . $omnivoreTicketId . " OrderId - " . $orderObject->getObjectId() . " - " . $ex->getMessage(),
                        1, 1);
                }

                if (isset($omnivoreTicketPostedObject->body->errors)
                    && count_like_php5($omnivoreTicketPostedObject->body->errors) > 0
                ) {

                    $errorAppend = json_encode($omnivoreTicketPostedObject->body->errors);

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_314", "",
                        "Unknown Error occurred! Order Total Pull for Ticket failed ($errorAppend)" . " OrderId - " . $orderObject->getObjectId(),
                        1, 1);
                }

                // handle tips
                // tip can be set up as fix value or percentage
/*
                if ($orderObject->get('fullfillmentType')=='d'){
                    if ($orderObject->get('tipAppliedAs')==\App\Consumer\Entities\Order::TIP_APPLIED_AS_PERCENTAGE){
                        $tipToPay = ($omnivoreTicketPostedObject->body->totals->sub_total * (floatval($orderObject->get('tipPct')) / 100));
                        $tipToPay = round($tipToPay);
                        $orderObject->set("tipAsCent", $tipToPay);
                    }

                    if ($orderObject->get('tipAppliedAs')==\App\Consumer\Entities\Order::TIP_APPLIED_AS_FIXED_VALUE){
                        $tipToPay = $orderObject->get('tipCents');
                        $tipPct = round($tipToPay/$omnivoreTicketPostedObject->body->totals->sub_total*100,2);
                        $orderObject->set("tipPct", $tipPct);
                    }
                }else{
                    $orderObject->set('tipAppliedAs',null);
                    $orderObject->set('tipCents',0);
                    $orderObject->set('tipPct',0);
                }
*/
                // Save the POS Totals separately
                $tipToPay = ($omnivoreTicketPostedObject->body->totals->sub_total * (floatval($orderObject->get('tipPct')) / 100));

                // This forces the balance of the Order to be same as POS
                // This way the order is closed out but any variances from what we calculated vs. the POS are reported in OrderVariances; typically tax rate differences cause this
                $posTotals["PreTaxTotal"] = $omnivoreTicketPostedObject->body->totals->sub_total;
                $posTotals["Taxes"] = $omnivoreTicketPostedObject->body->totals->tax;
                $posTotals["PreTipTotal"] = $omnivoreTicketPostedObject->body->totals->total;
                $posTotals["Tips"] = $tipToPay;

                // POS Coupons currently not supported
                $posTotals["PreCouponTotal"] = $posTotals["PreTipTotal"] + $posTotals["Tips"];
                $posTotals["Coupon"] = 0;

                $posTotals["Total"] = $posTotals["PreCouponTotal"] + $posTotals["Coupon"];

                $orderObject->set("totalsFromPOS", json_encode($posTotals));
                $orderObject->set("interimOrderStatus", 6);
                $orderObject->save();


                ////////////////////////////////////////////////////////////////////////////////////
                // Order Varriance Detected
                ////////////////////////////////////////////////////////////////////////////////////
                // If POS returned total is not same, then log as a Variance
                if ($posTotals["Total"] != $retailerTotals["Total"]) {

                    $orderBalancesObject = new ParseObject("OrderVariances");
                    $orderBalancesObject->set("order", $orderObject);
                    $orderBalancesObject->set("slackPosted", true);
                    $orderBalancesObject->save();

                    ////////////////////////////////////////////////////////////////////////////////////
                    // Order Varriance - Post on Slack
                    $response = informOrderVarrianceOnSlack($orderObject);

                    // Error while verifying pending errors
                    // Or there is a pending error for this order hence don't process
                    if (!empty($response)) {

                        return $response;
                    }
                    ////////////////////////////////////////////////////////////////////////////////////
                }
                ////////////////////////////////////////////////////////////////////////////////////
            }

            // #3 - Make Payment to POS
            // Override to not send PaymentId but instead send Order Sequence Id as paymentId
            if ($interimOrderStatus < 7) {

                $posTotals = json_decode($orderObject->get("totalsFromPOS"), true);

                $omnivoreTicketPaymentArray = array(
                    "type" => "3rd_party",
                    "amount" => $posTotals["PreTipTotal"],
                    "tender_type" => $omnivoreTenderTypeId,
                    "comment" => 'AS-' . strval($orderObject->get('orderSequenceId')),
                    "auto_close" => true
                );

                // If Tips to be applied
                if (isset($posTotals["Tips"])) {

                    $omnivoreTicketPaymentArray["tip"] = $posTotals["Tips"];
                }

                $urlTicketPayment = $omnivoreLocationId . "/tickets/" . $omnivoreTicketId . "/payments";

                ///////////////////////////////////////////////////////////////////////////////////////
                ////////////////////// POST TO OMNIVORE - Payment Info Send ///////////////////////////
                ///////////////////////////////////////////////////////////////////////////////////////
                try {

                    $omnivoreTicketPaymentObject = postToOmnivore($urlTicketPayment, $omnivoreTicketPaymentArray);
                } catch (Exception $ex) {

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_325", "",
                        "Omnviore connection failed! " . json_encode($omnivoreTicketPaymentArray) . " OrderId - " . $orderObject->getObjectId() . " - " . $ex->getMessage(),
                        1, 1);
                }

                // If Error Occured in applying POS payment
                if (isset($omnivoreTicketPaymentObject->body->errors)
                    && count_like_php5($omnivoreTicketPaymentObject->body->errors) > 0
                ) {

                    $errorAppend = json_encode($omnivoreTicketPaymentObject->body->errors);

                    decrementSubmissionAttempt($orderObject);
                    return order_processing_error($orderObject, "AS_315", "",
                        "Unknown Error occurred! Payment for Ticket failed ($errorAppend): " . json_encode($omnivoreTicketPaymentArray) . " OrderId - " . $orderObject->getObjectId(),
                        1, 1);
                } // If Balance is still remained after applying payment
                else {
                    if (isset($omnivoreTicketPaymentObject->body->_embedded->ticket->totals->due)
                        && $omnivoreTicketPaymentObject->body->_embedded->ticket->totals->due != 0
                    ) {

                        decrementSubmissionAttempt($orderObject);
                        return order_processing_error($orderObject, "AS_316", "",
                            "Unknown Error occurred! Payment for Ticket was not full, balance found: " . $omnivoreTicketPaymentObject->body->_embedded->ticket->totals->due . " OrderId - " . $orderObject->getObjectId(),
                            1, 1);
                    }
                }


                ////////////////////////////////////////////////////////////////////////////////////
                // Put on Queue to confirm POS order after 60 seconds
                ////////////////////////////////////////////////////////////////////////////////////
                // Put order on the queue for POS confirm
                try {

                    $workerQueue->sendMessage(
                        array(
                            "action" => "order_confirm_accepted_by_retailer",
                            "content" =>
                                array(
                                    "orderId" => $orderId,
                                )
                        ),
                        // DelaySeconds for 1 minute
                        60
                    );
                } catch (Exception $ex) {

                    $response = json_decode($ex->getMessage(), true);
                    return order_processing_error($orderObject, $response["error_code"],
                        $response["error_message_user"],
                        $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(),
                        $response["error_severity"], 1);
                }
                //////////////////////////////////////////////////////////////////////////////////////////


                $orderObject->set("interimOrderStatus", 7);
                $orderObject->save();

                ///////////////////////////////////////////////////////////////////////////////////////


                ////////////////////////////////////////////////////////////////////////////////////
                // Send Message to Retailer's POS Slack Channel
                ////////////////////////////////////////////////////////////////////////////////////
                $response = sendTabletSlackNotification($orderObject, $tabletSlackURL);

                // Error while notifying
                if (!empty($response)) {

                    return $response;
                }
                ////////////////////////////////////////////////////////////////////////////////////
            }
        }


        ///////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////// SLACK TABLET ORDER ////////////////////////////////////
        ///////////////////////////////////////////////////////////////////////////////////////

        // If Slack Tablet Retailer
        else {
            if (!empty($tabletId)) {

                if ($interimOrderStatus < 4) {

                    $orderType = strcasecmp($orderObject->get("fullfillmentType"), "p") == 0 ? "Pickup" : "Delivery";

                    // Generate slack message array
                    $slack = new SlackMessage($tabletSlackURL, 'tabletSlackURL - ' . $tabletId);
                    $slack->setText($orderType . ", Order# " . $orderObject->get('orderSequenceId'));

                    // Add Attachment
                    $attachment = $slack->addAttachment();

                    $attachment->setColorNew();

                    // $attachment->setAttribute("pretext", "Order# " . $orderObject->get("orderSequenceId")); // Shows up twice if we use fallback -> setText
                    $attachment->setAttribute("fallback",
                        $orderType . ", Order# " . $orderObject->get('orderSequenceId'));
                    $attachment->setAttribute("title", "");
                    $attachment->setAttribute("text", "`ACTION REQUIRED`");
                    $attachment->setAttribute("callback_id", "confirm_order__" . $orderObject->getObjectId());

                    $attachment->addTimestamp();

                    $attachment->addMarkdownAttribute("text");
                    $attachment->addMarkdownAttribute("pretext");
                    $attachment->addMarkdownAttribute("fields");

                    // Add Buttons
                    $buttonIndex = $attachment->addButtonPrimary("confirm_order", "Accept", 1);
                    $buttonIndex = $attachment->addButtonDanger("confirm_order", "Need Help?", -1);

                    $attachment->addConfirmToButton($buttonIndex, "Customer Service",
                        "Please provide more information in the response, regarding. Do you want to contact customer service?",
                        "Yes", "Nevermind");

                    // Order type field
                    $attachment->addField("", "*" . $orderType . " Order*", false);
                    $attachment->addFieldSeparator();

                    // Order highlights
                    $airporTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'),
                        date_default_timezone_get());
                    $attachment->addField("Order Date & time",
                        orderFormatDate($airporTimeZone, $orderObject->get('submitTimestamp'), 'both'), true);
                    $attachment->addField("Must prepare by",
                        "`" . orderFormatDate($airporTimeZone, (getOrderMustPrepareByTimestamp($orderObject)),
                            'time') . "`", true);
                    $attachment->addField("Number of Items", $orderSummaryArray["internal"]["itemQuantityCount"], true);
                    $attachment->addField("Customer",
                        $orderObject->get("user")->get("firstName") . " " . $orderObject->get("user")->get("lastName"),
                        true);
                    $attachment->addFieldSeparator();

                    foreach ($orderSummaryArray["items"] as $index => $item) {

                        $itemDetails = [];
                        $itemTitle = "";
                        $qtyTitle = "";

                        if ($index == 0) {

                            $itemTitle = "Item(s)";
                            $qtyTitle = "Qty";
                        }

                        // Add Item fields
                        $attachment->addField($itemTitle, $item["itemName"], true);
                        $attachment->addField($qtyTitle, $item["itemQuantity"], true);

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

                        decrementSubmissionAttempt($orderObject);
                        return order_processing_error($orderObject, "AS_873", "",
                            "Slack Order push failed! OrderId: " . $orderId . $ex->getMessage() . " - Error: " . json_encode($attachment->getAttachment()),
                            1, 1);
                    }


                    ///////////////////////////////////////////////////////////////////////////////////////
                    ///////////////////////// BUZZ RETAILER TABLET TO INFORM //////////////////////////////
                    ///////////////////////////////////////////////////////////////////////////////////////

                    if (!empty($tabletMobilockId)) {

                        // Buzz failed
                        if (!sendBuzzToPOSTablet($tabletMobilockId, 'order process ' . $orderObject->getObjectId())) {

                            json_error("AS_516", "",
                                "Buzz to retailer (" . $retailerUniqueId . ") failed for order (" . $orderId . ")", 1,
                                1);
                        }
                    }

                    ///////////////////////////////////////////////////////////////////////////////////////
                    /////////////////////// ORDER STATUS :: Pushed to Retailer ////////////////////////////
                    ///////////////////////////////////////////////////////////////////////////////////////

                    orderStatusChange_PushedToRetailer($orderObject);

                    ///////////////////////////////////////////////////////////////////////////////////////


                    ////////////////////////////////////////////////////////////////////////////////////
                    // Put on Queue to confirm retailer acceptance after 120 seconds
                    ////////////////////////////////////////////////////////////////////////////////////
                    // Put order on the queue for retailer acceptance or confirm
                    // try {

                    // 	$workerQueue->sendMessage(
                    // 			array("action" => "order_confirm_accepted_by_retailer",
                    // 				  "content" =>
                    // 				  	array(
                    // 				  		"orderId" => $orderId,
                    // 		  			)
                    // 				),
                    // 				// DelaySeconds for 1 minute
                    // 				120
                    // 			);
                    // }
                    // catch (Exception $ex) {

                    // 	$response = json_encode($ex->getMessage(), true);
                    // 	return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"], $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"], 1);
                    // }
                    //////////////////////////////////////////////////////////////////////////////////////////


                    $orderObject->set("interimOrderStatus", 4);
                    $orderObject->save();
                }
            }
            ///////////////////////////////////////////////////////////////////////////////////////


            ///////////////////////////////////////////////////////////////////////////////////////
            ///////////////////////////// EXT PARTNER ONLY ORDER //////////////////////////////////
            ///////////////////////////////////////////////////////////////////////////////////////

            // If external partner retailer but doesn't require tablet
            else {
                if (!empty($dualPartnerConfig)
                    && $dualPartnerConfig->get('tabletIntegrated') == false
                ) {

                    return processOrderDualConfig($orderId, true);
                } // This is triggered for DualConfig + Tablet, but when order fails to submit to partner via method processOrderDualConfig, however the order was manually pushed
                else {
                    if (!empty($dualPartnerConfig)
                        && $dualPartnerConfig->get('tabletIntegrated') == true
                        && in_array($orderObject->get('status'), listStatusesForConfirmedByTablet())
                    ) {

                        try {

                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue->sendMessage(
                                array(
                                    "action" => "order_submission_process_dualconfig",
                                    "content" =>
                                        array(
                                            "orderId" => $orderObject->getObjectId()
                                        )
                                ), 5 // secs later
                            );
                        } catch (Exception $ex) {

                            json_error("AS_1062", "",
                                "Order processing to push to Dual Config retailer failed! Order Id = " . $order->getObjectId() . " - " . $ex->getMessage(),
                                1, 1);
                        }
                    }


                    ///////////////////////////////////////////////////////////////////////////////////////
                    ///////////////////////// APP TABLET & DUAL CONFIG ORDER //////////////////////////////
                    ///////////////////////////////////////////////////////////////////////////////////////

                    // If App Tablet Retailer
                    else {

                        ///////////////////////////////////////////////////////////////////////////////////////
                        ////////////////// PUSHED TO RETAILER WHEN APP REQUESTS ORDER /////////////////////////
                        ///////////////////////////////////////////////////////////////////////////////////////
                    }
                }
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////


    // Return empty error message signifying success
    return "";
}

function processOrderDualConfig($orderId, $directProcessing = false)
{

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId, "__NE__interimOrderStatus" => -1), "Order", "", "",
        array(
            "retailer",
            "retailer.location",
            "user",
            "sessionDevice",
            "sessionDevice.userDevice",
            "deliveryLocation",
            "coupon",
            "coupon.applicableUser",
            "flightTrip.flight"
        ), 1);

    if (count_like_php5($orderObject) == 0) {

        // Warning, but no blocking error (processFlag = 1)
        return order_processing_error("", "AS_321", "",
            "Order could not be processed! Order Id: " . $orderId . " not found", 3, 1, "", 1);
    } // If interimOrderStatus == -1
    else {
        if ($orderObject->get('interimOrderStatus') == -1) {

            // Warning, but no blocking error (processFlag = 1)
            return order_processing_error("", "AS_321", "",
                "Order could not be processed, still in pending status! Order Id: " . $orderId . " not found", 3, 1, "",
                1);
        }
        // If not in submitted or awaiting confirmation state, return true
        // else if(!in_array($orderObject->get('status'), listStatusesForSubmittedOrAwaitingConfirmation())) {

        // 	json_error("AS_3020", "", "Order Processing skipped as order was not in submitted or awating conf state. Order Id: " . $orderId, 3, 1);
        // 	return "";
        // }
        // Is order cancelled, if so skip processing
        else {
            if (isOrderCancelled($orderObject)) {

                json_error("AS_3011", "", "Order Processing skipped as order was cancelled. Order Id: " . $orderId, 3,
                    1);
                return "";
            } else {
                if (!in_array($orderObject->get('status'), listStatusesForConfirmedByTablet())
                    && $directProcessing == false
                ) {

                    json_error("AS_3022", "",
                        "Order Processing skipped as order was not in Tablet confirmed state. Order Id: " . $orderId, 3,
                        1);
                    return "";
                }
            }
        }
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Submission Attempt verification
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    $orderObject->increment('submissionAttempt');
    $orderObject->save();
    $submissionAttempt = $orderObject->get('submissionAttempt');

    if ($submissionAttempt != 3) {

        decrementSubmissionAttempt($orderObject);
        return order_processing_error($orderObject, "AS_301", "",
            "Order could not be processed! Order Id: " . $orderId . ", Duplicate Order Process run caught! test2", 1,
            1);
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Fetch POS Config
    //////////////////////////////////////////////////////////////////////////////////////////////////////
    // Fetch POS Config data
    $objectParseQueryPOSConfig = parseExecuteQuery(array("retailer" => $orderObject->get('retailer')),
        "RetailerPOSConfig", "", "", ["dualPartnerConfig"], 1);

    // If Config is NOT found, let caller know
    if (count_like_php5($objectParseQueryPOSConfig) == 0) {

        decrementSubmissionAttempt($orderObject);
        return order_processing_error($orderObject, "AS_303", "",
            "Retailer not found! POS Config not found for the uniqueRetailerId (" . $orderObject->get('retailer')->get('uniqueId') . ") OrderId - " . $orderObject->getObjectId(),
            1, 1);
    } // Process the order
    else {

        $dualPartnerConfig = $objectParseQueryPOSConfig->get('dualPartnerConfig');
        $tenderTypeId = $objectParseQueryPOSConfig->get('tenderTypeId');

        // HMS Host
        if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

            try {

                $hmshost = new HMSHost($dualPartnerConfig->get('airportId'), $dualPartnerConfig->get('retailerId'),
                    $orderObject->get('retailer')->get('uniqueId'), 'order');
            } catch (Exception $ex) {

                decrementSubmissionAttempt($orderObject);
                json_error("AS_895", "", "Failed to connect to HMSHost for cart totals, Order Id: " . $orderId, 1);
            }

            list($orderSummaryArray, $retailerTotals) = getOrderSummary($orderObject, 1);
            $cartFormatted = $hmshost->format_cart($orderSummaryArray, $retailerTotals, $tenderTypeId, 2);

            // Cart Id
            try {

                $transactionId = $hmshost->push_cart_for_submission($orderSummaryArray["internal"]["orderIdDisplay"],
                    $cartFormatted);
            } catch (Exception $ex) {

                decrementSubmissionAttempt($orderObject);

                if ($directProcessing == true) {

                    decrementSubmissionAttempt($orderObject);
                }

                return order_processing_error($orderObject, "AS_896", "",
                    "Failed to submit order, Order Id: " . $orderId . " - " . $ex->getMessage(), 1, 1);
            }

            $orderObject->set("orderPOSId", $transactionId);

            // Mark status as accepted by Retailer
            orderStatusChange_ConfirmedByRetailer($orderObject);

            $orderObject->save();

            // If delivery order, attempt to assign delivery person
            if (strcasecmp($orderObject->get('fullfillmentType'), 'd') == 0) {

                // Assign order for delivery for dualConfig order
                try {

                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue->sendMessage(
                        array(
                            "action" => "order_delivery_assign_delivery",
                            "content" =>
                                array(
                                    "orderId" => $orderId
                                )
                        ), 5
                    );
                } catch (Exception $ex) {

                    return order_processing_error($orderObject, "AS_303", "",
                        "Dual Config order assign for delivery failed! uniqueRetailerId (" . $orderObject->get('retailer')->get('uniqueId') . ") OrderId - " . $orderObject->getObjectId(),
                        1, 1);
                }
            } // If pickup order, set complete time
            else {

                try {

                    // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue->sendMessage(
                        array(
                            "action" => "order_pickup_mark_complete",
                            "processAfter" => ["timestamp" => $orderObject->get('etaTimestamp')],
                            "content" =>
                                array(
                                    "orderId" => $orderId,
                                    "etaTimestamp" => $orderObject->get('etaTimestamp')
                                )
                        ),
                        // DelaySeconds after ETA timestamp
                        $workerQueue->getWaitTimeForDelay($orderObject->get('etaTimestamp'))
                    );
                } catch (Exception $ex) {

                    $response = json_decode($ex->getMessage(), true);
                    json_error($response["error_code"], $response["error_message_user"],
                        $response["error_message_log"] . " OrderId - " . $orderId, $response["error_severity"], 1);
                }
            }
        } else {

            decrementSubmissionAttempt($orderObject);
            return order_processing_error($orderObject, "AS_303", "",
                "No exnternal processing class not found! uniqueRetailerId (" . $orderObject->get('retailer')->get('uniqueId') . ") OrderId - " . $orderObject->getObjectId(),
                1, 1);
        }
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////

    return "";
}

function markPickupOrderComplete($orderId)
{

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array(
        "retailer",
        "user",
        "sessionDevice",
        "sessionDevice.userDevice",
        "deliveryLocation",
        "coupon",
        "coupon.applicableUser",
        "flightTrip.flight"
    ), 1);

    // Is order cancelled, if so skip processing
    if (isOrderCancelled($orderObject)) {

        json_error("AS_3011", "", "Order Pickup mark complete skipped as order was cancelled.", 3, 1);
        return "";
    } // Check if the order status is still In Progress
    else {
        if (!in_array($orderObject->get('status'), listStatusesForInProgress())) {

            return order_processing_error($orderObject, "AS_320", "",
                "Order Status Type not in Progress" . ' - PickupComplete - ' . " OrderId - " . $orderObject->getObjectId(),
                1, 1);
        }
    }


    ///////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////// ORDER STATUS :: Completed /////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    $response = orderStatusChange_Completed($orderObject);

    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"],
            1);
    }
    ///////////////////////////////////////////////////////////////////////////////////////

    $orderObject->save();

    /*
    $orderSeqId = $orderObject->get('orderSequenceId');
    $customerName = $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName');
    $retailerName = $orderObject->get('retailer')->get('retailerName');
    $retailerLocation = $orderObject->get('retailer')->get('location')->get('airportIataCode') . ', ' . $orderObject->get('retailer')->get('location')->get('locationDisplayName');//gateDisplayName

    //slack notification

    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'order-notification');
    $slackMessage = createOrderNotificationSlackMessageByAirportIataCode($orderObject->get('retailer')->get('location')->get('airportIataCode'));

    $slackMessage->setText("Pickup Order Accepted by Retailer");
    $attachment = $slackMessage->addAttachment();
    $attachment->addField("Order ID :", $orderSeqId, true);
    $attachment->addField("ENV :", getenv('env_EnvironmentDisplayCode'), true);
    $attachment->addField("Retailer:", $retailerName . " (" . $retailerLocation . ")", false);
    $attachment->addField("Customer:", $customerName, false);

    try {
        // Post to order help channel
        $slackMessage->send();
    } catch (Exception $ex) {
        //throw $ex;
    }
    */


    return orderCompleteMethods($orderObject);
}


function sendNotificationPickupOrderAccepted($orderId)
{

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", [
        "retailer",
        "retailer.location",
        "user",
        "sessionDevice",
        "sessionDevice.userDevice",
        "deliveryLocation",
        "coupon",
        "coupon.applicableUser",
        "flightTrip.flight"
    ], 1);

    // Is order cancelled, if so skip processing
    if (isOrderCancelled($orderObject)) {

        json_error("AS_3011", "", "Order Pickup mark complete skipped as order was cancelled.", 3, 1);
        return "";
    } // Check if the order status is still In Progress
    else {
        if (!in_array($orderObject->get('status'), listStatusesForInProgress())) {

            return order_processing_error($orderObject, "AS_320", "",
                "Order Status Type not in Progress" . ' - PickupComplete - ' . " OrderId - " . $orderObject->getObjectId(),
                1, 1);
        }
    }


    $orderSeqId = $orderObject->get('orderSequenceId');
    $customerName = $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName');
    $retailerName = $orderObject->get('retailer')->get('retailerName');
    $retailerLocation = $orderObject->get('retailer')->get('location')->get('airportIataCode') . ', ' . $orderObject->get('retailer')->get('location')->get('locationDisplayName');//gateDisplayName

    //slack notification

    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'order-notification');
    $slackMessage = createOrderNotificationSlackMessageByAirportIataCode($orderObject->get('retailer')->get('location')->get('airportIataCode'));

    $slackMessage->setText("Pickup Order Accepted by Retailer");
    $attachment = $slackMessage->addAttachment();
    $attachment->addField("Order ID :", $orderSeqId, true);
    $attachment->addField("ENV :", getenv('env_EnvironmentDisplayCode'), true);
    $attachment->addField("Retailer:", $retailerName . " (" . $retailerLocation . ")", false);
    $attachment->addField("Customer:", $customerName, false);


    try {
        // Post to order help channel
        $slackMessage->send();
    } catch (Exception $ex) {
        //throw $ex;
    }


    return orderCompleteMethods($orderObject);
}


function informOrderVarrianceOnSlack(&$orderObject)
{

    $orderId = $orderObject->get('orderId');

    $objectParseQueryOrder = $orderObject;
    $orderId = $objectParseQueryOrder->getObjectId();

    // Order Totals
    $totalsForRetailer = json_decode($objectParseQueryOrder->get("totalsForRetailer"), true);
    $totalsFromPOS = json_decode($objectParseQueryOrder->get("totalsFromPOS"), true);

    $total = $totalsForRetailer["Total"];
    $totalPOS = $totalsFromPOS["Total"];

    // Fetch Customer Name
    $customerName = $objectParseQueryOrder->get('user')->get('firstName') . ' ' . $objectParseQueryOrder->get('user')->get('lastName');


    $submissionDateTime = date("M j, g:i a", $objectParseQueryOrder->get('submitTimestamp'));

    ///////////////////////////////////////////////////////////////////////////////////////
    // Prepare for Slack post
    ///////////////////////////////////////////////////////////////////////////////////////
    $slack = new SlackMessage($GLOBALS['env_SlackWH_ordersBalVariance'], 'env_SlackWH_ordersBalVariance');
    $slack->setText("Order: " . $objectParseQueryOrder->getObjectId() . " (" . $objectParseQueryOrder->get('retailer')->get('retailerName') . ")");

    $attachment = $slack->addAttachment();
    $attachment->addField("Customer:", $customerName, true);
    $attachment->addField("Submitted:", $submissionDateTime, true);
    $attachment->addField("POS Total:", "$" . ($totalPOS / 100), true);
    $attachment->addField("Paid Total:", "$" . ($total / 100), true);

    try {

        $slack->send();
    } catch (Exception $ex) {

        return order_processing_error($orderObject, "AS_1054", "",
            "Slack post failed informing Order Balance Varriance! orderId=(" . $orderObject->getObjectId() . "), Post Array=" . json_encode($responseArray) . " -- " . $ex->getMessage(),
            3, 1, "", 1);
    }

    // Return empty error message signifying success
    return "";
}

function verifyPendingOrderErrors(&$orderObject)
{

    $orderId = $orderObject->get('orderId');

    // Get Any Pending Errors on the order
    $lastProcessingErrorMessage = "";
    $objectParseQueryOrderProcessingErrors = parseExecuteQuery(array("order" => $orderObject, "processStatusFlag" => 0),
        "OrderProcessingErrors");

    // If Found && processed flag is set to 0 (not fixed yet)
    if (count_like_php5($objectParseQueryOrderProcessingErrors) > 0) {

        $lastProcessingErrorMessage = $objectParseQueryOrderProcessingErrors[0]->get('lastProcessingErrorMessage');
    }

    // Check OrderProcessingErrors Class if this Order exists with a pending error Flag, if so don't process
    // If an older error was found
    // Or after processing an error was found
    // Inform on Slack
    if (!empty($lastProcessingErrorMessage)) {

        // respond with error
        return json_error_return_array("AS_1031", "",
            "Repeat Processing error orderId=(" . $orderObject->getObjectId() . "), lastProcessingErrorMessage=" . $lastProcessingErrorMessage,
            3, 1);
    }

    // Return empty error message signifying success
    return "";
}

function newOrderNotification(&$orderObject)
{

    $orderId = $orderObject->get('orderId');

    $submissionDateTime = date("M j, g:i a", $orderObject->get('submitTimestamp'));
    $orderTotalArray = json_decode($orderObject->get('totalsWithFees'), true);
    $orderTotal = $orderTotalArray["TotalDisplay"];

    // Fetch Item Counts
    $objParseQueryOrderModifiersResults = parseExecuteQuery(array("order" => $orderObject), "OrderModifiers");
    $orderItemCount = count_like_php5($objParseQueryOrderModifiersResults);
    unset($objParseQueryOrderModifiersResults);

    // Fetch Retailer Name associated with the Order
    $objectParseQueryRetailer = $orderObject->get('retailer');
    $retailerName = $objectParseQueryRetailer->get('retailerName');
    $retailerIataCode = $objectParseQueryRetailer->get('airportIataCode');
    unset($objectParseQueryRetailer);

    // Fetch Customer Name
    $objectParseQueryUser = $orderObject->get('user');
    $customerName = $objectParseQueryUser->get('firstName') . ' ' . $objectParseQueryUser->get('lastName');
    unset($objectParseQueryUser);

    ///////////////////////////////////////////////////////////////////////////////////////
    // Prepare for Slack post
    ///////////////////////////////////////////////////////////////////////////////////////

    // Slack it
    $slack = createOrderNotificationSlackMessage($orderObject->getObjectId());
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
    $slack->setText($retailerName);

    $app ='';
    if ($orderObject->get('sessionDevice')->get('userDevice')->has('isIos')
        &&
        $orderObject->get('sessionDevice')->get('userDevice')->get('isIos') == true
    )
    {
        $app = 'iOS';
    }
    elseif ($orderObject->get('sessionDevice')->get('userDevice')->has('isAndroid')
        &&
        $orderObject->get('sessionDevice')->get('userDevice')->get('isAndroid') == true
    )
    {
        $app = 'Android';
    }
    elseif (
        $orderObject->get('sessionDevice')->get('userDevice')->has('isWeb')
        &&
        $orderObject->get('sessionDevice')->get('userDevice')->get('isWeb') == true
    )
    {
        $app = 'Web';
    }

    $partnerOrderInfo = '';
    if (!empty($orderObject->get('partnerName'))){
        $partnerName = $orderObject->get('partnerName');
        $partnerOrderId = trim($orderObject->get('partnerOrderId'));
        if (empty($partnerOrderId)){
            $partnerOrderId = '- not set -';
        }
        $partnerOrderInfo = 'Partner name: '.$partnerName.', Partner Order Id: '.$partnerOrderId;
    }

    $attachment = $slack->addAttachment();
    $attachment->addField("Order Id:", $orderObject->getObjectId(), true);
    $attachment->addField("Order Id:", $orderObject->get("orderSequenceId"), true);
    if (!empty($partnerOrderInfo)){
        $attachment->addField("Parner Order Info:", $partnerOrderInfo, false);
    }
    $attachment->addField("Airport:", $retailerIataCode, true);
    $attachment->addField("Submitted:", $submissionDateTime, true);
    $attachment->addField("Customer:", $customerName, false);
    $attachment->addField("App:", $app, true);
    $attachment->addField("Coupon:",
        $orderObject->has('coupon') == true ? $orderObject->get('coupon')->get('couponCode') : ' ', true);
    $attachment->addField("Total:", $orderTotal, true);
    $attachment->addField("Type:", $orderObject->get('fullfillmentType') == 'p' ? 'Pickup' : 'Delivery', true);

    if (strcasecmp($orderObject->get('fullfillmentType'), 'd') == 0) {


        // TAG
        $attachment->addField("Delivery Location:", $orderObject->get('deliveryLocation')->get('locationDisplayName'),
            false);
    }

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
                $flight->get("info")->get("airlineIataCode") . ' ' . $flight->get("info")->get("flightNum"), false);
            $attachment->addField("To", $flight->get('arrival')->getAirportInfo()['airportIataCode'], true);
            $attachment->addField("Departure Time", $flight->get('departure')->getLastKnownTimestampFormatted(), true);
            $attachment->addField("Boarding Time", $flight->get('departure')->getBoardingTimestampFormatted(), true);
            $attachment->addField("Gate", $flight->get('departure')->getGateDisplayName(), true);
        }
    }

    try {

        $slack->send();
    } catch (Exception $ex) {

        return order_processing_error($orderObject, "AS_1014", "",
            "Slack post failed informing Order Notification! orderId=(" . $orderObject->getObjectId() . ") -- " . $ex->getMessage(),
            3, 1, "", 1);
    }


    // $text = $retailerName;

    // $attachments = array(
    // array(
    // 			"fallback" => $text,
    // 			"fields" => array(
    // 							array("title" => "ENV:", "value" => $GLOBALS['env_EnvironmentDisplayCode'], "short" => false),
    // 							array("title" => "Order Id:", "value" => trim($orderObject->getObjectId()), "short" => true),
    // 							array("title" => "Airport:", "value" => trim($retailerIataCode), "short" => true),
    // 							array("title" => "Customer:", "value" => trim($customerName), "short" => true),
    // 							array("title" => "Submitted:", "value" => trim($submissionDateTime), "short" => true),
    // 							array("title" => "Total:", "value" => trim($orderTotal), "short" => true),
    // 							array("title" => "Type:", "value" => $orderObject->get('fullfillmentType') == 'p' ? 'Pickup' : 'Delivery', "short" => true),
    // 						)
    // 		)
    // );

    // $responseArray = array("text" => trim($text), "attachments" => $attachments);

    // try {

    // 	// Post to order-notifications channel
    // 	$response = Request::post($GLOBALS['env_SlackWH_orderNotifications'])
    // 				->body(json_encode($responseArray))
    // 				->send();
    // }
    // catch (Exception $ex) {

    // 	return order_processing_error($orderObject, "AS_1014", "", "Slack post failed informing Order Notification! orderId=(" . $orderObject->getObjectId() . "), Post Array=" . json_encode($responseArray) ." -- " . $ex->getMessage(), 3, 1, "", 1);
    // }

    // Return empty error message signifying success
    return "";
}

function sendTabletSlackNotification(&$orderObject, $tabletSlackURL)
{

    // If no tablet slack URL found
    if (empty($tabletSlackURL)) {

        return "";
    }

    $orderId = $orderObject->get('orderId');

    $submissionDateTime = date("M j, g:i a", $orderObject->get('submitTimestamp'));
    $orderTotalArray = json_decode($orderObject->get('totalsWithFees'), true);
    $orderTotal = $orderTotalArray["TotalDisplay"];

    // Fetch Item Counts
    $objParseQueryOrderModifiersResults = parseExecuteQuery(array("order" => $orderObject), "OrderModifiers");
    $orderItemCount = count_like_php5($objParseQueryOrderModifiersResults);

    // Fetch Customer Name
    $customerName = $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName');

    // Fetch retailer name
    $retailerName = $orderObject->get('retailer')->get('retailerName');

    // Slack it
    $slack = new SlackMessage($tabletSlackURL, 'tabletSlackURL - ' . $retailerName);
    $slack->setText($orderObject->get('fullfillmentType') == 'p' ? 'Pickup' : 'Delivery');

    $attachment = $slack->addAttachment();
    $attachment->addField("Customer:", $customerName, false);
    $attachment->addField("Order Id:", $orderObject->get('orderSequenceId'), false);
    $attachment->addField("Order Date:", $submissionDateTime, false);
    $attachment->addField("Order Total:", $orderTotal, false);

    try {

        $slack->send();
    } catch (Exception $ex) {

        return order_processing_error($orderObject, "AS_1030", "",
            "Slack Retailer Tablet Order Notification failed! orderId=(" . $orderObject->getObjectId() . "), Post Array=" . json_encode($responseArray) . " -- " . $ex->getMessage(),
            3, 1, "", 1);
    }

    // Return empty error message signifying success
    return "";
}

function retailerRequestingHelpFromTablet($orderId)
{

    try {

        orderHelpContactCustomerService($orderId, "", 'Retailer requesting order support from tablet.');
    } catch (Exception $ex) {

        $error_array = json_decode($ex->getMessage(), true);

        if (is_array($error_array)) {

            return order_processing_error($order, $error_array["error_code"], "", $error_array["error_message_log"], 3,
                1, "", 1);
        } else {

            return order_processing_error("", "AS_1000", "", $ex->getMessage(), 3, 1, "", 1);
        }
    }

    return "";
}

function retailerEarlyClose($uniqueId, $closeLevel, $closeForSecs)
{

    try {
        // check if we still need to close retailer
        // if we don't, then log it only and return
        if (!isRetailerCloseEarlyForNewOrders($uniqueId)) {
            json_error("AS_2009", "Retailer reopened business right after closing for new orders",
                "retailerEarlyClose found that setRetailerClosedEarly is not needed for retailer " . $uniqueId, 3, 1);
            return '';
        }

        // Is retailer already closed
        if (isRetailerClosedEarly($uniqueId)) {

            json_error("AS_2009", "", "retailerEarlyClose found that retailer was already closed " . $uniqueId, 3, 1);
            return '';
        }

        if (strcasecmp($closeForSecs, "EOD") != 0) {

            $closeForSecs = intval($closeForSecs);
        }

        setRetailerClosedEarly($uniqueId, $closeLevel, $closeForSecs);
    } catch (Exception $ex) {

        return json_error_return_array("AS_2008", "", $ex->getMessage(), 1);
    }

    return "";
}

function cancel_order_by_ops($orderId, $cancelOptions = [], $adminOverride = false)
{

    try {

        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
    } catch (Exception $ex) {

        return json_decode($ex->getMessage(), true);
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////

    $orderObject = parseExecuteQuery(["objectId" => $orderId], "Order", "", "",
        ["retailer", "user", "sessionDevice", "sessionDevice.userDevice", "coupon"], 1);
    ///////////////////////////////////////////////////////////////////////////////////////

    if (!isOrderCancellable($orderObject)
        && $adminOverride == false
    ) {

        // Slack inform
        informOpsOrderCancellationRequestFailed($orderObject, ' - Order Status no longer cancellable.');

        // Order already confirmed
        $error_array = order_processing_error($orderObject, "AS_2007", "",
            "Order can not be canceled.  orderId=(" . $orderId . ") -- ", 3, 1, "", 1);

        json_error($error_array["error_code"], "", $error_array["error_message_log"], $error_array["error_message_log"],
            1, 1);

        return "";
    }

    // Has the Order ETA time passed
    if ($orderObject->get('etaTimestamp') < time()) {

        // But fail if order is not in Awaiting Confirmation
        // and Admin override is false
        if (!in_array($orderObject->get('status'), listStatusesForAwaitingConfirmation())
            && $adminOverride == false
        ) {

            // Slack inform
            informOpsOrderCancellationRequestFailed($orderObject,
                ' - Order cannot be cancelled after pickup/delivery time. Attempt to cancel with Admin or else raise ticket for Tech team to handle before close of business today.');

            // Order already confirmed
            $error_array = order_processing_error($orderObject, "AS_2007", "",
                "Order ETA time has passed, and hence can not be canceled.  orderId=(" . $orderId . ") -- ", 3, 1, "",
                1);

            json_error($error_array["error_code"], "", $error_array["error_message_log"],
                $error_array["error_message_log"], 1, 1);

            return "";
        }
    }
    ///////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////// Update Order Reason Details ///////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    if (isset($cancelOptions["cancelReasonCode"])) {

        $orderObject->set("cancelReasonCode", sanitize($cancelOptions["cancelReasonCode"]));
    }
    if (isset($cancelOptions["cancelReason"])) {

        $orderObject->set("cancelReason", sanitize($cancelOptions["cancelReason"]));
    }
    if (isset($cancelOptions["refundType"])) {

        $orderObject->set("refundType", sanitize($cancelOptions["refundType"]));
    }
    if (isset($cancelOptions["refundRetailer"])) {

        $orderObject->set("refundRetailer", convertToBoolFromInt(sanitize($cancelOptions["refundRetailer"])));
    }
    if (isset($cancelOptions["partialRefundAmount"])) {

        $orderObject->set("partialRefundAmount", intval(sanitize($cancelOptions["partialRefundAmount"])));
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    //////////////////////// ORDER STATUS :: Cancelled by Ops /////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    $response = orderStatusChange_CancelledByOps($orderObject);
    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), $response["error_severity"],
            1);
    }

    // TAG
    // Save Order
    $orderObject->save();

    if ($orderObject->has("coupon")
        && couponAddedToOrderViaCart($orderObject)
    ) {
        $orderObject->get("coupon")->fetch();
        // If order had a coupon usage and was added in cart (not pre-applied via UserCoupons), let's update local usage cache
        addCouponUsageByUser($orderObject->get("user")->getObjectId(), $orderObject->get("coupon")->get("couponCode"),
            false);
        addCouponUsageByCode($orderObject->get("coupon")->get("couponCode"), false);
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////// If order had credits //////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    // Adjust availableCredits if needed
    $userCreditsAppliedMap = parseExecuteQuery(array("appliedToOrder" => $orderObject), "UserCreditsAppliedMap",
        "createdAt", "", array("userCredit"));

    // TAG
    if (count_like_php5($userCreditsAppliedMap) > 0) {

        $userCreditRowsToAdjust = [];
        foreach ($userCreditsAppliedMap as $creditMapForOrder) {

            // Get all map rows for the userCredit used for the order
            // So we can adjust the availableCredits
            $userCreditsAppliedMapForUpdate = parseExecuteQuery(array("userCredit" => $creditMapForOrder->get('userCredit')),
                "UserCreditsAppliedMap", "createdAt", "", array("appliedToOrder"));

            foreach ($userCreditsAppliedMapForUpdate as $creditMap) {

                // Check if this credit row was used on another order
                // If so we will need to adjust the availableCredit rows
                if ($creditMap->get('userCredit')->getObjectId() != $orderObject->getObjectId()) {

                    $userCreditRowsToAdjust[$creditMap->get('userCredit')->getObjectId()] = 1;
                }
            }
        }

        foreach (array_keys($userCreditRowsToAdjust) as $userCreditObjectId) {

            // Get the total credits available for it
            $userCreditsToAdjust = parseExecuteQuery(array("objectId" => $userCreditObjectId), "UserCredits", "", "",
                [], 1);

            // Get all applied rows for this credit
            // We will skip the canceled orders and fix the availableCredit for the rest
            $userCreditsAppliedMapForUpdate = parseExecuteQuery(array("userCredit" => $userCreditsToAdjust),
                "UserCreditsAppliedMap", "createdAt", "", array("appliedToOrder"));

            $availableCreditsInCents = $userCreditsToAdjust->get('creditsInCents');
            foreach ($userCreditsAppliedMapForUpdate as $rowToUpdate) {

                // If canceled
                if (in_array($rowToUpdate->get('appliedToOrder')->get('status'), listStatusesForCancelled())) {

                    continue;
                }

                // Deduct used credits
                $availableCreditsInCents = $availableCreditsInCents - $rowToUpdate->get('appliedCreditsInCents');

                $rowToUpdate->set('availableCreditsInCents', $availableCreditsInCents);
                $rowToUpdate->save();
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////// Process Refunds /////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////

    // Refund to Payment soruce
    if (strcasecmp($cancelOptions["refundType"], "source") == 0) {

        $response = refundOrderToSource($orderObject->getObjectId(), $orderObject->get('paymentId'));
    } // Full Refund as Credits
    else {
        if (strcasecmp($cancelOptions["refundType"], "fullcredit") == 0) {

            $response = refundOrderWithCredits($orderObject);
        } // Partial Refund as Credits
        else {
            if (strcasecmp($cancelOptions["refundType"], "partialcredit") == 0) {

                $response = refundOrderWithCredits($orderObject, intval($cancelOptions["partialRefundAmount"]), true);
            }
        }
    }

    if (is_array($response)) {

        return order_processing_error($orderObject, $response["error_code"], $response["error_message_user"],
            "Refund faied - " . $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(),
            $response["error_severity"], 1);
    }
    ///////////////////////////////////////////////////////////////////////////////////////

    $referralUsedWarning = "";
    if (hasOrderHaveReferralEarnedAndCreditUsed($orderObject)[0] == true) {

        $referralUsedWarning .= "Referral reward from this order has been earned and can be used without this user reordering.";
    }

    if (hasOrderHaveReferralEarnedAndCreditUsed($orderObject)[1] == true) {

        $referralUsedWarning .= "Referral reward from this order has been earned and used by the referrer.";
    }

    ////////////////////////////////////////////////////////////////////////////////////
    // Slack message for Ops know to refund the order
    ////////////////////////////////////////////////////////////////////////////////////
    $slack = createOrderNotificationSlackMessage($orderObject->getObjectId());
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
    $slack->setText('Order Cancelled!');

    $attachment = $slack->addAttachment();
    $attachment->addField("Order Id:", $orderObject->getObjectId(), true);
    $attachment->addField("Order Id:", $orderObject->get("orderSequenceId"), true);
    $attachment->addField("Airport:", $orderObject->get('retailer')->get('airportIataCode'), true);
    $attachment->addField("Type:", $orderObject->get('fullfillmentType') == 'p' ? 'Pickup' : 'Delivery', true);
    $attachment->addField("Customer:",
        $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName'), false);
    $attachment->addField("Reason Code:", $cancelOptions["cancelReasonCode"], true);
    $attachment->addField("Refund:", ucwords($cancelOptions["refundType"]), true);
    $attachment->addField("Refund Retailer?", intval($cancelOptions["refundRetailer"]) == 1 ? "Y" : "N", false);

    if ($adminOverride) {

        $attachment->addField("Status:", 'Cancelled by Admin Override!', false);
    } else {

        $attachment->addField("Status:", 'Cancelled by Ops!', false);
    }

    $attachment->addField("Reason:", $cancelOptions["cancelReason"], false);

    if (!empty($referralUsedWarning)) {

        $attachment->addField("Notes:", $referralUsedWarning, false);
    }

    try {

        $slack->send();
    } catch (Exception $ex) {

        return order_processing_error($orderObject, "AS_1014", "",
            "Slack post failed informing Order cancellation Notification! orderId=(" . $orderObject->getObjectId() . ") -- " . $ex->getMessage(),
            3, 1, "", 1);
    }
    //////////////////////////////////////////////////////////////////////////////////////////

    return "";
}

function informOpsOrderCancellationRequestFailed($orderObject, $error_msg = '')
{

    ////////////////////////////////////////////////////////////////////////////////////
    // Slack message for Ops know to refund the order
    ////////////////////////////////////////////////////////////////////////////////////
    $slack = createOrderNotificationSlackMessage($orderObject->getObjectId());
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
    $slack->setText('Order Cancellation failed!');

    $attachment = $slack->addAttachment();
    $attachment->addField("Order Id:", $orderObject->getObjectId(), true);
    $attachment->addField("Order Id:", $orderObject->get("orderSequenceId"), true);
    $attachment->addField("Airport:", $orderObject->get('retailer')->get('airportIataCode'), true);
    $attachment->addField("Type:", $orderObject->get('fullfillmentType') == 'p' ? 'Pickup' : 'Delivery', true);
    $attachment->addField("Customer:",
        $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName'), false);
    $attachment->addField("Status:", 'Cancellation request failed!' . $error_msg, false);

    try {

        $slack->send();
    } catch (Exception $ex) {

        return order_processing_error($orderObject, "AS_1014", "",
            "Slack post failed informing failed Order cancellation Notification! orderId=(" . $orderObject->getObjectId() . ") -- " . $ex->getMessage(),
            3, 1, "", 1);
    }
    //////////////////////////////////////////////////////////////////////////////////////////

    return "";
}

function referral_reward_earn_qualify($orderId)
{

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "",
        array("retailer", "deliveryLocation", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

    // If the user was acquired through referral
    // And we have not yet awardd the earnings for him/her
    if (wasUserBeenAcquiredViaReferral($orderObject->get('user')) == true
        && hasReferralAlreadyBeenEarned($orderObject->get('user')) == false
    ) {

        // $userWhoReferred = parseExecuteQuery(["reasonForCredit" => "Signup Referral Code", "user" => $orderObject->get('user')], "UserCredits", "", "createdAt", ["userReferral.user"], 1);
        $userReferralSignupCredits = parseExecuteQuery([
            "reasonForCreditCode" => getUserCreditReasonCode('ReferralSignup'),
            "user" => $orderObject->get('user')
        ], "UserCredits", "", "createdAt", ["userReferral.user"], 1);
        $userWhoReferred = $userReferralSignupCredits->get('userReferral')->get('user');

        ///////////////////////////////////////////////////////////////////////////
        // Disqualification checks
        ///////////////////////////////////////////////////////////////////////////
        $overrideReward = -1;
        $overrideRewardReason = "";
        // Is the Referring User locked
        if ($userWhoReferred->get("isLocked") == true) {

            $overrideReward = 0;
            $overrideRewardReason = "No reward given since referring account is locked";
        }

        // Disqualification checks
        // Has the referring user earned max rewards per time
        else {
            if (hasMaxReferralRewardsEarned($userWhoReferred) == true) {

                $overrideReward = 0;
                $overrideRewardReason = "No reward given since max rewards reached for given time period";
            }
        }
        ///////////////////////////////////////////////////////////////////////////

        return scheduleRewardEarn($orderObject, $userWhoReferred, $overrideReward, $overrideRewardReason);
    }

    return "";
}

function referral_reward_process($orderId, $userWhoReferredId, $overrideReward, $overrideRewardReason)
{

    ///////////////////////////////////////////////////////////////////////////////////////
    // Fetch Order object and get initial info
    ///////////////////////////////////////////////////////////////////////////////////////
    $orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "",
        array("retailer", "deliveryLocation", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

    // If we have not yet awarded the earnings for him/her
    if (hasReferralAlreadyBeenEarned($orderObject->get('user')) == false) {

        $userWhoReferred = parseExecuteQuery(array("objectId" => $userWhoReferredId), "_User", "", "", [], 1);

        // == 2 means reward has to be awarded
        if (logOrderAndValidateReferralEarning($orderObject, $userWhoReferred) == 2) {

            $rewardInCents = rewardReferralCredit($orderObject, $userWhoReferred, $overrideReward,
                $overrideRewardReason);
            if ($rewardInCents > 0) {

                informUserOfReferralReward($userWhoReferred, $orderObject, dollar_format_no_cents($rewardInCents));
            }
        }
    }

    return "";
}

?>
