<?php

use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseQuery;

Braintree_Configuration::environment($env_BraintreeEnvironment);
Braintree_Configuration::merchantId($env_BraintreeMerchantId);
Braintree_Configuration::publicKey($env_BraintreePublicKey);
Braintree_Configuration::privateKey($env_BraintreePrivateKey);

function refundPartialOrder($options, $override = 0) {

    $refundedToSourceFailedBase = true;
    $refundedToSourceFailedFee = true;

    // Convert to integer
    $options["orderSequenceId"] = intval($options["orderSequenceId"]);

    // Find order
    $order = parseExecuteQuery(["orderSequenceId" => $options["orderSequenceId"], "status" => array_merge(listStatusesForSuccessCompleted())], "Order", "", "", ["user"], 1);

    // Verify order was found
    if(count_like_php5($order) == 0) {

        return [false, "Completed Order not found!"];
    }

    // Refund timeline passed
    $timestampUntilProcessing = strtotime(date("Y-m-d", $order->get("submitTimestamp"))) + 26.5*60*60;
    if($timestampUntilProcessing < time() && $override == 0) {
        // @todo revert comment
        //return [false, "Failed. Order cannot be refunded beyond 2.30 AM the day after order submission. Open ticket for tech team."];
    }

    // Limit on refund per order
    if($order->get("totalsOfItemsNotFulfilledByRetailerInCents") > 0 
        && $override == 0) {

        return [false, "Failed. Partial refund already processed for order. Refund additional amount, please raise a ticket for Tech."];
    }

    // Limit on refund per order
    $alreadyRefundedAmount = $order->get("totalsCreditedByAS") + $order->get("totalsRefundedByAS") + $order->get("totalsOfItemsNotFulfilledByRetailerInCents");

    // Set current refund amount
    $totalsOfItemsNotFulfilledByRetailerInCents = intval($options["inCents"]);

    // Set current reasonForPartialRefund
    $reasonForPartialRefund = $options["reason"];
    if($order->has("reasonForPartialRefund") || empty($order->get("reasonForPartialRefund"))) {

        $reasonForPartialRefund = $order->get("reasonForPartialRefund") . "; " . $options["reason"];
    }

    // Total paid is created can refunded expected
    if(($alreadyRefundedAmount+intval($options["inCents"])) > (getOrderPaidAmountInCents($order)+getOrderPaidAmountInCredits($order))) {

        return [false, "Failed. Refund amount more than balance left on order"];
    }

    // JMD
    // Identify amount that needs to be charged to the retailer
    $totalsWithFees = json_decode($order->get('totalsWithFees'), true);
    $airportSherpaTotalFees = intval($totalsWithFees['ServiceFee']) + intval($totalsWithFees['AirportSherpaFee']);
    $totalPaidOnOrder = getOrderPaidAmountInCents($order); // $s paid

    // To do:
    // Identify how much can be refunded on Credits and how much $
    // The current set up doesn't allow for it
    // This method returns 0 for now
    $totalPaidInCreditsOnOrder = getOrderPaidAmountInCredits($order); // Credits paid

    // Amount we can refund from AS's fees
    $totalThatCanBeRefundedByAS = $airportSherpaTotalFees - intval($order->get("totalsCreditedByAS")) - intval($order->get("totalsRefundedByAS"));

    // JMD
    $totalThatCanBeRefundedByRetailer = $totalPaidInCreditsOnOrder + $totalPaidOnOrder - intval($order->get("totalsOfItemsNotFulfilledByRetailerInCents")) - $totalThatCanBeRefundedByAS;

    // Requested refund
    $totalRequestedToBeRefunded = intval($options["inCents"]);

    // Total that can be refunded by the retailer has been exhausted
    // So we need to deduct from our fees
    if($totalThatCanBeRefundedByRetailer < $totalRequestedToBeRefunded) {

        $totalThatWillBeRefundedByRetailer = $totalThatCanBeRefundedByRetailer;
        $totalThatWillBeRefundedByAS = $totalRequestedToBeRefunded - $totalThatCanBeRefundedByRetailer;

        if($totalThatWillBeRefundedByAS > $totalThatCanBeRefundedByAS) {

            return [false, "Failed. Balance amount that needs to be refunded by AS is higher than what AS collected. Raise Tech ticket to refund this order."];
        }
    }
    else {

        $totalThatWillBeRefundedByRetailer = $totalRequestedToBeRefunded;
        $totalThatWillBeRefundedByAS = 0;
    }

    $totalToBeRefundedByRetailer = $totalThatWillBeRefundedByRetailer + intval($order->get("totalsOfItemsNotFulfilledByRetailerInCents"));
    //////////////////////////////////////////////////////////////////////////////

    // Full refund the source
    $fullRefund = false;
    if($totalPaidOnOrder == $totalRequestedToBeRefunded) {

        $fullRefund = true;
    }
    $responseToShowOnError = '';

    // Partial refund the source
    if(strcasecmp($options["refundType"], "source")==0) {

        // Format as dollar
        $dollarToRefund = number_format(intval($totalThatWillBeRefundedByRetailer)/100, 2);

        // Refund to source payment
        if($fullRefund) {

            $response = refundOrderToSource($options["orderSequenceId"], $order->get('paymentId'));
        }
        else {

            $response = refundOrderToSource($options["orderSequenceId"], $order->get('paymentId'), $dollarToRefund);
        }

        // Error
        if(is_array($response)) {

            // Refund failed but will be retried
            if($response["error_code"] == "AS_330") {

                $refundedToSourceFailedBase = false;
                if (isset($response['error_message_log'])){
                    $responseToShowOnError=$response['error_message_log'];
                }
            }

            else {

                return [false, "Failed. " . $response["error_message_log"]];
            }
        }
    }
    // Refund with Credits
    // JMD
    else {

        // Add credits
        $userCredits = new ParseObject("UserCredits");
        $userCredits->set("user", $order->get("user"));
        $userCredits->set("fromOrder", $order);
        $userCredits->set("creditsInCents", intval($totalThatWillBeRefundedByRetailer));
        // $userCredits->set("reasonForCredit", "Partial Credit - " . $options["reason"]);
        $userCredits->set("reasonForCredit", getUserCreditReason('PartialCredit') . " - " . $options["reason"]);
        $userCredits->set("reasonForCreditCode", getUserCreditReasonCode('PartialCredit'));
        // Refund credits don't expire
        $userCredits->set('expireTimestamp', -1);
        $userCredits->save();

        // Remove cart cache, allow credits to be loaded to the cart
        $cacheKeyList[] = ['cart' . '__u__' . $order->get('user')->getObjectId()];
        resetCache($cacheKeyList);
    }

    // Add refund total to the order
    $order->set("totalsOfItemsNotFulfilledByRetailerInCents", $totalToBeRefundedByRetailer);
    $order->set("reasonForPartialRefund", $reasonForPartialRefund);
    $order->save();

    // Do we need to refund any by AS
    if($totalThatWillBeRefundedByAS > 0) {

        // Refund the source
        if(strcasecmp($options["refundType"], "source")==0) {

            // Format as dollar
            $dollarToRefund = number_format(intval($totalThatWillBeRefundedByAS)/100, 2);

            // Refund to source payment
            // If Full Refund then it must have been refunded above
            if(!$fullRefund) {

                $response = refundOrderToSource($options["orderSequenceId"], $order->get('paymentId'), $dollarToRefund);
            }
            else {

                $response = "";
            }

            // Error
            if(is_array($response)) {

                // Refund failed but will be retried
                if($response["error_code"] == "AS_330") {

                    $refundedToSourceFailedFee = false;
                }
                else {              

                    return [false, "Failed. " . $response["error_message_log"]];
                }
            }

            $order->set("totalsRefundedByAS", $totalThatWillBeRefundedByAS);
        }
        // Refund with Credits
        else {

            // Add credits
            $userCredits = new ParseObject("UserCredits");
            $userCredits->set("user", $order->get("user"));
            $userCredits->set("fromOrder", $order);
            $userCredits->set("creditsInCents", intval($totalThatWillBeRefundedByAS));
            // $userCredits->set("reasonForCredit", "Partial Credit - " . $options["reason"]);
            $userCredits->set("reasonForCredit", getUserCreditReason('PartialCreditAtASCost') . " - " . $options["reason"]);
            $userCredits->set("reasonForCreditCode", getUserCreditReasonCode('PartialCreditAtASCost'));
            // Refund credits don't expire
            $userCredits->set('expireTimestamp', -1);
            $userCredits->save();

            $order->set("totalsCreditedByAS", $totalThatWillBeRefundedByAS);
        }

        $order->set("reasonForASCreditOrRefund", 'Pending balance on retailer credit');
    }

    // Add refund total to the order
    $order->save();

    if(!$refundedToSourceFailedBase || !$refundedToSourceFailedFee) {
        return [false, "Refund to Source failed, and will be reattempted! reason: ".$responseToShowOnError];
    }

    return [true, ""];
}

function cancel_order_by_ops_admin($orderId, $cancelOptions) {

    // Find order
    $order = parseExecuteQuery(["objectId" => $orderId], "Order", "", "", ["retailer", "retailer.location", "user", "sessionDevice", "sessionDevice.userDevice", "deliveryLocation", "coupon",  "coupon.applicableUser", "flightTrip.flight"], 1);

    // Verify order was found
    if(count_like_php5($order) == 0) {

        return [false, "Failed. Order not found!"];
    }

    // Cancel Timeline passed
    $timestampUntilProcessing = strtotime(date("Y-m-d", $order->get("submitTimestamp"))) + 26.5*60*60;
    if($timestampUntilProcessing < time()) {

        // @todo revert comment
        //return [false, "Failed. Order cannot be cancelled beyond 2.30 AM the day after order submission. Open ticket for tech team."];
    }

    // Checks 3 things:
    // Order had referral credit (signup)
    // Time to earn the reward has passed (i.e. reward was earned)
    // Referral reward has already been earned by the referrer
    if(hasOrderHaveReferralEarnedAndCreditUsed($order)[1] == true) {

        return [false, "Failed. Order cannot be cancelled. This order has referral credits. Its referral reward has already been processed and earning has already been used by the referrer. Open ticket for tech team."];
    }

    // Cancel order with admin privilege
    $response = cancel_order_by_ops($orderId, $cancelOptions, true);

    if(is_array($response)) {

        return[false, json_encode($response)];
    }

    return [true, ""];
}

// JMD
function complete_order_admin($orderSequenceId, $override = 0) {

    // Convert to integer
    $orderSequenceId = intval($orderSequenceId);

    // Find order
    $order = parseExecuteQuery(["orderSequenceId" => $orderSequenceId], "Order", "", "", ["user"], 1);

    // Verify order was found
    if(count_like_php5($order) == 0) {

        return [false, "Failed. Order not found!"];
    }

    // Cancel Timeline passed
    $timestampUntilProcessing = strtotime(date("Y-m-d", $order->get("submitTimestamp"))) + 26.5*60*60;
    if($timestampUntilProcessing < time()
        && $override == 0) {

        // @todo revert comment
        //return [false, "Failed. Order cannot be cancelled beyond 2.30 AM the day after order submission. Open ticket for tech team."];
    }

    if(in_array($order->get("status"), listStatusesForCancelled())) {

        return [false, "Failed. Order already canceled!"];
    }

    if(in_array($order->get("status"), listStatusesForSuccessCompleted())) {

        return [false, "Failed. Order already in completed state!"];
    }

    if(strcasecmp($order->get('fullfillmentType'), 'd')==0) {

        $order->set("statusDelivery", 10);
    }

    $order->set("status", 10);
    $order->save();

    addRemainingOrderStatuses($order);

    $response = orderCompleteMethods($order);

    if(!empty($response)) {

        return [false, json_encode($response)];
    }
    
    return[true, ""];
}

function addRemainingOrderStatuses($orderObject) {

    if(strcasecmp($orderObject->get('fullfillmentType'), 'd')==0) {

        // Add a new for Accepted order
        $orderStatusAccepted = parseExecuteQuery(["status" => 5, "statusDelivery" => 0, "order" => $orderObject], "OrderStatus", "", "", [], 1);

        if(count_like_php5($orderStatusAccepted) == 0) {

            $orderStatusAccepted = new ParseObject("OrderStatus");
            $orderStatusAccepted->set("status", 5);
            $orderStatusAccepted->set("order", $orderObject);
            $orderStatusAccepted->set("statusDelivery", 0);
            $orderStatusAccepted->set("manualTimestamp", $orderObject->get('submitTimestamp')+3*60);
            $orderStatusAccepted->save();
        }

        // Add a new for Picked up order
        $orderStatusPickedUp = parseExecuteQuery(["status" => 5, "statusDelivery" => 5, "order" => $orderObject], "OrderStatus", "", "", [], 1);

        if(count_like_php5($orderStatusPickedUp) == 0) {

            $orderStatusPickedUp = new ParseObject("OrderStatus");
            $orderStatusPickedUp->set("status", 5);
            $orderStatusPickedUp->set("order", $orderObject);
            $orderStatusPickedUp->set("statusDelivery", 5);
            $orderStatusPickedUp->set("manualTimestamp", $orderObject->get('retailerETATimestamp')+2*60);
            $orderStatusPickedUp->save();
        }

        // Add a new for Delivered
        $orderStatusCheck = parseExecuteQuery(["status" => 10, "statusDelivery" => 10, "order" => $orderObject], "OrderStatus", "", "", [], 1);

        if(count_like_php5($orderStatusCheck) == 0) {

            // If delivered tap was missed but pickup was done on time
            // if(!$orderStatusAccepted->has('manualTimestamp')
            //     || empty($orderStatusAccepted->get('manualTimestamp'))) {

            //     $manualTimestamp = $orderStatusAccepted->getCreatedAt()->getTimestamp() + 10*60;
            // }
            // else {

                $manualTimestamp = $orderObject->get('etaTimestamp')-5;
            // }

            $orderStatus = new ParseObject("OrderStatus");
            $orderStatus->set("order", $orderObject);
            $orderStatus->set("status", 10);
            $orderStatus->set("statusDelivery", 10);
            $orderStatus->set("manualTimestamp", $manualTimestamp);
            $orderStatus->save();
        }
    }
    else {

        // Add a new accepted
        $orderStatusAccepted = parseExecuteQuery(["status" => 5, "order" => $orderObject], "OrderStatus", "", "", [], 1);

        if(count_like_php5($orderStatusAccepted) == 0) {

            $orderStatus = new ParseObject("OrderStatus");
            $orderStatus->set("status", 5);
            $orderStatus->set("order", $orderObject);
            $orderStatus->set("statusDelivery", 0);
            $orderStatus->set("manualTimestamp", $orderObject->get('submitTimestamp')+3*60);
            $orderStatus->save();
        }

        // Picked ready
        $orderStatusCheck = parseExecuteQuery(["status" => 10, "order" => $orderObject], "OrderStatus", "", "", [], 1);

        if(count_like_php5($orderStatusCheck) == 0) {

            $orderStatus = new ParseObject("OrderStatus");
            $orderStatus->set("status", 10);
            $orderStatus->set("order", $orderObject);
            $orderStatus->set("statusDelivery", 0);
            $orderStatus->set("manualTimestamp", $orderObject->get('retailerETATimestamp')-10);
            $orderStatus->save();
        }
    }
}

function refundPartialOrderASCost($options, $override = 0) {

    $refundedToSourceFailedBase = true;

    // Convert to integer
    $options["orderSequenceId"] = intval($options["orderSequenceId"]);

    // Find order
    $order = parseExecuteQuery(["orderSequenceId" => $options["orderSequenceId"], "status" => array_merge(listStatusesForSuccessCompleted())], "Order", "", "", ["user"], 1);

    // Verify order was found
    if(count_like_php5($order) == 0) {

        return [false, "Completed Order not found!"];
    }

    // Refund timeline passed
    $timestampUntilProcessing = strtotime(date("Y-m-d", $order->get("submitTimestamp"))) + 26.5*60*60;
    if($timestampUntilProcessing < time() && $override == 0) {

        // @todo revert comment
        //return [false, "Failed. Order cannot be refunded beyond 2.30 AM the day after order submission. Open ticket for tech team."];
    }

    // Limit on refund per order
    if(($order->get("totalsCreditedByAS") > 0 || $order->get("totalsRefundedByAS") > 0)
        && $override == 0) {

        return [false, "Failed. Partial refund already processed for order. Refund additional amount, please raise a ticket for Tech."];
    }

    // Limit on refund per order
    $alreadyRefundedAmount = $order->get("totalsCreditedByAS") + $order->get("totalsRefundedByAS") + $order->get("totalsOfItemsNotFulfilledByRetailerInCents");

    // Set current refund amount
    $totalsOfItemsNotFulfilledByRetailerInCents = intval($options["inCents"]);

    // Set current reasonForASCreditOrRefund
    $reasonForASCreditOrRefund = $options["reason"];
    if($order->has("reasonForASCreditOrRefund") || empty($order->get("reasonForASCreditOrRefund"))) {

        $reasonForASCreditOrRefund = $order->get("reasonForASCreditOrRefund") . "; " . $options["reason"];
    }

    ///////////////////

    // Total paid is created can refunded expected
    if(($alreadyRefundedAmount+intval($options["inCents"])) > getOrderPaidAmountInCents($order)) {

        return [false, "Failed. Refund amount more than balance left on order"];
    }

    $totalsRefundedByAS = $totalsCreditedByAS = 0;

    // Refund the source
    if(strcasecmp($options["refundType"], "source")==0) {

        $totalsRefundedByAS = intval($options["inCents"]);

        // Format as dollar
        $dollarToRefund = number_format(intval($options["inCents"])/100, 2);

        // Refund to source payment
        $response = refundOrderToSource($options["orderSequenceId"], $order->get('paymentId'), $dollarToRefund);

        // Error
        if(is_array($response)) {

            // Refund failed but will be retried
            if($response["error_code"] == "AS_330") {

                $refundedToSourceFailedBase = false;
            }
            else {              

                return [false, "Failed. " . $response["error_message_log"]];
            }
        }
    }
    // Refund with Credits
    else {

        $totalsCreditedByAS = intval($options["inCents"]);

        // Add credits
        $userCredits = new ParseObject("UserCredits");
        $userCredits->set("user", $order->get("user"));
        $userCredits->set("fromOrder", $order);
        $userCredits->set("creditsInCents", intval($options["inCents"]));
        // $userCredits->set("reasonForCredit", "Partial Credit - AS Cost - " . $options["reason"]);
        $userCredits->set("reasonForCredit", getUserCreditReason('PartialCreditAtASCost') . " - " . $options["reason"]);
        $userCredits->set("reasonForCreditCode", getUserCreditReasonCode('PartialCreditAtASCost'));
        // Refund credits don't expire
        $userCredits->set('expireTimestamp', -1);
        $userCredits->save();

        // Remove cart cache, allow credits to be loaded to the cart
        $cacheKeyList[] = ['cart' . '__u__' . $order->get('user')->getObjectId()];
        resetCache($cacheKeyList);
    }

    // Add refund total to the order
    $order->set("totalsRefundedByAS", $totalsRefundedByAS);
    $order->set("totalsCreditedByAS", $totalsCreditedByAS);
    $order->set("reasonForASCreditOrRefund", $reasonForASCreditOrRefund);
    $order->save();

    if(!$refundedToSourceFailedBase) {

        return [false, "Refund to Source failed, and will be reattempted!"];
    }

    return [true, ""];
}

function grantOpsCourtseyCredits($options, $override = 0) {

    // Convert to integer
    $options["orderSequenceId"] = intval($options["orderSequenceId"]);

    // Find order
    $order = parseExecuteQuery(["orderSequenceId" => $options["orderSequenceId"], "status" => array_merge(listStatusesForSuccessCompleted())], "Order", "", "", ["user"], 1);

    // Verify order was found
    if(count_like_php5($order) == 0) {

        return [false, "Completed Order not found!"];
    }

    // Check if credits were already provided for this order
    $parseUserCredits = parseExecuteQuery(array("fromOrder" => $order, "reasonForCreditCode" => getUserCreditReasonCode('CourtseyCredit')), "UserCredits", "", "", [] , 1);

    // Verify if CC credits found for the order
    if(!empty($parseUserCredits)){

        return [false, "Courtsey Credits Already provided for this order."];
    }

    // Add credits
    $userCredits = new ParseObject("UserCredits");
    $userCredits->set("user", $order->get("user"));
    $userCredits->set("fromOrder", $order);
    $userCredits->set("creditsInCents", intval($options["inCents"]));
    $userCredits->set("reasonForCredit", getUserCreditReason('CourtseyCredit') . " - " . $options["reason"]);
    $userCredits->set("reasonForCreditCode", getUserCreditReasonCode('CourtseyCredit'));
    // Courtsey Credits expire
    $userCredits->set('expireTimestamp', time()+183*24*60*60);
    $userCredits->save();

    // Remove cart cache, allow credits to be loaded to the cart
    $cacheKeyList[] = ['cart' . '__u__' . $order->get('user')->getObjectId()];
    resetCache($cacheKeyList);

    // Add credit total to the order
    // $order->set("totalsCreditedByAS", intval($order->get("totalsCreditedByAS")) + intval($options["inCents"]));
    // $order->set("reasonForASCreditOrRefund", $order->get("reasonForASCreditOrRefund") . '; ' . getUserCreditReason('CourtseyCredit') . ' ' . $options["reason"]);
    // $order->save();

    return [true, ""];
}

function reverseCreditsToRefundForOrder($orderSequenceId, $override = 0) {

    $refundedToSourceFailedBase = true;

    // Convert to integer
    $orderSequenceId = intval($orderSequenceId);

    // Find order
    $order = parseExecuteQuery(["orderSequenceId" => $orderSequenceId, "status" => array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled())], "Order", "", "", ["user"], 1);

    // Verify order was found
    if(count_like_php5($order) == 0) {

        return [false, "Completed Order not found!", "", 0];
    }

    // Refund timeline passed
    $timestampUntilProcessing = strtotime(date("Y-m-d", $order->get("submitTimestamp"))) + 26.5*60*60;
    if($timestampUntilProcessing < time() && $override == 0) {

        // @todo revert comment
        //return [false, "Failed. Order cannot be refunded beyond 2.30 AM the day after order submission. Open ticket for tech team."];
    }

    $userCredits = parseExecuteQuery(["fromOrder" => $order, "expireTimestamp" => -1], "UserCredits");

    // JMD
    // Check if Credit actually processed
    if(count_like_php5($userCredits) == 0) {

        return [false, "Failed. No credit refund found for order.", "", 0];
    }

    $refundReason = $order->get("reasonForPartialRefund") . " " . $order->get("cancelReason") . " " . $order->get("reasonForASCreditOrRefund");
    $refundAmount = 0;
    foreach($userCredits as $userCredit) {

        $refundReason .= " " . $userCredit->get("reasonForCredit");
        $refundAmount = $refundAmount + $userCredit->get("creditsInCents");

        ////////////////////////////////////////////////
        // Check if credits were used
        $userCreditsApplied = parseExecuteQuery(["user" => $order->get('user')], "UserCreditsApplied");


        foreach($userCreditsApplied as $applied) {

            if($applied->getCreatedAt()->getTimestamp() > $userCredit->getCreatedAt()->getTimestamp()) {

                return [false, "Failed. Credits already used.", "", 0];
            }
        }
        ////////////////////////////////////////////////

        $userCreditsAppliedMap = parseExecuteQuery(["userCredit" => $userCredit], "UserCreditsApplied");

        if(count_like_php5($userCreditsAppliedMap) > 0) {

            return [false, "Failed. Credits already used.", "", 0];
        }

        // Remove credits

        $userCredit->set("expireTimestamp", time()-60);
        $userCredit->set("notes", "Reversed credits to refund per request from customer");
        $userCredit->save();

    }

    // Format as dollar
    $dollarToRefund = number_format(intval($refundAmount)/100, 2);

    // Refund to source payment
    $response = refundOrderToSource($orderSequenceId, $order->get('paymentId'), $dollarToRefund);

    // Error
    if(is_array($response)) {

        // Refund failed but will be retried
        if($response["error_code"] == "AS_330") {

            $refundedToSourceFailedBase = false;
        }
        else {              

            return [false, "Failed. " . $response["error_message_log"], "", 0];
        }
    }

    // Remove cart cache, allow credits to be loaded to the cart
    $cacheKeyList[] = ['cart' . '__u__' . $order->get('user')->getObjectId()];
    resetCache($cacheKeyList);

    if(in_array($order->get('status'), array_merge(listStatusesForCancelled(), listStatusesForSuccessCompleted()))) {

        $order->set('refundType', 'source');
        $order->save();
    }

    if(!$refundedToSourceFailedBase) {

        return [false, "Refund to Source failed, and will be reattempted!", trim($refundReason), $refundAmount];
    }

    return [true, "", trim($refundReason), $refundAmount];
}

?>
