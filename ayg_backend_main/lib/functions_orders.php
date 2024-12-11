<?php

use App\Consumer\Dto\PartnerIntegration\CartItemList;
use App\Consumer\Helpers\OrderHelper;
use App\Consumer\Entities\Order;
use App\Consumer\Services\CacheServiceFactory;
use App\Consumer\Services\OrderServiceFactory;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;

use Httpful\Request;

use Jsor\HalClient\Client;

use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;

use App\Background\Repositories\PingLogMysqlRepository;

// Braintree icons
$paymentMethodsIconURLs = array(
    "Visa" => "https://assets.braintreegateway.com/payment_method_logo/visa.png?environment=production",
    "American Express" => "https://assets.braintreegateway.com/payment_method_logo/american_express.png?environment=production",
    "MasterCard" => "https://assets.braintreegateway.com/payment_method_logo/mastercard.png?environment=production",
    "Discover" => "https://assets.braintreegateway.com/payment_method_logo/discover.png?environment=production",
    "JCB" => "https://assets.braintreegateway.com/payment_method_logo/jcb.png?environment=production",
    "Maestro" => "https://assets.braintreegateway.com/payment_method_logo/maestro.png?environment=production",
    "PayPal" => "https://assets.braintreegateway.com/payment_method_logo/paypal.png?environment=production",
    "Unknown" => "https://assets.braintreegateway.com/payment_method_logo/unknown.png?environment=production"
);

$creditCardTypes = array("Visa", "American Express", "MasterCard", "Discover", "JCB", "Maestro");

$statusIndexexForPrint = [
    "d" =>
        [
            0 => "2-0", // Status - StatusDelivery; if StatusDelivery > 0 then its status is used
            1 => "5-0",
            2 => "5-5",
            3 => "10-10"
        ],
    "p" =>
        [
            0 => "2-0", // Status - StatusPickup
            1 => "5-0",
            2 => "10-0"
        ],
];

$statusNames = array(
    // Placed order but no payment made yet
    2 => [
        "internal" => "Submitted",
        "print" => "Submitted",
        "type" => "SUBMITTED",
        "inform_user" => true,
        "statusCategoryCode" => 100,
        "is_completed" => false,
        'tablet_order_status_display' => 'Ordered',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 900,
        'tablet_category_code_delivery' => 900,
    ],

    // Paid order
    3 => [
        "internal" => "Payment Accepted",
        "print" => "Submitted",
        "print_for_retailer" => "New",
        "type" => "SUBMITTED",
        "inform_user" => false,
        "statusCategoryCode" => 100,
        "is_completed" => false,
        'tablet_order_status_display' => 'Pending',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 900,
        'tablet_category_code_delivery' => 900,
    ],

    // POS Ticket was sent || Printer Job was sent for printing || Tablet has received it but accept not pressed
    4 => [
        "internal" => "Pushed to Retailer",
        "print" => "Submitted",
        "print_for_retailer" => "New",
        "type" => "AWAITING_CONFIRMATION",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        "is_completed" => false,
        'tablet_order_status_display' => 'Pending',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 100,
        'tablet_category_code_delivery' => 100,
    ],

    // POS Ticket was succcessfuly sent (or Tablet Retailer taps Accept) || Printer Printed receipt
    5 => [
        "internal" => "Accepted by Retailer",
        "print" => "Being Prepared",
        "print_for_retailer" => "Being Prepared",
        "type" => "IN_PROGRESS",
        "inform_user" => true,
        "statusCategoryCode" => 200,
        "is_completed" => false,
        'tablet_order_status_display' => 'Preparing',
        'tablet_status_depends_on_delivery_status' => true,
        'tablet_category_code_pickup' => 200,
        'tablet_category_code_delivery' => 200,
    ],

    // Tablet accepted the DualConfig order
    // For DualConfig orders (e.g. those have Tablet and POS integration)
    // This status will occur before Status = 5
    // Then the fact that POS accepts (as in allows order to be pushed) constitutes as Status = 5
    12 => [
        "internal" => "Accepted on Tablet",
        "print" => "Being Prepared",
        "print_for_retailer" => "Being Prepared",
        "type" => "IN_PROGRESS",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        "is_completed" => false,
        'tablet_order_status_display' => 'Preparing',
        'tablet_status_depends_on_delivery_status' => true,
        'tablet_category_code_pickup' => 200,
        'tablet_category_code_delivery' => 200,
    ],

    // System cancelled order
    6 => [
        "internal" => "Cancelled",
        "print" => "Cancelled",
        "print_for_retailer" => "Cancelled",
        "type" => "NOT_FULLFILLED",
        "inform_user" => true,
        "statusCategoryCode" => 600,
        "is_completed" => true,
        'tablet_order_status_display' => 'Canceled',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 600,
        'tablet_category_code_delivery' => 600,
    ],

    // User cancelled order; only allowed for scheduled orders that are still in Scheduled state
    7 => [
        "internal" => "Cancelled",
        "print" => "Cancelled",
        "print_for_retailer" => "Cancelled",
        "type" => "NOT_FULLFILLED",
        "inform_user" => true,
        "statusCategoryCode" => 600,
        "is_completed" => true,
        'tablet_order_status_display' => 'Canceled',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 600,
        'tablet_category_code_delivery' => 600,
    ],

    // Specific time order for scheduled processing (not ordered yet) :: TBD scheduling functionality
    8 => [
        "internal" => "Scheduled",
        "print" => "Submitted",
        "print_for_retailer" => "",
        "type" => "SUBMITTED",
        // "type" => "PENDING",
        "inform_user" => true,
        "statusCategoryCode" => 100,
        // "statusCategoryCode" => 700,
        "is_completed" => false,
        'tablet_order_status_display' => 'Ordered',
        // 'tablet_order_status_display' => 'Scheduled',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 900,
        'tablet_category_code_delivery' => 900,
    ],

    // Order that requires user input before it can be processed
    // Possibly redundant Status (with Status = 11) that can be reused
    9 => [
        "internal" => "Order Ready by Retailer",
        "print" => "Order is Ready",
        "print_for_retailer" => "Order Ready",
        "type" => "PENDING",
        "inform_user" => true,
        "statusCategoryCode" => 200,
        "is_completed" => false,
        'tablet_order_status_display' => 'Order Ready by Retailer',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 900,
        'tablet_category_code_delivery' => 900,
    ],

    // Completed order
    // For Pickup orders = When etaTimestamp passes
    // For Delivery orders = When the Delivery Person indicates statusDelivery = Delivered = 10
    10 => [
        "internal" => "Completed",
        "print" => "Completed",
        "type" => "FULLFILLED",
        "inform_user" => true,
        "print_for_retailer" => "Completed",
        "statusCategoryCode" => 400,
        "consolidateMultipleStatusReports" => true,
        "is_completed" => true,
        'tablet_order_status_display' => 'Completed',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 400,
        'tablet_category_code_delivery' => 400,
    ],

    // Order that requires user input before it can be processed
    11 => [
        "internal" => "Needs Review",
        "print" => "Needs your review",
        "type" => "PENDING",
        "inform_user" => true,
        "print_for_retailer" => "Awaiting customer input",
        "statusCategoryCode" => 500,
        "is_completed" => false,
        'tablet_order_status_display' => 'Needs Review',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 900,
        'tablet_category_code_delivery' => 900,
    ],

    // INTERNAL ONLY: Just a cart
    1 => [
        "internal" => "Not Ordered",
        "print" => "",
        "type" => "CART",
        "inform_user" => false,
        "statusCategoryCode" => 0,
        "is_completed" => false,
        'tablet_order_status_display' => 'Not Ordered',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 900,
        'tablet_category_code_delivery' => 900,
    ],

    // INTERNAL ONLY: Reset cart
    100 => [
        "internal" => "Abandoned",
        "print" => "",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 600,
        "is_completed" => true,
        'tablet_order_status_display' => 'Abandoned',
        'tablet_status_depends_on_delivery_status' => false,
        'tablet_category_code_pickup' => 900,
        'tablet_category_code_delivery' => 900,
    ],
);

// Status Category
// 100 - SUBMITTED
// 200 - IN PROGRESS
// 300 - IN TRANSIT
// 400 - COMPLETED
// 500 - NEEDS REVIEW
// 600 - CANCELLED
// 700 - SCHEDULED
$statusDeliveryNames = array(

    // Delivery Person search not started yet
    0 => [
        "internal" => "Not Processed",
        "print" => "",
        "type" => "INTERNAL",
        "inform_user" => false,
        'tablet_completed_by_retailer_perspective' => false,
        'tablet_display_by_retailer_perspective' => 'Preparing',
        'tablet_category_code_delivery' => 200,
    ],

    // Looking for Delivery Person
    1 => [
        "internal" => "Being Assigned",
        "print" => "",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        'tablet_completed_by_retailer_perspective' => false,
        'tablet_display_by_retailer_perspective' => 'Preparing',
        'tablet_category_code_delivery' => 200,
    ],

    // Assigned to a Delivery Person
    2 => [
        "internal" => "Assigned",
        "print" => "Assigned for Delivery",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        'tablet_completed_by_retailer_perspective' => false,
        'tablet_display_by_retailer_perspective' => 'Preparing',
        'tablet_category_code_delivery' => 200,
    ],

    // Delivery Person has accepted delivery
    3 => [
        "internal" => "Accepted for Delivery",
        "print" => "Accepted for Delivery",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        'tablet_completed_by_retailer_perspective' => false,
        'tablet_display_by_retailer_perspective' => 'Preparing',
        'tablet_category_code_delivery' => 200,
    ],

    // delivery person has arrived at retailer
    4 => [
        "internal" => "Arrived at Retailer",
        "print" => "We have arrived to pickup",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        'tablet_completed_by_retailer_perspective' => false,
        'tablet_display_by_retailer_perspective' => 'Preparing',
        'tablet_category_code_delivery' => 200,
    ],

    // delivery person picked up order
    5 => [
        "internal" => "Picked up",
        "print" => "Flying to you",
        "type" => "PICKED_UP",
        "inform_user" => true,
        "statusCategoryCode" => 300,
        'tablet_completed_by_retailer_perspective' => true,
        'tablet_display_by_retailer_perspective' => 'Completed',
        'tablet_category_code_delivery' => 400,
    ],

    // delivery person has arrived at the delivery location
    6 => [
        "internal" => "Arrived at Delivery location",
        "print" => "Arrived @ Delivery location",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 300,
        'tablet_completed_by_retailer_perspective' => true,
        'tablet_display_by_retailer_perspective' => 'Completed',
        'tablet_category_code_delivery' => 400,
    ],

    // Order being reassigned to another delivery person, because earlier delivery person who accepted canceled later
    7 => [
        "internal" => "Cancelled for delivery",
        "print" => "",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        'tablet_completed_by_retailer_perspective' => false,
        'tablet_display_by_retailer_perspective' => '',
        'tablet_category_code_delivery' => 900,
    ],

    // Order being reassigned to another delivery person, because earlier delivery person who accepted canceled later
    8 => [
        "internal" => "Being Reassigned",
        "print" => "Assigned to delivery person",
        "type" => "INTERNAL",
        "inform_user" => false,
        "statusCategoryCode" => 200,
        'tablet_completed_by_retailer_perspective' => false,
        'tablet_display_by_retailer_perspective' => '',
        'tablet_category_code_delivery' => 900,
    ],

    // Order delivered to consumer
    10 => [
        "internal" => "Delivered",
        "print" => "Delivered!",
        "type" => "DELIVERED",
        "inform_user" => true,
        "statusCategoryCode" => 400,
        'tablet_completed_by_retailer_perspective' => true,
        'tablet_display_by_retailer_perspective' => 'Completed',
        'tablet_category_code_delivery' => 400,
    ],
);

// COPY ADDITIONS TO HELPER FUNCTIONS
$reasonForCredit = [
    "GeneralSignupPromo" => ["code" => "SPC", "reason" => "Signup Promo Code"],
    "ReferralSignup" => ["code" => "SRC", "reason" => "Signup Referral Code"],
    "ReferralReward" => ["code" => "RR", "reason" => "Referral Reward"],
    "OrderCancelByOpsPartialRefund" => ["code" => "OCOPR", "reason" => "Order Cancellation by Ops - Partial Refund"],
    "PartialCredit" => ["code" => "PC", "reason" => "Partial Credit"],
    "PartialCreditAtASCost" => ["code" => "PCASC", "reason" => "Partial Credit - AS Cost"],
    "OrderCancelByOpsFullRefund" => ["code" => "OCOFR", "reason" => "Order Cancellation by Ops - Full Refund"],
    "CourtseyCredit" => ["code" => "CC", "reason" => "Courtesy Credit"],
    // Credits customer bought upfront, e.g. Gift card, bulk purchase
    "PaidCredit" => ["code" => "PDC", "reason" => "Paid Credits"],
    "ManualAdjustment" => ["code" => "MA", "reason" => "Manual Adjustment"],
];

$statusCompleted = array(
    // JMD
    // Status to show for completed orders when type is d
    "d" => "Delivered",

    // Status to show for completed orders when type is p
    "p" => "Ready For Pickup",
);

function order_processing_error(
    $orderIdBeingProcessed = "",
    $error_code,
    $user_error_description,
    $error_descriptive = "",
    $error_type = 3,
    $error_noexit = 1,
    $backtrace = "",
    $processStatusFlag = 0
) {

    $logging = true;

    // Add the error to the OrderProcessingErrors Class
    $addProcessErrorObject = new ParseObject("OrderProcessingErrors");

    if (is_object($orderIdBeingProcessed)) {

        $addProcessErrorObject->set("order", $orderIdBeingProcessed);
    }

    $lastProcessingErrorMessage = $error_code . ": " . $error_descriptive;
    $addProcessErrorObject->set("lastProcessingErrorMessage", $lastProcessingErrorMessage);
    $addProcessErrorObject->set("processStatusFlag", $processStatusFlag);

    try {

        $addProcessErrorObject->save();
    } catch (Exception $ex) {

        $logging = false;
    }

    if (is_object($orderIdBeingProcessed)) {

        $customerName = $orderIdBeingProcessed->get('user')->get('firstName') . ' ' . $orderIdBeingProcessed->get('user')->get('lastName');
        $submissionDateTime = date("M j, g:i a", $orderIdBeingProcessed->get('submitTimestamp'));

        // Slack it
        $orderIdBeingProcessed->fetch();
        $orderIdBeingProcessed->get('retailer')->fetch();
        $slack = new SlackMessage($GLOBALS['env_SlackWH_orderProcErrors'], 'env_SlackWH_orderProcErrors');
        $slack->setText("Order: " . $orderIdBeingProcessed->getObjectId() . " (" . $orderIdBeingProcessed->get('retailer')->get('retailerName') . ")");

        $attachment = $slack->addAttachment();
        $attachment->addField("Customer:", $customerName, true);
        // JMD
        $attachment->addField("Submitted:", $submissionDateTime, false);
        $attachment->addField("Error:", $lastProcessingErrorMessage, false);

        try {

            $slack->send();
        } catch (Exception $ex) {


            $errorArray = [
                $orderIdBeingProcessed,
                $error_code,
                $user_error_description,
                $error_descriptive,
                $error_type,
                $error_noexit,
                $backtrace,
                $processStatusFlag,
            ];


            json_error("AS_1008", "",
                "Slack post failed informing Order Process errors! orderId=(" . $orderIdBeingProcessed->getObjectId() . "), Post Array=" . json_encode($errorArray) . " -- " . $ex->getMessage(),
                1, 1);
        }
    }

    if ($logging == false) {

        // No exit error
        return json_error_return_array("AS_304", "", "Order Processing Error Log failed!" . $ex->getMessage(), 1, 1);
    } else {

        // System output
        return json_error_return_array($error_code, $user_error_description, $error_descriptive, $error_type,
            $error_noexit, $backtrace);
    }
}

function encryptToken($string)
{

    if (!isset($GLOBALS['currentlyOperatedDevice']) || empty($GLOBALS['currentlyOperatedDevice'])) {
        $deviceCurrentlyUsed = '';
    } else {
        $deviceCurrentlyUsed = $GLOBALS['currentlyOperatedDevice'];
    }
    return encryptString($string, $GLOBALS['env_TokenEncryptionKey' . $deviceCurrentlyUsed]);
}

function decryptToken($string)
{
    if (!isset($GLOBALS['currentlyOperatedDevice']) || empty($GLOBALS['currentlyOperatedDevice'])) {
        $deviceCurrentlyUsed = '';
    } else {
        $deviceCurrentlyUsed = $GLOBALS['currentlyOperatedDevice'];
    }
    return decryptString($string, $GLOBALS['env_TokenEncryptionKey' . $deviceCurrentlyUsed]);
}

function encryptPaymentInfo($string)
{

    if (!isset($GLOBALS['currentlyOperatedDevice']) || empty($GLOBALS['currentlyOperatedDevice'])) {
        $deviceCurrentlyUsed = '';
    } else {
        $deviceCurrentlyUsed = $GLOBALS['currentlyOperatedDevice'];
    }
    return encryptString($string, $GLOBALS['env_PaymentResponseEncryptionKey' . $deviceCurrentlyUsed]);
}

function decryptPaymentInfo($string)
{


    if (!isset($GLOBALS['currentlyOperatedDevice']) || empty($GLOBALS['currentlyOperatedDevice'])) {
        $deviceCurrentlyUsed = '';
    } else {
        $deviceCurrentlyUsed = $GLOBALS['currentlyOperatedDevice'];
    }
    return decryptString($string, $GLOBALS['env_PaymentResponseEncryptionKey' . $deviceCurrentlyUsed]);
}

function fetchAirportTimeZone($airportIataCode, $currentTimeZone = '')
{

    $objParseAirports = getAirportByIataCode($airportIataCode);

    // If missing Set it to default
    if (count_like_php5($objParseAirports) == 0) {

        if (empty($currentTimeZone)) {

            $currentTimeZone = date_default_timezone_get();
        }

        $airporTimeZone = $currentTimeZone;
        json_error("AS_854", "", "Missing Timezone for Airport IATA: " . $airportIataCode, 3, 1);
    } // Else fetch it
    else {

        $airporTimeZone = $objParseAirports->get('airportTimezone');
    }

    return $airporTimeZone;
}

function isAirportDeliveryReady($airportIataCode)
{

    $objParseAirports = getAirportByIataCode($airportIataCode);

    // If missing Set it to default
    if (count_like_php5($objParseAirports) == 0) {

        json_error("AS_854", "", "Not found Airport IATA: " . $airportIataCode, 3, 1);
        return false;
    } // Else fetch it
    else {

        return $objParseAirports->get('isDeliveryReady');
    }
}

function fetchAirportEmployeeDiscountPCT(
    string $airportIataCode,
    ?bool $employeeDiscountAllowed,
    ?float $employeeDiscountPCT
) {
    if (is_null($employeeDiscountAllowed) || empty($employeeDiscountAllowed) || $employeeDiscountAllowed == true) {
        if ($employeeDiscountPCT > 0) {
            return $employeeDiscountPCT;
        }

        $objParseQueryAirports = getAirportByIataCode($airportIataCode);

        // If missing Set it to default
        if (count_like_php5($objParseQueryAirports) == 0) {

            json_error("AS_854", "", "Discount row not found for Airport IATA: " . $airportIataCode, 3, 1);
        }
        // Else fetch it
        return $objParseQueryAirports->get('employeeDiscountPCT');
    } else {
        return 0;
    }
}

function fetchMilitaryDiscountPCT(string $airportIataCode, ?bool $militaryDiscountAllowed, ?float $militaryDiscountPCT)
{
    if (is_null($militaryDiscountAllowed) || empty($militaryDiscountAllowed) || $militaryDiscountAllowed == true) {
        if ($militaryDiscountPCT > 0) {
            return $militaryDiscountPCT;
        }

        $objParseQueryAirports = getAirportByIataCode($airportIataCode);

        // If missing Set it to default
        if (count_like_php5($objParseQueryAirports) == 0) {

            json_error("AS_854", "", "Discount row not found for Airport IATA: " . $airportIataCode, 3, 1);
        }
        // Else fetch it
        return $objParseQueryAirports->get('militaryDiscountPCT');
    } else {
        return 0;
    }
}

function initiateOrder($user, $retailer)
{

    // Find if there is an existing open (Open or Ordered, but not Paid) order for the User and Retailer
    $objParseQueryOrderResults = parseExecuteQuery(array(
        "user" => $user,
        "status" => listStatusesForCart(),
        "retailer" => $retailer
    ), "Order", "", "", array("retailer", "retailer.location", "deliveryLocation", "coupon"));

    $orderOpenComment = " ";
    if (count_like_php5($objParseQueryOrderResults) > 0) {

        $orderObjectId = $objParseQueryOrderResults[0]->getObjectId();
        $status = $objParseQueryOrderResults[0]->get('status');
    } // No order found, then add one and return
    else {

        $order = createOrder($user, $retailer);
        $orderObjectId = $order->getObjectId();

        addOrderStatus($order, $orderOpenComment);

        $status = $order->get('status');
    }

    return array($orderObjectId, $status);
}

function closeOrder($objParseQueryOrderResults, $retailer, $user)
{

    $orderId = $objParseQueryOrderResults->getObjectId();

    // If Order is found, set its status as Abandoned
    if (in_array($objParseQueryOrderResults->get('status'), listStatusesForCart())) {

        $orderStatusCode = $objParseQueryOrderResults->get('status');

        $orderObjectId = $objParseQueryOrderResults->getObjectId();

        // Order Abandoned
        orderStatusChange_Abandon($objParseQueryOrderResults);
        $objParseQueryOrderResults->save();

        // Return auth code as 1
        $responseArray = array("reset" => "1");
    } else {

        // Return auth code as 1
        $responseArray = array("reset" => "0");
    }

    return $responseArray;
}

function braintreeErrorCollect($object)
{

    $errorCollected = "";
    if (isset($object->errors)) {

        foreach ($object->errors->deepAll() as $error) {

            $errorCollected .= $error->code . ": " . $error->message . "\n";
        }
    }

    if (isset($object->message)) {

        $errorCollected .= ", Message: " . $object->message;
    }

    if (isset($object->code)) {

        $errorCollected .= ", Code: " . $object->code;
    }

    return $errorCollected;
}

function doesUserAlreadyHaveSignupCoupon($user, $couponCode)
{

    $hasSignupCoupon = false;
    $invalidReasonUser = '';
    $invalidReasonLog = '';
    $invalidErrorCode = 0;

    list($couponObj, $isCouponForSignup) = isCouponForSignup($couponCode);

    // Find this coupon in UserCoupon and not yet used
    $countOfUserCoupons = parseExecuteQuery(["coupon" => $couponObj, "user" => $user, "__DNE__appliedToOrder" => true],
        "UserCoupons", "", "", [], 1, false, [], 'count');

    if ($countOfUserCoupons > 0) {

        $hasSignupCoupon = true;
        $invalidReasonUser = 'Sorry, you have already added this offer.';
        $invalidReasonLog = 'Coupon already in UserCoupons.';
        $invalidErrorCode = 116;
    } // If a signup coupon, then check if any other have been used in past
    else {
        if ($isCouponForSignup == true) {

            // Find any coupons in UserCoupons
            $countOfUserCoupons = parseExecuteQuery(["addedOnStep" => "signup", "user" => $user], "UserCoupons", "", "",
                [], 1, false, [], 'count');

            // Find any signup coupons in UserCredit
            $countOfUserCredits = parseExecuteQuery(["__E__signupCoupon" => true, "user" => $user], "UserCredits", "",
                "", [], 1, false, [], 'count');

            if ($countOfUserCoupons + $countOfUserCredits > 0) {

                $hasSignupCoupon = true;
                $invalidReasonUser = 'Sorry, this offer is only available for new users.';
                $invalidReasonLog = 'Signup promo been used before.';
                $invalidErrorCode = 102;
            }
        }
    }

    return array(
        $couponObj,
        $hasSignupCoupon,
        $invalidReasonUser,
        $invalidReasonLog,
        $invalidErrorCode,
        $isCouponForSignup
    );
}

function isCouponForSignup($couponCode)
{

    $couponObj = parseExecuteQuery(["couponCode" => strtolower($couponCode)], "Coupons", "", "", ["applicableUser"], 1);

    if (count_like_php5($couponObj) > 0) {

        if ($couponObj->get('forSignup')) {

            return [$couponObj, true];
        } else {

            return [$couponObj, false];
        }
    } else {

        return ["", false];
    }
}

function fetchValidUserReferral($referralCode, $user)
{

    $isValid = false;
    $invalidReasonUser = 'This code is not valid.';
    $invalidReasonLog = 'Referral code not found.';

    $invalidErrorCode = 201;

    // Check if the user referral is valid
    $userReferral = parseExecuteQuery(["referralCode" => strtoupper($referralCode)], "UserReferral", "", "", ["user"],
        1);

    // Code was found
    // User who referred it was not locked
    // User who referred is not the same as the currnt user
    if (count_like_php5($userReferral) > 0
        && $userReferral->get('user')->get('isLocked') == false
        && strcasecmp($userReferral->get('user')->getObjectId(), $user->getObjectId()) != 0
    ) {

        // Current user's device matches the referred code user's devices
        list($isValidReferral, $errorInvalidReferral) = validateReferralCodeValidationForDevice($user,
            $userReferral->get('user'));
        if ($isValidReferral == false) {

            $isValid = false;
            $invalidReasonUser = 'Sorry, your account is not eligible for this offer.';
            $invalidReasonLog = $errorInvalidReferral;
            $invalidErrorCode = 209;

            return [[$isValid, $invalidReasonUser, $invalidReasonLog, $invalidErrorCode, true], []];
        }

        // Else it is fine, apply the code
        return [[true, "", "", 0, true], $userReferral];
    } else {

        return [[$isValid, $invalidReasonUser, $invalidReasonLog, $invalidErrorCode, true], []];
    }
}

// couponObj = Object of Coupon if available
// couponCode = For pulling the object if one not provided
// retailer = For applying Airport or Retailer restrictions
// user = For identifying the current user
// forSignup = Is the coupon being applied at Signup?
// creditAppliedMap = Output of getOrderSummary with index of creditAppliedMap

function fetchValidCoupon(
    $couponObj,
    $couponCode,
    $retailer,
    $user,
    $forSignup = false,
    $applySignupOnlyCheck = true,
    $creditAppliedMap = "",
    $preAppliedCoupon = false
) {

    if (empty($user)) {

        $user = $GLOBALS['user'];
    }

    if (!empty($couponCode)) {

        $couponCode = trim($couponCode);
    }

    $isReferralCode = false;

    if (empty($couponObj)) {

        // Is the code a referral code
        // Referral codes allowed only on signup
        if (isCodeOfReferralType($couponCode)
            && $GLOBALS['env_UserReferralRewardEnabled'] == true
        ) {

            $isReferralCode = true;

            list($error_array, $userReferralObj) = fetchValidUserReferral($couponCode, $user);

            return array(
                $userReferralObj,
                $error_array[0],
                $error_array[1],
                $error_array[2],
                $error_array[3],
                $isReferralCode
            );
        }

        // Continue to check a regular code
        $couponObj = parseExecuteQuery(["couponCode" => strtolower($couponCode)], "Coupons", "", "", ["applicableUser"],
            1);
    }

    // Pull Coupon Groups
    $couponGroupsList = [];
    $couponGroupsObj = [];
    $couponGroupsWithoutCurrentObj = [];
    if (!empty($couponObj)) {

        // Get all associated group Id
        $couponGroupsListObj = parseExecuteQuery(["coupon" => $couponObj], "CouponGroups", "", "", ["coupon"]);
        foreach ($couponGroupsListObj as $couponGroup) {

            $couponGroupsList[] = $couponGroup->get("groupId");
        }

        // Get all coupons for these groupIds
        $couponsForGroupObj = parseExecuteQuery(["__CONTAINEDIN__groupId" => $couponGroupsList], "CouponGroups", "", "",
            ["coupon"]);
        foreach ($couponsForGroupObj as $couponGroup) {

            $couponGroupsObj[] = $couponGroup->get("coupon");

            // Skip the object of the coupon being used
            if (strcasecmp($couponGroup->get("coupon")->get('couponCode'), $couponObj->get('couponCode')) != 0) {

                $couponGroupsWithoutCurrentObj[] = $couponGroup->get("coupon");
            }
        }
    }

    /////////////////////////////////////////////////////
    // Check validity of coupon
    /////////////////////////////////////////////////////

    $isValid = true;
    $invalidReasonUser = '';
    $invalidReasonLog = '';
    $invalidErrorCode = 0;

    $countOfUsageByDevice = 0;
    if ($GLOBALS['env_AllowDuplicateCouponUsageByDevice'] == false
        && count_like_php5($couponObj) > 0
        && $couponObj->get('maxUsageAllowedByDevice') > 0
    ) {

        list($potentialDuplicateUser, $countOfUsageByDevice) = couponValidationCountOrdersWithCouponForDevice($couponObj,
            $user, $couponGroupsObj, $couponGroupsWithoutCurrentObj, $forSignup);
    }

    /////////////////////////////////////////////////////////////////////
    // Fetch device id
    $userDeviceId = fetchCurrentDeviceId($user);

    /////////////////////////////////////////////////////////////////////
    // Find if the user's device is whitelisted

    $isUserDeviceWhiteListed = isUserDeviceWhiteListed($userDeviceId, 'coupon');

    // Was coupon found
    if (count_like_php5($couponObj) == 0) {

        // Log invalid coupon code
        logInvalidSignupCoupons($user, "", $couponCode);

        $isValid = false;
        $invalidReasonUser = 'This code is not valid.';
        $invalidReasonLog = 'Coupon not found.';
        $invalidErrorCode = 101;
    } // Is this coupon NOT for signup? and the application WAS at signup time
    else {
        if ($couponObj->get('forSignup') == false && is_bool($forSignup)
            && $forSignup == true
            && $applySignupOnlyCheck == true
        ) {

            $isValid = false;
            $invalidReasonUser = 'Sorry, this offer can be applied only on checkout';
            $invalidReasonLog = 'Coupon is NOT for Signup.';
            $invalidErrorCode = 103;
        } // Is this coupon NOT for cart? and the application WAS at cart
        else {
            if ($couponObj->get('forCart') == false && is_bool($forSignup)
                && $forSignup == false && $preAppliedCoupon == false
            ) {

                $isValid = false;
                $invalidReasonUser = 'Sorry, this offer is only available for new users.';
                $invalidReasonLog = 'Coupon is only for Signup.';
                $invalidErrorCode = 102;
            }
            // // Is this coupon only for signup? and the application was not at signup time
            // else if ($couponObj->get('forSignup') == true && is_bool($forSignup)
            //     && $forSignup == false
            //     && $applySignupOnlyCheck == true
            // ) {

            //     $isValid = false;
            //     $invalidReasonUser = 'Sorry, this promo is only available for new users.';
            //     $invalidReasonLog = 'Coupon is only for Signup.';
            //     $invalidErrorCode = 102;
            // }
            // Is this coupon for cart? and the application WAS at cart BUT is of credit type
            else {
                if ($couponObj->get('forCart') == true && is_bool($forSignup)
                    && $forSignup == false
                    && isCouponCreditType($couponObj)
                ) {

                    $isValid = false;
                    $invalidReasonUser = 'Sorry, this offer is only available for new users.';
                    $invalidReasonLog = 'Coupon of credit type attempted in CART.';
                    $invalidErrorCode = 115;
                } // Check if coupon is active
                else {
                    if ($couponObj->get('isActive') == false) {

                        $isValid = false;
                        $invalidReasonUser = 'This offer is no longer available.';
                        $invalidReasonLog = 'Coupon is inactive.';
                        $invalidErrorCode = 104;
                    } // Check if coupon has active timestamp that is current
                    else {
                        if ($couponObj->get('activeTimestamp') > time()) {

                            $isValid = false;
                            $invalidReasonUser = 'This offer is not available.';
                            $invalidReasonLog = 'Coupon has not reached active state yet.';
                            $invalidErrorCode = 105;
                        } // Check if coupon has expired
                        else {
                            if ($couponObj->get('expiresTimestamp') < time()) {

                                $isValid = false;
                                $invalidReasonUser = 'This offer is no longer available.';
                                $invalidReasonLog = 'Coupon has expired.';
                                $invalidErrorCode = 106;
                            }

                            // Check if coupon is valid for the airport
                            // If count of ids provided is > 0
                            // And if the id we have is not in this list
                            else {
                                if (
                                    $retailer != ''
                                    && count(array_filter($couponObj->get('applicableAirportIataCodes'))) > 0
                                    && !in_array($retailer->get('airportIataCode'),
                                        $couponObj->get('applicableAirportIataCodes'))
                                ) {

                                    $isValid = false;
                                    $invalidReasonUser = 'This offer is not valid for the selected airport.';
                                    $invalidReasonLog = 'Coupon not valid for requested airport.';
                                    $invalidErrorCode = 107;
                                }
                                // Check if coupon is valid for the retailer
                                // JMD
                                else {
                                    if (
                                        $retailer != ''
                                        && count(array_filter($couponObj->get('applicableRetailerUniqueIds'))) > 0
                                        && !in_array($retailer->get('uniqueId'),
                                            $couponObj->get('applicableRetailerUniqueIds'))
                                    ) {

                                        $isValid = false;
                                        $invalidReasonUser = 'This offer is not valid for the selected retailer.';
                                        $invalidReasonLog = 'Coupon not valid for requested retailer.';
                                        $invalidErrorCode = 108;
                                    } /*
    // Check if coupon is disallowed for users acquired through referral
    // And if env_UserReferralApplyCouponRestriction is true
    // This limits a certain coupon set up with this restriction
    else if ($GLOBALS['env_UserReferralApplyCouponRestriction'] == true
        && $couponObj->has('allowWithReferralCredit')
        && $couponObj->get('allowWithReferralCredit') == false
        && wasUserBeenAcquiredViaReferral($user) == true
    ) {

        $isValid = false;
        $invalidReasonUser = 'Sorry, your account is not eligible for this offer.';
        $invalidReasonLog = 'Referral acquired user attempting to use promo coupon';
        $invalidErrorCode = 109;
    }
    */
                                    else {
                                        if ($couponObj->has('disallowForCreditReasonCodes')
                                            && count_like_php5($couponObj->get('disallowForCreditReasonCodes')) > 0
                                            && !empty($creditAppliedMap)
                                            && count_like_php5($creditAppliedMap) > 0
                                            && doesCartIncludeUserCreditType($couponObj->get('disallowForCreditReasonCodes'),
                                                $creditAppliedMap)
                                        ) {
                                            logResponse(json_encode('CCC'),false);
                                            logResponse(json_encode(

                                                [
                                                    $creditAppliedMap,
                                                    $couponObj->has('disallowForCreditReasonCodes')
                                                ,count_like_php5($couponObj->get('disallowForCreditReasonCodes')) > 0
                                                    ,!empty($creditAppliedMap)
                                                    ,count_like_php5($creditAppliedMap) > 0
                                                    ,$couponObj->get('disallowForCreditReasonCodes')
                                                    ,doesCartIncludeUserCreditType($couponObj->get('disallowForCreditReasonCodes'),
                                                    $creditAppliedMap)]),false);
                                            $isValid = false;
                                            $invalidReasonUser = 'Sorry, you have promotional credits in cart and cannot apply this coupon with it.';
                                            $invalidReasonLog = 'Restricted User Credits found for coupon usage';
                                            $invalidErrorCode = 109;
                                        }

                                        // Check if coupon is valid for only a specific user
                                        // And provided user doesn't match
                                        else {
                                            if (!empty($couponObj->get('applicabeUser'))
                                                && strcasecmp($user->getObjectId(),
                                                    $couponObj->get('applicabeUser')->getObjectId()) != 0
                                            ) {

                                                $isValid = false;
                                                $invalidReasonUser = 'Sorry, your account is not eligible for this offer.';
                                                $invalidReasonLog = 'User-specific coupon being used by another user.';
                                                $invalidErrorCode = 109;
                                            }

                                            /////////////////////////////////////////////////////
                                            // Check counts
                                            /////////////////////////////////////////////////////

                                            // Is this coupon first use only
                                            // And if the user has already placed one order
                                            else {
                                                if ($couponObj->get('isFirstUseOnly') == true
                                                    && couponValidationCountValidOrdersForUser($user) > 0
                                                ) {

                                                    $isValid = false;
                                                    $invalidReasonUser = 'This offer is only valid for your first order.';
                                                    $invalidReasonLog = 'First order coupon being used.';
                                                    $invalidErrorCode = 110;
                                                } // Has this coupon reached overall max usage
                                                else {
                                                    if ($couponObj->get('maxUserAllowedByAll') != 0
                                                        && $preAppliedCoupon == false
                                                        && $couponObj->get('maxUserAllowedByAll') <= couponValidationCountAllWithCoupon($couponObj,
                                                            $couponGroupsObj, $couponGroupsWithoutCurrentObj,
                                                            $forSignup)
                                                    ) {

                                                        $isValid = false;
                                                        $invalidReasonUser = 'This offer is no longer available.';
                                                        $invalidReasonLog = 'Max coupon usage for all users reached.';
                                                        $invalidErrorCode = 111;
                                                    } // Has this coupon reach device level max usage
                                                    else {
                                                        if ($couponObj->get('maxUsageAllowedByDevice') > 0
                                                            && $preAppliedCoupon == false
                                                            && $couponObj->get('maxUsageAllowedByDevice') <= $countOfUsageByDevice
                                                            && $isUserDeviceWhiteListed == false
                                                        ) {

                                                            $isValid = false;
                                                            $invalidReasonUser = 'Sorry, your account is not eligible for this offer.';
                                                            $invalidReasonLog = 'Max coupon (or group) usage for current device reached.';
                                                            $invalidErrorCode = 114;

                                                            // Log duplicate usage
                                                            try {

                                                                logAccountDuplicateUsage($user, $potentialDuplicateUser,
                                                                    "Coupon usage - " . $couponCode);
                                                            } catch (Exception $ex) {

                                                                json_error("AS_891", "",
                                                                    "DeviceId based Coupon decline log failure " . $ex->getMessage(),
                                                                    2, 1);
                                                            }
                                                        } // Has this coupon reach user level max usage
                                                        else {
                                                            if ($couponObj->get('maxUserAllowedByUser') != 0
                                                                && $preAppliedCoupon == false
                                                                && $couponObj->get('maxUserAllowedByUser') <= couponValidationCountOrdersWithCouponForUser($couponObj,
                                                                    $user, $couponGroupsObj)
                                                            ) {

                                                                $isValid = false;
                                                                $invalidReasonUser = 'This offer can be used only once.';
                                                                $invalidReasonLog = 'Max coupon (or group) usage for current user reached.';
                                                                $invalidErrorCode = 112;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /////////////////////////////////////////////////////
    /////////////////////////////////////////////////////

    return array($couponObj, $isValid, $invalidReasonUser, $invalidReasonLog, $invalidErrorCode, $isReferralCode);
}

function isCouponCreditType($couponObj)
{

    if ($couponObj->has('onSignupAcctCreditsInCents')
        && $couponObj->get('onSignupAcctCreditsInCents') > 0
    ) {

        return true;
    }

    return false;
}

// Fetches total count of valid orders for the user
function couponValidationCountValidOrdersForUser($user)
{

    return parseExecuteQuery(["status" => listStatusesForCouponValidation(), "user" => $user], "Order", "", "", [], 1,
        false, [], 'count');
}

// Validates if the current user has a device Id that matches the referral user's device
function validateReferralCodeValidationForDevice($referredUser, $byUser)
{

    $deviceCheckPassed = true;
    $phoneCheckPassed = true;

    /////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////
    /////////////////////////// PHONE CHECK /////////////////////////////
    /////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////
    // Find current phone number of the current user
    $userPhone = parseExecuteQuery(["user" => $referredUser, "isActive" => true, "phoneVerified" => true], "UserPhones",
        "", "updatedAt", [], 1);


    // Is phone white listed for referral
    $objUserPhonesWhiteList = parseExecuteQuery([
        "phoneNumber" => strval($userPhone->get('phoneNumber')),
        "phoneCountryCode" => strval($userPhone->get('phoneCountryCode')),
        "allowForDuplicateReferral" => true
    ], "UserPhonesWhiteList");



    if (count_like_php5($objUserPhonesWhiteList) > 0) {

        $phoneCheckPassed = true;
    } else {


        /////////////////////////////////////////////////////////////////////
        // Find any other users with the same phone number
        $userPhoneOthers = parseExecuteQuery([
            "phoneNumber" => $userPhone->get('phoneNumber'),
            "phoneCountryCode" => strval($userPhone->get('phoneCountryCode')),
            "phoneVerified" => true
        ], "UserPhones");

        // If more than one user has the phone
        if (count_like_php5($userPhoneOthers) > 1) {

            return [false, "Duplicate account by phone"];
        }

    }

    /////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////
    ////////////////////////// DEVICE CHECK /////////////////////////////
    /////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////

    /////////////////////////////////////////////////////////////////////
    // Find current session device of the current user
    $sessionDevices = parseExecuteQuery(["user" => $referredUser, "isActive" => true], "SessionDevices", "",
        "updatedAt", ["userDevice"]);

    /////////////////////////////////////////////////////////////////////
    // Find current user device id
    $userDeviceId = "";
    $userDeviceIdList = [];
    foreach ($sessionDevices as $sessionDevice) {

        // Current device id
        if (empty($userDeviceId)) {

            $userDeviceId = $sessionDevice->get("userDevice")->get("deviceId");
        }

        $userDeviceIdList[] = $sessionDevice->get("userDevice")->get("deviceId");
    }

    // Is device white listed for referral
    $isUserDeviceWhiteListed = isUserDeviceWhiteListed($userDeviceId, 'referral');


    if ($isUserDeviceWhiteListed == true) {

        $deviceCheckPassed = true;
    } else {

        /////////////////////////////////////////////////////////////////////
        // Same Device Self Referral Check
        // Current User Device Id(s) != Referred User Device Id(s)
        /////////////////////////////////////////////////////////////////////
        // Find all device Ids for the user who's code was used
        // Ensures that the referred person is not the same the one referring
        $userDevicesOfByUser = parseExecuteQuery(["user" => $byUser], "UserDevices");

        foreach ($userDevicesOfByUser as $userDeviceOne) {

            // Check if the device ids matches the current user
            if (in_array($userDeviceOne->get("deviceId"), $userDeviceIdList)) {

                
                return [false, "Self Referral"];
            }
        }

        /////////////////////////////////////////////////////////////////////
        // 2nd account to use referral code disallowed
        /////////////////////////////////////////////////////////////////////
        // Check if this user already has an account with us
        if ($GLOBALS['env_AllowDuplicateAccountsForReferral'] == false) {

            foreach ($userDeviceIdList as $userDeviceIdOne) {

                $userDevicesOfAnyUser = parseExecuteQuery(["deviceId" => $userDeviceIdOne], "UserDevices", "", "",
                    ["user"]);

                foreach ($userDevicesOfAnyUser as $userDeviceOne) {

                    // Check if the device ids matches the current user
                    // But not the same user

                    if (
                        strcasecmp($userDeviceOne->get("deviceId"), $userDeviceIdOne) == 0
                        &&
                        ($userDeviceOne->get("user") !== null)
                        &&
                        strcasecmp($userDeviceOne->get("user")->getObjectId(), $referredUser->getObjectId()) != 0
                    ) {

                        return [false, "Duplicate account by device"];
                    }
                }
            }
        }

        /*
        /////////////////////////////////////////////////////////////////////////////////////////
        // Find any other account with this deviceId that also used a referral code for signup
        $userDevicesOfOtherUsersWithThisDeviceId = parseExecuteQuery(["deviceId" => $userDeviceId], "UserDevices", "", "", ["user"], 5000);
        
        $usersSeenList = [];
        foreach($userDevicesOfOtherUsersWithThisDeviceId as $userDeviceOne) {

            // If this user was not seen earlier, let's check it
            if(!in_array($userDeviceOne->get('user')->getObjectId(), $usersSeenList)) {

                // Add it to the list
                $usersSeenList[] = $userDeviceOne->get('user')->getObjectId();

                // Find out if this user used a referral code for signup
                $results = parseExecuteQuery(["__E__userReferral" => true, "user" => $userDeviceOne->get('user')], "UserCredits", "", "", ["userReferral"]);

                // Then not allowed to signup again with referral code
                if(count_like_php5($results) > 0) {

                    return false;
                }
            }
        }
        */
    }

    return [true, ""];
}

// Fetches total count of valid orders that have used this code
function couponValidationCountAllWithCoupon($coupon, $couponGroupsObj, $couponGroupsWithoutCurrentObj, $forSignup)
{

    // If a groupId exists on the coupon
    if (count_like_php5($couponGroupsObj) == 0) {

        $couponGroupsObj[] = $coupon;
    }

    // $couponGroupsCodes = extractCouponCodes($couponGroupsObj);

    // Search for All coupons only at signup step
    if ($forSignup == true) {

        // JMD
        $count = 0;
        foreach ($couponGroupsObj as $couponToCheck) {

            $count = $count + getCouponUsageByCode($couponToCheck->get("couponCode"));
        }

        return $count;

        // Fetch all User Credits for all within the coupon list
        // $results = parseExecuteQuery(["__E__signupCoupon" => true], "UserCredits", "", "", ["signupCoupon"], 5000);

        // $countOfUserCredits = getCouponCountForMatch($couponGroupsCodes, $results, "signupCoupon");

        // // Fetch all User Coupons for all within the coupon list
        // $results = parseExecuteQuery([], "UserCoupons", "", "", ["coupon"], 5000);

        // $countOfUserCoupons = getCouponCountForMatch($couponGroupsCodes, $results, "coupon");
    }
    // Else Search without the current coupon
    // Since that would have been loaded already to these tables
    else {

        // JMD
        $count = 0;
        foreach ($couponGroupsWithoutCurrentObj as $couponToCheck) {

            $count = $count + getCouponUsageByCode($couponToCheck->get("couponCode"));
        }

        return $count;

        // $couponGroupsWithoutCurrentCodes = extractCouponCodes($couponGroupsWithoutCurrentObj);

        // Fetch all User Credits for all within the coupon list
        // $results = parseExecuteQuery(["__E__signupCoupon" => true], "UserCredits", "", "", ["signupCoupon"], 5000);

        // $countOfUserCredits = getCouponCountForMatch($couponGroupsWithoutCurrentCodes, $results, "signupCoupon");

        // // Fetch all User Coupons for all within the coupon list
        // $countOfUserCoupons = parseExecuteQuery([], "UserCoupons", "", "", ["coupon"], 5000);

        // $countOfUserCoupons = getCouponCountForMatch($couponGroupsWithoutCurrentCodes, $results, "coupon");
    }

    // Fetch all Orders with the coupons and for all
    // $results = parseExecuteQuery(["__E__coupon" => true, "status" => listStatusesForCouponValidation()], "Order", "", "", ["coupon"], 10000);

    // $countOfOrder = getCouponCountForMatch($couponGroupsCodes, $results, "coupon");

    // $countOfUsageByAll = $countOfUserCredits + $countOfUserCoupons + $countOfOrder;

    // return $countOfUsageByAll;

    /*
    // If coupon is a promo of credit type
    if(isCouponCreditType($coupon)) {

        // Fetch all coupons with this group
        return parseExecuteQuery(["__CONTAINEDIN__signupCoupon" => $couponGroupsObj], "UserCredits", "", "", [], 1, false, [], 'count');
    }
    // If coupon is a forSignup but not of credit type
    else if($coupon->get('forSignup') == true) {

        // Fetch all coupons with this group
        return parseExecuteQuery(["__CONTAINEDIN__coupon" => $couponGroupsObj], "UserCoupons", "", "", [], 1, false, [], 'count');
    }
    else {

        // Fetch all coupons with this group
        return parseExecuteQuery(["__CONTAINEDIN__coupon" => $couponGroupsObj, "status" => listStatusesForCouponValidation()], "Order", "", "", [], 1, false, [], 'count');
    }
    */
}

// Fetches total count of valid orders that have used this code by this user
function couponValidationCountOrdersWithCouponForUser($coupon, $user, $couponGroupsObj)
{

    // Note: We don't check UserCoupons or UserCredits here since this check is for usage > 1
    // Since a particular user can sign up only once the above two tables are not required to be checked
    // This count is at User
    // For Device or overall counts, you have to check the other two tables

    // If a groupId exists on the coupon
    if (count_like_php5($couponGroupsObj) == 0) {

        $couponGroupsObj[] = $coupon;
    }

    $count = 0;
    $couponsUsedByUser = getCouponUsageByUser($user->getObjectId());
    foreach ($couponGroupsObj as $couponToCheck) {

        if (in_array($couponToCheck->get("couponCode"), array_keys($couponsUsedByUser))) {

            $count = $count + $couponsUsedByUser[$couponToCheck->get("couponCode")];
        }
    }

    return $count;

    // $couponGroupsCodes = extractCouponCodes($couponGroupsObj);

    // // Fetch all coupons with this group for this user
    // $results = parseExecuteQuery(["__E__coupon" => true, "status" => listStatusesForCouponValidation(), "user" => $user], "Order", "", "", ["coupon"]);

    // return getCouponCountForMatch($couponGroupsCodes, $results, "coupon");
}

// Identifies if the user device is whitelisted for the requested purpose
function isUserDeviceWhiteListed($deviceId, $purpose)
{

    $userDevices = [];
    if (strcasecmp($purpose, 'coupon') == 0) {

        $userDevices = parseExecuteQuery(["deviceId" => $deviceId, 'allowDeviceRestrictedCoupons' => true],
            "UserDevicesWhiteList");
    }

    if (count_like_php5($userDevices) > 0) {

        return true;
    }

    if (strcasecmp($purpose, 'referral') == 0) {

        $userDevices = parseExecuteQuery(["deviceId" => $deviceId, 'allowDeviceRestrictedReferral' => true],
            "UserDevicesWhiteList");
    }

    if (count_like_php5($userDevices) > 0) {

        return true;
    }

    return false;
}

function fetchCurrentDeviceId($user)
{

    /////////////////////////////////////////////////////////////////////
    // Find current session device
    $sessionDevice = parseExecuteQuery(["user" => $user, "isActive" => true], "SessionDevices", "", "updatedAt",
        ["userDevice"], 1);

    /////////////////////////////////////////////////////////////////////
    // Find current user device id
    $userDeviceId = $sessionDevice->get("userDevice")->get("deviceId");

    return $userDeviceId;
}

// Fetches total count of valid orders that have used this code by this device
function couponValidationCountOrdersWithCouponForDevice(
    $coupon,
    $user,
    $couponGroupsObj,
    $couponGroupsWithoutCurrentObj,
    $forSignup
) {

    /////////////////////////////////////////////////////////////////////
    // Fetch device id
    $userDeviceId = fetchCurrentDeviceId($user);

    /////////////////////////////////////////////////////////////////////
    // Find all users that have the same deviceId
    $userDevices = parseExecuteQuery(["deviceId" => $userDeviceId], "UserDevices", "", "updatedAt", ["user"]);

    $userAddedToList = [];
    $usersWithSameDeviceId = [];
    $usersWithSameDeviceIdButNotCurrentUser = [];
    foreach ($userDevices as $userDeviceOne) {

        if ($userDeviceOne->get("user") === null) {
            continue;
        }

        if (!in_array($userDeviceOne->get("user")->getObjectId(), $userAddedToList)) {

            $userAddedToList[] = $userDeviceOne->get("user")->getObjectId();
            $usersWithSameDeviceId[] = $userDeviceOne->get("user");

            if ($user->getObjectId() != $userDeviceOne->get("user")->getObjectId()) {

                $usersWithSameDeviceIdButNotCurrentUser[] = $userDeviceOne->get("user");
            }
        }
    }

    /////////////////////////////////////////////////////////////////////

    // If no group-based coupons found
    if (count_like_php5($couponGroupsObj) == 0) {

        $couponGroupsObj[] = $coupon;
    }

    // $countOfUserCredits = 0;
    // $countOfUserCoupons = 0;
    // $countOfOrder = 0;

    /*
    // If coupon is a promo of credit type
    if(isCouponCreditType($coupon) == true) {

        // Fetch all User Credits for the user device Ids within the coupon list
        $countOfUserCredits = parseExecuteQuery(["__CONTAINEDIN__signupCoupon" => $couponGroupsObj, "__CONTAINEDIN__user" => $usersWithSameDeviceId], "UserCredits", "", "", [], 1, false, [], 'count');
    }
    // If coupon is a forSignup but not of credit type
    else if($coupon->get('forSignup') == true) {

        // Is this part of the Signup step call?
        if($forSignup == true) {

            // Fetch all User Coupons for the user device Ids within the coupon list
            $countOfUserCoupons = parseExecuteQuery(["__CONTAINEDIN__coupon" => $couponGroupsObj, "__CONTAINEDIN__user" => $usersWithSameDeviceId], "UserCoupons", "", "", [], 1, false, [], 'count');
        }
        // Else if it was allowed on signup then we honor it
        else {

            $countOfUserCoupons = 0;
        }
    }
    else {

        // Fetch all Orders with the coupons and for the user device Ids
        $countOfOrder = parseExecuteQuery(["status" => listStatusesForCouponValidation(), "__CONTAINEDIN__coupon" => $couponGroupsObj, "__CONTAINEDIN__user" => $usersWithSameDeviceId], "Order", "", "", [], 1, false, [], 'count');
    }
    */

    // $couponGroupsCodes = extractCouponCodes($couponGroupsObj);

    // Search for All coupons only at signup step
    if ($forSignup == true) {

        $count = 0;

        // Fetch all for the user device Ids within the coupon list
        foreach ($usersWithSameDeviceId as $user) {

            $couponsUsedByUser = getCouponUsageByUser($user->getObjectId());
            foreach ($couponGroupsObj as $couponToCheck) {

                if (in_array($couponToCheck->get("couponCode"), array_keys($couponsUsedByUser))) {

                    $count = $count + $couponsUsedByUser[$couponToCheck->get("couponCode")];
                }
            }
        }

        return [$usersWithSameDeviceIdButNotCurrentUser, $count];

        // Fetch all User Credits for the user device Ids within the coupon list
        // JMD
        // $results = parseExecuteQuery(["__E__signupCoupon" => true, "__CONTAINEDIN__user" => $usersWithSameDeviceId], "UserCredits", "", "", ["signupCoupon"]);

        // $countOfUserCredits = getCouponCountForMatch($couponGroupsCodes, $results, "signupCoupon");

        // // Fetch all User Coupons for the user device Ids within the coupon list
        // $results = parseExecuteQuery(["__CONTAINEDIN__user" => $usersWithSameDeviceId], "UserCoupons", "", "", ["coupon"]);

        // $countOfUserCoupons = getCouponCountForMatch($couponGroupsObj, $results, "coupon");
    }
    // Else Search without the current coupon
    // Since that would have been loaded already to these tables
    else {

        // $couponGroupsWithoutCurrentCodes = extractCouponCodes($couponGroupsWithoutCurrentObj);
        $count = 0;
        $couponsUsedByAllUsersWithTheSameDeviceId = [];
        // Fetch all for the user device Ids within the coupon list
        foreach ($usersWithSameDeviceId as $k => $user) {


            $couponsUsedByAllUsersWithTheSameDeviceId[] = $couponsUsedByUser = getCouponUsageByUser($user->getObjectId());
            /*
            foreach($couponGroupsWithoutCurrentObj as $couponToCheck) {

                if(in_array($couponToCheck->get("couponCode"), array_keys($couponsUsedByUser))) {

                    $count = $count + $couponsUsedByUser[$couponToCheck->get("couponCode")];
                }
            }
            */
        }

        $result = [];
        foreach ($couponsUsedByAllUsersWithTheSameDeviceId as $k => $v) {
            if (empty($v)) {
                continue;
            }
            foreach ($v as $kk => $vv) {
                if (!isset($result[$kk])) {
                    $result[$kk] = $vv;
                } else {
                    $result[$kk] = $result[$kk] + $vv;
                }
            }
        }

        $count = 0;
        if (isset($result[$coupon->get("couponCode")])) {
            $count = $result[$coupon->get("couponCode")];
        }
        return [$usersWithSameDeviceIdButNotCurrentUser, $count];

        json_error(json_encode($couponsUsedByAllUsersWithTheSameDeviceId), '', '');


        $count = 0;
        $xxxx = [];
        // Fetch all for the user device Ids within the coupon list
        foreach ($usersWithSameDeviceId as $user) {
            $xxxx[] = $user->getObjectId();
            $couponsUsedByUser = getCouponUsageByUser($user->getObjectId());

            if ($user->getObjectId() == '0cxIBDl01k') {
                $yyyy = $couponsUsedByUser;
            }
            foreach ($couponGroupsWithoutCurrentObj as $couponToCheck) {

                if (in_array($couponToCheck->get("couponCode"), array_keys($couponsUsedByUser))) {

                    $count = $count + $couponsUsedByUser[$couponToCheck->get("couponCode")];
                }
            }
        }


        json_error(json_encode(count($couponGroupsWithoutCurrentObj)), '', '');
        return [$usersWithSameDeviceIdButNotCurrentUser, $count];

        // Fetch all User Credits for the user device Ids within the coupon list
        // $results = parseExecuteQuery(["__E__signupCoupon" => true, "__CONTAINEDIN__user" => $usersWithSameDeviceId], "UserCredits", "", "", ["signupCoupon"]);

        // $countOfUserCredits = getCouponCountForMatch($couponGroupsWithoutCurrentCodes, $results, "signupCoupon");

        // // Fetch all User Coupons for the user device Ids within the coupon list
        // $results = parseExecuteQuery(["__CONTAINEDIN__user" => $usersWithSameDeviceId], "UserCoupons", "", "", ["coupon"]);

        // $countOfUserCoupons = getCouponCountForMatch($couponGroupsWithoutCurrentCodes, $results, "coupon");
    }

    // Fetch all Orders with the coupons and for the user device Ids

    // $results = parseExecuteQuery(["__E__coupon" => true, "status" => listStatusesForCouponValidation(), "__CONTAINEDIN__user" => $usersWithSameDeviceId], "Order", "", "", ["coupon"], 10000);

    // $countOfOrder = getCouponCountForMatch($couponGroupsCodes, $results, "coupon");

    // $countOfUsageByDevice = $countOfUserCredits + $countOfUserCoupons + $countOfOrder;

    // return [$usersWithSameDeviceIdButNotCurrentUser, $countOfUsageByDevice];
}

function extractCouponCodes($matchCoupons)
{

    $couponCodes = [];
    foreach ($matchCoupons as $coupon) {

        $couponCodes[] = $coupon->get("couponCode");
    }

    return $couponCodes;
}

function getCouponCountForMatch($couponCodes, $resultCoupons, $couponIndex)
{

    $count = 0;
    foreach ($resultCoupons as $result) {

        if ($result->has($couponIndex)
            && $result->get($couponIndex)->has("couponCode")
            && in_array($result->get($couponIndex)->get("couponCode"), $couponCodes)
        ) {

            $count++;
        }
    }

    return $count;
}

/**
 * @param $order
 * @param $user
 * @param $creditsInCents
 * @return ParseObject
 */
function applyCreditsToOrder($order, $user, $creditsInCents)
{

    $userCreditsApplied = new ParseObject("UserCreditsApplied");
    $userCreditsApplied->set('appliedToOrder', $order);
    $userCreditsApplied->set('user', $user);
    $userCreditsApplied->set('appliedInCents', $creditsInCents);
    $userCreditsApplied->save();

    return $userCreditsApplied;
}

/**
 * @param $order
 * @param $user
 * @param $creditsInCents
 * @return ParseObject
 */
function applyCreditsToOrderViaMap($appliedToOrder, $user, $creditAppliedMap)
{

    $appliedInCents = 0;
    foreach ($creditAppliedMap as $details) {

        // Get user credit
        $userCredit = parseExecuteQuery(["objectId" => $details["userCredit"]], "UserCredits", "", "", [], 1);

        $uniqueId = md5($details["userCredit"] . '-' . $appliedToOrder->getObjectId() . '-' . $details["availableCreditsInCents"] . '-' . $details["appliedCreditsInCents"]);

        $userCreditsAppliedMap = new ParseObject("UserCreditsAppliedMap");
        $userCreditsAppliedMap->set('appliedToOrder', $appliedToOrder);
        $userCreditsAppliedMap->set('user', $user);
        $userCreditsAppliedMap->set('userCredit', $userCredit);
        $userCreditsAppliedMap->set('uniqueId', $uniqueId);
        $userCreditsAppliedMap->set("availableCreditsInCents", intval($details["availableCreditsInCents"]));
        $userCreditsAppliedMap->set("appliedCreditsInCents", intval($details["appliedCreditsInCents"]));
        $userCreditsAppliedMap->save();

        $appliedInCents = $appliedInCents + $details["appliedCreditsInCents"];
    }

    $userCreditsApplied = new ParseObject("UserCreditsApplied");
    $userCreditsApplied->set('appliedToOrder', $appliedToOrder);
    $userCreditsApplied->set('user', $user);
    $userCreditsApplied->set('appliedInCents', $appliedInCents);
    $userCreditsApplied->save();

    return $userCreditsApplied;
}

function getDisclaimerTextForDelivery($displayDisclaimer = false)
{

    if (($GLOBALS['env_AllowCreditsForDelivery'] == false
            && isset($GLOBALS['user']) && getAvailableUserCreditsViaMap($GLOBALS['user'])[1] > 0) || $displayDisclaimer == true
    ) {

        return "Credits or selected offer cannot be applied to Delivery orders.";
    }

    return "";
}

function getDisclaimerTextForPickup($displayDisclaimer = false)
{

    if (($GLOBALS['env_AllowCreditsForPickup'] == false
            && isset($GLOBALS['user']) && getAvailableUserCreditsViaMap($GLOBALS['user'])[1] > 0) || $displayDisclaimer == true
    ) {

        return "Credits or selected offer cannot be applied to Pickup orders.";
    }

    return "";
}

function areCreditsApplicable($fullfillmentType = '')
{

    if (empty($fullfillmentType)) {

        return true;
    }

    if (strcasecmp($fullfillmentType, 'p') == 0
        && $GLOBALS['env_AllowCreditsForPickup'] == false
    ) {

        return false;
    }

    if (strcasecmp($fullfillmentType, 'd') == 0
        && $GLOBALS['env_AllowCreditsForDelivery'] == false
    ) {

        return false;
    }

    return true;
}

/**
 * @param $order
 * @return null|int
 */
function getCreditsAppliedToOrder($order)
{
    $parseUserCredits = parseExecuteQuery(array("appliedToOrder" => $order), "UserCreditsApplied", "", "", [], 1);
    if (empty($parseUserCredits)) {
        return null;
    }
    return $parseUserCredits->appliedInCents;
}

/**
 * @param $order
 * @return null
 */
function clearCreditsAppliedMapToOrder($order)
{

    $creditAppliedMap = [];
    $parseUserCreditsAppliedMap = parseExecuteQuery(array("appliedToOrder" => $order), "UserCreditsAppliedMap");
    if (empty($parseUserCreditsAppliedMap)) {
        return null;
    }

    foreach ($parseUserCreditsAppliedMap as $map) {

        $map->destroy();
        $map->save();
    }

    return null;
}

/**
 * @param $order
 * @return null|int
 */
function getCreditsAppliedMapToOrder($order)
{

    $creditAppliedMap = [];
    $parseUserCreditsAppliedMap = parseExecuteQuery(array("appliedToOrder" => $order), "UserCreditsAppliedMap", "", "",
        ["userCredit"]);
    if (empty($parseUserCreditsAppliedMap)) {
        return null;
    }

    foreach ($parseUserCreditsAppliedMap as $map) {

        $creditAppliedMap[] =
            [
                "userCredit" => $map->get("userCredit")->getObjectId(),
                "userCreditReasonCode" => $map->get("userCredit")->get('reasonForCreditCode'),
                "availableCreditsInCents" => $map->get("availableCreditsInCents"),
                "appliedCreditsInCents" => $map->get("appliedCreditsInCents")
            ];
    }

    return $creditAppliedMap;
}

// Identifies if the order has first use referral signup created applied
function doesOrderHaveReferralSignupCreditApplied($order)
{

    // JMD
    $internal = getOrderSummary($order, 1)[0]["internal"];
    if (isset($internal["referralSignupCreditApplied"])
        && $internal["referralSignupCreditApplied"] == true
    ) {

        return true;
    }

    return false;
}

// Identifies if the order has first use referral signup applied
// AND
// Identifies of the referer has already used the earnings
// Returns => rewardsEarned, earnedRewardsUsed
function hasOrderHaveReferralEarnedAndCreditUsed($order)
{

    if (doesOrderHaveReferralSignupCreditApplied($order) == true) {

        // Find the reward row in UserCredits
        // $parseUserCredits = parseExecuteQuery(array("fromOrder" => $order, "__SW__reasonForCredit" => "Referral Reward - "), "UserCredits", "", "", [] , 1);
        $parseUserCredits = parseExecuteQuery(array(
            "fromOrder" => $order,
            "reasonForCreditCode" => getUserCreditReasonCode('ReferralReward')
        ), "UserCredits", "", "", [], 1);

        // Not earned yet
        if (empty($parseUserCredits)) {

            return [false, false];
        }

        // Was the earning from this order already used
        $parseUserCreditsMap = parseExecuteQuery(array("userCredit" => $parseUserCredits), "UserCreditsAppliedMap", "",
            "", ["appliedToOrder"], 1);

        // Used and the used order is not canceled
        if (!empty($parseUserCreditsMap)
            && $parseUserCreditsMap->has('appliedToOrder')
            && !in_array($parseUserCreditsMap->get('appliedToOrder')->get('status'), listStatusesForCancelled())
        ) {

            return [true, true];
        }

        return [true, false];
    }

    return [false, false];
}

// JMD
// Does this credit belong to Sign up Referral credit
function isUserCreditFromReferralOffer($userCredit)
{

    if ($userCredit->has('userReferral')
        && !empty($userCredit->get('userReferral'))
        // && preg_match("/Signup Referral Code/si", $userCredit->get('reasonForCredit')))
        && strcasecmp($userCredit->get('reasonForCreditCode'), getUserCreditReasonCode('ReferralSignup')) == 0
    ) {

        return true;
    }

    return false;
}

/**
 * @param $user
 * @return int
 *
 * Gets pending credits for the consumer user
 */
function getAvailableUserCredits($user, $subTotal = -1)
{

    if (empty($user)) {

        return [0, false];
    }

    $totalLifttimeUserCredit = 0;
    $totalUsedUserCredit = 0;
    $referralSignupCreditFound = false;
    $parseUserCredits = parseExecuteQuery(array("user" => $user), "UserCredits", "", "", array("user"));

    if (!empty($parseUserCredits)) {

        foreach ($parseUserCredits as $userCredit) {

            // Has User Credit Expired
            // -1 used for refund credits that don't expire
            if ($userCredit->has('expireTimestamp')
                && !empty($userCredit->get('expireTimestamp'))
                && ($userCredit->get('expireTimestamp') < time()
                    && $userCredit->get('expireTimestamp') != -1)
            ) {

                continue;
            }

            // Check if this credit is from a Referral signup (not earned but signup)
            // Then compare Subtotal for the minimum required spend
            // If lower, then don't award credit usage
            if (isUserCreditFromReferralOffer($userCredit)) {

                if ($subTotal != -1
                    && $subTotal < $GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']
                ) {

                    $referralSignupCreditFound = false;
                    continue;
                } else {

                    $referralSignupCreditFound = true;
                }
            }

            $totalLifttimeUserCredit += $userCredit->get("creditsInCents");
        }
    }

    // Get the total of all applied credit (used)
    // Skip any canceled orders
    $parseUserCreditApplied = parseExecuteQuery(array("user" => $user), "UserCreditsApplied", "", "",
        array("appliedToOrder"));
    if (!empty($parseUserCreditApplied)) {

        foreach ($parseUserCreditApplied as $userCreditApplied) {
            if ($userCreditApplied->get("appliedToOrder") !== null) {
                $orderStatus = $userCreditApplied->get("appliedToOrder")->get("status");

                // Status not canceled
                if (in_array($orderStatus, listStatusesForGreaterThanSubmittedOrder())) {
                    $totalUsedUserCredit += $userCreditApplied->get("appliedInCents");
                }
            }
        }
    }

    // Get balance credits
    $totalAvailableCredits = $totalLifttimeUserCredit - $totalUsedUserCredit;
    if ($totalAvailableCredits < 0) {

        $totalAvailableCredits = 0;
    }

    $wereReferralSignupCreditAppliedToThisOrder = $referralSignupCreditFound;
    // If the user has no credits left or had more than one credit available (over time), indicating the user doesn't have referral credits anymore
    // TODO: Track UserCredit row used when Applying to avoid this check (count of parseUserCredits)
    if ($totalAvailableCredits == 0 || count_like_php5($parseUserCredits) > 1) {

        $wereReferralSignupCreditAppliedToThisOrder = false;
    }

    return [$totalAvailableCredits, $wereReferralSignupCreditAppliedToThisOrder];
}

/**
 * @param $order
 * @param $user
 * @param $creditsInCents
 *
 * Add applied cents to Order for Consumer user
 */
function addUserCreditsApplied($order, $user, $creditsInCents)
{
    if ($creditsInCents > 0) {
        $parseUserCreditApplied = new ParseObject("UserCreditsApplied");
        $parseUserCreditApplied->set('user', $user);
        $parseUserCreditApplied->set('appliedToOrder', $order);
        $parseUserCreditApplied->set('appliedInCents', $creditsInCents);
        $parseUserCreditApplied->save();
    }
}

/**
 * @param ParseUser $user
 * @return ParseObject|null
 */
function getAvailableAndApplicableUserCoupon($user, $order)
{

    $parseUserCoupons = parseExecuteQuery(array("user" => $user), "UserCoupons", "createdAt", "",
        array("coupon", "coupon.applicableUser", "appliedToOrder"));

    // iterate through all UserCoupons
    foreach ($parseUserCoupons as $parseUserCoupon) {

        // if coupon is not applied to any order we can take it
        $parseOrder = $parseUserCoupon->get('appliedToOrder');
        if (empty($parseOrder)) {
            $couponValidation = fetchValidCoupon($parseUserCoupon->get('coupon'), '', $order->get('retailer'), $user,
                false, false, "", true);
            if ($couponValidation[1]) {
                return $parseUserCoupon;
            }
            continue;
        }

        // if coupon is applied, we need to check if it applied to canceled order (if so, we can take it)
        $orderStatus = $parseOrder->get('status');
        if (in_array($orderStatus, listStatusesForCancelled()) || in_array($orderStatus, listStatusesForInternal())) {
            $couponValidation = fetchValidCoupon($parseUserCoupon->get('coupon'), '', $order->get('retailer'), $user,
                false, false, "", true);
            if ($couponValidation[1]) {
                return $parseUserCoupon;
            }
            continue;
        }
    }
    return null;
}


function isOrderWithExplicitServiceFeeShown($order)
{

    $serviceFeeByAirport = getServiceFeePCTByIata($order->get('retailer')->get('airportIataCode'));

    if ($serviceFeeByAirport > 0) {

        $sessionDevice = parseExecuteQuery(["user" => $order->get('user'), "isActive" => true], "SessionDevices", "",
            "updatedAt", ["userDevice"], 1);

        if (count_like_php5($sessionDevice) == 0 || !$sessionDevice->has("userDevice")) {

            return false;
        } else {

            $env_MinVersionServiceFeeiOS = intval(str_replace('.', '', $GLOBALS['env_MinVersionServiceFeeiOS']));
            $env_MinVersionServiceFeeAndroid = intval(str_replace('.', '',
                $GLOBALS['env_MinVersionServiceFeeAndroid']));
            $env_MinVersionServiceFeeWeb = intval(str_replace('.', '', $GLOBALS['env_MinVersionServiceFeeWeb']));

            $deviceAppVersion = intval(str_replace('.', '', $sessionDevice->get('userDevice')->get('appVersion')));

            // Is iOS
            if ($sessionDevice->get('userDevice')->get('isIos')
                && $deviceAppVersion >= $env_MinVersionServiceFeeiOS
            ) {

                return true;
            } // Is Android
            else {
                if ($sessionDevice->get('userDevice')->get('isAndroid')
                    && $deviceAppVersion >= $env_MinVersionServiceFeeAndroid
                ) {

                    return true;
                } else {
                    if ($sessionDevice->get('userDevice')->get('isWeb')
                        && $deviceAppVersion >= $env_MinVersionServiceFeeWeb
                    ) {
                        return true;
                    }
                }
            }

        }
    }

    return false;
}

function isAirportEmployeeDiscountEnabled($retailerUniqueId, $globalSettingRequested = false)
{

    $obj = new ParseQuery("Retailers");
    $retailer = parseSetupQueryParams(["uniqueId" => $retailerUniqueId], $obj);
    $retailerPOSConfig = parseExecuteQuery(["__MATCHESQUERY__retailer" => $retailer], "RetailerPOSConfig", "", "", [],
        1);

    // If disallowed at the retailer level
    if ($retailerPOSConfig->has('disallowEmpDiscount')
        && $retailerPOSConfig->get('disallowEmpDiscount') == true
    ) {

        return false;
    }

    if ($globalSettingRequested) {

        // Use global setting
        return $GLOBALS['env_AirportEmployeeDiscountEnabled'];
    }

    // Used for calculation of discount to allow user level employee setting to take
    return true;
}

function isMilitaryDiscountEnabled($retailerUniqueId)
{

    $obj = new ParseQuery("Retailers");
    $retailer = parseSetupQueryParams(["uniqueId" => $retailerUniqueId], $obj);
    $retailerPOSConfig = parseExecuteQuery(["__MATCHESQUERY__retailer" => $retailer], "RetailerPOSConfig", "", "", [],
        1);

    if (empty($retailerPOSConfig)) {

        return false;
    }

    // If disallowed at the retailer level
    if ($retailerPOSConfig->has('disallowMilitaryDiscount')
        && $retailerPOSConfig->get('disallowMilitaryDiscount') == true
    ) {

        return false;
    }

    // Else use global setting
    return $GLOBALS['env_MilitaryDiscountEnabled'];
}

function isOrderEligibleForEmployeeDiscount($order)
{

    if (isAirportEmployeeDiscountEnabled($order->get('retailer')->get('uniqueId')) == false) {

        return false;
    }

    if (!empty($order->get('user')->get('airEmpValidUntilTimestamp'))
        && $order->get('user')->get('airEmpValidUntilTimestamp') > time()
        // Ensures if the user has toggled to false (i.e. this field is now populated), then don't give discount
        && !$order->has('airportEmployeeDiscount')
    ) {

        return true;
    } else {
        if (!empty($order->get('airportEmployeeDiscount'))
            && $order->get('airportEmployeeDiscount') == true
        ) {

            return true;
        }
    }

    return false;
}

function isOrderEligibleForMilitaryDiscount($order)
{

    if (isMilitaryDiscountEnabled($order->get('retailer')->get('uniqueId')) == false) {

        return false;
    }

    if (!empty($order->get('militaryDiscount'))
        && $order->get('militaryDiscount') == true
    ) {

        return true;
    }

    return false;
}

/**
 * @param $order
 * @param int $getPaymentDetailsFlag
 * @param bool $forSubmission
 * @param int $requestedFullFillmentTimestamp
 * @param bool $finalSubmit - this is true only when this function is called by submit/ endpoint (not a validation, but final submit)
 * @param string $fullfillmentType - p or d [calculates order totals for each, e.g. for pickup we skip credits]
 * @return array
 */

// $order = Order Parse Object
// $getPaymentDetailsFlag = Provide Payment details
// $forSubmission = Used during /submit/validation and /submit to force a FulfillmentFee
// $requestedFullFillmentTimestamp = To identify Item restrictions
// $finalSubmit = If true, saves UserCoupons coupon as applied
// $fullfillmentType = To identify if credits should be allowed for Delivery or Pickup orders
// deliveryLocationObject = Not used today, but can be in future for dynamic pricing

function getOrderSummary(
    $order,
    $getPaymentDetailsFlag = 0,
    $forSubmission = false,
    $requestedFullFillmentTimestamp = 0,
    $finalSubmit = false,
    $fullfillmentType = '',
    $deliveryLocationObject = ''
) {

    if (count_like_php5($order) == 0 || !isset($order)) {

        json_error("AS_831", "", "Order not found!");
    }

    // Initialize
    $orderId = $order->getObjectId();

    $orderDate = "";
    $orderSubmitAirportDateTime = "";
    $fullfillmentETATimeDisplay = "";
    $fullfillmentETATimezoneShort = "";
    $fullfillmentTypeDisplay = "";
    $etaTimestamp = "";
    $fullfillmentFee = 0;
    $serviceFee = 0;
    $submitTimestamp = "";
    $statusDelivery = "";
    $deliveryLocation = "";
    $subTotal = 0;
    $referralSignupCreditApplied = false;
    $creditAppliedMap = [];
    $creditsAppliedToFees = 0;
    $cartUpdateMessage = "";

    // Fetch Retailer Info
    $retailer = $order->get('retailer');
    $retailerTotals = array();
    //$uniqueRetailerId = $order->get('retailer')->get('uniqueId');
    $order->get('retailer')->fetch();
    $retailerObjectResults = $order->get('retailer');
    $airporTimeZone = fetchAirportTimeZone($retailerObjectResults->get('airportIataCode'), date_default_timezone_get());


    if (strcasecmp($fullfillmentType, 'd') == 0) {


        if ($order->get('airportEmployeeDiscount') === true) {
            $feesInCentsAfterCredits = getDeliveryFeesInCentsByIataForEmployee($retailerObjectResults->get('airportIataCode'));
        } else {
            $feesInCentsAfterCredits = getDeliveryFeesInCentsByIata($retailerObjectResults->get('airportIataCode'));
        }
        // employee new value

        //$feesInCentsAfterCredits = getDefaultDeliveryFeesInCents(0);
    } else {
        if (strcasecmp($fullfillmentType, 'p') == 0) {

            $feesInCentsAfterCredits = getPickupFeesInCentsByIata($retailerObjectResults->get('airportIataCode'));
            //$feesInCentsAfterCredits = getDefaultPickupFeesInCents(0);
        }
    }


    //////////////////////////////////////////////////////////////////////////////////////
    // STATUS Calculation
    //////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////////////////
    // Active order BUT NOT a CART either
    //////////////////////////////////////////////////////////////////////////////////////
    // Get the last status that is marked as User Inform, and show that
    // This allows us to skip any internal statuses
    if (strcasecmp(isOrderActiveOrCompleted($order), 'a') == 0
        && !in_array($order->get('status'), array_merge(listStatusesForCart(), listStatusesForInternal()))
    ) {

        // Get list of status that can be shown to the user
        $orderUserStatusList = orderStatusList($order);

        $orderStatusCode = $orderUserStatusList[count_like_php5($orderUserStatusList) - 1]["statusCode"];
        $orderStatusDisplay = $orderUserStatusList[count_like_php5($orderUserStatusList) - 1]["status"];
        $orderStatusCategoryCode = orderStatusCategory($order, $orderStatusCode);
    }
    //////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////////////////
    // CART or Completed order
    //////////////////////////////////////////////////////////////////////////////////////
    // Just show the last status we have
    else {

        $orderStatusCode = $order->get('status');
        $orderStatusDisplay = orderStatusToPrint($order);
        $orderStatusCategoryCode = orderStatusCategory($order);
    }
    //////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////
    // Totals Calculation
    //////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////

    //////////////////////////////////////////////////////////////////////////////////////
    // NOT CART
    //////////////////////////////////////////////////////////////////////////////////////
    // Order that is no longer a Cart (i.e. has been placed)
    if (!in_array($order->get('status'), listStatusesForCart())) {

        $fullfillmentTypeDisplay = $order->get('fullfillmentType');
        $etaTimestamp = $order->get('etaTimestamp');
        $fullfillmentFee = $order->get('fullfillmentFee');
        $serviceFee = intval($order->get('serviceFee'));
        $submitTimestamp = $order->get('submitTimestamp');
        $statusDelivery = $order->get('statusDelivery');
        $orderSubmitAirportDateTime = orderFormatDate($airporTimeZone, $order->get('submitTimestamp'));
        $fullfillmentETATimeDisplay = orderFormatDate($airporTimeZone, $order->get('etaTimestamp'));
        $fullfillmentETATimezoneShort = getTimezoneShort($airporTimeZone);
        $orderDate = orderFormatDate($airporTimeZone, $order->get('submitTimestamp'));

        if (!empty($order->get('coupon'))) {

            $couponObjectId = $order->get('coupon')->getObjectId();
        }

        if (!empty($order->get('deliveryLocation'))) {

            $deliveryLocation = $order->get('deliveryLocation')->getObjectId();
        }

        // Get Summary Item List
        list($itemList, $subTotalAlreadyTaxedAtItemLevel, $taxesAtItemLevel, $itemQuantityCount, $orderNotAllowedThruSecurity, $subTotal) = json_decode($order->get('itemList'),
            true);

        // Get Totals
        $retailerTotals = json_decode($order->get('totalsForRetailer'), true);
        $responseArray["totals"] = json_decode($order->get('totalsWithFees'), true);

        if (!isset($responseArray["totals"]['TipsAppliedAs'])) {
            $responseArray["totals"]['TipsAppliedAs'] = null;
        }

        $serviceFeeExplicitlyShown = isset($responseArray["totals"]["serviceFeeExplicitlyShown"]) ? $responseArray["totals"]["serviceFeeExplicitlyShown"] : false;

    }

    //////////////////////////////////////////////////////////////////////////////////////
    // Order has NOT been submitted yet
    //////////////////////////////////////////////////////////////////////////////////////
    else {

        ////////////////////////////////////////////////////////////////////////////////////
        // Available Coupons
        ////////////////////////////////////////////////////////////////////////////////////
        $couponObjectId = "";
        $couponObject = "";
        $skipCouponValidationForPromocode = false;
        $availableUserCoupon = null;
        $serviceFeeExplicitlyShown = isOrderWithExplicitServiceFeeShown($order);

        // If coupon is already applied
        // During Cart Status = User applied coupon
        // During Post Submission Status = User applied or System Applied coupon
        if (!empty($order->get('coupon'))) {

            $couponObjectId = $order->get('coupon')->getObjectId();
            $couponObject = $order->get('coupon');
        } // Search of UserCoupons = System Auto apply coupon
        else {

            // when there is no coupon applied to the order,
            // we try to apply first one from the UserCoupons list,
            // script takes all UserCoupons ans iterate by then,
            // takes first one that is either not applied to any order
            // or it is applied to an canceled order
            $availableUserCoupon = getAvailableAndApplicableUserCoupon($order->get('user'), $order);

            // If an available coupon is found
            if ($availableUserCoupon !== null) {

                // Get coupon object
                $couponObjectId = $availableUserCoupon->get('coupon')->getObjectId();
                $couponObject = $availableUserCoupon->get('coupon');

                // Since we already validated this available coupon, we can skip it later in the process
                $skipCouponValidationForPromocode = true;

                // If Order is being submitted
                if ($finalSubmit) {

                    // Save coupon in the Order object
                    $order->set('coupon', $availableUserCoupon->get('coupon'));
                    $order->save();

                    // Also mark UserCoupon object as applied
                    $availableUserCoupon->set('appliedToOrder', $order);
                    $availableUserCoupon->save();
                }
            }
        }
        //////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////
        // If Summary is being generated for order submission
        // /submit/validation or /submit
        if ($forSubmission) {

            // Take the last quoted fee
            $fullfillmentFee = $order->get('fullfillmentFee');

            // Used for identifying restricted items at the time of Order fulfillment
            // Get Day of the Week and Seconds since Midnight for the requested fullfillment time, Airport time
            list($dayOfWeekAtAirport, $secondsSinceMidnight) = getDayOfWeekAndSecsSinceMidnight($order->get('retailer')->get('airportIataCode'),
                $requestedFullFillmentTimestamp);
        } else {

            // Used for identifying restricted items for current time (i.e. since order not being submitted yet)
            // Get Day of the Week and Seconds since Midnight for now, Airport time
            list($dayOfWeekAtAirport, $secondsSinceMidnight) = getDayOfWeekAndSecsSinceMidnight($order->get('retailer')->get('airportIataCode'),
                time());
        }

        // Get Summary Item List
        $orderSummaryItemList = getOrderSummaryItemlist($order, $dayOfWeekAtAirport);
        list($itemList, $subTotalAlreadyTaxedAtItemLevel, $taxesAtItemLevel, $itemQuantityCount, $orderNotAllowedThruSecurity, $subTotal, $inActiveItems) = $orderSummaryItemList;

        $cartUpdateMessage = "";
        if (count_like_php5($inActiveItems) > 0) {

            $cartUpdateMessage = 'A few items in cart are no longer available and have been removed.';
        }

        // Save the item list if the order is being submitted
        if ($finalSubmit) {

            // Save itemList in the Order object
            $order->set('itemList', json_encode($orderSummaryItemList));
            $order->save();
        }

        ////////////////////////////////////////////////////////////////////////////////////////////
        // Calculate totals
        ////////////////////////////////////////////////////////////////////////////////////////////
        $totals = array();

        // Fetch Retailer Tax Rate
        $orderPOSConfig = parseExecuteQuery(array("retailer" => $order->get('retailer')), "RetailerPOSConfig", "", "",
            ["dualPartnerConfig", "retailer"], 1);
        $retailerTaxRate = trim(floatval($orderPOSConfig->get('taxRate')));

        // Sequence of calculations
        // 1) Subtract - Apply Airport Employee & Military Discount
        //      => Taxable total
        // 2) Calculate Taxes at item level (if rules ask for it)
        //      TODO: Deduct any Airport Employee or Military discount at item level before taxing
        //      TODAY we don't have item level discounts
        // 3) Add - Total Taxes [overall taxes (on items not taxed yet) + item level taxes]
        // 4) Add - Service Charge
        // 5) Add - Tip (calculated out of items without taxes + service charges)
        // 6) Subtract - Airport Sherpa Credits
        // 7) Subtract - Coupon Savings
        //      => caveat (not yet applicable); if retailer compensates for coupon then refund the taxes on the coupon amount
        // 8) Grand Total

        // Begin with subTotal
        // Third party retailers / POS integrated can have their own tax calculations
        if (doesRetailerHaveCustomTaxCalculation($orderPOSConfig)) {

            $taxesAtItemLevel = $taxes = 0;

            // JMD
            // JMD
            if (strcasecmp($orderPOSConfig->get('dualPartnerConfig')->get('partner'), 'hmshost') == 0) {

                try {

                    // Check cache
                    list($subTotal, $taxes) = getCacheHMSHostTaxForOrder($order->getObjectId());

                    if (empty($taxes)) {

                        $tempItems = [];
                        foreach ($itemList as $item) {

                            $tempItems["items"][] = $item;
                        }

                        // If there are items in the cart
                        if (count_like_php5($tempItems) > 0) {

                            $hmshost = new HMSHost($orderPOSConfig->get('dualPartnerConfig')->get('airportId'),
                                $orderPOSConfig->get('dualPartnerConfig')->get('retailerId'),
                                $order->get('retailer')->get('uniqueId'), 'order');

                            list($subTotal, $taxes) = $hmshost->get_taxes_and_subtotal($order->get('orderSequenceId'),
                                $tempItems);
                            $hmshost->session_end();

                            if (empty($subTotal) || $subTotal < 0) {

                                throw new Exception($order->getObjectId() . " subTotal returned as 0 or negative (" . $subTotal . ")");
                            }
                            if (empty($taxes) || $taxes < 0) {

                                throw new Exception($order->getObjectId() . " taxes returned as 0 or negative (" . $taxes . ")");
                            }
                        } else {

                            $subTotal = $taxes = 0;
                        }

                        // set for cache
                        setCacheHMSHostTaxForOrder($order->getObjectId(), [$subTotal, $taxes]);
                    }
                } catch (Exception $ex) {

                    json_error("AS_896", "", "Subtotal and Tax calculation for HMSHost failed - " . $ex->getMessage(),
                        1);
                }
            } else {

                json_error("AS_897", "", "Tax calculation for failed - Invalid partner", 1);
            }
        }

        // in case it is partners retailer, taxes need to be checked and overwritten
        $retailerPartnerServiceFactory = new  \App\Consumer\Services\PartnerIntegrationServiceFactory(
            new \App\Consumer\Repositories\RetailerPartnerCacheRepository(
                new \App\Consumer\Repositories\RetailerPartnerParseRepository(),
                \App\Consumer\Services\CacheServiceFactory::create()
            )
        );

        $isRetailerAPartnerRetailer= false;
        $retailerPartnerService = $retailerPartnerServiceFactory->createByRetailerUniqueId($order->get('retailer')->get('uniqueId'));
        if ($retailerPartnerService!==null){
            $partnerRetailer = $retailerPartnerService->getPartnerIdByRetailerUniqueId($order->get('retailer')->get('uniqueId'));
            if ($retailerPartnerService !== null) {
                $isRetailerAPartnerRetailer = true;
                $cart = new \App\Consumer\Dto\PartnerIntegration\Cart(
                    new \App\Consumer\Dto\PartnerIntegration\CartUserDetails(
                        (string)$order->get('user')->get('firstName'),
                        (string)$order->get('user')->get('lastName')
                    ),
                    $partnerRetailer->getPartnerId(),
                    CartItemList::createFromGetOrderSummaryItemListResult(
                        $itemList
                    ),
                    new \DateTimeZone($airporTimeZone),
                    $subTotal,
                    strtolower($fullfillmentType) == 'p' ? true : false,
                    strtolower($fullfillmentType) == 'd' ? true : false
                );


                $employeeDiscount = null;
                if ((bool)$order->get('airportEmployeeDiscount')==true){
                    $employeeDiscount = $retailerPartnerService->getEmployeeDiscount($cart);
                }

                // when there is discount, subtotal and taxes are with those discount
                $cartTotals = $retailerPartnerService->getCartTotals($cart,$employeeDiscount);

                //$subTotal = $cartTotals->getSubtotal();

                // we are taking it as subtotal without discount applied
                $subTotal = $cart->getSubTotal();
                $taxes = $cartTotals->getTax();


            }
        }

        $totalsSoFar = $subTotal;

        $totals["serviceFeeExplicitlyShown"] = $serviceFeeExplicitlyShown;
        $totals["subTotal"] = $subTotal;
        $totals["subTotalDisplay"] = dollar_format($subTotal);

        ////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////// STEP 1 //////////////////
        ////////// Airport Employee Savings /////////
        ////////////////////////////////////////////////////////////////////////////////////////////

        // Initialize
        $discountAirportEmployee = 0;

        // Is user is an airport employee
        $employeeDiscountPCT = 0;

        if (isOrderEligibleForEmployeeDiscount($order)
            && !doesRetailerHaveCustomTaxCalculation($orderPOSConfig)
        ) {
            if ($isRetailerAPartnerRetailer && isset($cart)) {
                // for partners get discount by calling endpoint

                $employeeDiscount = $retailerPartnerService->getEmployeeDiscount($cart);
                if (!$employeeDiscount->isIsApplicable() || !$employeeDiscount->isPercentage()){
                    $employeeDiscountPCT = 0;
                }else{
                    $employeeDiscountPCT = $employeeDiscount->getDiscountPercentage()/100;
                }

            }else{
                // Fetch the employee discount at airport
                $employeeDiscountPCT = fetchAirportEmployeeDiscountPCT($order->get('retailer')->get('airportIataCode'),
                    $order->get('retailer')->get('employeeDiscountAllowed'),
                    $order->get('retailer')->get('employeeDiscountPCT'));
            }



            // Calculate Airport Employee Discount
            $discountAirportEmployee = $employeeDiscountPCT * $subTotal;

            // Apply Airport Employee Discount
            $totalsSoFar = $totalsSoFar - $discountAirportEmployee;
        }

        $totals['AirEmployeeDiscountApplied'] = (bool)$order->get('airportEmployeeDiscount');
        $totals["AirEmployeeDiscount"] = $discountAirportEmployee;
        $totals["AirEmployeeDiscountPercentageDisplay"] = strval($employeeDiscountPCT * 100) . '%';
        $totals["AirEmployeeDiscountDisplay"] = dollar_format($discountAirportEmployee);


        $totals["AirEmployeeEligable"] = isAirportEmployeeDiscountEnabled($order->get('retailer')->get('uniqueId'));
        ////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////// STEP 1A //////////////////
        ////////////// Military Savings /////////////
        ////////////////////////////////////////////////////////////////////////////////////////////

        // Initialize
        $discountMilitary = 0;

        // Is user is an Military
        $militaryDiscountPCT = 0;
        if (isOrderEligibleForMilitaryDiscount($order)
            && !doesRetailerHaveCustomTaxCalculation($orderPOSConfig)
        ) {

            // Fetch the military discount at airport
            $militaryDiscountPCT = fetchMilitaryDiscountPCT($order->get('retailer')->get('airportIataCode'),
                $order->get('retailer')->get('militaryDiscountAllowed'),
                $order->get('retailer')->get('militaryDiscountPCT'));

            // Calculate Military Discount
            $discountMilitary = $militaryDiscountPCT * $subTotal;

            // Apply Military Discount
            $totalsSoFar = $totalsSoFar - $discountMilitary;
        }

        $totals["MilitaryDiscount"] = $discountMilitary;
        $totals["MilitaryDiscountPercentageDisplay"] = strval($militaryDiscountPCT * 100) . '%';
        $totals["MilitaryDiscountDisplay"] = dollar_format($discountMilitary);

        $totals["MilitaryDiscountEligable"] = isMilitaryDiscountEnabled($order->get('retailer')->get('uniqueId'));


        ////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////// STEP 2 //////////////////
        /////////////// Pretax Total ////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////
        $totals["PreTaxTotal"] = dollar_format_float($totalsSoFar);
        $totals["PreTaxTotalDisplay"] = dollar_format($totalsSoFar);
        ////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////// STEP 3A //////////////////
        /////////////////// Taxes ///////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////
        if (!doesRetailerHaveCustomTaxCalculation($orderPOSConfig) && !$isRetailerAPartnerRetailer) {

            // Tax the total that has not already been taxed at item level
            $taxes = dollar_format_float($retailerTaxRate * ($totalsSoFar - $subTotalAlreadyTaxedAtItemLevel));
        }

        // Add item level taxes with balance subtotal taxes
        $totals["Taxes"] = $taxesAtItemLevel + $taxes;
        $totals["TaxesDisplay"] = dollar_format($totals["Taxes"]);

        ////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////// STEP 4A //////////////////////////////////////
        /////////////// Total with Taxes ///////////////
        ////////////////////////////////////////////////////////////////////////////////////////////
        $totalsSoFar = $totals["PreTaxTotal"] + $totals["Taxes"];
        $totals["PreTipTotal"] = $totalsSoFar;
        $totals["PreTipTotalDisplay"] = dollar_format($totalsSoFar);

        ////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////////////
        // Retailers Total are same as Totals till this point
        $totalsSoFarForRetailer = $totalsSoFar;
        $retailerTotals = $totals;
        $isRetailerCompensated = false;
        ////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////// STEP 4B //////////////////
        /////////////// Service Fees ////////////////
        ////////////////////////////////////////////////////////////////////////////////////////////
        // Service calculation on PreTaxTotal

        $serviceFeeByAirport = getServiceFeePCTByIata($order->get('retailer')->get('airportIataCode'));
        $serviceFee = dollar_format_float($totals["PreTaxTotal"] * $serviceFeeByAirport);

        // Add item level taxes with balance subtotal taxes
        $totals["ServiceFee"] = $serviceFee;
        $totals["ServiceFeeDisplay"] = dollar_format($serviceFee);
        ////////////////////////////////////////////////////////////////////////////////////////////

        $totals["ServiceFeeAndTaxes"] = $totals["ServiceFee"] + $totals["Taxes"];
        $totals["ServiceFeeAndTaxesDisplay"] = dollar_format($totals["ServiceFeeAndTaxes"]);
        ////////////////////////////////////////////////////////////////////////////////////////////

        // If this user will be shown service fee explicitly
        // Then we add it to the total, else it would have been added to the fulfillment fee
        if ($serviceFeeExplicitlyShown) {

            $totalsSoFar = $totalsSoFar + $totals["ServiceFee"];
        }
        ////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////////////
        ////////////////// STEP 4C //////////////////
        ///////////////// Tips Total ///////////////
        // TODO: Move this after the Credits
        ////////////////////////////////////////////////////////////////////////////////////////////
        // Calculate Tip


        $deliveryFeeForTips = 0;
        if (strcasecmp($fullfillmentType, 'd') == 0) {
            if ($order->get('airportEmployeeDiscount') === true) {
                $deliveryFeeForTips = getDeliveryFeesInCentsByIataForEmployee($retailerObjectResults->get('airportIataCode'));
            } else {
                $deliveryFeeForTips = getDeliveryFeesInCentsByIata($retailerObjectResults->get('airportIataCode'));
            }
        }


        $tipPct = 0;
        $tip = 0;

        // calculation for tipping should be items without taxes + fee
        $totalForTipsCalculation = $totalsSoFar - $totals["Taxes"] + $deliveryFeeForTips;


        if ($fullfillmentType == 'd') {
            if ($order->get('tipAppliedAs') == \App\Consumer\Entities\Order::TIP_APPLIED_AS_PERCENTAGE) {
                $tipPct = $order->get('tipPct');
                $tip = ($totalForTipsCalculation * ($tipPct / 100));
            }
            if ($order->get('tipAppliedAs') == \App\Consumer\Entities\Order::TIP_APPLIED_AS_FIXED_VALUE) {
                $tip = $order->get('tipCents');
                $tipPct = round($tip / $totalForTipsCalculation * 100, 2);
            }
        }


        // JMD
        $totals["TipsPCT"] = $tipPct . "%";
        $totals["Tips"] = dollar_format_float($tip);
        $totals["TipsDisplay"] = dollar_format($tip);
        $totals["TipsAppliedAs"] = $order->get('tipAppliedAs');

        // Add the Tip
        $totalsSoFar = $totalsSoFar + $tip;
        $totals["PreCouponTotal"] = dollar_format_float($totalsSoFar);
        $totals["PreCouponTotalDisplay"] = dollar_format($totalsSoFar);
        $totalsSoFar = $totals["PreCouponTotal"];
        ////////////////////////////////////////////////////////////////////////////////////////////

        // Retailer is not charged tips
        $retailerTotals["PreCouponTotal"] = $retailerTotals["PreTipTotal"];
        $retailerTotals["PreCouponTotalDisplay"] = dollar_format($retailerTotals["PreCouponTotal"]);

        ////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////// STEP 5 //////////////////
        /////////////// Credits Total ///////////////
        ////////////////////////////////////////////////////////////////////////////////////////////
        $totals["PreCreditTotal"] = $totalsSoFar;

        $totals["CreditsAppliedInCents"] = 0;
        $totals["CreditsAppliedDisplay"] = dollar_format(0);

        // Check if we have already applied any credits to this Order
        /*
        $appliedCredits = getCreditsAppliedToOrder($order);
        if($appliedCredits !== null) {

            $totals["CreditsAppliedInCents"] = $appliedCredits;
            $totals["CreditsAppliedDisplay"] = dollar_format($appliedCredits);

            // Credits are less than the total due
            if($totalsSoFar > $appliedCredits) {

                $totalsSoFar = $totalsSoFar - $appliedCredits;
                $totals["CreditsAvailableInCents"] = 0;
            }
            // Credits are more than the total due
            else {

                $totalsSoFar = 0;
                $totals["CreditsAvailableInCents"] = $appliedCredits - $totalsSoFar;
            }
        }
        */
        $feeInCents = 0;
        if (strcasecmp($fullfillmentType, 'p') == 0) {

            $feeInCents = getPickupFeesInCentsByIata($retailerObjectResults->get('airportIataCode'));
            //$feeInCents = getDefaultPickupFeesInCents($totalsSoFar);
        } else {
            if (strcasecmp($fullfillmentType, 'd') == 0) {
                if ($order->get('airportEmployeeDiscount') === true) {
                    $feeInCents = getDeliveryFeesInCentsByIataForEmployee($retailerObjectResults->get('airportIataCode'));
                } else {
                    $feeInCents = getDeliveryFeesInCentsByIata($retailerObjectResults->get('airportIataCode'));
                }
                //$feeInCents = getDefaultDeliveryFeesInCents($totalsSoFar);
            }
        }

        $maxCreditNeeded = $totalsSoFar + $feeInCents;

        // If pre-credit applied total is 0 then let's clear any existing credits we had applied
        // This happens when items were in cart and hence credits were applied
        // But then items were removed so let's clear AppliedMap table for this order
        if ($maxCreditNeeded == 0) {

            clearCreditsAppliedMapToOrder($order);
        }

        $creditAppliedMap = getCreditsAppliedMapToOrder($order);
        if (count_like_php5($creditAppliedMap) > 0) {

            $appliedCreditsForOrder = 0;
            // Add applied credits
            foreach ($creditAppliedMap as $map) {

                $appliedCreditsForOrder = $appliedCreditsForOrder + $map["appliedCreditsInCents"];
            }

            list($feesInCentsAfterCredits, $creditsAppliedToFees) = calculateCreditsAppliedToFees($maxCreditNeeded,
                $feeInCents, $appliedCreditsForOrder);

            $totals["CreditsAppliedInCents"] = $appliedCreditsForOrder;
            $totals["CreditsAppliedDisplay"] = dollar_format($appliedCreditsForOrder);

            // This will limit the ability to apply credit to fees
            $totals["CreditsAvailableInCents"] = 0;
            $totalsSoFar = $totalsSoFar - $appliedCreditsForOrder + $creditsAppliedToFees;
        } // Else check the credits available
        else {

            // Check if credits can be applied for this type of fulfillmentType = d, p
            if (areCreditsApplicable($fullfillmentType)) {

                //////////////////////////////////////////////////////////////////
                // credits have not been applied, then calculate it, if not, skipp
                /*
                list($availableCreditsForUser, $referralSignupCreditApplied) = getAvailableUserCredits($order->get('user'), $totals["TotalWithCoupon"]);
                */
                list($creditAppliedMap, $appliedCreditsForOrder, $referralSignupCreditApplied) = getAvailableUserCreditsViaMap($order->get('user'),
                    $maxCreditNeeded, $totals);

                list($feesInCentsAfterCredits, $creditsAppliedToFees) = calculateCreditsAppliedToFees($maxCreditNeeded,
                    $feeInCents, $appliedCreditsForOrder);

                if ($maxCreditNeeded == 0) {

                    $appliedCreditsForOrder = $feesInCentsAfterCredits = $creditsAppliedToFees = 0;
                }

                /*
                // more credits available than remainder total
                if($availableCreditsForUser >= $totalsSoFar) {

                    // That means total remainder value is considered credit
                    $totals["CreditsAppliedInCents"] = $totalsSoFar;
                    $totals["CreditsAppliedDisplay"] = dollar_format($totalsSoFar);
                    $totals["CreditsAvailableInCents"] = $availableCreditsForUser - $totalsSoFar;
                    $totalsSoFar = 0;
                }
                else {

                    $totals["CreditsAppliedInCents"] = $availableCreditsForUser;
                    $totals["CreditsAppliedDisplay"] = dollar_format($availableCreditsForUser);
                    $totals["CreditsAvailableInCents"] = 0;
                    $totalsSoFar = $totalsSoFar - $availableCreditsForUser;
                }
                */

                $totals["CreditsAppliedInCents"] = $appliedCreditsForOrder;
                $totals["CreditsAppliedDisplay"] = dollar_format($appliedCreditsForOrder);

                // This will limit the ability to apply credit to fees
                $totals["CreditsAvailableInCents"] = 0;
                $totalsSoFar = $totalsSoFar - $appliedCreditsForOrder + $creditsAppliedToFees;
                //////////////////////////////////////////////////////////////////

                // If Final Submission, so let's add to Credits that we used fullfillment credits
                if ($finalSubmit) {

                    // $quotedFullfillmentCredits = 0;
                    // if(strcasecmp($order->get('fullfillmentType'), 'd')==0) {

                    //     if($order->has('quotedFullfillmentDeliveryFeeCredits')) {

                    //         $quotedFullfillmentCredits = intval($order->get('quotedFullfillmentDeliveryFeeCredits'));
                    //     }
                    // }
                    // else {

                    //     if($order->has('quotedFullfillmentPickupFeeCredits')) {

                    //         $quotedFullfillmentCredits = intval($order->get('quotedFullfillmentPickupFeeCredits'));
                    //     }
                    // }

                    // $totals["CreditsAppliedInCents"] = $totals["CreditsAppliedInCents"] + $quotedFullfillmentCredits;
                    // $totals["CreditsAppliedDisplay"] = dollar_format($totals["CreditsAppliedInCents"]);
                    // $totals["CreditsAvailableInCents"] = $totals["CreditsAvailableInCents"] - $quotedFullfillmentCredits;
                }
            }
        }

        $totals["TotalWithCouponAndCredit"] = $totals["PreFeeTotal"] = dollar_format_float($totalsSoFar);
        $totals["TotalWithCouponAndCreditDisplay"] = $totals["PreFeeTotalDisplay"] = dollar_format($totalsSoFar);
        ////////////////////////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////// STEP 6 //////////////////
        /////////////// Coupons Total ///////////////
        ////////////////////////////////////////////////////////////////////////////////////////////
        // Seed
        $totals["Coupon"] = 0;
        $totals["CouponDisplay"] = dollar_format($totals["Coupon"]);
        $totals["CouponForFeeFixed"] = 0;
        $totals["CouponForFeePCT"] = 0;
        $totals["CouponCodeApplied"] = "";
        $totals["CouponForFee"] = $couponForFee = false;
        $totals["CouponOrderMinMet"] = false;
        $totals["CouponAppliedByDefault"] = false;
        $totals["CouponIsFullfillmentRestrictionApplied"] = false;

        // Check if any applied coupon exists
        // This is the coupon we identified either applied by User or System
        if (!empty($couponObjectId)) {

            list($couponSavings, $couponSavingsDisplay, $couponForFeeFixed, $couponForFeePCT, $couponCode, $couponForFee, $couponAppliedByDefault, $couponOrderMinMet, $isFullfillmentRestrictionApplied) = getOrderSummaryApplyCoupon($order,
                $couponObject, $availableUserCoupon, $skipCouponValidationForPromocode, $totalsSoFar, $fullfillmentType,
                $creditAppliedMap);

            $totals["Coupon"] = $couponSavings;
            $totals["CouponDisplay"] = $couponSavingsDisplay;
            $totals["CouponForFeeFixed"] = $couponForFeeFixed;
            $totals["CouponForFeePCT"] = $couponForFeePCT;
            $totals["CouponCodeApplied"] = ($isFullfillmentRestrictionApplied == true ? "" : $couponCode);
            $totals["CouponForFee"] = $couponForFee;
            $totals["CouponOrderMinMet"] = $couponOrderMinMet;
            $totals["CouponIsFullfillmentRestrictionApplied"] = $isFullfillmentRestrictionApplied;
            $totals["couponCodeTitle"] = $couponCode;

            // Let App know that the Coupon can't be removed because it was added by default
            $totals["CouponAppliedByDefault"] = $couponAppliedByDefault;

            // Deduct coupon or discount value
            $totalsSoFar = $totalsSoFar - $couponSavings;

            // After applying coupon if the value is less 0 then set to 0
            if ($totalsSoFar < 0) {

                $totalsSoFar = 0;
            }
        }

        $totals["TotalWithCoupon"] = $totalsSoFar;

        ////////////////////////////////////////////////////////////////////////////////////////////
        // Totals for the Retailer; Use coupon savings if it is retailer processed
        // => caveat (not yet applicable); if retailer compensates for coupon then refund the taxes on the coupon amount
        if ($isRetailerCompensated) {

            $retailerTotals["Coupon"] = $totals["Coupon"];
            $retailerTotals["CouponDisplay"] = $totals["CouponDisplay"];
            $retailerTotals["CouponCodeApplied"] = $totals["CouponCodeApplied"];

            // Check if total is greater than coupon value
            if ($retailerTotals["PreCouponTotal"] > $retailerTotals["Coupon"]) {

                $retailerTotals["Total"] = $retailerTotals["PreCouponTotal"] - $retailerTotals["Coupon"];
            } else {

                $retailerTotals["Total"] = 0;
            }

            $retailerTotals["TotalDisplay"] = dollar_format($retailerTotals["Total"]);
        } // Else take totals before coupon, i.e. the amount we need to pay the retailer
        else {

            $retailerTotals["Total"] = $retailerTotals["PreCouponTotal"]; // also Pre Credit total
            $retailerTotals["TotalDisplay"] = $retailerTotals["PreCouponTotalDisplay"];
        }
        ////////////////////////////////////////////////////////////////////////////////////////////


        ////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////// STEP 7 //////////////////
        ////////////// Add delivery Fees //////////////
        ////////////////////////////////////////////////////////////////////////////////////////////

        // Orders that have been placed
        $totals["AirportSherpaFee"] = $fullfillmentFee;
        $totals["AirportSherpaFeeDisplay"] = dollar_format($fullfillmentFee);

        // Add fulfillment fees (only when submitting order)
        $totalsSoFar = $totalsSoFar + $fullfillmentFee;

        $totals["Total"] = dollar_format_float($totalsSoFar);
        $totals["TotalDisplay"] = dollar_format($totalsSoFar);
        ////////////////////////////////////////////////////////////////////////////////////////////

        $responseArray["totals"] = $totals;
        ////////////////////////////////////////////////////////////////////////////////////////////
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////
    // Prepare response
    $responseArray["internal"] = array(
        "retailerUniqueId" => $retailerObjectResults->get('uniqueId'),
        "retailerName" => $retailerObjectResults->get('retailerName'),
        "retailerAirportIataCode" => $retailerObjectResults->get('airportIataCode'),
        "retailerImageLogo" => preparePublicS3URL($retailerObjectResults->get('imageLogo'),
            getS3KeyPath_ImagesRetailerLogo($retailerObjectResults->get('airportIataCode')),
            $GLOBALS['env_S3Endpoint']),
        "retailerLocation" => $retailerObjectResults->get('location')->getObjectId(),
        "orderId" => $orderId,
        "orderIdDisplay" => $order->get('orderSequenceId'),

        // Real status code
        "orderInternalStatusCode" => $order->get('status'),
        "orderInternalStatusDisplay" => orderStatusToPrint($order),

        // User ready codes (inform_user = true)
        "orderStatusCode" => $orderStatusCode,
        "orderStatusDisplay" => $orderStatusDisplay,
        "orderStatusCategoryCode" => $orderStatusCategoryCode,

        "orderStatusDeliveryCode" => $statusDelivery,
        "orderDate" => $orderDate,
        "fullfillmentETATimestamp" => $etaTimestamp,
        "fullfillmentETATimeDisplay" => $fullfillmentETATimeDisplay,
        "fullfillmentETATimezoneShort" => $fullfillmentETATimezoneShort,
        "fullfillmentType" => $fullfillmentTypeDisplay,
        "fullfillmentFee" => $fullfillmentFee,
        "orderSubmitAirportTimeDisplay" => $orderSubmitAirportDateTime,
        "orderSubmitTimestampUTC" => $submitTimestamp,
        "deliveryLocation" => $deliveryLocation,
        "deliveryName" => getCurrentDeliveryNameByOrder($order),
        "paymentType" => "",
        "paymentTypeName" => "",
        "paymentTypeId" => "",
        "paymentTypeIconURL" => "",
        "cartUpdateMessage" => $cartUpdateMessage,

        "orderNotAllowedThruSecurity" => $orderNotAllowedThruSecurity,
        "itemQuantityCount" => $itemQuantityCount,

        "serviceFeeExplicitlyShown" => $serviceFeeExplicitlyShown,
        "referralSignupCreditApplied" => $referralSignupCreditApplied,
    );

    if (empty($fullfillmentType)) {

        $responseArray["internal"]["creditAppliedMap"]["default"]["map"] = $creditAppliedMap;
    } else {

        $responseArray["internal"]["creditAppliedMap"][$fullfillmentType]["map"] = $creditAppliedMap;
        $responseArray["internal"]["creditAppliedMap"][$fullfillmentType]["feeCreditSummary"] = [
            "feesInCentsAfterCredits" => $feesInCentsAfterCredits,
            "creditsAppliedToFees" => $creditsAppliedToFees
        ];
    }

    // $responseArray["items"] = $itemList;

    // Change orderId keys to sequential keys
    foreach ($itemList as $item) {

        $responseArray["items"][] = $item;
    }

    if (!isset($responseArray["items"])) {

        $responseArray["items"] = [];
    }

    // PAYMENT INFO
    $responseArray["payment"]["paymentType"] = "";
    $responseArray["payment"]["paymentTypeName"] = "";
    $responseArray["payment"]["paymentTypeId"] = "";
    $responseArray["payment"]["paymentTypeIconURL"] = "";

    if ($getPaymentDetailsFlag == 1
        && !empty($order->get('paymentTypeId'))
    ) {

        $responseArray["payment"]["paymentType"] = $order->get('paymentType');
        $responseArray["payment"]["paymentTypeName"] = $order->get('paymentTypeName');
        $responseArray["payment"]["paymentTypeId"] = decryptPaymentInfo($order->get('paymentTypeId'));
        $responseArray["payment"]["paymentTypeIconURL"] = isset($GLOBALS['paymentMethodsIconURLs'][$responseArray["payment"]["paymentTypeName"]]) ? $GLOBALS['paymentMethodsIconURLs'][$responseArray["payment"]["paymentTypeName"]] : $GLOBALS['paymentMethodsIconURLs']["Unknown"];
    }
    ///////////////////////////////////////////////////////////////////////////////////////////////

    return array($responseArray, $retailerTotals);
}

// JMD
function calculateCreditsAppliedToFees($maxCreditNeeded, $feeInCents, $appliedCreditsForOrder)
{

    $totalNeededForBaseOrder = $maxCreditNeeded - $feeInCents;

    // Any credits were applied to fees?
    if ($appliedCreditsForOrder > $totalNeededForBaseOrder) {

        $creditsAppliedToFees = $appliedCreditsForOrder - $totalNeededForBaseOrder;

        // Credits applied didn't cover full fees
        if ($creditsAppliedToFees < $feeInCents) {

            $feesInCentsAfterCredits = $feeInCents - $creditsAppliedToFees;
        } // Covered all fees
        else {

            $feesInCentsAfterCredits = 0;
        }
    } else {

        $creditsAppliedToFees = 0;
        $feesInCentsAfterCredits = $feeInCents;
    }

    return [$feesInCentsAfterCredits, $creditsAppliedToFees];
}

function createPDFFromHTML($orderHTML)
{

    $dompdf = new Dompdf();

    $fontmetrics = new FontMetrics($dompdf->get_canvas(), $dompdf->getOptions());
    $font = $fontmetrics->get_font("Helvetica", "bold");

    try {

        $dompdf->loadHtml($orderHTML);

        $dompdf->render();

        // Set Header for Page; Must happen after render function
        $canvas = $dompdf->get_canvas();
        $canvas->page_text(72, 18, "Page: {PAGE_NUM} of {PAGE_COUNT}", $font, 12, array(0, 0, 0));
        $orderPDF = $dompdf->output();
    } catch (Exception $ex) {

        json_error("AS_845", "",
            "PDF file could not be generated. HTML: " . base64_encode($orderHTML) . " :: Error: " . $ex->getMessage());
    }

    // ob_start();
    // $dompdf->stream('invoice.pdf', array('Attachment'=>'0'));
    // $orderPDF = ob_get_contents();
    // ob_end_clean();

    return $orderPDF;
}

function decrementSubmissionAttempt($orderObject)
{

    $orderObject->increment('submissionAttempt', -1);
    $orderObject->save();
}

function doesItemHaveModifiers($uniqueRetailerItemId)
{

    $objParseQueryItemModifiersResults = parseExecuteQuery(array(
        "uniqueRetailerItemId" => $uniqueRetailerItemId,
        "isActive" => true
    ), "RetailerItemModifiers");

    if (count_like_php5($objParseQueryItemModifiersResults) == 0) {

        return false;
    } else {

        return true;
    }
}

function baseOrderItemChecks($userid, $orderObjectId, $uniqueRetailerItemId = "")
{

    // Check if Orders exists
    $user = isCorrectObject(array("objectId" => $orderObjectId, "status" => listStatusesForCart()), "Order", "Order",
        "user");

    // Check if the Order belongs to the User
    if ($user->getObjectId() != $userid) {

        json_error("AS_834", "",
            "Potential Hacking attempt: Order Id = " . $orderObjectId . " but user doesn't match parseuserid = " . $userid,
            1);
    }

    // Check if Item Object exists and is Active
    if (!empty($uniqueRetailerItemId)) {

        // Check if this Order belongs to this Retailer
        $objItems = parseExecuteQuery(array("uniqueId" => $uniqueRetailerItemId, "isActive" => true), "RetailerItems");

        if (count_like_php5($objItems) == 0) {

            json_error("AS_868", "", "Item not found = " . $uniqueRetailerItemId, 1);
        }

        $objRetailer = parseExecuteQuery(array("uniqueId" => $objItems[0]->get('uniqueRetailerId'), "isActive" => true),
            "Retailers");
        $objOrderMatch = parseExecuteQuery(array("objectId" => $orderObjectId, "retailer" => $objRetailer), "Order", "",
            "", array("retailer", "retailer.location", "deliveryLocation", "coupon"));

        if (count_like_php5($objOrderMatch) == 0 || count_like_php5($objItems) == 0) {

            json_error("AS_835", "",
                "Potential Hacking attempt: Order Id = " . $orderObjectId . " but user doesn't match the retailer = " . $uniqueRetailerItemId,
                1);
        }

        return array($objOrderMatch[0], $objItems[0]);
    }
}

/*
function baseOrderItemModifierOptionsChecks($uniqueRetailerItemId, $uniqueRetailerItemModifierOptionId) {

    // Check if Item Modifier Option Object exists and is Active
    $uniqueRetailerItemModifierId = isCorrectObject(array("uniqueId" => $uniqueRetailerItemModifierOptionId, "isActive" => true), "RetailerItemModifierOptions", "Menu Option", "uniqueRetailerItemModifierId");

    // Check if Modifier Option belongs to the Items and is Active
    $uniqueRetailerItemIdSearched = isCorrectObject(array("uniqueId" => $uniqueRetailerItemModifierId, "isActive" => true), "RetailerItemModifiers", "", "uniqueRetailerItemId", 0);

    if($uniqueRetailerItemId != $uniqueRetailerItemIdSearched) {

        json_error("AS_836", "", "Option selection is invalid or not active! Potential Hacking attempt: Item Option = " . $uniqueRetailerItemModifierOptionId . " doesn't belong to Item Id = " . $uniqueRetailerItemId, 1);
    }

    return $uniqueRetailerItemModifierId;
}

function checkPOSOrderStatus($orderPOSId, $omnivoreLocationId) {

    global $env_OmnivoreAPIURLPrefix;

    $omnivoreTicketObject = getJSONDataFromOmnivore($env_OmnivoreAPIURLPrefix . $omnivoreLocationId . "/tickets/" . $orderPOSId, );

    try {

        $openStatus = $omnivoreTicketObject->getProperty('open');
    }
    catch (Exception $ex) {

        json_error("AS_842", "", "Order POS status check failed. POS Order Id = " . $orderPOSId, 2);
    }

    if($openStatus == false) {

        return 0;
    }
    else {

        return 1;
    }
}
*/

function createOrder($user, $retailer)
{

    $createSequence = new ParseQuery("Sequences");
    $createSequence->equalTo('keyName', 'order');
    $orderSequenceObject = $createSequence->first();

    $randomNumber = mt_rand(1, 15);

    for ($i = 0; $i <= $randomNumber; $i++) {

        // Increment and save
        $orderSequenceObject->increment('sequenceNumber');
    }

    $orderSequenceObject->save();

    $orderSequenceId = $orderSequenceObject->get('sequenceNumber');

    $createOrder = new ParseObject("Order");
    $createOrder->set("status", 1);
    $createOrder->set("user", $user);
    $createOrder->set("retailer", $retailer);
    $createOrder->set("interimOrderStatus", -1);
    $createOrder->set("orderSequenceId", $orderSequenceId);

    try {

        $createOrder->save();
    } catch (ParseException $ex) {

        json_error("AS_801", "", "New Order Creation Failed! " . $ex->getMessage(), 1);
    }

    return $createOrder;
}

function addOrderStatus($order, $comment = " ")
{

    $addOrderStatusObject = new ParseObject("OrderStatus");
    $addOrderStatusObject->set("status", $order->get('status'));
    $addOrderStatusObject->set("statusDelivery", $order->get('statusDelivery'));
    $addOrderStatusObject->set("order", $order);
    $addOrderStatusObject->set("comment", $comment);

    try {

        $addOrderStatusObject->save();
    } catch (ParseException $ex) {

        // No exit error
        json_error("AS_837", "", "Order Status update failed!" . $ex->getMessage(), 1, 1);
    }
}

function isCorrectObject($objectValueArray, $className, $classNameDisplay, $returnObjectName = "", $displayError = 1)
{

    // Find Row by its objectId
    $objParseQueryResults = parseExecuteQuery($objectValueArray, $className);

    // If row is not found
    if (count_like_php5($objParseQueryResults) == 0 && $displayError == 1) {

        json_error("AS_839", "",
            $classNameDisplay . " not found! " . "Query object: " . base64_encode(serialize($objectValueArray)));
    }

    if (!empty($returnObjectName)) {

        if (count_like_php5($objParseQueryResults) > 0) {

            if ($returnObjectName == "objectId") {

                return $objParseQueryResults[0]->getObjectId();
            } else {

                return $objParseQueryResults[0]->get($returnObjectName);
            }
        } else {

            return "";
        }
    }
}

/*
function getOpenTableId($omnivoreLocationId, $allowedTableIds) {

    global $env_OmnivoreAPIURLPrefix;

    $omnivoreTableObject = getJSONDataFromOmnivore($env_OmnivoreAPIURLPrefix . $omnivoreLocationId . "/tables");

    try {

        $tableList = $omnivoreTableObject->getEmbed('tables');
    }
    catch (Exception $ex) {

        json_error("AS_840", "", "Order could not be processed! " . "Get Table list failed for Location Id: " . $omnivoreLocationId, 2);
    }

    foreach($tableList as $tables) {

        $isAvailable = $tables->getProperty('available');

        $id = $tables->getProperty('id');

        if($isAvailable && in_array($id, $allowedTableIds)) {

            return intval($id);
        }
    }

    json_error("AS_841", "", "Order could not be processed! " . "No available table found for Location Id: " . $omnivoreLocationId, 2);
}

*/

// Has the retailer been closed early already
function isRetailerClosedEarly($uniqueId)
{

    return empty(getRetailerOpenAfterClosedEarly($uniqueId)) ? false : true;
}

// Has the retailer closing been requested already
function isRetailerCloseEarlyForNewOrders($uniqueId)
{

    return empty(getRetailerCloseEarlyForNewOrders($uniqueId)) ? false : true;
}

function isRetailerClosed($retailer, $fullfillmentTimeInSeconds, $requestedFullFillmentTimestamp)
{

    // Find POS Config settings
    //$objectParseQuery = parseExecuteQuery(array("uniqueId" => $uniqueRetailerId, "isActive" => true), "Retailers");

    if (empty($retailer)) {

        $isClosed = 1;
        $errorMsg = "The retailer is currently not open for business.";

        return array($isClosed, $errorMsg);
    }

    $isClosed = 0;
    $errorMsg = "";

    if (empty($requestedFullFillmentTimestamp)) {

        $currentTimestamp = time();
    }
    // Assume the time when the order needs to be fullfilled by
    // Reduce the time it will take to fullfill (aka to calculate the time the order will be placed)
    else {

        // Had comment this out as it was being used for immediate orders too as I am sending requestedFullFillmentTimestamp for those too
        // We probably need to send a flag for Scheduled orders or consitently not send requestedFullFillmentTimestamp for immediate orders
        // $currentTimestamp = $requestedFullFillmentTimestamp - $fullfillmentTimeInSeconds;

        // Temporary fix
        $currentTimestamp = time();
    }

    $currentTimeZone = date_default_timezone_get();
    $airporTimeZone = fetchAirportTimeZone($retailer->get('airportIataCode'), $currentTimeZone);

    if (strcasecmp($airporTimeZone, $currentTimeZone) != 0) {

        date_default_timezone_set($airporTimeZone);
    }
    $dayOfTheWeekAtAirport = date("l", $currentTimestamp);
    $openingTimestamp = strtotime($retailer->get('openTimes' . $dayOfTheWeekAtAirport));
    $closingTimestamp = strtotime($retailer->get('closeTimes' . $dayOfTheWeekAtAirport));

    if ($openingTimestamp >= $closingTimestamp) {
        $closingTimestamp = $closingTimestamp + 24 * 60 * 60;
    }


    if (strcasecmp($airporTimeZone, $currentTimeZone) != 0) {

        date_default_timezone_set($currentTimeZone);
    }

    // Either the retailer is not open
    // Or if the retailer is open, but current time + time to prepare is after the closing time
    $hasRetailerClosedEarly = isRetailerClosedEarly($retailer->get('uniqueId'));
    $hasRetailerClosingBeenRequested = isRetailerCloseEarlyForNewOrders($retailer->get('uniqueId'));

//var_dump([($currentTimestamp + $fullfillmentTimeInSeconds) > $closingTimestamp,$currentTimestamp ,$openingTimestamp]);
    if ($currentTimestamp < $openingTimestamp
        || ($currentTimestamp + $fullfillmentTimeInSeconds) > $closingTimestamp
        || $hasRetailerClosedEarly
        || $hasRetailerClosingBeenRequested
    ) {

        $isClosed = 1;
        $errorMsg = "The retailer is currently not open for business.";


        if ($currentTimestamp < $closingTimestamp
            && $currentTimestamp > $openingTimestamp
            && !$hasRetailerClosedEarly
            && !$hasRetailerClosingBeenRequested
        ) {

            $isClosed = 2;
            $errorMsg = "The retailer will be closing soon and won't be able to process your order in time.";
        }

        // If this logic is being called, let's make sure it was because retailer is open right now but will close before prep time
    }

    return array($isClosed, $errorMsg);
}

// Delivery can arrive prior but must arrive and have order picked up by this timestamp
function timestampDeliveryPickupByForPickup($order)
{

    return ($order->get('etaTimestamp') - $order->get('fullfillmentProcessTimeInSeconds'));
}

function pingRetailer(
    $retailer,
    $order = "",
    $fullfillmentTimeInSeconds = "",
    $requestedFullFillmentTimestamp = 0,
    $retailerPOSConfig = ""
) {

    if (empty($fullfillmentTimeInSeconds)) {

        // If pickup order
        if (empty($order)
            || strcasecmp($order->get('fullfillmentType'), "p") == 0
        ) {

            list($fullfillmentTimeInSeconds, $fullfillmentProcessTimeInSeconds) = getPickupTimeInSeconds($retailer);
        } // else delivery
        else {

            $deliveryLocation = $order->get('deliveryLocation');
            list($fullfillmentTimeInSeconds, $fullfillmentProcessTimeInSeconds, $fullfillmentTimeInSecondsOverriden, $requestedFullFillmentTimestampOverriden) = getDeliveryTimeInSeconds($retailer,
                $deliveryLocation, $order);

            if ($fullfillmentTimeInSecondsOverriden > 0) {

                $fullfillmentTimeInSeconds = $fullfillmentTimeInSecondsOverriden;
            }
        }
    }

    list($isClosed, $error) = isRetailerClosed($retailer, $fullfillmentTimeInSeconds, $requestedFullFillmentTimestamp);

    if ($isClosed == 1) {

        $isClosedBoolean = true;
        return array(false, $isClosedBoolean, $error, "This retailer has closed for business for the day.");
    } else {
        if ($isClosed == 2) {

            $isClosedBoolean = false;
            return array(
                false,
                $isClosedBoolean,
                $error,
                "The retailer will be closing soon and won't be able to fulfill your order."
            );
        } else {

            $isClosedBoolean = false;
        }
    }

    /*
    if(empty($lastSuccessfulPingTimestamp)) {

        // Find POS Config settings
        $objectParseQueryPOSConfig = parseExecuteQuery(array("retailer" => $retailer), "RetailerPOSConfig");

        // If Config is NOT found, let caller know
        if(count_like_php5($objectParseQueryPOSConfig) == 0) {

            json_error("AS_505", "", "Retailer not found! POS Config not found for the uniqueRetailerId (" . $retailer->get('uniqueId') . ")", 1);
        }

        $lastSuccessfulPingTimestamp = intval($objectParseQueryPOSConfig[0]->get('lastSuccessfulPingTimestamp'));
    }
    */

    // Future time ping requested
    if ($requestedFullFillmentTimestamp > time()) {

        return array(true, $isClosedBoolean, "", "");
    } else {
        if (isRetailerPingActive($retailer, $retailerPOSConfig)) {

            return array(true, $isClosedBoolean, "", "");
        } else {

            return array(false, $isClosedBoolean, "", "This retailer is currently not accepting orders.");
        }
    }
}

function getRetailerPingTimestampThreshold()
{

    return (time() - (intval($GLOBALS['env_PingSlackGraceMultiplier']) * intval($GLOBALS['env_TabletAppDefaultPingIntervalInSecs'])));
}

function isRetailerDualConfigPingActive($retailer)
{

    $retailerPingTimestampThreshold = getRetailerPingTimestampThreshold();
    $lastSuccessfulPingTimestamp = getRetailerDualConfigPingTimestamp($retailer->get('uniqueId'));

    if ($lastSuccessfulPingTimestamp >= $retailerPingTimestampThreshold) {

        return true;
    } else {

        return false;
    }
}

function isRetailerDualConfig($retailerUniqueId)
{

    // Pull POS record for the retailer
    $obj = new ParseQuery("Retailers");
    $retailer = parseSetupQueryParams(["uniqueId" => $retailerUniqueId], $obj);

    // JMD
    $objectParseQueryPOSConfig = parseExecuteQuery(array("__MATCHESQUERY__retailer" => $retailer), "RetailerPOSConfig",
        "", "", array("retailer", "dualPartnerConfig"), 1);

    if (count_like_php5($objectParseQueryPOSConfig) > 0
        && !empty($objectParseQueryPOSConfig->get('dualPartnerConfig'))
        && $objectParseQueryPOSConfig->get('dualPartnerConfig')->get('tabletIntegrated') == true
    ) {

        return true;
    }

    return false;
}

function doesRetailerHaveCustomTaxCalculation($objectParseQueryPOSConfig)
{

    // JMD
    if (count_like_php5($objectParseQueryPOSConfig) > 0
        && !empty($objectParseQueryPOSConfig->get('dualPartnerConfig'))
        && $objectParseQueryPOSConfig->get('dualPartnerConfig')->get('extTaxCalculation') == true
    ) {

        return true;
    }

    // JMD
    // JMD
    return false;
}

function isExternalPartnerOrder($retailerUniqueId)
{

    // Pull POS record for the retailer
    $obj = new ParseQuery("Retailers");
    $retailer = parseSetupQueryParams(["uniqueId" => $retailerUniqueId], $obj);

    // JMD
    $objectParseQueryPOSConfig = parseExecuteQuery(array("__MATCHESQUERY__retailer" => $retailer), "RetailerPOSConfig",
        "", "", array("retailer", "dualPartnerConfig"), 1);

    if (count_like_php5($objectParseQueryPOSConfig) > 0
        && !empty($objectParseQueryPOSConfig->get('dualPartnerConfig'))
    ) {

        return [
            true,
            $objectParseQueryPOSConfig->get('tenderTypeId'),
            $objectParseQueryPOSConfig->get('dualPartnerConfig')
        ];
    }

    return [false, 0, ''];
}

function isRetailerPingActive($retailer, $retailerPOSConfig = "")
{

    $retailerPingTimestampThreshold = getRetailerPingTimestampThreshold();

    if (empty($retailerPOSConfig)) {

        $retailerPOSConfig = parseExecuteQuery(array("retailer" => $retailer), "RetailerPOSConfig", "", "", [], 1);

        if (count_like_php5($retailerPOSConfig) == 0) {

            return false;
        }
    }
    $lastSuccessfulPingTimestamp = getRetailerPingTimestamp($retailer->get('uniqueId'));

    // $lastSuccessfulPingTimestamp = $retailerPOSConfig->get("lastSuccessfulPingTimestamp");


    // If Tablet App Retailer and not a dual config
    if ((!$retailerPOSConfig->has('locationId') || empty($retailerPOSConfig->get('locationId')))
        && (!$retailerPOSConfig->has('printerId') || empty($retailerPOSConfig->get('printerId')))
        && (!$retailerPOSConfig->has('tabletId') || empty($retailerPOSConfig->get('tabletId')))
        && (!$retailerPOSConfig->has('dualPartnerConfig') || empty($retailerPOSConfig->get('dualPartnerConfig')))
    ) {

        // If lastSucessful Ping was within Multiplier of env_TabletAppDefaultPingIntervalInSecs
        if ($lastSuccessfulPingTimestamp >= $retailerPingTimestampThreshold) {

            return true;
        }
    } // Slack or Dual Tablet (since these are checked on a loop)
    else {
        // If lastSucessful Ping was within Multiplier of env_PingRetailerIntervalInSecs
        if ($lastSuccessfulPingTimestamp >= (time() - (intval($GLOBALS['env_PingSlackGraceMultiplier']) * intval($GLOBALS['env_PingRetailerIntervalInSecs'])))) {

            return true;
        }
    }

    return false;
}

// Pull method: Hence not used for Tablet App retailers (onlt for Printers, POS, and Table Slack retailers)
function pingRetailerWithAPI($locationId, $printerId, $tabletId, $dualConfig = '')
{

    $errorAppend = "";
    $passed = 0;

    // If Printer Retailer
    if (!empty($printerId)) {

        list($response, $statuses) = checkGooglePrinter($printerId);

        if ($response == 1) {

            $passed = 1;
        } else {

            $errorAppend = "Printer is Offline. Statuses: " . json_encode($statuses);
        }
    } // If POS Retailer
    else {
        if (!empty($locationId)) {

            $response = getFromOmnivore($locationId);

            if (isset($response->body->status) && $response->body->status == 'online') {

                $passed = 1;
            } else {

                if (isset($response->body->errors)
                    && count_like_php5($response->body->errors) > 0
                ) {

                    $errorAppend .= json_encode($response->body->errors);
                }
            }
        } // If Tablet Slack Retailer
        else {
            if (!empty($tabletId)) {

                list($response, $statuses) = checkTabletStatus($tabletId);

                if ($response == 1) {

                    $passed = 1;
                } else {

                    $errorAppend = "Tablet Slack user is not online. Statuses: " . json_encode($statuses);
                }
            } // External Partner Retailer
            else {
                if (!empty($dualConfig)) {

                    $errorMsg = "";
                    if (strcasecmp($dualConfig->get('partner'), 'hmshost') == 0) {

                        $hmshost = new HMSHost($dualConfig->get('airportId'), $dualConfig->get('retailerId'));
                        list($response, $errorMsg) = $hmshost->ping_retailer();
                        unset($hmshost);
                    }

                    if ($response == 1) {

                        $passed = 1;

                    } else {

                        $errorAppend = "Partner Tablet is down. (" . $errorMsg . ")";
                    }
                } // Else
                else {

                    $errorAppend = "POS Config not found or App retailer";
                }
            }
        }
    }

    // Verify Checks
    if ($passed == 1) {

        return array(1, "", "");
    } else {

        if (!empty($errorAppend)) {

            return array(0, $errorAppend, "");
        } else {

            return array(0, "", "");
        }
    }
}

function retailerPOSType($objRetailerPOSConfig)
{

    // If Printer Retailer
    if (!empty($objRetailerPOSConfig->get("printerId"))) {

        $retailerPOSType = "print";
    } // If POS Retailer
    else {
        if (!empty($objRetailerPOSConfig->get("locationId"))) {

            $retailerPOSType = "pos";
        } // If Tablet Slack Retailer
        else {
            if (!empty($objRetailerPOSConfig->get("tabletId"))) {

                $retailerPOSType = "slack";
            } // Else
            else {

                $retailerPOSType = "app";
            }
        }
    }

    return $retailerPOSType;
}

function checkGooglePrinter($printerId)
{

    $gcp = new GoogleCloudPrint();

    $token = $gcp->getAccessTokenByRefreshToken($GLOBALS['GCP_urlconfig']['refreshtoken_url'],
        http_build_query($GLOBALS['GCP_refreshTokenConfig']));

    if (empty($token)) {

        json_error("AS_515", "", "GooglePrint Token fetch failed for printerId (" . $printerId . ")", 1, 1);
        return array(0, []);
    }

    $gcp->setAuthToken($token);
    $statusArray = $gcp->printerStatus($printerId);

    if (in_array($statusArray["connectionStatus"], array("OFFLINE", "DORMANT")) ||
        in_array($statusArray["semanticState"], array("STOPPED", "OFFLINE", "NOT_CONFIGURED")) ||
        in_array($statusArray["uiState"], array("STOPPED", "OFFLINE"))
    ) {

        return array(0, $statusArray);
    }

    return array(1, $statusArray);
}

function checkTabletStatus($tabletId)
{

    $response = Request::get($GLOBALS['env_SlackPingAPIURLPrefix'] . '?token=' . $GLOBALS['env_Slack_tokenPing'] . '&user=' . $tabletId)
        ->send();

    try {

        if (isset($response->body)
            && isset($response->body->ok)
            && $response->body->ok == true
            &&
            (
                // If presence != active, but if online=true and auto_away=true [e.g. screen locked]
                // But this type of check is only allowed if last_activity was within last 1 hour
                (
                    strcasecmp($response->body->presence, "active") != 0
                    && isset($response->body->online)
                    && $response->body->online == true
                    && $response->body->auto_away == true
                    && $response->body->last_activity >= (time() - 1 * 60 * 60)
                )
                // Else presence = active
                ||
                (
                    strcasecmp($response->body->presence, "active") == 0
                )
            )
        ) {

            // Tablet is active
            return array(1, json_decode($response->raw_body, true));
        }

        if (!isset($response->raw_body)) {

            $raw_body = [];
        } else {

            $raw_body = $response->raw_body;
        }
    } catch (Exception $ex) {

        return array(0, json_decode("Invalid response from Slack", true));
    }

    return array(0, json_decode($raw_body, true));
}

function getFromOmnivore($url)
{

    $response = Request::get($GLOBALS['env_OmnivoreAPIURLPrefix'] . $url)
        ->addHeader('Api-Key', $GLOBALS['env_OmnivoreAPIKey'])
        ->send();

    return $response;
}

function postToOmnivore($url, $postArray = array())
{

    $response = "";

    try {

        if (count_like_php5($postArray) == 0) {

            $response = Request::post($GLOBALS['env_OmnivoreAPIURLPrefix'] . $url)
                ->addHeader('Api-Key', $GLOBALS['env_OmnivoreAPIKey'])
                ->send();
        } else {

            $response = Request::post($GLOBALS['env_OmnivoreAPIURLPrefix'] . $url)
                ->sendsJson()
                ->addHeader('Api-Key', $GLOBALS['env_OmnivoreAPIKey'])
                ->body(json_encode($postArray))
                ->send();
        }
    } catch (Exception $ex) {

        json_error("AS_1006", "", "Ominvore Post failed! URL=$url, $postArray" . json_encode($postArray), 3, 1);
    }

    return $response;
}

/*
function findOrderForUser($userid, $uniqueRetailerId, $orderStatusArray, $objParseQueryOrder) {

    $objParseQueryOrder->containedIn("status", $orderStatusArray);
    $objParseQueryOrder->equalTo("userObjectId", $userid);
    $objParseQueryOrder->equalTo("uniqueRetailerId", $uniqueRetailerId);

    return $objParseQueryOrder->find();
}
*/

/*
function nameForPOS($name, $totalAvailLength=14) {

    $name = str_ireplace("-", " ", $name);

    // Get the last space and the last name initial
    $posLastName = strripos($name, " ");

    if ($posLastName === false) {

        // Don't do anything
        $lastName = "";
    }
    else {

        // Take the space + last initial
        $lastName = substr($name, $posLastName, 2);
    }

    // Get the first space and the first name
    $posFirstName = stripos($name, " ");

    // Total 15 chars, 1 > and then 2 for last initial plus space = balance 12
    // If no space is found then give all 14 to the First name
    if ($posFirstName === false) {

        $posFirstName = $totalAvailLength;
    }

    $fistName = substr($name, 0, $posFirstName);

    $nameNew = $fistName . $lastName;

    // Check if the first name Total length is greater than 15-1 = 14 characters, if so trim first name
    if(strlen($nameNew) > $totalAvailLength) {

        // If Last initial exists
        if(strlen($lastName) > 0) {

            // Leave two spaces for last initial and a space
            $nameNew = substr($fistName, 0, $totalAvailLength-2) . $lastName;
        }
        else {

            $nameNew = substr($fistName, 0, $totalAvailLength);
        }
    }

    return $nameNew;
}
*/

function createOrderTicketPDF(
    $order,
    $customerName,
    $retailerName,
    $submissionTimestamp,
    $etaTimestamp,
    $orderSummary,
    $retailerTotals
) {

    $airporTimeZone = fetchAirportTimeZone($order->get('retailer')->get('airportIataCode'),
        date_default_timezone_get());

    // $submissionDateTime = orderFormatDate($airporTimeZone, $submissionTimestamp);
    $orderIdDisplay = $orderSummary["internal"]["orderIdDisplay"];
    $submissionDateTime = formatDateByTimezone($airporTimeZone, $submissionTimestamp, "M-j-y, g:i A");
    $orderDetails = "";
    $deliveryInstructions = "";

    // If Pickup Order
    if (strcasecmp($orderSummary["internal"]["fullfillmentType"], 'p') == 0) {

        $fullfillmentByRetailerTime = orderFormatDate($airporTimeZone, $etaTimestamp, 'time');
        $fullfillmentByRetailerDescription = 'Customer Pickup Time';
        $fullfillmentTypeDescription = 'Pickup';
    } // Delivery
    else {

        // Deduct fullfillment process time (delivery walk time by delivery person) from ETA to provide Retailer's ready time
        $fullfillmentByRetailerTime = orderFormatDate($airporTimeZone, $etaTimestamp, 'time');
        $fullfillmentByRetailerDescription = 'Delivery Time';
        $fullfillmentTypeDescription = 'Delivery';

        if ($order->has('deliveryInstructions')
            && !empty($order->get('deliveryInstructions'))
        ) {

            $deliveryInstructions = $order->get('deliveryInstructions');
        }
    }


    foreach ($orderSummary["items"] as $item) {

        $qty = $item["itemQuantity"];
        $itemName = $item["itemName"];
        $itemTotalPrice = $item["itemTotalPriceDisplay"];
        $itemComment = $item["itemComment"];
        $itemCategoryNames = $item["itemCategoryName"];

        if (!empty($item["itemSecondCategoryName"])) {

            $itemCategoryNames .= ", " . $item["itemSecondCategoryName"];
        }

        if (!empty($item["itemThirdCategoryName"])) {

            $itemCategoryNames .= ", " . $item["itemThirdCategoryName"];
        }

        $orderDetails .=
            "
            <tr>
                <td>$qty</td>
                <td align=\"left\">$itemName</td>
                <td>$itemTotalPrice</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"left\"><u>Under:</u> $itemCategoryNames</td>
                <td>&nbsp;</td>
            </tr>
";

        if (!empty($itemComment) && strcasecmp($itemComment, "") != 0) {

            $orderDetails .=
                "
            <tr>
                <td>&nbsp;</td>
                <td align=\"left\"><b><i>Special Instructions:</b></i> $itemComment</td>
                <td>&nbsp;</td>
            </tr>
";
        }

        if (isset($item["options"])) {

            foreach ($item["options"] as $options) {

                $optionName = $options["optionName"];
                $optionTotalPrice = $options["priceTotalDisplay"];
                $modifierName = $options["modifierName"];

                $orderDetails .=
                    "
            <tr>
                <td>&nbsp;</td>
                <td align=\"left\">+ <i>$modifierName</i> &gt;&gt; $optionName</td>
                <td>$optionTotalPrice</td>
            </tr>
";
            }
        }
    }

    $subtotal = $orderSummary["totals"]["subTotalDisplay"];
    $taxes = $orderSummary["totals"]["TaxesDisplay"];
    $serviceFee = isset($orderSummary["totals"]["ServiceFeeDisplay"]) ? $orderSummary["totals"]["ServiceFeeDisplay"] : "";

    //////////////////////////////////////////////////////////////////////
    // Fee Credits
    $fullfillmentFeeCredits = 0;
    if ($order->get('fullfillmentType') == 'd'
        && $order->has('quotedFullfillmentDeliveryFeeCredits')
        && $order->get('quotedFullfillmentDeliveryFeeCredits') > 0
    ) {

        $fullfillmentFeeCredits = $order->get('quotedFullfillmentDeliveryFeeCredits');
    } else {
        if ($order->get('fullfillmentType') == 'p'
            && $order->has('quotedFullfillmentPickupFeeCredits')
            && $order->get('quotedFullfillmentPickupFeeCredits') > 0
        ) {

            $fullfillmentFeeCredits = $order->get('quotedFullfillmentPickupFeeCredits');
        }
    }

    if ($orderSummary["totals"]["AirportSherpaFee"] == 0) {

        // If this was due to the credits, then show the credits as the fee
        if ($fullfillmentFeeCredits > 0) {

            $airportSherpaFee = dollar_format($fullfillmentFeeCredits);
        } // Else this was FREE for non-credit reasons, coupon or promo rate
        else {

            $airportSherpaFee = dollar_format(0);
        }
    } // If fee was paid
    else {

        $airportSherpaFee = dollar_format($orderSummary["totals"]["AirportSherpaFee"] + $fullfillmentFeeCredits);
    }
    //////////////////////////////////////////////////////////////////////

    // $airportSherpaFee = $orderSummary["totals"]["AirportSherpaFeeDisplay"];

    $credits = $orderSummary["totals"]["CreditsAppliedDisplay"];
    $couponApplied = $orderSummary["totals"]["CouponDisplay"];
    $couponCode = empty($orderSummary["totals"]["CouponCodeApplied"]) ? "" : "(" . $orderSummary["totals"]["CouponCodeApplied"] . ")";
    // $tip = $orderSummary["totals"]["TipsDisplay"];
    $employeeDiscount = $orderSummary["totals"]["AirEmployeeDiscountDisplay"];
    $militaryDiscount = $orderSummary["totals"]["MilitaryDiscountDisplay"];
    $total = $orderSummary["totals"]["TotalDisplay"];

    $paymentTypeId = $orderSummary["payment"]["paymentTypeId"];
    $paymentTypeName = $orderSummary["payment"]["paymentTypeName"];

    $totalDetails =
        "
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Sub-Total</td>
                <td>$subtotal</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Airport Employee Discount</td>
                <td>($employeeDiscount)</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Military Discount</td>
                <td>($militaryDiscount)</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Taxes</td>
                <td>$taxes</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Service Fee</td>
                <td>$serviceFee</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Delivery/Pickup Fee</td>
                <td>$airportSherpaFee</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Credits</td>
                <td>($credits)</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Coupon applied $couponCode</td>
                <td>($couponApplied)</td>
            </tr>
";
    // <tr>
    //     <td>&nbsp;</td>
    //     <td align=\"right\">Tip(s)</td>
    //     <td>$tip</td>
    // </tr>

    /*
        if (isset($orderSummary["totals"]["CouponDisplay"])) {

            $couponSavings = $orderSummary["totals"]["CouponDisplay"];
            $couponCodeApplied = $orderSummary["totals"]["CouponCodeApplied"];

            $totalDetails .=
                "
                <tr>
                    <td>&nbsp;</td>
                    <td align=\"right\">Coupon [$couponCodeApplied] savings</td>
                    <td>($couponSavings)</td>
                </tr>
    ";
        }
    */

    $totalDetails .=
        "
            <tr>
                <td>&nbsp;</td>
                <td align=\"right\">Total Paid</td>
                <td>$total</td>
            </tr>
";

    $orderHTML = "
<html>
<body>
    <div align=\"center\">

        <h2>AtYourGate</h2>

        <h3>$retailerName</h3>

        <h4>$fullfillmentTypeDescription Order for <u>$customerName</u></h4>

        <table width=\"95%\" cellpadding=\"5\" style=\"text-align: center; border: 1px solid black\">
            <tr>
                <th width=\"33%\"><b>Check Time</b></td>
                <th width=\"33%\"><b>$fullfillmentByRetailerDescription</b></td>
                <th width=\"33%\"><b>Check #</b></td>
            </tr>
            <tr>
                <td width=\"33%\">$submissionDateTime</td>
                <td width=\"33%\">$fullfillmentByRetailerTime</td>
                <td width=\"33%\">$orderIdDisplay</td>
            </tr>
            <tr>
                <td colspan=\"3\">$deliveryInstructions</td>
            </tr>
        </table>

        <table width=\"95%\" cellpadding=\"5\" style=\"text-align: center; border: 1px solid black\">
            <tr>
                <th width=\"10%\"><strong>Qty</strong></th>
                <th width=\"70%\">&nbsp;</th>
                <th width=\"20%\"><strong>Price</strong></th>
            </tr>
$orderDetails
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
$totalDetails
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>";

    if (!empty($paymentTypeName)) {
        $orderHTML .= "<tr>
                <td colspan=\"3\" align=\"center\">Paid for using <i>$paymentTypeName</i> ending in <i>$paymentTypeId</i></td>
            </tr>";
    }

    $orderHTML .= "</table>
    </div>
</body>
</html>
";

    $orderPDF = createPDFFromHTML($orderHTML);

    // S3 Upload Directions Image (Private)
    $invoiceFileName = $orderSummary["internal"]["orderId"] . '_' . md5(time() . rand(1, 1000)) . '.pdf';
    $s3_client = getS3ClientObject();
    $keyWithFolderPath = getS3KeyPath_FilesInvoice() . '/' . $invoiceFileName;
    $response = S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath, $orderPDF,
        false);

    if (is_array($response)) {

        json_error($response["error_code"], "",
            $response["error_message_log"] . " PDF file could not be saved on Parse. Order Id: " . $orderSummary["internal"]["orderId"],
            1);
    }

    $getPrivateURLForPrinting = S3GetPrivateFile($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath);

    /*
    // Save the file on Parse
    $objParseImageObject = ParseFile::createFromData($orderPDF, "invoice.pdf");

    try {

        $objParseImageObject->save();
    }
    catch (ParseException $ex) {

        json_error("AS_843", "", "PDF file could not be saved on Parse. Order Id: " . $orderSummary["internal"]["orderId"] . $ex->getMessage());
    }

    $url = $objParseImageObject->getURL();
    */

    return [$getPrivateURLForPrinting, $invoiceFileName];
}

function getFullfillmentInfoEmpty($retailerUniqueId)
{

    $responseArray["i"]["ping"] = false;
    $responseArray["i"]["uniqueId"] = $retailerUniqueId;
    $responseArray["d"]["isAvailable"] = false;
    $responseArray["d"]["fullfillmentFeesInCents"] = -1;
    $responseArray["d"]["fullfillmentFeesDisplay"] = "";
    // JMD
    // JMD
    $responseArray["d"]["fullfillmentTimeRangeEstimateLowInSeconds"] = "";
    $responseArray["d"]["fullfillmentTimeRangeEstimateHighInSeconds"] = "";
    $responseArray["d"]["fullfillmentTimeEstimateInSeconds"] = -1;
    $responseArray["d"]["fullfillmentTimeRangeEstimateDisplay"] = "";
    $responseArray["d"]["TotalInCents"] = -1;
    $responseArray["d"]["TotalDisplay"] = "";
    $responseArray["d"]["disclaimerText"] = getDisclaimerTextForDelivery();
    $responseArray["p"]["isAvailable"] = false;
    $responseArray["p"]["fullfillmentFeesInCents"] = -1;
    $responseArray["p"]["fullfillmentFeesDisplay"] = "";
    $responseArray["p"]["fullfillmentTimeEstimateInSeconds"] = -1;
    $responseArray["p"]["fullfillmentTimeRangeEstimateDisplay"] = "";
    $responseArray["p"]["fullfillmentTimeRangeEstimateLowInSeconds"] = "";
    $responseArray["p"]["fullfillmentTimeRangeEstimateHighInSeconds"] = "";
    $responseArray["p"]["TotalInCents"] = -1;
    $responseArray["p"]["TotalDisplay"] = "";
    $responseArray["p"]["disclaimerText"] = getDisclaimerTextForPickup();

    return $responseArray;
}


function getFullfillmentInfoWithoutOrderNew(
    $retailerObject,
    $posConfigObject,
    $toLocationObject,
    $isDeliveryAvailableAtLocation,
    $deliveryAvailabilityErrorMessage
) {

    $orderService = OrderServiceFactory::create(CacheServiceFactory::create());

    $responseArray = [];
    $requestedFullFillmentTimestamp = 0;

    $maxSLA = getOrderPrepTime($retailerObject, "", 0, $posConfigObject);
    // echo($retailerObject->getObjectId() . "\t getOrderPrepTime\t" . calctimeused() . "\r\n");

    //  return $posConfigObject->getObjectId();

    if ($retailerObject->get('hasPickup')) {

        //////////// PICKUP //////////////////
        list($fullfillmentTimeForPickup, $fullfillmentProcessTimeInSeconds) = getPickupTimeInSeconds($retailerObject,
            [], $requestedFullFillmentTimestamp, $maxSLA, $posConfigObject);
        // echo($retailerObject->getObjectId() . "\t getPickupTimeInSeconds\t" . calctimeused() . "\r\n");

        list($isPickupAvailableForRetailer, $pickupAvailabilityErrorMessage) = isPickupAvailableForRetailer($retailerObject,
            $fullfillmentTimeForPickup, $requestedFullFillmentTimestamp, $toLocationObject);
        // echo($retailerObject->getObjectId() . "\t isPickupAvailableForRetailer\t" . calctimeused() . "\r\n");
    } else {
        $isPickupAvailableForRetailer = false;
        $pickupAvailabilityErrorMessage = 'Retailer does not support pickup orders';
        $fullfillmentTimeForPickup = 0;
    }

    if ($retailerObject->get('hasDelivery')) {
        //////////// Delivery //////////////////
        list($fullfillmentTimeForDelivery, $fullfillmentProcessTimeInSecondsForDelivery, $fullfillmentTimeInSecondsOverriden, $requestedFullFillmentTimestampOverriden) = getDeliveryTimeInSeconds($retailerObject,
            $toLocationObject, [], $requestedFullFillmentTimestamp, $maxSLA);

        if ($fullfillmentTimeInSecondsOverriden > 0) {

            $fullfillmentTimeInSeconds = $fullfillmentTimeInSecondsOverriden;
        }

        // echo($retailerObject->getObjectId() . "\t getDeliveryTimeInSeconds\t" . calctimeused() . "\r\n");

        $fullfillmentTimeInSecondsForDelivery = $fullfillmentTimeForDelivery + $fullfillmentProcessTimeInSecondsForDelivery;
        // echo($retailerObject->getObjectId() . "\t pingRetailer\t" . calctimeused() . "\r\n");

        // Use fullfillmentTimeForPickup to calculate availability of delivery
        list($isDeliveryAvailableForRetailer, $deliveryAvailabilityForRetailerErrorMessage,$isDeliveryAvailableForRetailerReason) = isDeliveryAvailableForRetailer($retailerObject,
            $fullfillmentTimeForDelivery, $toLocationObject, $requestedFullFillmentTimestamp);
        // echo($retailerObject->getObjectId() . "\t isDeliveryAvailableForRetailer\t" . calctimeused() . "\r\n");

    } else {
        $isDeliveryAvailableForRetailerReason = '';
        $isDeliveryAvailableForRetailer = false;
        $deliveryAvailabilityErrorMessage = 'Retailer does not support delivery orders';
        $deliveryAvailabilityForRetailerErrorMessage = 'Retailer does not support delivery orders';
        $fullfillmentTimeForDelivery = 0;
    }

    if ($isDeliveryAvailableAtLocation
        && $isDeliveryAvailableForRetailer
    ) {

        $responseArray["d"]["isAvailable"] = true;
        $responseArray["d"]["isNotAvailableReason"] = "";
        $responseArray["d"]["isAvailableAtDeliveryLocation"] = true;
        $responseArray["d"]["fullfillmentFeesInCents"] = -1;
        $responseArray["d"]["fullfillmentFeesDisplay"] = "";
        $responseArray["d"]["fullfillmentTimeEstimateInSeconds"] = $fullfillmentTimeForDelivery;
        $responseArray["d"]["TotalInCents"] = -1;
        $responseArray["d"]["TotalDisplay"] = "";
        $responseArray["d"]["disclaimerText"] = getDisclaimerTextForDelivery();
    } else {

        $responseArray["d"]["isAvailable"] = false;
        $responseArray["d"]["isNotAvailableReason"] = ($isDeliveryAvailableForRetailer == false) ? $deliveryAvailabilityForRetailerErrorMessage : $deliveryAvailabilityErrorMessage;
        $responseArray["d"]["isAvailableAtDeliveryLocation"] = $orderService->getDeliveryAvailabilityForRetailerAtLocation($retailerObject->getObjectId(), $toLocationObject->getObjectId())->isAvailable();
        $responseArray["d"]["fullfillmentFeesInCents"] = -1;
        $responseArray["d"]["fullfillmentFeesDisplay"] = "";
        $responseArray["d"]["fullfillmentTimeEstimateInSeconds"] = -1;
        $responseArray["d"]["TotalInCents"] = -1;
        $responseArray["d"]["TotalDisplay"] = "";
        $responseArray["d"]["disclaimerText"] = getDisclaimerTextForDelivery();
    }

    if ($isPickupAvailableForRetailer) {

        $responseArray["p"]["isAvailable"] = true;
        $responseArray["p"]["isNotAvailableReason"] = "";
        $responseArray["p"]["isPickupAvailableForUserLocation"] = isPickupAvailableForRetailerAtLocation($retailerObject, $toLocationObject);
        $responseArray["p"]["fullfillmentFeesInCents"] = -1;
        $responseArray["p"]["fullfillmentFeesDisplay"] = "";
        $responseArray["p"]["fullfillmentTimeEstimateInSeconds"] = $fullfillmentTimeForPickup;
        $responseArray["p"]["TotalInCents"] = -1;
        $responseArray["p"]["TotalDisplay"] = "";
        $responseArray["p"]["disclaimerText"] = getDisclaimerTextForPickup();
    } else {

        $responseArray["p"]["isAvailable"] = false;
        $responseArray["p"]["isNotAvailableReason"] = $pickupAvailabilityErrorMessage;
        $responseArray["p"]["isPickupAvailableForUserLocation"] = isPickupAvailableForRetailerAtLocation($retailerObject,
            $toLocationObject);
        $responseArray["p"]["fullfillmentFeesInCents"] = -1;
        $responseArray["p"]["fullfillmentFeesDisplay"] = "";
        $responseArray["p"]["fullfillmentTimeEstimateInSeconds"] = -1;
        $responseArray["p"]["TotalInCents"] = -1;
        $responseArray["p"]["TotalDisplay"] = "";
        $responseArray["p"]["disclaimerText"] = getDisclaimerTextForPickup();
    }

    // Ping status
    // list($ping, $error, $pingStatusDescription) = pingRetailer($retailerObject, [], $fullfillmentTimeForDelivery, $requestedFullFillmentTimestamp);

    $responseArray["i"]["uniqueId"] = $retailerObject->get('uniqueId');

    if ($responseArray["p"]["isAvailable"] == true
        || $responseArray["d"]["isAvailable"] == true
    ) {

        $responseArray["i"]["ping"] = true;
        $responseArray["i"]["pingStatusDescription"] = "";
    } else {

        $responseArray["i"]["ping"] = false;

        if ($responseArray["p"]["isAvailable"] == false) {

            $responseArray["i"]["pingStatusDescription"] = $responseArray["p"]["isNotAvailableReason"];
        } else {

            $responseArray["i"]["pingStatusDescription"] = $responseArray["d"]["isNotAvailableReason"];
        }
    }

    return $responseArray;
}


function getFullfillmentInfoWithoutOrder(
    $retailerObject,
    $toLocationObject,
    $isDeliveryAvailableAtLocation,
    $deliveryAvailabilityErrorMessage,
    $requestedFullFillmentTimestamp = 0
) {

    $orderService = OrderServiceFactory::create(CacheServiceFactory::create());

    $responseArray = [];

    $maxSLA = getOrderPrepTime($retailerObject, "");
    // echo($retailerObject->getObjectId() . "\t getOrderPrepTime\t" . calctimeused() . "\r\n");


    //////////// PICKUP //////////////////
    list($fullfillmentTimeForPickup, $fullfillmentProcessTimeInSeconds) = getPickupTimeInSeconds($retailerObject, [],
        $requestedFullFillmentTimestamp, $maxSLA);
    // echo($retailerObject->getObjectId() . "\t getPickupTimeInSeconds\t" . calctimeused() . "\r\n");

    list($isPickupAvailableForRetailer, $pickupAvailabilityErrorMessage) = isPickupAvailableForRetailer($retailerObject,
        $fullfillmentTimeForPickup, $requestedFullFillmentTimestamp, $toLocationObject);
    // echo($retailerObject->getObjectId() . "\t isPickupAvailableForRetailer\t" . calctimeused() . "\r\n");

    //////////// Delivery //////////////////
    list($fullfillmentTimeForDelivery, $fullfillmentProcessTimeInSecondsForDelivery, $fullfillmentTimeInSecondsOverriden, $requestedFullFillmentTimestampOverriden) = getDeliveryTimeInSeconds($retailerObject,
        $toLocationObject, [], $requestedFullFillmentTimestamp, $maxSLA);

    // echo($retailerObject->getObjectId() . "\t getDeliveryTimeInSeconds\t" . calctimeused() . "\r\n");

    $fullfillmentTimeInSecondsForDelivery = $fullfillmentTimeForDelivery + $fullfillmentProcessTimeInSecondsForDelivery;
    // echo($retailerObject->getObjectId() . "\t pingRetailer\t" . calctimeused() . "\r\n");

    // Use fullfillmentTimeForPickup to calculate availability of delivery
    list($isDeliveryAvailableForRetailer, $deliveryAvailabilityForRetailerErrorMessage,$isDeliveryAvailableForRetailerReason) = isDeliveryAvailableForRetailer($retailerObject,
        $fullfillmentTimeForDelivery, $toLocationObject, $requestedFullFillmentTimestamp);
    // echo($retailerObject->getObjectId() . "\t isDeliveryAvailableForRetailer\t" . calctimeused() . "\r\n");

    if ($fullfillmentTimeInSecondsOverriden > 0) {
        //$fullfillmentTimeInSeconds = $fullfillmentTimeInSecondsOverriden;
        $fullfillmentTimeForDelivery = $fullfillmentTimeInSecondsOverriden;
    }

    if ($isDeliveryAvailableAtLocation
        && $isDeliveryAvailableForRetailer
    ) {

        $responseArray["d"]["isAvailable"] = true;
        $responseArray["d"]["isNotAvailableReason"] = "";
        $responseArray["d"]["isAvailableAtDeliveryLocation"] = true;
        $responseArray["d"]["fullfillmentFeesInCents"] = -1;
        $responseArray["d"]["fullfillmentFeesDisplay"] = "";
        $responseArray["d"]["fullfillmentTimeEstimateInSeconds"] = $fullfillmentTimeForDelivery;
        $responseArray["d"]["TotalInCents"] = -1;
        $responseArray["d"]["TotalDisplay"] = "";
        $responseArray["d"]["disclaimerText"] = getDisclaimerTextForDelivery();
    } else {

        $responseArray["d"]["isAvailable"] = false;
        $responseArray["d"]["isNotAvailableReason"] = ($isDeliveryAvailableForRetailer == false) ? $deliveryAvailabilityForRetailerErrorMessage : $deliveryAvailabilityErrorMessage;
        $responseArray["d"]["isAvailableAtDeliveryLocation"] = $orderService->getDeliveryAvailabilityForRetailerAtLocation($retailerObject->getObjectId(), $toLocationObject->getObjectId())->isAvailable();
        $responseArray["d"]["fullfillmentFeesInCents"] = -1;
        $responseArray["d"]["fullfillmentFeesDisplay"] = "";
        $responseArray["d"]["fullfillmentTimeEstimateInSeconds"] = -1;
        $responseArray["d"]["TotalInCents"] = -1;
        $responseArray["d"]["TotalDisplay"] = "";
        $responseArray["d"]["disclaimerText"] = getDisclaimerTextForDelivery();
    }

    if ($isPickupAvailableForRetailer) {

        $responseArray["p"]["isAvailable"] = true;
        $responseArray["p"]["isNotAvailableReason"] = "";
        $responseArray["p"]["isPickupAvailableForUserLocation"] = isPickupAvailableForRetailerAtLocation($retailerObject, $toLocationObject);
        $responseArray["p"]["fullfillmentFeesInCents"] = -1;
        $responseArray["p"]["fullfillmentFeesDisplay"] = "";
        $responseArray["p"]["fullfillmentTimeEstimateInSeconds"] = $fullfillmentTimeForPickup;
        $responseArray["p"]["TotalInCents"] = -1;
        $responseArray["p"]["TotalDisplay"] = "";
        $responseArray["p"]["disclaimerText"] = getDisclaimerTextForPickup();
    } else {

        $responseArray["p"]["isAvailable"] = false;
        $responseArray["p"]["isNotAvailableReason"] = $pickupAvailabilityErrorMessage;
        $responseArray["p"]["isPickupAvailableForUserLocation"] = isPickupAvailableForRetailerAtLocation($retailerObject,
            $toLocationObject);
        $responseArray["p"]["fullfillmentFeesInCents"] = -1;
        $responseArray["p"]["fullfillmentFeesDisplay"] = "";
        $responseArray["p"]["fullfillmentTimeEstimateInSeconds"] = -1;
        $responseArray["p"]["TotalInCents"] = -1;
        $responseArray["p"]["TotalDisplay"] = "";
        $responseArray["p"]["disclaimerText"] = getDisclaimerTextForPickup();
    }

    // Ping status
    // list($ping, $isClosed, $error, $pingStatusDescription) = pingRetailer($retailerObject, [], $fullfillmentTimeForDelivery, $requestedFullFillmentTimestamp);
    $responseArray["i"]["uniqueId"] = $retailerObject->get('uniqueId');

    if ($responseArray["p"]["isAvailable"] == true
        || $responseArray["d"]["isAvailable"] == true
    ) {

        $responseArray["i"]["ping"] = true;
        $responseArray["i"]["pingStatusDescription"] = "";
    } else {

        $responseArray["i"]["ping"] = false;

        if ($responseArray["p"]["isAvailable"] == false) {

            $responseArray["i"]["pingStatusDescription"] = $responseArray["p"]["isNotAvailableReason"];
        } else {

            $responseArray["i"]["pingStatusDescription"] = $responseArray["d"]["isNotAvailableReason"];
        }
    }

    return $responseArray;
}

function getFullfillmentInfoWithOrder($retailerObject, $toLocationObject, $requestedFullFillmentTimestamp, $order)
{
    $orderService = OrderServiceFactory::create(CacheServiceFactory::create());

    $responseArray = [];
    $orderTotal = 0;

    // Fetch Order Summary
    list($responseArraySummaryPickup, $retailerTotals) = getOrderSummary($order, 0, false,
        $requestedFullFillmentTimestamp, false, 'p');
//json_error(json_encode($responseArraySummaryPickup),'');

    $orderTotalPickup = $responseArraySummaryPickup["totals"]["Total"];
    $orderTotalForCouponPickup = $responseArraySummaryPickup["totals"];
    $creditsAppliedToPickupFees = $responseArraySummaryPickup["internal"]["creditAppliedMap"]['p']["feeCreditSummary"]["creditsAppliedToFees"];
    $feesAfterCreditsForPickup = $responseArraySummaryPickup["internal"]["creditAppliedMap"]['p']["feeCreditSummary"]["feesInCentsAfterCredits"];

    list($responseArraySummaryDelivery, $retailerTotals) = getOrderSummary($order, 0, false,
        $requestedFullFillmentTimestamp, false, 'd');


    $orderTotalDelivery = $responseArraySummaryDelivery["totals"]["Total"];
    $orderTotalForCouponDelivery = $responseArraySummaryDelivery["totals"];
    $creditsAppliedToDeliveryFees = $responseArraySummaryDelivery["internal"]["creditAppliedMap"]['d']["feeCreditSummary"]["creditsAppliedToFees"];
    $feesAfterCreditsForDelivery = $responseArraySummaryDelivery["internal"]["creditAppliedMap"]['d']["feeCreditSummary"]["feesInCentsAfterCredits"];

    $serviceFeeExplicitlyShown = false;
    $serviceFee = 0;
    if (isset($responseArraySummaryDelivery["totals"]["serviceFeeExplicitlyShown"])) {

        $serviceFeeExplicitlyShown = $responseArraySummaryDelivery["totals"]["serviceFeeExplicitlyShown"];
    }

    // If service fee wasn't shown, then we need to add it here to the fullfillment fee
    if (!$serviceFeeExplicitlyShown
        && isset($orderTotalForCouponDelivery["ServiceFee"])
    ) {

        $serviceFee = $orderTotalForCouponDelivery["ServiceFee"];
    }

    // Pickup
    list($fullfillmentTimeForPickup, $fullfillmentProcessTimeInSeconds) = getPickupTimeInSeconds($retailerObject,
        $order, $requestedFullFillmentTimestamp);

    list($isPickupAvailableForRetailer, $pickupAvailabilityErrorMessage) = isPickupAvailableForRetailer($retailerObject,
        $fullfillmentTimeForPickup, $requestedFullFillmentTimestamp, $toLocationObject);


    // Delivery
    list($fullfillmentTimeForDelivery, $fullfillmentProcessTimeInSecondsForDelivery, $fullfillmentTimeInSecondsOverriden, $requestedFullFillmentTimestampOverriden) = getDeliveryTimeInSeconds($retailerObject,
        $toLocationObject, $order);

    $fullfillmentTimeInSecondsForDelivery = $fullfillmentTimeForDelivery + $fullfillmentProcessTimeInSecondsForDelivery;

    // Check delivery availability for a specific location
    list($deliveryAvailability, $deliveryAvailabilityErrorMessage) = isDeliveryAvailableAt($retailerObject,
        $toLocationObject, $requestedFullFillmentTimestamp);

    // Ping status
    // list($ping, $isClosed, $error, $pingStatusDescription) = pingRetailer($retailerObject, $order, $fullfillmentTimeForDelivery, $requestedFullFillmentTimestamp);
    // $responseArray["i"]["ping"] = $ping;
    // $responseArray["i"]["pingStatusDescription"] = $pingStatusDescription;

    list($isDeliveryAvailableForRetailer, $deliveryAvailabilityForRetailerErrorMessage,$isDeliveryAvailableForRetailerReason) = isDeliveryAvailableForRetailer($retailerObject,
        $fullfillmentTimeForDelivery, $toLocationObject, $requestedFullFillmentTimestamp);

    if ($fullfillmentTimeInSecondsOverriden > 0) {

        //$fullfillmentTimeInSeconds = $fullfillmentTimeInSecondsOverriden;
        $fullfillmentTimeForDelivery = $fullfillmentTimeInSecondsOverriden;
    }
    $airportTimeZone = fetchAirportTimeZone($order->get('retailer')->get('airportIataCode'), date_default_timezone_get());





    if ($deliveryAvailability
        && $isDeliveryAvailableForRetailer
    ) {

        $responseArray["d"]["isAvailable"] = true;
        $responseArray["d"]["isNotAvailableReason"] = "";
        $responseArray["d"]["isAvailableAtDeliveryLocation"] = $orderService->getDeliveryAvailabilityForRetailerAtLocation($retailerObject->getObjectId(), $toLocationObject->getObjectId())->isAvailable();

        list($deliveryFees) = getDeliveryFeesInCents($retailerObject, $toLocationObject, $orderTotalForCouponDelivery,
            $serviceFee, $feesAfterCreditsForDelivery);

        // If credits were applied to the fees
        if ($creditsAppliedToDeliveryFees > 0) {

            if ($deliveryFees > 0) {

                $fullfillmentFeesDisplay = dollar_format($deliveryFees) . " (-" . dollar_format($creditsAppliedToDeliveryFees) . " credits)";
            } else {

                //////////////////////////////////////
                // Forces iOS to show the Display text
                $deliveryFees = -1;
                //////////////////////////////////////
                $fullfillmentFeesDisplay = "FREE!" . " (-" . dollar_format($creditsAppliedToDeliveryFees) . " credits)";
            }
        } // Else display normally
        else {
            if ($deliveryFees > 0) {

                $fullfillmentFeesDisplay = dollar_format($deliveryFees);
            } else {

                $fullfillmentFeesDisplay = "FREE!";
            }
        }

        $responseArray["d"]["fullfillmentFeesInCents"] = $deliveryFees;
        $responseArray["d"]["fullfillmentFeesDisplay"] = $fullfillmentFeesDisplay;
        $responseArray["d"]["fullfillmentTimeEstimateInSeconds"] = $fullfillmentTimeForDelivery;

        //list($fullfillmentTimeRangeEstimateDisplay, $fullfillmentTimeRangeEstimateLowInSeconds, $fullfillmentTimeRangeEstimateHighInSeconds) = getOrderFullfillmentTimeRangeEstimateDisplay($fullfillmentTimeForDelivery);
        list($fullfillmentTimeRangeEstimateDisplay, $fullfillmentTimeRangeEstimateLowInSeconds, $fullfillmentTimeRangeEstimateHighInSeconds) =
            \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                $fullfillmentTimeForDelivery,
                $GLOBALS['env_fullfillmentETALowInSecs'],
                $GLOBALS['env_fullfillmentETAHighInSecs'],
                $airportTimeZone
            );


        $responseArray["d"]["fullfillmentTimeRangeEstimateDisplay"] = $fullfillmentTimeRangeEstimateDisplay;
        $responseArray["d"]["fullfillmentTimeRangeEstimateLowInSeconds"] = $fullfillmentTimeRangeEstimateLowInSeconds;
        $responseArray["d"]["fullfillmentTimeRangeEstimateHighInSeconds"] = $fullfillmentTimeRangeEstimateHighInSeconds;

        //////////////////////////////////////
        /// Force iOS
        if ($responseArray["d"]["fullfillmentFeesInCents"] == -1) {

            $responseArray["d"]["TotalInCents"] = dollar_format_float($orderTotalDelivery);
        } else {

            //////////////////////////////////////
            $responseArray["d"]["TotalInCents"] = dollar_format_float($orderTotalDelivery + $responseArray["d"]["fullfillmentFeesInCents"]);
            //////////////////////////////////////
        }
        //////////////////////////////////////

        $responseArray["d"]["TotalDisplay"] = dollar_format($responseArray["d"]["TotalInCents"]);
        $responseArray["d"]["disclaimerText"] = getDisclaimerTextForDelivery($responseArraySummaryDelivery["totals"]["CouponIsFullfillmentRestrictionApplied"]);
    } else {

        // delivery fee need to be calculated due to scheduled order
        list($deliveryFees) = getDeliveryFeesInCents($retailerObject, $toLocationObject, $orderTotalForCouponDelivery,
            $serviceFee, $feesAfterCreditsForDelivery);

        // If credits were applied to the fees
        if ($creditsAppliedToDeliveryFees > 0) {

            if ($deliveryFees > 0) {

                $fullfillmentFeesDisplay = dollar_format($deliveryFees) . " (-" . dollar_format($creditsAppliedToDeliveryFees) . " credits)";
            } else {

                //////////////////////////////////////
                // Forces iOS to show the Display text
                $deliveryFees = -1;
                //////////////////////////////////////
                $fullfillmentFeesDisplay = "FREE!" . " (-" . dollar_format($creditsAppliedToDeliveryFees) . " credits)";
            }
        } // Else display normally
        else {
            if ($deliveryFees > 0) {

                $fullfillmentFeesDisplay = dollar_format($deliveryFees);
            } else {

                $fullfillmentFeesDisplay = "FREE!";
            }
        }


        $responseArray["d"]["isAvailable"] = false;
        $responseArray["d"]["isNotAvailableReason"] = ($isDeliveryAvailableForRetailer == false) ? $deliveryAvailabilityForRetailerErrorMessage : $deliveryAvailabilityErrorMessage;
        $responseArray["d"]["isAvailableAtDeliveryLocation"] = $orderService->getDeliveryAvailabilityForRetailerAtLocation($retailerObject->getObjectId(), $toLocationObject->getObjectId())->isAvailable();
        $responseArray["d"]["fullfillmentFeesInCents"] = $deliveryFees;
        $responseArray["d"]["fullfillmentFeesDisplay"] = $fullfillmentFeesDisplay;
        $responseArray["d"]["fullfillmentTimeEstimateInSeconds"] = -1;
        $responseArray["d"]["fullfillmentTimeRangeEstimateDisplay"] = "";
        $responseArray["d"]["fullfillmentTimeRangeEstimateLowInSeconds"] = "";
        $responseArray["d"]["fullfillmentTimeRangeEstimateHighInSeconds"] = "";

        if ($responseArray["d"]["fullfillmentFeesInCents"] == -1) {
            $responseArray["d"]["TotalInCents"] = dollar_format_float($orderTotalDelivery);
        } else {
            $responseArray["d"]["TotalInCents"] = dollar_format_float($orderTotalDelivery + $responseArray["d"]["fullfillmentFeesInCents"]);
        }
        $responseArray["d"]["TotalDisplay"] = dollar_format($responseArray["d"]["TotalInCents"]);

        //$responseArray["d"]["TotalInCents"] = -1;
        //$responseArray["d"]["TotalDisplay"] = "";
        $responseArray["d"]["disclaimerText"] = getDisclaimerTextForDelivery();
    }

    if ($isPickupAvailableForRetailer) {

        $responseArray["p"]["isAvailable"] = true;
        $responseArray["p"]["isNotAvailableReason"] = "";
        $responseArray["p"]["isPickupAvailableForUserLocation"] = $orderService->getPickupAvailabilityForRetailerAtLocation($retailerObject->getObjectId(), $toLocationObject->getObjectId())->isAvailable();
        list($pickupFees) = getPickupFeesInCents($retailerObject, $orderTotalForCouponPickup, $serviceFee,
            $feesAfterCreditsForPickup);

        // If credits were applied to the fees
        if ($creditsAppliedToPickupFees > 0) {

            if ($pickupFees > 0) {

                $fullfillmentFeesDisplay = dollar_format($pickupFees) . "(-" . dollar_format($creditsAppliedToDeliveryFees) . " credits)";
            } else {

                //////////////////////////////////////
                // Forces iOS to show the Display text
                $pickupFees = -1;
                //////////////////////////////////////
                $fullfillmentFeesDisplay = "FREE!" . " (-" . dollar_format($creditsAppliedToDeliveryFees) . " credits)";
            }
        } // Else display normally
        else {
            if ($pickupFees > 0) {

                $fullfillmentFeesDisplay = dollar_format($pickupFees);
            } else {

                $fullfillmentFeesDisplay = "FREE!";
            }
        }

        $responseArray["p"]["fullfillmentFeesInCents"] = $pickupFees;
        $responseArray["p"]["fullfillmentFeesDisplay"] = $fullfillmentFeesDisplay;
        $responseArray["p"]["fullfillmentTimeEstimateInSeconds"] = $fullfillmentTimeForPickup;

        //list($fullfillmentTimeRangeEstimateDisplay, $fullfillmentTimeRangeEstimateLowInSeconds, $fullfillmentTimeRangeEstimateHighInSeconds) = getOrderFullfillmentTimeRangeEstimateDisplay($fullfillmentTimeForPickup);

        list($fullfillmentTimeRangeEstimateDisplay, $fullfillmentTimeRangeEstimateLowInSeconds, $fullfillmentTimeRangeEstimateHighInSeconds) =
            \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                $fullfillmentTimeForPickup,
                $GLOBALS['env_fullfillmentETALowInSecs'],
                $GLOBALS['env_fullfillmentETAHighInSecs'],
                $airportTimeZone
            );

        $responseArray["p"]["fullfillmentTimeRangeEstimateDisplay"] = $fullfillmentTimeRangeEstimateDisplay;
        $responseArray["p"]["fullfillmentTimeRangeEstimateLowInSeconds"] = $fullfillmentTimeRangeEstimateLowInSeconds;
        $responseArray["p"]["fullfillmentTimeRangeEstimateHighInSeconds"] = $fullfillmentTimeRangeEstimateHighInSeconds;

        //////////////////////////////////////
        /// Force iOS
        if ($responseArray["p"]["fullfillmentFeesInCents"] == -1) {

            $responseArray["p"]["TotalInCents"] = dollar_format_float($orderTotalPickup);
        } else {

            //////////////////////////////////////
            $responseArray["p"]["TotalInCents"] = dollar_format_float($orderTotalPickup + $responseArray["p"]["fullfillmentFeesInCents"]);
            //////////////////////////////////////
        }
        //////////////////////////////////////

        $responseArray["p"]["TotalDisplay"] = dollar_format($responseArray["p"]["TotalInCents"]);
        $responseArray["p"]["disclaimerText"] = getDisclaimerTextForPickup($responseArraySummaryPickup["totals"]["CouponIsFullfillmentRestrictionApplied"]);
    } else {

        $responseArray["p"]["isAvailable"] = false;
        $responseArray["p"]["isNotAvailableReason"] = $pickupAvailabilityErrorMessage;
        $responseArray["p"]["isPickupAvailableForUserLocation"] = isPickupAvailableForRetailerAtLocation($retailerObject,
            $toLocationObject);
        $responseArray["p"]["fullfillmentFeesInCents"] = -1;
        $responseArray["p"]["fullfillmentFeesDisplay"] = "";
        $responseArray["p"]["fullfillmentTimeEstimateInSeconds"] = -1;
        $responseArray["p"]["fullfillmentTimeRangeEstimateDisplay"] = "";
        $responseArray["p"]["fullfillmentTimeRangeEstimateLowInSeconds"] = "";
        $responseArray["p"]["fullfillmentTimeRangeEstimateHighInSeconds"] = "";
        $responseArray["p"]["TotalInCents"] = -1;
        $responseArray["p"]["TotalDisplay"] = "";
        $responseArray["p"]["disclaimerText"] = getDisclaimerTextForPickup();
    }

    $responseArray["quotedFullfillmentDeliveryFeeCredits"] = $creditsAppliedToDeliveryFees;
    $responseArray["quotedFullfillmentPickupFeeCredits"] = $creditsAppliedToPickupFees;
    $responseArray = OrderHelper::addOrderAllowedWithoutCreditCardInfo($responseArray);

    $responseArray['d']["TipsPCT"] = $responseArraySummaryDelivery['totals']["TipsPCT"];
    $responseArray['d']["Tips"] = $responseArraySummaryDelivery['totals']["Tips"];
    $responseArray['d']["TipsDisplay"] = $responseArraySummaryDelivery['totals']["TipsDisplay"];
    $responseArray['d']["TipsAppliedAs"] = $responseArraySummaryDelivery['totals']["TipsAppliedAs"];


    return $responseArray;
}

function isDeliveryAvailableAt($retailerObject, $deliveryLocation, $requestedFullFillmentTimestamp)
{

    // Is any delivery available at this time
    if (!isDeliveryAvailableForDelivery($deliveryLocation, $requestedFullFillmentTimestamp)) {

        return [false, getDefaultErrorMessageForNoDelivery()];
    }

    return [true, ""];
}

function getDefaultErrorMessageForNoDelivery()
{

    return "We are sorry, currently delivery is not available.";
}

function isDeliveryAvailableForRetailer(
    $retailerObject,
    $fullfillmentTimeInSeconds,
    $deliveryLocation,
    $requestedFullFillmentTimestamp
) {

    // Does airport support delivery
    if (!isAirportDeliveryReady($retailerObject->get("airportIataCode"))) {

        return [false, "Delivery coming soon to this airport!", 'other'];
    }

    // Does retailer support delivery
    if (!$retailerObject->get("hasDelivery")) {

        return [false, "This retailer currently doesn't offer delivery.", 'other'];
    }

    // Search if there are limitation for delivery location and retailer
    $isDeliveryLocationNotAvailable = parseExecuteQuery([
        "deliveryLocation" => $deliveryLocation,
        "retailer" => $retailerObject,
        "isDeliveryLocationNotAvailable" => true
    ], "TerminalGateMapRetailerRestrictions");

    // Does retailer support delivery
    if (count_like_php5($isDeliveryLocationNotAvailable) > 0) {

        return [false, "This retailer currently doesn't offer delivery to your selected location.", 'location'];
    }

    // Does airport support delivery
    if (!isAirportDeliveryReady($retailerObject->get("airportIataCode"))) {

        return [false, "Delivery coming soon to this airport!", 'other'];
    }

    // Does retailer support delivery
    if (!$retailerObject->get("hasDelivery")) {

        return [false, "This retailer currently doesn't offer delivery.", 'other'];
    }

    // Is retailer open at this time
    $isClosed = 0;
    list($isClosed, $errMsg) = isRetailerClosed($retailerObject, $fullfillmentTimeInSeconds,
        $requestedFullFillmentTimestamp);

    if ($isClosed != 0) {

        return [false, $errMsg,'other'];
    }

    if (!isRetailerPingActive($retailerObject)) {

        return [false, "This retailer is currently not accepting orders.", 'other'];
    }

    return [true, "",''];
}

function isPickupAvailableForRetailerAtLocation($retailerObject, $userLocation)
{
    // Search if there are limitation for user location and retailer
    $isPickupLocationNotAvailable = parseExecuteQuery([
        "deliveryLocation" => $userLocation,
        "retailer" => $retailerObject,
        "isPickupLocationNotAvailable" => true
    ], "TerminalGateMapRetailerRestrictions");

    // Does retailer support delivery
    if (count_like_php5($isPickupLocationNotAvailable) > 0) {

        return false;
    }
    return true;
}

function isPickupAvailableForRetailer(
    $retailerObject,
    $fullfillmentTimeInSeconds,
    $requestedFullFillmentTimestamp,
    $userLocation
) {

    // Does retailer support pickup
    if (!$retailerObject->get("hasPickup")) {

        return [false, "This retailer currently doesn't offer pickup."];
    }

    // Search if there are limitation for delivery location and retailer
    $isPickupLocationNotAvailable = parseExecuteQuery([
        "deliveryLocation" => $userLocation,
        "retailer" => $retailerObject,
        "isPickupLocationNotAvailable" => true
    ], "TerminalGateMapRetailerRestrictions");

    // Does retailer support delivery
    if (count_like_php5($isPickupLocationNotAvailable) > 0) {

        return [false, "This retailer currently doesn't offer pickup."];
    }

    // Is retailer open at this time
    $isClosed = 0;
    list($isClosed, $errMsg) = isRetailerClosed($retailerObject, $fullfillmentTimeInSeconds,
        $requestedFullFillmentTimestamp);

    if ($isClosed != 0) {

        return [false, $errMsg];
    }

    if (!isRetailerPingActive($retailerObject)) {

        return [false, "This retailer is currently not accepting orders."];
    }

    return [true, ""];
}

function getOrderPrepTime(
    $retailerObject,
    $orderObject = "",
    $requestedFullFillmentTimestamp = 0,
    $posConfigObject = ""
) {

    $avgPrepTimeInSeconds = 0;

    // Fetch Retailer's Avg. Prep Time
    if ($posConfigObject == "") {
        $objectParseQueryPOSConfig = parseExecuteQuery(array("retailer" => $retailerObject), "RetailerPOSConfig", "",
            "", [], 1);
    } else {
        $objectParseQueryPOSConfig = $posConfigObject;
    }
    if (count_like_php5($objectParseQueryPOSConfig) > 0) {
        $avgPrepTimeInSeconds = $objectParseQueryPOSConfig->get('avgPrepTimeInSeconds');
    }

    // Inital max prep time with the Retailer's average prep time
    $retailerMaxPrepTime = $avgPrepTimeInSeconds;

    // If an order was provided
    if (!empty($orderObject)) {

        // If for Immediate Delivery, add 15 minutes before last call
        if ($requestedFullFillmentTimestamp == 0) {

            $requestedFullFillmentTimestamp = time();
        } else {

            $requestedFullFillmentTimestamp = $requestedFullFillmentTimestamp;
        }

        // Get Day of the Week and Seconds since Midnight, Airport time
        list($dayOfWeekAtAirport, $secondsSinceMidnight) = getDayOfWeekAndSecsSinceMidnight($orderObject->get('retailer')->get('airportIataCode'),
            $requestedFullFillmentTimestamp);

        // Find Order Items under the order
        $orderObjectModifier = parseExecuteQuery(array("order" => $orderObject), "OrderModifiers", "", "",
            array("retailerItem"));

        foreach ($orderObjectModifier as $objModifier) {

            // Find Item Properties
            $itemProperties = parseExecuteQuery(array(
                "uniqueRetailerItemId" => $objModifier->get('retailerItem')->get('uniqueId'),
                "dayOfWeek" => $dayOfWeekAtAirport,
                "isActive" => true
            ), "RetailerItemProperties", "", "",
                array("prepTimeCategoryGroup1", "prepTimeCategoryGroup2", "prepTimeCategoryGroup3"), 1);

            // Properties found for the day
            if (count_like_php5($itemProperties) > 0) {

                $itemPrepTime = 0;

                // If no prep time provided for Group 1, leads to no prep time properties taken
                if ($itemProperties->get('prepRestrictTimeInSecsStartGroup1') == -1
                    || $itemProperties->get('prepRestrictTimeInSecsStartGroup1') == 0
                ) {

                    continue;
                } // If current order time falls under Group 1 range
                else {
                    if ($itemProperties->get('prepRestrictTimeInSecsStartGroup1') <= $secondsSinceMidnight
                        && $itemProperties->get('prepRestrictTimeInSecsEndGroup1') >= $secondsSinceMidnight
                        && $itemProperties->has("prepTimeCategoryGroup1")
                    ) {

                        $itemPrepTime = $itemProperties->get("prepTimeCategoryGroup1")->get("prepTimeInSeconds");
                    } // If current order time falls under Group 2 range
                    else {
                        if ($itemProperties->get('prepRestrictTimeInSecsStartGroup2') <= $secondsSinceMidnight
                            && $itemProperties->get('prepRestrictTimeInSecsEndGroup2') >= $secondsSinceMidnight
                            && $itemProperties->has("prepTimeCategoryGroup2")
                        ) {

                            $itemPrepTime = $itemProperties->get("prepTimeCategoryGroup2")->get("prepTimeInSeconds");
                        } // If current order time falls under Group 3 range
                        else {
                            if ($itemProperties->get('prepRestrictTimeInSecsStartGroup3') <= $secondsSinceMidnight
                                && $itemProperties->get('prepRestrictTimeInSecsEndGroup3') >= $secondsSinceMidnight
                                && $itemProperties->has("prepTimeCategoryGroup3")
                            ) {

                                $itemPrepTime = $itemProperties->get("prepTimeCategoryGroup3")->get("prepTimeInSeconds");
                            }
                        }
                    }
                }

                // If item prep time is greater than retailer's max/avg prep time
                if ($itemPrepTime > $retailerMaxPrepTime) {

                    $retailerMaxPrepTime = $itemPrepTime;
                }
            }
        }
    }

    return $retailerMaxPrepTime;
}

function getOverrideAdjustmentForDeliveryTimeInSeconds($retailerObject, $toLocationObject)
{

    return intval(getCacheOverrideAdjustmentForDeliveryTimeInSeconds($retailerObject->get('airportIataCode'),
        $retailerObject->get('uniqueId'), $toLocationObject->getObjectId()));
}

function getItemRestrictTimes($uniqueRetailerItemId, $dayOfWeekAtAirport)
{

    $itemProperties = parseExecuteQuery(array(
        "uniqueRetailerItemId" => $uniqueRetailerItemId,
        "dayOfWeek" => $dayOfWeekAtAirport,
        "isActive" => true
    ), "RetailerItemProperties", "", "",
        array("prepTimeCategoryGroup1", "prepTimeCategoryGroup2", "prepTimeCategoryGroup3"), 1);

    // Properties found for the day
    if (count_like_php5($itemProperties) > 0) {

        return [
            $itemProperties->get('restrictOrderTimeInSecsStart'),
            $itemProperties->get('restrictOrderTimeInSecsEnd')
        ];
    }

    // Default if no row is found
    return [0, 0];
}

// Daylight savings safe
function getDayOfWeekAndSecsSinceMidnight($airportIataCode, $timestamp)
{

    // Find day of week at the Airport
    $currentTimeZone = date_default_timezone_get();
    $airportTimeZone = fetchAirportTimeZone($airportIataCode, $currentTimeZone);
    $adjustForDST = 0;

    date_default_timezone_set($airportTimeZone);

    // If time changed today
    if (date('I', strtotime('yesterday 7 PM')) != date('I', strtotime('today 7 PM'))) {

        // If DST as in 1 hr move ahead
        if (date('I', strtotime('today 7 PM')) == 1) {

            $adjustForDST = 1 * 60 * 60;
        } else {
            if (date('I', strtotime('yesterday 7 PM')) == 0) {

                $adjustForDST = -1 * 60 * 60;
            }
        }
    }

    $dayOfWeekAtAirport = intval(date("N", $timestamp));
    $secondsSinceMidnight = $timestamp - strtotime('Today 12:00:01 AM') + $adjustForDST;
    // $secondsSinceMidnight = strtotime('today 11:59:59 pm') - $timestamp + $adjustForDST;
    date_default_timezone_set($currentTimeZone);

    return [$dayOfWeekAtAirport, $secondsSinceMidnight];
}

// Time before we stop offering items if they have a restricted time window
function getBufferBeforeOrderTimeInSecondsRange()
{

    return $GLOBALS['env_bufferBeforeOrderTimeInSecondsRange'] * 60;
    // return 15 * 60;
}

// Prep time buffer
function getBufferForPrepTimeInSeconds()
{

    return $GLOBALS['env_bufferForPrepTimeInSeconds'] * 60;
    // return 5 * 60;
}

// delivery (for delivery) buffer
function getBufferForDeliveryTimeInSeconds()
{

    return $GLOBALS['env_bufferForDeliveryTimeInSeconds'] * 60;
    // return 5 * 60;
}

// If no walk time can be calculated, then use this buffer
function getDefaultDeliveryWalkTimeInSeconds()
{

    return $GLOBALS['env_defaultDeliveryWalkTimeInSeconds'] * 60;
    // return 15 * 60;
}

function getDefaultDeliveryFeesInCents($orderTotal)
{
    return $GLOBALS['env_DeliveryFeesInCentsDefault'];
}

function getDefaultPickupFeesInCents($orderTotal)
{
    return $GLOBALS['env_PickupFeesInCentsDefault'];
}

function getDeliveryFeesInCentsByIata($airportIataCode)
{
    $airportInfo = getAirportByIataCode($airportIataCode);
    $airportDeliveryFee = $airportInfo->get('deliveryFeeInCents');
    if (!empty_zero_allowed($airportDeliveryFee)) {
        return (int)$airportDeliveryFee;
    }
    return getDefaultDeliveryFeesInCents(0);
}

function getDeliveryFeesInCentsByIataForEmployee($airportIataCode)
{
    $airportInfo = getAirportByIataCode($airportIataCode);
    $airportEmployeeDeliveryFee = $airportInfo->get('employeeDeliveryFeeInCents');
    if (!empty_zero_allowed($airportEmployeeDeliveryFee)) {
        return (int)$airportEmployeeDeliveryFee;
    }
    return getDeliveryFeesInCentsByIata($airportIataCode);
}

function getPickupFeesInCentsByIata($airportIataCode)
{
    $airportInfo = getAirportByIataCode($airportIataCode);
    $airportPickupFee = $airportInfo->get('pickupFeeInCents');
    if (!empty_zero_allowed($airportPickupFee)) {
        return (int)$airportPickupFee;
    }
    return getDefaultPickupFeesInCents(0);
}

function getServiceFeePCTByIata($airportIataCode)
{
    $airportInfo = getAirportByIataCode($airportIataCode);
    $airportServiceFee = $airportInfo->get('serviceFeePCT');
    if (!empty_zero_allowed($airportServiceFee)) {
        return $airportServiceFee;
    }

    return $GLOBALS['env_ServiceFeePCT'];
}


function getFeeAfterCredits($feeInCents, $orderTotal, $user)
{

    list($creditAppliedMap, $appliedInCents, $wereReferralSignupCreditAppliedToThisOrder) = getAvailableUserCreditsViaMap($user,
        $orderTotal["TotalWithCoupon"] + $feeInCents, $orderTotal);
    $appliedInCentsToFee = $appliedInCents - $orderTotal["CreditsAppliedInCents"];

    // After credits
    $feesInCentsAfterCredits = $feeInCents - $appliedInCentsToFee;

    /*
    $creditsApplied = 0;

    // Check if there are any credits
    if(isset($orderTotal["CreditsAvailableInCents"])
        && $orderTotal["CreditsAvailableInCents"] > 0) {

        // Fee is more than Credits available
        if($feeInCents > $orderTotal["CreditsAvailableInCents"]) {

            $feeInCents = $feeInCents - $orderTotal["CreditsAvailableInCents"];
            $orderTotal["CreditsAvailableInCents"] = 0;
            $creditsApplied = $orderTotal["CreditsAvailableInCents"];
        }
        // Credits are more than Fee
        else {

            $orderTotal["CreditsAvailableInCents"] = $orderTotal["CreditsAvailableInCents"] - $feeInCents;
            $creditsApplied = $feeInCents;
            $feeInCents = 0;
        }
    }

    $feesInCentsAfterCredits = $feeInCents;
    */

    return [$feesInCentsAfterCredits, $appliedInCentsToFee, $creditAppliedMap];
}

function getDeliveryFeesInCents($retailerObject, $toLocationObject, $orderTotal = [], $serviceFee = 0, $feeInCents)
{

    $deliveryFeeInCents = $couponDeliveryFeeInCents = $feeInCents;

    /*
    $creditsApplied = 0;
    $couponDeliveryFeeInCents = $deliveryFeeInCents = getDefaultDeliveryFeesInCents($orderTotal) + $serviceFee;

    if(areCreditsApplicable('d')) {

        list($couponDeliveryFeeInCents, $creditsApplied, $creditAppliedMap) = getFeeAfterCredits($deliveryFeeInCents, $orderTotal, $user);

        // The new base fee is now the after credits fee
        $deliveryFeeInCents = $couponDeliveryFeeInCents;
    }
    */


    // Verify if there was a coupon for fullfillment fee
    if (count_like_php5($orderTotal) > 0
        && isset($orderTotal["CouponOrderMinMet"])
        && $orderTotal["CouponOrderMinMet"] == true
        && isset($orderTotal["CouponForFee"])
        && $orderTotal["CouponForFee"] == true
    ) {




        // Fixed fee
        if ($orderTotal["CouponForFeeFixed"] > 0) {

            // Check if after credits fee is greater than fixed fee coupon, else let the user pay the lower fee
            if ($couponDeliveryFeeInCents > $orderTotal["CouponForFeeFixed"]) {

                $couponDeliveryFeeInCents = $orderTotal["CouponForFeeFixed"];
            }
        } // PCT off
        else {

            $couponDeliveryFeeInCents = round($deliveryFeeInCents * (1 - $orderTotal["CouponForFeePCT"]), 2);
        }
    }

    return [dollar_format_float(min([$deliveryFeeInCents, $couponDeliveryFeeInCents]))];
}

function getDeliveryTimeInSeconds(
    $retailerObject,
    $toLocationObject,
    $orderObject = "",
    $requestedFullFillmentTimestamp = 0,
    $retailerMaxPrepTime = ""
) {

    $requestedFullFillmentTimestampOverriden = $fullfillmentTimeInSecondsOverriden = 0;

    if ($requestedFullFillmentTimestamp == 0) {

        $requestedFullFillmentTimestamp = time();
    }

    if (empty($retailerMaxPrepTime)) {

        $retailerMaxPrepTime = getOrderPrepTime($retailerObject, $orderObject, $requestedFullFillmentTimestamp);
    }

    $deliveryTimeInSeconds = '';

    if (!empty($toLocationObject)) {

        // Find the time it takes the delivery walk from Retailer location to Delivery Location
        // echo($retailerObject->get('location')->get("gateDisplayName") . " to " . $toLocationObject->get("gateDisplayName") . "<br />");


        // If from and to locations are same
        if ($retailerObject->get('location')->getObjectId() == $toLocationObject->getObjectId()) {

            $directionsFromRetailerToDeliveryLocation["walkingTime"] = 0.1; // 6 secs
            // $directionsFromRetailerToDeliveryLocation["totalDistanceMetricsForTrip"]["walkingTime"] = 0.1; // 6 secs
        } else {

            $retailerObject->fetch();
            $retailerObject->get('location')->fetch();
            $directionsFromRetailerToDeliveryLocation = getDistanceMetrics($toLocationObject->get('terminal'),
                $toLocationObject->get('concourse'), $toLocationObject->get('gate'),
                $retailerObject->get('location')->get('terminal'), $retailerObject->get('location')->get('concourse'),
                $retailerObject->get('location')->get('gate'), true, $retailerObject->get('airportIataCode'));

            $directionsFromRetailerToDeliveryLocation["walkingTime"] = $directionsFromRetailerToDeliveryLocation["walkingTimeToGate"];
            // $directionsFromRetailerToDeliveryLocation = getDirections($retailerObject->get('airportIataCode'), $retailerObject->get('location')->getObjectId(), $toLocationObject->getObjectId());
        }

        // if (isset($directionsFromRetailerToDeliveryLocation["totalDistanceMetricsForTrip"]["walkingTime"])) {

        //     $deliveryTimeInSeconds = $directionsFromRetailerToDeliveryLocation["totalDistanceMetricsForTrip"]["walkingTime"] * 60;
        // }

        if (isset($directionsFromRetailerToDeliveryLocation["walkingTime"])) {

            $deliveryTimeInSeconds = $directionsFromRetailerToDeliveryLocation["walkingTime"] * 60;
        }

        /*
        $directionsFromRetailerToDeliveryLocation = getDistanceMetrics(
                            $retailerObject->get('location')->get('terminal'),
                            $retailerObject->get('location')->get('concourse'),
                            $retailerObject->get('location')->get('gate'),
                            $toLocationObject->get('terminal'),
                            $toLocationObject->get('concourse'),
                            $toLocationObject->get('gate'),
                            true,
                            $retailerObject->get('airportIataCode'));

        if(isset($directionsFromRetailerToDeliveryLocation["walkingTimeToGate"])) {

            $deliveryTimeInSeconds = $directionsFromRetailerToDeliveryLocation["walkingTimeToGate"] * 60;
        }
        */
    }

    if (empty($deliveryTimeInSeconds)) {

        // Since no order information is available, use an estimated delivery walk time to delivery location
        // Or no delivery directions were found, use the default
        $deliveryTimeInSeconds = getDefaultDeliveryWalkTimeInSeconds();
    }

    // Add buffer time for prep
    $bufferForPrepTimeInSeconds = getBufferForPrepTimeInSeconds();

    // Add buffer time for delivery walking over
    $bufferForDeliveryTimeInSeconds = getBufferForDeliveryTimeInSeconds();

    // Time Retailer will take to prepare order
    $retailerPrepTimeInSeconds = ($retailerMaxPrepTime + $bufferForPrepTimeInSeconds);

    // Time we will take deliver order
    $deliveryTimeInSeconds = ($deliveryTimeInSeconds + $bufferForDeliveryTimeInSeconds);

    $fullfillmentTimeInSeconds = $retailerPrepTimeInSeconds + $deliveryTimeInSeconds;
    $fullfillmentProcessTimeInSeconds = $deliveryTimeInSeconds;

    // Add/Substract any known override time adjustment
    $overrideAdjustmentForDeliveryTimeInSeconds = 0;

    if (!empty($retailerObject)) {

        $overrideAdjustmentForDeliveryTimeInSeconds = getOverrideAdjustmentForDeliveryTimeInSeconds($retailerObject,
            $toLocationObject);

        // Limit the reduction of time
        // Only apply adjustment if it is not higher than overall delivery + 5 mins
        // Ensures the delivery time is atleast 5 mins
        if (
            ($overrideAdjustmentForDeliveryTimeInSeconds < 0
                && ((-1 * $overrideAdjustmentForDeliveryTimeInSeconds) < ($fullfillmentProcessTimeInSeconds - 5 * 60)))
            ||
            ($overrideAdjustmentForDeliveryTimeInSeconds > 0)
        ) {

            $fullfillmentTimeInSeconds = $fullfillmentTimeInSeconds + $overrideAdjustmentForDeliveryTimeInSeconds;

            $fullfillmentProcessTimeInSeconds = $fullfillmentProcessTimeInSeconds + $overrideAdjustmentForDeliveryTimeInSeconds;
        }
    }

    // Verify if this location has override rules
    if (!empty($toLocationObject)
        && $toLocationObject->has('deliveryLimitedToPerHourOffset')
        && $toLocationObject->get('deliveryLimitedToPerHourOffset') > 0
    ) {

        // List in mins how much time per 60 mins we should offset
        // A value of 0 means every top of the hour
        $deliveryLimitedToPerHourOffset = intval($toLocationObject->get('deliveryLimitedToPerHourOffset'));

        // Assume deliveryLimitedToPerHourOffsetMin as fullfillmentTimeInSeconds to calculate next delivery time
        $numberOfDeliveriesPerHour = floor(60 / $deliveryLimitedToPerHourOffset);

        // Calculat expected ETA timestamp
        $expectedETATimestamp = $requestedFullFillmentTimestamp + $fullfillmentTimeInSeconds;

        $currentTimeZone = date_default_timezone_get();
        $airportTimeZone = fetchAirportTimeZone($retailerObject->get('airportIataCode'), $currentTimeZone);
        if (strcasecmp($airportTimeZone, $currentTimeZone) != 0) {

            // Set Airport Timezone
            date_default_timezone_set($airportTimeZone);
        }

        $minsToPreviousTopOfHour = date("i", $expectedETATimestamp);

        if (strcasecmp($airportTimeZone, $currentTimeZone) != 0) {

            date_default_timezone_set($currentTimeZone);
        }

        $timestampToPreviousTopOfHour = $expectedETATimestamp - ($minsToPreviousTopOfHour * 60);

        $availableETATimestamp[0] = $timestampToPreviousTopOfHour + ($deliveryLimitedToPerHourOffset * 60);
        for ($i = 1; $i <= $numberOfDeliveriesPerHour; $i++) {

            $availableETATimestamp[$i] = $availableETATimestamp[$i - 1] + ($deliveryLimitedToPerHourOffset * 60);
        }

        $timestampOfOverriden = 0;
        foreach ($availableETATimestamp as $timestamp) {

            if ($timestamp < $expectedETATimestamp) {

                continue;
            }

            // Use this time
            $timestampOfOverriden = $timestamp;
            break;
        }

        if ($timestampOfOverriden > 0) {

            // This time will be shown as total estimate
            $fullfillmentTimeInSecondsOverriden = $timestampOfOverriden - $requestedFullFillmentTimestamp;

            // New Requested Timestamp
            $requestedFullFillmentTimestampOverriden = $timestampOfOverriden;

            // New total delivery time will be new estimated time minus requested timestamp
            // $fullfillmentTimeInSeconds = $timestampOfOverriden-$requestedFullFillmentTimestamp;

            // process time in seconds, recalculate
            // $fullfillmentProcessTimeInSeconds = $fullfillmentTimeInSeconds - $retailerPrepTimeInSeconds;
        }
    }

    return [
        $fullfillmentTimeInSeconds,
        $fullfillmentProcessTimeInSeconds,
        $fullfillmentTimeInSecondsOverriden,
        $requestedFullFillmentTimestampOverriden
    ];
}


function getPickupTimeInSeconds(
    $retailerObject,
    $orderObject = "",
    $requestedFullFillmentTimestamp = 0,
    $retailerMaxPrepTime = "",
    $posConfigObject = ""
) {

    if ($requestedFullFillmentTimestamp == 0) {

        $requestedFullFillmentTimestamp = time();
    }

    if (empty($retailerMaxPrepTime)) {

        $retailerMaxPrepTime = getOrderPrepTime($retailerObject, $orderObject, $requestedFullFillmentTimestamp,
            $posConfigObject);
    }

    // Add buffer time
    $bufferForPrepTimeInSeconds = getBufferForPrepTimeInSeconds();

    return [($retailerMaxPrepTime + $bufferForPrepTimeInSeconds), 0];
}

function getPickupFeesInCents($retailerObject, $orderTotal = [], $serviceFee = 0, $feeInCents)
{

    $pickupFeeInCents = $couponPickupFeeInCents = $feeInCents;
    /*
    $creditsApplied = 0;
    $couponPickupFeeInCents = $pickupFeeInCents = getDefaultPickupFeesInCents($orderTotal) + $serviceFee;

    if(areCreditsApplicable('p')) {

        list($couponPickupFeeInCents, $creditsApplied) = getFeeAfterCredits($pickupFeeInCents, $orderTotal);

        // The new base fee is now the after credits fee
        $pickupFeeInCents = $couponPickupFeeInCents;
    }*/

    // Verify if there was a coupon for fullfillment
    if (count_like_php5($orderTotal) > 0
        && isset($orderTotal["CouponOrderMinMet"])
        && $orderTotal["CouponOrderMinMet"] == true
        && isset($orderTotal["CouponForFee"])
        && $orderTotal["CouponForFee"] == true
    ) {
        // Fixed fee
        if ($orderTotal["CouponForFeeFixed"] > 0) {

            // Check if after credits fee is greater than fixed fee coupon, else let the user pay the lower fee
            if ($couponPickupFeeInCents > $orderTotal["CouponForFeeFixed"]) {

                $couponPickupFeeInCents = $orderTotal["CouponForFeeFixed"];
            }
        } // PCT off
        else {
            $couponPickupFeeInCents = round($pickupFeeInCents * (1 - $orderTotal["CouponForFeePCT"]), 2);
        }
    }
    // JMD
    // JMD
    return [dollar_format_float(min([$pickupFeeInCents, $couponPickupFeeInCents]))];
}

// TODO:
function isDeliveryAvailableForDelivery($deliveryLocation, $requestedFullFillmentTimestamp)
{
    // Return first element which is the availability status
    return isDeliveryFromSlackAvailableForDelivery($deliveryLocation, $requestedFullFillmentTimestamp)[0];

    // return true;
}

// TODO:
function getDeliveryAvailableForDelivery($deliveryLocation, $requestedFullFillmentTimestamp, $forAssignment = false)
{

    return isDeliveryFromSlackAvailableForDelivery($deliveryLocation, $requestedFullFillmentTimestamp, $forAssignment);
}

function isAnyDeliveryFromSlackAvailable($deliveryLocation, $requestedFullFillmentTimestamp)
{

    // To do: Use requestedFullFillmentTimestamp to predict when the delivery will be available and hence can deliver this order
    // Currently we only check currently active assignments instead of by when they will be available

    if (empty($deliveryLocation)) {

        return [false, ""];
    }

    // Find if there is a specific delivery user who can delivery to this location
    $deliveryUserIdsRequiredToBeOnline = [];
    $zDeliverySlackUserLocationRestrictions = parseExecuteQuery(["location" => $deliveryLocation],
        "zDeliverySlackUserLocationRestrictions");


    if (count_like_php5($zDeliverySlackUserLocationRestrictions) > 0) {

        foreach ($zDeliverySlackUserLocationRestrictions as $deliveryUserNeeded) {

            $deliveryUserIdsRequiredToBeOnline[] = $deliveryUserNeeded->get('deliveryUser')->getObjectId();
        }
    }
    // Find all Deliverys for this airport
    $slackDelivery = parseExecuteQuery([
        "airportIataCode" => $deliveryLocation->get('airportIataCode'),
        "isActive" => true
    ], "zDeliverySlackUser");
    $availableDeliveryPerson = [];

    foreach ($slackDelivery as $deliveryUser) {

        $isActive = false;

        // Check if delivery is inactive, skip
        if (isDeliveryPingActive($deliveryUser->getObjectId())) {
            $isActive = true;
        }

        // If we needed a specific user to be online but wasn't
        if (count_like_php5($deliveryUserIdsRequiredToBeOnline) > 0
            && !in_array($deliveryUser->getObjectId(), $deliveryUserIdsRequiredToBeOnline)
        ) {

            $isActive = false;
        }

        if ($isActive == true) {

            return [true, ""];
        }
    }

    return [false, getDefaultErrorMessageForNoDelivery()];
}

function isDeliveryFromSlackAvailableForDelivery(
    $deliveryLocation,
    $requestedFullFillmentTimestamp,
    $forAssignment = false
) {

    // To do: Use requestedFullFillmentTimestamp to predict when the delivery will be available and hence can deliver this order
    // Currently we only check currently active assignments instead of by when they will be available

    // To do: Add mechanism to reserve the delivery for this order for 2 mins
    // After which reservation expires
    // In order submission, verify if a reserveration + 1 min (for order processing) is valid
    // If not, try to reserve a new delivery
    // If unsuccessful, then throw an error saying no deliverys available
    if (empty($deliveryLocation)) {

        return setGlobalsForDeliveryAvailable("", false, "");
    }

    // TEMP: To allow /fullfillment for all retailers to use the same availability of delivery
    $last_runValues = getGlobalsForDeliveryAvailable($deliveryLocation->get('airportIataCode'));
    if (count_like_php5($last_runValues) > 0
        && $forAssignment == false
    ) {

        return [$last_runValues[0], $last_runValues[1]];
    }
    //////////////////

    // Find if there is a specific delivery user who can delivery to this location
    $deliveryUserIdsRequiredToBeOnline = [];
    $zDeliverySlackUserLocationRestrictions = parseExecuteQuery(["location" => $deliveryLocation],
        "zDeliverySlackUserLocationRestrictions");

    if (count_like_php5($zDeliverySlackUserLocationRestrictions) > 0) {

        foreach ($zDeliverySlackUserLocationRestrictions as $deliveryUserNeeded) {

            $deliveryUserIdsRequiredToBeOnline[] = $deliveryUserNeeded->get('deliveryUser')->getObjectId();
        }
    }

    // Find all delivery persons for this airport
    $slackDelivery = parseExecuteQuery([
        "airportIataCode" => $deliveryLocation->get('airportIataCode'),
        "isActive" => true
    ], "zDeliverySlackUser");
    $availableDeliveryPerson = [];
    $activeDeliveryUserFound = false;;
    foreach ($slackDelivery as $deliveryUser) {

        // TODO: Convert this to a cached Leaderboard and use that to check availability and reserve delivery person

        // If we needed a specific user to be online but wasn't
        if (count_like_php5($deliveryUserIdsRequiredToBeOnline) > 0
            && !in_array($deliveryUser->getObjectId(), $deliveryUserIdsRequiredToBeOnline)
        ) {

            continue;
        }

        // Check if delivery is inactive, skip
        if (!isDeliveryPingActive($deliveryUser->getObjectId())) {

            continue;
        }

        $activeDeliveryUserFound = true;

        // Get count of active orders for this delivery person
        $zDeliverySlackOrderAssignmentCount = getDeliveryActiveOrderCount($deliveryLocation->get('airportIataCode'),
            $deliveryUser);

        // If active assignments for delivery are less than 10, they can be assigned
        if ($zDeliverySlackOrderAssignmentCount < intval($GLOBALS['env_DeliveryMaxActiveOrders'])) {

            $availableDeliveryPerson[$zDeliverySlackOrderAssignmentCount] = $deliveryUser;
        }
    }

    if (count_like_php5($availableDeliveryPerson) > 0) {

        // Sort delivery persons per their total active assignments ascending
        // Sort only if more than one delivery available
        if (count_like_php5($availableDeliveryPerson) > 1) {

            ksort($availableDeliveryPerson);
        }

        // print_r($availableDeliveryPerson);exit;
        // Return the delivery with the least active orders
        return setGlobalsForDeliveryAvailable($deliveryLocation->get('airportIataCode'), true,
            array_values($availableDeliveryPerson)[0]);
    } else {
        if ($activeDeliveryUserFound) {

            json_error("AS_892", "", "Delivery users online but have reach max orders hence delivery now disabled!", 1,
                1);
        }
    }

    // No available delivery found
    return setGlobalsForDeliveryAvailable($deliveryLocation->get('airportIataCode'), false, "");
}

function getDeliveryActiveOrderCount($airportIataCode, $deliveryUser)
{

    // Find all retailers for this airport
    $retailerRefObject = new ParseQuery("Retailers");
    $retailerAssociation = parseSetupQueryParams(["airportIataCode" => $airportIataCode], $retailerRefObject);

    // Find all active delivery orders
    $orderRefObject = new ParseQuery("Order");
    $orderAssociation = parseSetupQueryParams([
        "__MATCHESQUERY__retailer" => $retailerAssociation,
        "fullfillmentType" => "d",
        "status" => listStatusesForPendingInProgress()
    ], $orderRefObject);

    // Find all delivery perons and their counts of orders
    $zDeliverySlackOrderAssignmentCount = parseExecuteQuery([
        "deliveryUser" => $deliveryUser,
        "__MATCHESQUERY__order" => $orderAssociation
    ], "zDeliverySlackOrderAssignments", "", "", [], 1, false, [], 'count');

    return $zDeliverySlackOrderAssignmentCount;
}

function setGlobalsForDeliveryAvailable($airportIataCode, $isAvailable, $delivery)
{

    setCacheForDeliveryAvailable($airportIataCode, ["isAvailable" => $isAvailable, "delivery" => $delivery]);

    // $GLOBALS['isDeliveryAvailable_isAvailable'] = $isAvailable;
    // $GLOBALS['isAvailable_deliveryPerson'] = $delivery;

    return [$isAvailable, $delivery];
}

function getGlobalsForDeliveryAvailable($airportIataCode)
{
    $array = getCacheForDeliveryAvailable($airportIataCode);

    if (!isset($array['isDeliveryAvailable_isAvailable'])) {

        return $array;
    } else {

        return [];
    }
}

function isDeliveryPingActive($deliveryUserId)
{
    // skipeed
    return true;


    $lastSuccessfulPingTimestamp = getSlackDeliveryPingTimestamp($deliveryUserId);
    // If lastSucessful Ping was within Multiplier of env_PingSlackDeliveryIntervalInSecs

    if ($lastSuccessfulPingTimestamp > (time() - (intval($GLOBALS['env_PingSlackGraceMultiplier']) * intval($GLOBALS['env_PingSlackDeliveryIntervalInSecs'])))) {

        return true;
    } else {

        return false;
    }
}

function orderStatusType($status)
{

    if (empty($status)) {

        return "";
    }

    // Ensure the status is not of internal type
    if (!isOrderStatusInternal($status)) {

        return $GLOBALS['statusNames'][$status]['type'];
    } else {

        return "";
    }
}

// Provides the statusCodes for statuses for Abandoned Cart
function listStatusesForAbandonedCart()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (in_array($status, [100])) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type = CART
function listStatusesForCart()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "CART") == 0) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type = CART
function listStatusesForAcceptedByRetailer()
{

    return [5];
}

// Provides the statusCodes for statuses of type = SUBMITTED
function listStatusesForSubmitted($list_only_inform_user = false)
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "SUBMITTED") == 0) {

            if ($list_only_inform_user == false
                || ($list_only_inform_user == true && $GLOBALS['statusNames'][$status]['inform_user'] == true)
            ) {

                $returnArray[] = $status;
            }
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type = FULLFILLED
function listStatusesForSuccessCompleted()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "FULLFILLED") == 0) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type = NOT_FULLFILLED
function listStatusesForCancelled($list_only_inform_user = false)
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "NOT_FULLFILLED") == 0) {

            if ($list_only_inform_user == false
                || ($list_only_inform_user == true && $GLOBALS['statusNames'][$status]['inform_user'] == true)
            ) {

                $returnArray[] = $status;
            }
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type != INTERNAL
function listStatusesForNonInternal()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "INTERNAL") != 0) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type == INTERNAL
function listStatusesForInternal()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "INTERNAL") == 0) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type == INTERNAL
function listStatusesForConfirmedByTablet()
{

    return [12];
}

// Provides the statusCodes for statuses of type = PENDING, IN_PROGRESS, SUBMITTED, AWAITING_CONFIRMATION
function listStatusesForPendingInProgress()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "PENDING") == 0
            || strcasecmp($GLOBALS['statusNames'][$status]['type'], "AWAITING_CONFIRMATION") == 0
            || strcasecmp($GLOBALS['statusNames'][$status]['type'], "IN_PROGRESS") == 0
            || strcasecmp($GLOBALS['statusNames'][$status]['type'], "SUBMITTED") == 0
        ) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type = IN_PROGRESS, AWAITING_CONFIRMATION
function listStatusesForInProgress()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "IN_PROGRESS") == 0
            || strcasecmp($GLOBALS['statusNames'][$status]['type'], "AWAITING_CONFIRMATION") == 0
        ) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides boolean for is Order cancelled
function isOrderCancelled($order)
{

    if (count_like_php5($order) > 0
        && in_array($order->get('status'), listStatusesForCancelled())
    ) {

        return true;
    }

    return false;
}

// Provides the statusCodes for statuses of accepted by retailer
function listStatusesForConfirmedByRetailer()
{

    $returnArray = [5];

    return $returnArray;
}

// Provides the statusCodes for statuses of type != iNTERNAL and != CART and !=IN_PROGRESS
function listStatusesForScheduled()
{

    $returnArray = [8];

    return $returnArray;
}

// Provides the statusCodes for statuses of type != iNTERNAL and != CART and !=IN_PROGRESS
function listStatusesForSubmittedOrAwaitingConfirmation()
{

    $returnArray = array_unique(array_merge(listStatusesForAwaitingConfirmation(), listStatusesForSubmitted()));

    return $returnArray;
}

// Provides the statusCodes for statuses of type != iNTERNAL and != CART
function listStatusesForAwaitingConfirmation()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "AWAITING_CONFIRMATION") == 0) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type != iNTERNAL and != CART
function listStatusesForNonInternalNonCart()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "INTERNAL") != 0
            && strcasecmp($GLOBALS['statusNames'][$status]['type'], "CART") != 0
        ) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for statuses of type != iNTERNAL and != CART and != NOT_FULLFILLED
function listStatusesForCouponValidation()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if (strcasecmp($GLOBALS['statusNames'][$status]['type'], "INTERNAL") != 0
            && strcasecmp($GLOBALS['statusNames'][$status]['type'], "CART") != 0
            && strcasecmp($GLOBALS['statusNames'][$status]['type'], "NOT_FULLFILLED") != 0
        ) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the statusCodes for Cancellable Order
function listStatusesForCancellableOrder()
{

    $returnArray = [
        "3", // Payment Accepted
        "4", // Awaiting Confirmation
        "5", // Accepted by Retailer
        "8", // Scheduled
        "9", // Retailer has prepared order / Order Ready
        "11" // Needs Review
    ];

    return $returnArray;
}

// Provides the statusCodes for Greater Than Submitted
function listStatusesForGreaterThanSubmittedOrder()
{

    $returnArray = [
        2, // Submitted
        3, // Payment Accepted
        4, // Awaiting Confirmation
        5, // Accepted by Retailer
        8, // Scheduled
        9, // Retailer has prepared order / Order Ready
        10,// Completed
        11 // Needs Review
    ];

    return $returnArray;
}

// Provides the statusCodes for statuses that are set to inform user
function listStatusesForInformUser()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusNames']) as $status) {

        if ($GLOBALS['statusNames'][$status]['inform_user'] == true) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

// Provides the delivery statusCodes for statuses that are set to inform user
function listDeliveryStatusesForInformUser()
{

    $returnArray = array();
    foreach (array_keys($GLOBALS['statusDeliveryNames']) as $status) {

        if ($GLOBALS['statusDeliveryNames'][$status]['inform_user'] == true) {

            $returnArray[] = $status;
        }
    }

    return $returnArray;
}

function isOrderStatusInternal($status)
{

    // If status is internal or cart
    if (isset($GLOBALS['statusNames'][$status]['type'])
        && (strcasecmp($GLOBALS['statusNames'][$status]['type'], "INTERNAL") == 0
            || strcasecmp($GLOBALS['statusNames'][$status]['type'], "CART") == 0)
    ) {

        return true;
    }

    return false;
}

function formatDateByTimezone($timezone, $timestamp, $dateFormat = 'M-j-Y g:i A')
{

    $currentTimeZone = date_default_timezone_get();

    // Set Airport Timezone
    date_default_timezone_set($timezone);

    // Format timestamp
    $formatedDateTime = date($dateFormat, $timestamp);

    // Set Default Timezone
    date_default_timezone_set($currentTimeZone);

    return $formatedDateTime;
}

function getOrderFullfillmentTimeRangeEstimateDisplay($fullfillmentTimeInSecs)
{
    /** @see \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay() **/

    if (empty($fullfillmentTimeInSecs)) {

        return ["", 0, 0];
    }

    $rangeLowInSecs = $GLOBALS['env_fullfillmentETALowInSecs'];
    $rangeHighInSecs = $GLOBALS['env_fullfillmentETAHighInSecs'];

    $fullfillmentTimeLowInSecs = $fullfillmentTimeInSecs - $rangeLowInSecs;
    $fullfillmentTimeHighInSecs = $fullfillmentTimeInSecs + $rangeHighInSecs;

    $displayText = floor($fullfillmentTimeLowInSecs / 60) . " - " . floor($fullfillmentTimeHighInSecs / 60) . " mins";

    return [$displayText, $fullfillmentTimeLowInSecs, $fullfillmentTimeHighInSecs];
}

function orderFormatDate($timezone, $timestamp, $dateOrTime = 'auto', $addTimezone = 0, $currentTimeZone = "")
{

    if (empty($currentTimeZone)) {

        $currentTimeZone = date_default_timezone_get();
    }

    if (strcasecmp($currentTimeZone, $timezone) != 0) {

        // Set Airport Timezone
        date_default_timezone_set($timezone);
    }

    $dateFormatDateTime = "M-j-y g:i A";
    $dateFormatDate = "M-j-y";
    $dateFormatTime = "g:i A";

    if (strcasecmp($dateOrTime, 'auto') == 0) {

        // If the provided timestamp has a different day from today, then use a date format with date
        // If year is different
        if (strcasecmp(date("y", $timestamp), date("y")) != 0) {

            // Show Date + time
            $dateFormat = $dateFormatDateTime;
        } // Else if month or date is different
        else {
            if (strcasecmp(date("m", $timestamp), date("m")) != 0
                || strcasecmp(date("j", $timestamp), date("j")) != 0
            ) {

                // Show Date + time
                $dateFormat = "M-j g:i A";
            } // else just show time
            else {

                // Show Time only
                $dateFormat = $dateFormatTime;
            }
        }

        if ($addTimezone == 1) {

            $dateFormat .= " T";
        }
    } else {
        if (strcasecmp($dateOrTime, 'date') == 0) {

            // Show Date only
            $dateFormat = $dateFormatDate;
        } else {
            if (strcasecmp($dateOrTime, 'time') == 0) {

                // Show Time only
                $dateFormat = $dateFormatTime;

                if ($addTimezone == 1) {

                    $dateFormat .= " T";
                }
            } else {
                if (strcasecmp($dateOrTime, 'both') == 0) {

                    // Show Date & Time
                    $dateFormat = $dateFormatDateTime;

                    if ($addTimezone == 1) {

                        $dateFormat .= " T";
                    }
                }
            }
        }
    }

    // Format timestamp
    $formatedDateTime = date($dateFormat, $timestamp);

    if (strcasecmp($currentTimeZone, $timezone) != 0) {

        // Set Default Timezone
        date_default_timezone_set($currentTimeZone);
    }

    return $formatedDateTime;
}

// Used for generating print ready status
function orderStatusToPrint($order, $status = "", $statusDelivery = "")
{

    if (empty($status)) {

        $status = $order->get('status');
        $statusDelivery = $order->get('statusDelivery');
    }

    $fullfillmentType = $order->get('fullfillmentType');

    // If completed order
    if (strcasecmp(orderStatusType($status), "FULLFILLED") == 0) {

        $statusToPrint = $GLOBALS['statusCompleted'][$fullfillmentType];
    } // Check if a print ready name exists
    else {
        if (!empty(($GLOBALS['statusNames'][$status]["print"]))) {

            // Use the avilable print status
            $statusToPrint = $GLOBALS['statusNames'][$status]["print"];

            // If delivery order and a status value is available
            // then use this
            if (strcasecmp($fullfillmentType, "d") == 0
                && !empty($GLOBALS['statusDeliveryNames'][$statusDelivery]["print"])
                && strcasecmp(orderStatusType($status), "NOT_FULLFILLED") != 0
            ) {

                $statusToPrint = $GLOBALS['statusDeliveryNames'][$statusDelivery]["print"];
            }
        } // No status should be provided
        else {

            $statusToPrint = "";
        }
    }

    return strtoupper($statusToPrint);
}

// Used for generating status category
function orderStatusCategory($order, $status = "", $statusDelivery = "")
{

    if (empty($status)) {

        $status = $order->get('status');
        $statusDelivery = $order->get('statusDelivery');
    }

    $fullfillmentType = $order->get('fullfillmentType');

    $statusCategoryCode = $GLOBALS['statusNames'][$status]["statusCategoryCode"];

    // If delivery order and a status value is available
    if (strcasecmp($fullfillmentType, "d") == 0
        && !empty($GLOBALS['statusDeliveryNames'][$statusDelivery]["statusCategoryCode"])
        && strcasecmp(orderStatusType($status), "NOT_FULLFILLED") != 0
    ) {

        $statusCategoryCode = $GLOBALS['statusDeliveryNames'][$statusDelivery]["statusCategoryCode"];
    }

    return $statusCategoryCode;
}

/*
function getOrderProcessDelayInSecondsForSQS($requestedFullfillmentStartTimestamp) {

    $delayTimeInSeconds = 0;
    $currentTimestamp = time();
    $sqsMaxDelayInSeconds = 15 * 60;

    // If Scheduled/request fullfillment START timestamp > current time
    if($requestedFullfillmentStartTimestamp > $currentTimestamp) {

        // If Scheduled/request fullfillment START timestamp > current time + max delay allowed by SQS
        // Then use the max delay
        if($requestedFullfillmentStartTimestamp > ($currentTimestamp+$sqsMaxDelayInSeconds)) {

            $delayTimeInSeconds = $sqsMaxDelayInSeconds;
        }

        // Else use the difference
        else {

            $delayTimeInSeconds = $requestedFullfillmentStartTimestamp - $currentTimestamp;
        }
    }

    return $delayTimeInSeconds;
}

function getOrderPickupCompleteDelayInSecondsForSQS($etaTimestamp) {

    $delayTimeInSeconds = 0;
    $currentTimestamp = time();

    $sqsMaxDelayInSeconds = 15 * 60;

    // If etaTimestamp > current time
    if($etaTimestamp > $currentTimestamp) {

        // If etaTimestamp > current time + max delay allowed by SQS
        // Then use the max delay
        if($etaTimestamp > ($currentTimestamp+$sqsMaxDelayInSeconds)) {

            $delayTimeInSeconds = $sqsMaxDelayInSeconds;
        }

        // Else use the difference
        else {

            $delayTimeInSeconds = $etaTimestamp - $currentTimestamp;
        }
    }

    return $delayTimeInSeconds;
}
*/

// Step 0 - Scheduled
function orderStatusChange_Scheduled(&$order)
{

    $order->set("status", 8);
    addOrderStatus($order);
}

// Step 1 - Submitted
function orderStatusChange_Submitted(&$order)
{

    $order->set("status", 2);
    addOrderStatus($order);
}

// Step 2 - Payment Accepted
function orderStatusChange_PaymentAccepted(&$order)
{

    $order->set("status", 3);
    addOrderStatus($order);
}

// Step 3 - POS Ticket pushed / Printer Job pushed
function orderStatusChange_PushedToRetailer(&$order)
{

    $order->set("status", 4);
    addOrderStatus($order);
}

// Step 4A - Tablet Acknowledged / for DualConfig Retailer
function orderStatusChange_ConfirmedByTablet(&$order)
{

    $order->set("status", 12);
    addOrderStatus($order);

    try {

        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue->sendMessage(
            array(
                "action" => "order_submission_process_dualconfig",
                "content" =>
                    array(
                        "orderId" => $order->getObjectId()
                    )
            ), 5 // secs later
        );
    } catch (Exception $ex) {

        json_error("AS_1062", "",
            "Order processing to push to Dual Config retailer failed! Order Id = " . $order->getObjectId() . " - " . $ex->getMessage(),
            1, 1);
    }
}

// Step 4 - Retailer Acknowledged / Printer Job printed
function orderStatusChange_ConfirmedByRetailer(&$order)
{
    $order->set("status", 5);
    addOrderStatus($order);
    $order->fetch();
    $order->get("retailer")->fetch();
    $order->get("retailer")->get("location")->fetch();

    //$message = "Your order has been confirmed by " . $order->get("retailer")->get("retailerName") . ". It's now being prepared.";
    $message = "your order has been confirmed by " . $order->get("retailer")->get("retailerName") . " at ".$order->get("retailer")->get("location")->get('locationDisplayName').'.';

    if ($order->get('fullfillmentType')=='p'){
        $message  = $message . '
Please go straight to the pickup area instead of waiting in line.';
    }


    $response = sendOrderNotification($order, $message);

    if (is_array($response)) {

        return $response;
    }

    return "";
}

// Cancel order by Ops
function orderStatusChange_CancelledByOps(&$order)
{

    $order->set("status", 6);
    addOrderStatus($order);

    $message = "We are sorry but your order for " . $order->get("retailer")->get("retailerName") . " has been canceled. Please contact customer service for support.";

    $response = sendOrderNotification($order, $message);

    if (is_array($response)) {

        return $response;
    }

    return "";
}

// Step Final - Order Completed
function orderStatusChange_Completed(&$order)
{

    $order->set("status", 10);
    addOrderStatus($order);

    // If Delivery order
    if (strcasecmp($order->get('fullfillmentType'), "d") == 0) {

        $message = "We hope you enjoyed using AtYourGate.";
        //"Please take a moment to rate our service.";
    } else {

        //$message = "Your order is ready for pickup at " . $order->get('retailer')->get('retailerName') . ".";
        $message = "we hope you have picked up your order from  " . $order->get('retailer')->get('retailerName') . ". If you need assistance please contact us at (430) 400-4283";
    }

    $response = sendOrderNotification($order, $message);

    if (is_array($response)) {

        return $response;
    }

    return "";
}

// Step - Abandon / Close Cart order
function orderStatusChange_Abandon(&$order)
{

    $order->set("status", 100);
    addOrderStatus($order);
}


// Get Status code for - Find Delivery
function getOrderStatusDeliveryFindDelivery()
{

    return 1;
}

// Delivery Step 1 - Find Delivery
function deliveryStatusChange_FindDelivery(&$order)
{

    $order->set("statusDelivery", getOrderStatusDeliveryFindDelivery());
    addOrderStatus($order);

    return "";
}

// Get Status code for - Assigned Delivery
function getOrderStatusDeliveryAssignedDelivery()
{

    return 2;
}

// Delivery Step 2 - Assigned Delivery
function deliveryStatusChange_AssignedDelivery(&$order)
{

    $order->set("statusDelivery", getOrderStatusDeliveryAssignedDelivery());
    addOrderStatus($order);

    return "";
}

// Get Status code for - Accepted by Delivery
function getOrderStatusDeliveryAcceptedDelivery()
{

    return 3;
}

// Delivery Step 3 - Accepted by Delivery
function deliveryStatusChange_AcceptedDelivery(&$order)
{

    $order->set("statusDelivery", getOrderStatusDeliveryAcceptedDelivery());
    addOrderStatus($order);

    return "";
}

// Get Status code for - Arrived Delivery
function getOrderStatusDeliveryArrivedDelivery()
{

    return 4;
}

// Delivery Step 4 - delivery Arrived
function deliveryStatusChange_ArrivedDelivery(&$order)
{

    $order->set("statusDelivery", getOrderStatusDeliveryArrivedDelivery());
    addOrderStatus($order);

    return "";
}

// Get Status code for - Pickup up by Delivery
function getOrderStatusDeliveryPickedupByDelivery()
{

    return 5;
}

// Delivery Step 5 - delivery Picked up
function deliveryStatusChange_PickedupByDelivery(&$order)
{

    $order->set("statusDelivery", getOrderStatusDeliveryPickedupByDelivery());
    addOrderStatus($order);

    return "";
}

// Get Status code for - At Delivery Location
function getOrderStatusDeliveryAtDeliveryLocationDelivery()
{

    return 6;
}

// Delivery Step 6 - delivery at Delivery Location
function deliveryStatusChange_AtDeliveryLocationDelivery(&$order)
{

    $order->set("statusDelivery", getOrderStatusDeliveryAtDeliveryLocationDelivery());
    addOrderStatus($order);

    return "";
}

// Get Status code for - Assigned and Arrived
function getOrderStatusDeliveryAssignedOrArrivedByDelivery()
{

    return [getOrderStatusDeliveryAssignedDelivery(), getOrderStatusDeliveryArrivedDelivery()];
}

// Get Status code for - Assigned, Accepted and Arrived
function getOrderStatusDeliveryAssignedOrAcceptedOrArrivedByDelivery()
{

    return [
        getOrderStatusDeliveryAssignedDelivery(),
        getOrderStatusDeliveryAcceptedDelivery(),
        getOrderStatusDeliveryArrivedDelivery()
    ];
}

// Get Status code for - Delivered
function getOrderStatusDeliveryDelivered()
{

    return 10;
}

// Delivery Step 10 - Delivered
function deliveryStatusChange_DeliveredyByDelivery(&$order)
{

    $order->set("statusDelivery", getOrderStatusDeliveryDelivered());
    addOrderStatus($order);

    return "";
}

// Send Order Notification
function sendOrderNotification($order, $message)
{
    $order->fetch();
    $order->get('user')->fetch();

    $order->get('sessionDevice')->fetch();
    // Prepare message
    $messagePrepped = getOrderStatusCustomMessage($message, $order->get('user')->get('firstName'),
        $order->get('sessionDevice')->get('timezoneFromUTCInSeconds'));

    // Get user's Phone Id
    $objUserPhone = parseExecuteQuery(array("user" => $order->get('user'), "isActive" => true, "phoneVerified" => true),
        "UserPhones", "", "updatedAt", [], 1);

    // Send SMS notification
    if (count_like_php5($objUserPhone) > 0
        && $objUserPhone->get('SMSNotificationsEnabled') == true
        && ($objUserPhone->has('SMSNotificationsOptOut') && $objUserPhone->get('SMSNotificationsOptOut') == false)
    ) {

        try {

            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue->sendMessage(
                array(
                    "action" => "order_status_via_sms",
                    "content" =>
                        array(
                            "userPhoneId" => $objUserPhone->getObjectId(),
                            "message" => $messagePrepped
                        )
                )
            );
        } catch (Exception $ex) {

            return json_error_return_array("AS_1062", "",
                "SMS Order Notification failed! PhoneId = " . $objUserPhone->getObjectId() . " - " . $ex->getMessage(),
                2);
            // return json_decode($ex->getMessage(), true);
        }
    }

    // Send push notification
    $userDevice = [];
    if ($order->has('sessionDevice')
        && $order->get('sessionDevice')->has('userDevice')
    ) {

        $userDevice = $order->get('sessionDevice')->get('userDevice');
    }

    list($oneSignalId, $isPushNotificationEnabled) = getPushNotificationInfo($userDevice);

    if (!empty($oneSignalId)
        && $isPushNotificationEnabled == true
    ) {

        // Send push notification via Queue
        try {

            // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueuePushAndSmsConsumerName']);

            $workerQueue->sendMessage(
                array(
                    "action" => "order_status_via_push_notification",
                    "content" =>
                        array(
                            "userDeviceId" => $userDevice->getObjectId(),
                            "oneSignalId" => $userDevice->get('oneSignalId'),
                            "message" => [
                                "title" => "Order update",
                                "text" => $messagePrepped,
                                "id" => $order->getObjectId(),
                                "data" => ["orderId" => $order->getObjectId()]
                            ]
                        )
                )
            );
        } catch (Exception $ex) {

            return json_error_return_array("AS_1063", "",
                "Push Order Notification failed! orderId = " . $order->getObjectId() . " User Device Id = " . $userDevice->getObjectId() . " - " . $ex->getMessage(),
                2);
        }
    }

    return "";
}

// Get Push Notification Id from User Device object
function getPushNotificationInfo($userDevice)
{
    $userDevice->fetch();
    return [$userDevice->get('isPushNotificationEnabled'), $userDevice->get('oneSignalId')];
}

function countOrderTotals($airportIataCode, $retailerType, $retailerUniqueId)
{

    // Find record in OrderCountsByRetailer
    $objRetailers = parseExecuteQuery(array("uniqueId" => $retailerUniqueId, "isActive" => true), "Retailers");

    $objOrderCountsByRetailers = parseExecuteQuery(array("retailer" => $objRetailers[0]), "OrderCountsByRetailer", "",
        "", array("retailer.retailerType"));

    // If doesn't exist, seed it
    if (count_like_php5($objOrderCountsByRetailers) == 0) {

        $orderSequenceObject = new ParseObject("OrderCountsByRetailer");
        $orderSequenceObject->set("orderCount", 1);
        $orderSequenceObject->set("retailer", $objRetailers[0]);
        $orderSequenceObject->save();
    } // Else, update count
    else {

        // Add to Parse database
        $objOrderCountsByRetailers[0]->increment('orderCount');
        $objOrderCountsByRetailers[0]->save();
    }
}

function getOrderStatusCustomMessage($message, $userFirstName, $timezoneFromUTCInSeconds)
{

    $env = '';
    if (!empty($GLOBALS['env_EnvironmentDisplayCodeNoProd'])) {

        $env = '(' . $GLOBALS['env_EnvironmentDisplayCodeNoProd'] . ') ';
    }

    return randomGreeting($timezoneFromUTCInSeconds, false, false) . " " . $userFirstName . ", " . $env . $message;
}

function getRewardCustomMessage($message, $userFirstName, $timezoneFromUTCInSeconds)
{

    $env = '';
    if (!empty($GLOBALS['env_EnvironmentDisplayCodeNoProd'])) {

        $env = '(' . $GLOBALS['env_EnvironmentDisplayCodeNoProd'] . ') ';
    }

    return randomGreeting($timezoneFromUTCInSeconds, false, false) . " " . $userFirstName . ", " . $env . $message;
}

function orderStatusList($orderObject)
{

    // If the order was cancelled
    // Show Submitted time and then cancelled time
    if (in_array($orderObject->get('status'), listStatusesForCancelled())) {

        $objParseQueryOrderStatusResults = parseExecuteQuery(array(
            "order" => $orderObject,
            "status" => array_merge(listStatusesForSubmitted(true), listStatusesForCancelled(true))
        ), "OrderStatus", "updatedAt");
    }

    // Else
    // Get all statuses that are inform user marked
    else {

        $objParseQueryOrderStatusResults = parseExecuteQuery(array(
            "order" => $orderObject,
            "status" => listStatusesForInformUser()
        ), "OrderStatus", "updatedAt");
    }

    $responseArrayTemp = array();
    $responseArray = array();
    $airporTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'),
        date_default_timezone_get());

    // Get all Statuses in an Array first
    foreach ($objParseQueryOrderStatusResults as $orderStatus) {

        $status = $orderStatus->get('status');
        $statusDelivery = $orderStatus->get('statusDelivery');

        // If at Order Status level, this flag set then, don't account for delivery level statuses separately
        if (isset($GLOBALS['statusNames'][$status]['consolidateMultipleStatusReports'])
            && $GLOBALS['statusNames'][$status]['consolidateMultipleStatusReports'] == true
        ) {

            // Create unique status key
            $statusKey = $status;
        } else {

            // Create unique status key
            $statusKey = $status . '-' . $statusDelivery;
        }

        // If Delivery Status is available, but this status is not allowed to be informed, then skip row
        if (!empty($statusDelivery)
            && !in_array($statusDelivery, listDeliveryStatusesForInformUser())
        ) {

            continue;
        }

        $lastUpdated = $orderStatus->getUpdatedAt();

        $responseArrayTemp[$statusKey]["lastUpdateAirportTime"] = orderFormatDate($airporTimeZone,
            date_timestamp_get($lastUpdated), 'time');
        $responseArrayTemp[$statusKey]["lastUpdateTimestampUTC"] = date_timestamp_get($lastUpdated);
        $responseArrayTemp[$statusKey]["status"] = orderStatusToPrint($orderObject, $status, $statusDelivery);
        $responseArrayTemp[$statusKey]["statusCode"] = $status;
        $responseArrayTemp[$statusKey]["statusDeliveryCode"] = $statusDelivery;
        $responseArrayTemp[$statusKey]["statusCategoryCode"] = orderStatusCategory($orderObject, $status,
            $statusDelivery);
    }

    foreach ($responseArrayTemp as $statusArray) {

        $responseArray[] = $statusArray;
    }

    return $responseArray;
}

function isOrderActiveOrCompleted($orderObject)
{

    $flag = 'a';

    // Order Status is fullfilled or Not fullfilled (e.g. cancelled)
    if (in_array($orderObject->get('status'),
        array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled()))) {

        // Don't List orders that were completed in last 1 hours
        // These are included in active
        // Exclude cancelled orders
        if (($orderObject->get('etaTimestamp') + 60 * 60) > time()
            && !in_array($orderObject->get('status'), listStatusesForCancelled())
        ) {

            $flag = 'a';
        } else {

            // Else List these orders
            $flag = 'c';
        }
    } else {

        $flag = 'a';
    }
    /*
        // Order Status is Pending or In Progress or SUBMITTED
        if(in_array($orderObject->get('status'), listStatusesForPendingInProgress())) {

            // List these orders
            $flag = 'a';
        }

        // List orders that were completed in last 1 hours
        else if($orderObject->get('etaTimestamp')+60*60 > time()
            && in_array($orderObject->get('status'), array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled()))) {

            // List these orders
            $flag = 'a';
        }
        else {

            $flag = 'c';
        }
    */

    return $flag;
}

function orderHelpContactCustomerService($orderId, $user = "", $comments)
{

    if (!empty($user)) {

        // Also checks if the order belongs to the User
        $order = parseExecuteQuery(array(
            "objectId" => $orderId,
            "user" => $user,
            "status" => listStatusesForNonInternalNonCart()
        ), "Order", "", "",
            array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user"), 1);
    } else {

        // Also checks if the order belongs to the User
        $order = parseExecuteQuery(array("objectId" => $orderId, "status" => listStatusesForNonInternalNonCart()),
            "Order", "", "",
            array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user"), 1);
    }

    if (count_like_php5($order) == 0) {

        throw new Exception (json_encode(json_error_return_array("AS_859", "", "Order not found! Order Id: " . $orderId,
            2)));
    }

    // Save Client in Parse
    $orderHelpForm = new ParseObject("OrderFeedback");
    $orderHelpForm->set('order', $order);
    $orderHelpForm->set('comments', $comments);

    if (!empty($user)) {

        $orderHelpForm->set('user', $GLOBALS['user']);
        $orderHelpForm->set('sessionDevice', getCurrentSessionDevice());
        $orderHelpForm->set('contactType', 'user');
    } else {

        $orderHelpForm->set('contactType', 'retailer');
    }

    $orderHelpForm->save();

    $customerName = $order->get('user')->get('firstName') . ' ' . $order->get('user')->get('lastName');
    $retailerName = $order->get('retailer')->get('retailerName');
    $retailerLocation = $order->get('retailer')->get('location')->get('airportIataCode') . ' ' . $order->get('retailer')->get('location')->get('gateDisplayName');
    $orderIdDisplay = $order->get('orderSequenceId');

    $slack = createOrderHelpChannelSlackMessageByAirportIataCode($order->get('retailer')->get('location')->get('airportIataCode'));
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderHelp'], 'env_SlackWH_orderHelp');

    $slack->setText($customerName . " (" . date("M j, g:i a", time()) . ")");

    $attachment = $slack->addAttachment();
    $attachment->setAttribute("fallback", $customerName . " (" . date("M j, g:i a", time()) . ")");
    $attachment->addField("Customer:", $customerName, false);
    $attachment->addField("Retailer:", $retailerName . " (" . $retailerLocation . ")", false);
    $attachment->addField("OrderId (Internal):", $orderId, true);
    $attachment->addField("OrderId (User):", $orderIdDisplay, true);
    $attachment->addField("Comments:", $comments, false);

    try {

        // Post to order help channel
        $slack->send();
    } catch (Exception $ex) {

        throw new Exception (json_encode(json_error_return_array("AS_1054", "",
            "Slack post failed informing order help! customerName=(" . $customerName . "), Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
            1)));
    }
}

function getPOSType($retailer)
{

    // Find POS Config
    $posConfig = parseExecuteQuery(array("retailer" => $retailer), "RetailerPOSConfig", "", "", [], 1);

    if (empty($posConfig)) {

        return '';
    }

    // For Print retailer
    if ($posConfig->has('printerId')
        && !empty($posConfig->get('printerId'))
    ) {

        return 'PRINT';
    } // For POS retailer
    else {
        if ($posConfig->has('locationId')
            && !empty($posConfig->get('locationId'))
        ) {

            return 'POS';
        } // For Tablet retailer
        else {
            if ($posConfig->has('tabletId')
                && !empty($posConfig->get('tabletId'))
            ) {

                return 'TABLET-SLACK';
            } else {

                return 'TABLET-APP';
            }
        }
    }
}

function slackDeliveryUpdateResponseMessage($payload, $deliveryObjectId, $actionName, $buttonText, $deliveryState = "")
{

    // Update response message

    // Create Slack attachment object from the first attachment
    $attachment = new SlackAttachment($payload["original_message"]["attachments"][0]);

    // In Progress state
    if (empty($deliveryState)) {

        // Update title text
        $attachment->setAttribute("text", "`" . "DELIVERY IN-PROGRESS" . "`");
    } // If cancelled
    else {
        if (strcasecmp($deliveryState, "ORDER CANCELLED") == 0
            || strcasecmp($deliveryState, "STATUS UPDATE DELAYED") == 0
        ) {

            $attachment->setColorRejected();

            // Update title text
            $attachment->setAttribute("text", "`" . $deliveryState . "`");
        } else {

            $attachment->setColorAccepted();

            // Update title text
            $attachment->setAttribute("text", "`" . $deliveryState . "`");
        }
    }

    // Remove all buttons
    $attachment->removeAllButtons();

    // If an action is required, add button
    if (!empty($actionName)) {

        // Add an action button for Arrived
        $buttonIndex = $attachment->addButtonPrimary($actionName, $buttonText, $deliveryObjectId);
        $attachment->addConfirmToButton($buttonIndex, "Confirm action", "Are you sure?", "Yes", "Nevermind");
    }

    // Update timestamp
    $attachment->addTimestamp();

    // Get the attachement for response
    return $attachment->getAttachment();
}

function getItemsNotAvailableAtOrderTime($orderObject, $requestedFullFillmentTimestamp)
{

    $unallowedItemsForOrder = [];

    // Get Day of the Week and Seconds since Midnight, Airport time
    //list($dayOfWeekAtAirport, $secondsSinceMidnight) = getDayOfWeekAndSecsSinceMidnight($orderObject->get('retailer')->get('airportIataCode'), $requestedFullFillmentTimestamp);


    $airportTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'), date_default_timezone_get());
    $dayOfWeekAtAirport = \App\Consumer\Helpers\DateTimeHelper::getDayOfWeekByTimestamp(
        $requestedFullFillmentTimestamp, $airportTimeZone
    );
    $secondsSinceMidnight = \App\Consumer\Helpers\DateTimeHelper::getAmountOfSecondsSinceMidnightByTimestamp(
        $requestedFullFillmentTimestamp, $airportTimeZone
    );

    //json_error(json_encode([$dayOfWeekAtAirport,$secondsSinceMidnight]),'');
    // Find Items under the order
    $orderObjectModifier = parseExecuteQuery(array("order" => $orderObject), "OrderModifiers", "", "",
        array("retailerItem"));

    foreach ($orderObjectModifier as $objModifier) {
        $itemName = !empty($objModifier->get('retailerItem')->get("itemDisplayName")) ? $objModifier->get('retailerItem')->get("itemDisplayName") : $objModifier->get('retailerItem')->get("itemPOSName");


        // If the item was 86ied
        if (isItem86isedFortheDay($objModifier->get('retailerItem')->get('uniqueId'))) {
            $unallowedItemsForOrder[] = $itemName;
            continue;

        } else {
            $retailerItemTimeRestrictionService = \App\Consumer\Services\RetailerItemTimeRestrictionServiceFactory::create();
            $retailerItemTimeRestrictions = $retailerItemTimeRestrictionService->getTimeRestrictionByRetailerItemUniqueIdAndDay(
                $objModifier->get('retailerItem')->get('uniqueId'),
                $dayOfWeekAtAirport
            );

            if ($retailerItemTimeRestrictions->isAvailableForDay() === false) {
                $unallowedItemsForOrder[] = $itemName;
                continue;
            }

            if ($retailerItemTimeRestrictions->isAvailableForGivenSecondOfDay($secondsSinceMidnight) === false) {
                $unallowedItemsForOrder[] = $itemName;
                continue;
            }
        }
    }


    return $unallowedItemsForOrder;
}

function getItemsNotAllowedThruSecurity($orderSummaryArray)
{

    $unallowedItemListThruSecurity = [];

    // List all items that are not allowed
    foreach ($orderSummaryArray["items"] as $item) {

        if ($item["allowedThruSecurity"] == false) {

            $unallowedItemListThruSecurity[] = $item["itemName"];
        }
    }

    return $unallowedItemListThruSecurity;
}

function addUponOrderDelay($order, $delayType, $delayInMins)
{

    $orderDelays = new ParseObject("OrderDelays");
    $orderDelays->set("order", $order);
    $orderDelays->set("delayType", $delayType);
    $orderDelays->set("delayInMins", intval($delayInMins));
    $orderDelays->save();
}

function getOrderMustPrepareByTimestamp($order)
{

    return ($order->get('etaTimestamp') - $order->get('fullfillmentProcessTimeInSeconds'));
}

function setRetailerClosedEarlyTimerMessage($uniqueId, $closeLevel, $closeForSecs = "EOD", $forceClose = false)
{

    // Is Retailer already closed?
    // Ensures lower level close request doesn't override a higher level
    if ($forceClose == false
        && (isRetailerClosedEarly($uniqueId) || isRetailerCloseEarlyForNewOrders($uniqueId))
    ) {

        return $GLOBALS['env_RetailerEarlyCloseMinWaitInSecs'];
    }

    // Set cache so no new orders are accepted
    setRetailerCloseEarlyForNewOrders($uniqueId, $closeLevel);

    try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue->sendMessage(
            array(
                "action" => "retailer_early_close",
                "content" =>
                    array(
                        "retailerUniqueId" => $uniqueId,
                        "closeLevel" => $closeLevel,
                        "closeForSecs" => $closeForSecs
                    )
            ),
            $GLOBALS['env_RetailerEarlyCloseMinWaitInSecs']
        );
    } catch (Exception $ex) {

        throw new Exception("Failed to schedule retailer early close: " . $ex->getMessage());
    }

    return $GLOBALS['env_RetailerEarlyCloseMinWaitInSecs'];
}

function logRetailerEarlyCloseEvent($uniqueRetailerId, $closeLevel, $eventType = 'Early Close')
{

    // get retailer info
    $retailerInfo = getRetailerInfo($uniqueRetailerId);

    // Non-exiting info log
    json_error("AS_3016", "",
        "Retailer " . $eventType . " " . $uniqueRetailerId . ", Retailer Name = " . $retailerInfo["retailerName"] . ", Location = " . $retailerInfo["airportIataCode"] . " - " . $retailerInfo["location"]["gateDisplayName"],
        3, 1);

    // Post on Slack channel - Order Help

    //$slack = createOrderHelpChannelSlackMessageByAirportIataCode($retailerInfo['airportIataCode']);
    $slack = new SlackMessage($GLOBALS['env_SlackWH_orderHelp'], 'env_SlackWH_orderHelp');
    $slack->setText($retailerInfo["retailerName"] . " (@" . $retailerInfo["airportIataCode"] . " - " . $retailerInfo["location"]["gateDisplayName"] . ")");

    $attachment = $slack->addAttachment();
    $attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
    $attachment->addField("Source:", $GLOBALS['tabletOpenCloseLevels'][$closeLevel], false);
    $attachment->addField("Event:", $eventType, false);

    try {

        $slack->send();
    } catch (Exception $ex) {

        throw new Exception("Failed to send Slack notification about retailer early close (" . $uniqueRetailerId . "): " . $ex->getMessage());
    }
}

function notifyOnSlackMenuUpdates($uniqueRetailerId, $eventTitle, $eventMessages)
{

    // get retailer info
    $title = "";
    if (!empty($uniqueRetailerId)) {

        $retailerInfo = getRetailerInfo($uniqueRetailerId);
        $title = $retailerInfo["retailerName"] . " (@" . $retailerInfo["airportIataCode"] . " - " . $retailerInfo["location"]["gateDisplayName"] . ")";
    }

    // Post on Slack channel - Menu Help
    $slack = new SlackMessage($GLOBALS['env_SlackWH_menuUpdates'], 'env_SlackWH_menuUpdates');
    $slack->setText($title);

    $attachment = $slack->addAttachment();


    $attachment->addField("Event:", $eventTitle, false);

    foreach ($eventMessages as $fieldName => $fieldValue) {

        $attachment->addField($fieldName . ":", $fieldValue, false);
    }

    try {

        $slack->send();
    } catch (Exception $ex) {

        throw new Exception("Failed to send Slack notification about menu updates (" . $uniqueRetailerId . "): " . $ex->getMessage());
    }
}

function getRetailerInfo($retailerUniqueId, &$objParseRetailer = "")
{

    // Look in cache
    $responseArray = getRetailerInfoCache($retailerUniqueId);

    // If found, return
    if (is_array($responseArray)) {

        return $responseArray;
    }

    // If no reference object was provided, then fetch from DB
    if (empty($objParseRetailer)) {

        $objParseRetailer = parseExecuteQuery(array("uniqueId" => $retailerUniqueId), "Retailers", "", "",
            ["location", "retailerType", "retailerPriceCategory", "retailerPriceCategory", "retailerFoodSeatingType"],
            1);

        if (empty($objParseRetailer)) {

            return [];
        }
    }

    // If no cache found
    $responseArray = array();

    $responseArray["airportIataCode"] = $objParseRetailer->get('airportIataCode');
    $responseArray["openTimesMonday"] = $objParseRetailer->get('openTimesMonday');
    $responseArray["openTimesTuesday"] = $objParseRetailer->get('openTimesTuesday');
    $responseArray["openTimesWednesday"] = $objParseRetailer->get('openTimesWednesday');
    $responseArray["openTimesThursday"] = $objParseRetailer->get('openTimesThursday');
    $responseArray["openTimesFriday"] = $objParseRetailer->get('openTimesFriday');
    $responseArray["openTimesSaturday"] = $objParseRetailer->get('openTimesSaturday');
    $responseArray["openTimesSunday"] = $objParseRetailer->get('openTimesSunday');
    $responseArray["closeTimesMonday"] = $objParseRetailer->get('closeTimesMonday');
    $responseArray["closeTimesTuesday"] = $objParseRetailer->get('closeTimesTuesday');
    $responseArray["closeTimesWednesday"] = $objParseRetailer->get('closeTimesWednesday');
    $responseArray["closeTimesThursday"] = $objParseRetailer->get('closeTimesThursday');
    $responseArray["closeTimesFriday"] = $objParseRetailer->get('closeTimesFriday');
    $responseArray["closeTimesSaturday"] = $objParseRetailer->get('closeTimesSaturday');
    $responseArray["closeTimesSunday"] = $objParseRetailer->get('closeTimesSunday');
    $responseArray["description"] = $objParseRetailer->get('description');
    $responseArray["hasDelivery"] = $objParseRetailer->get('hasDelivery');
    $responseArray["hasPickup"] = $objParseRetailer->get('hasPickup');
    $responseArray["imageBackground"] = preparePublicS3URL($objParseRetailer->get('imageBackground'),
        getS3KeyPath_ImagesRetailerBackground($responseArray["airportIataCode"]), $GLOBALS['env_S3Endpoint']);
    $responseArray["imageLogo"] = preparePublicS3URL($objParseRetailer->get('imageLogo'),
        getS3KeyPath_ImagesRetailerLogo($responseArray["airportIataCode"]), $GLOBALS['env_S3Endpoint']);
    $responseArray["isActive"] = $objParseRetailer->get('isActive');
    $responseArray["isChain"] = $objParseRetailer->get('isChain');
    $responseArray["retailerName"] = $objParseRetailer->get('retailerName');
    $responseArray["searchTags"] = $objParseRetailer->get('searchTags');
    $responseArray["uniqueId"] = $objParseRetailer->get('uniqueId');

    $responseArray["retailerType"]["displayOrder"] = $objParseRetailer->get('retailerType')->get("displayOrder");
    $responseArray["retailerType"]["retailerType"] = $objParseRetailer->get('retailerType')->get("retailerType");
    $responseArray["retailerType"]["iconCode"] = $objParseRetailer->get('retailerType')->get("iconCode");

    $responseArray["retailerPriceCategory"]["displayOrder"] = $objParseRetailer->get('retailerPriceCategory')->get("displayOrder");
    $responseArray["retailerPriceCategory"]["retailerPriceCategory"] = $objParseRetailer->get('retailerPriceCategory')->get("retailerPriceCategory");
    $responseArray["retailerPriceCategory"]["retailerPriceCategorySign"] = $objParseRetailer->get('retailerPriceCategory')->get("retailerPriceCategorySign");
    $responseArray["retailerPriceCategory"]["iconCode"] = $objParseRetailer->get('retailerPriceCategory')->get("iconCode");

    // Can be multiple
    foreach ($objParseRetailer->get('retailerCategory') as $category) {

        $retailerCategory = parseExecuteQuery(array("objectId" => $category->getObjectId()), "RetailerCategory");

        $responseArray["retailerCategory"]["displayOrder"] = $retailerCategory[0]->get("displayOrder");
        $responseArray["retailerCategory"]["retailerCategory"] = $retailerCategory[0]->get("retailerCategory");
        $responseArray["retailerCategory"]["iconCode"] = $retailerCategory[0]->get("iconCode");
    }

    // Can be multiple
    foreach ($objParseRetailer->get('retailerFoodSeatingType') as $foodSeatingType) {

        $retailerFoodSeatingType = parseExecuteQuery(array("objectId" => $foodSeatingType->getObjectId()),
            "RetailerFoodSeatingType");

        $responseArray["retailerFoodSeatingType"]["displayOrder"] = $retailerFoodSeatingType[0]->get("displayOrder");
        $responseArray["retailerFoodSeatingType"]["retailerFoodSeatingType"] = $retailerFoodSeatingType[0]->get("retailerFoodSeatingType");
        $responseArray["retailerFoodSeatingType"]["iconCode"] = $retailerFoodSeatingType[0]->get("iconCode");
    }

    $responseArray["location"]["locationId"] = $objParseRetailer->get('location')->getObjectId();
    $responseArray["location"]["locationDisplayName"] = $objParseRetailer->get('location')->get('locationDisplayName');
    $responseArray["location"]["gateDisplayName"] = $objParseRetailer->get('location')->get('gateDisplayName');
    $responseArray["location"]["terminal"] = $objParseRetailer->get('location')->get('terminal');
    $responseArray["location"]["concourse"] = $objParseRetailer->get('location')->get('concourse');
    $responseArray["location"]["gate"] = $objParseRetailer->get('location')->get('gate');
    $responseArray["location"]["displaySequence"] = $objParseRetailer->get('location')->get('displaySequence');
    $responseArray["location"]["geoPointLocation"] = array(
        "longitude" => $objParseRetailer->get('location')->get('geoPointLocation')->getLongitude(),
        "latitude" => $objParseRetailer->get('location')->get('geoPointLocation')->getLatitude()
    );

    // Set cache
    setRetailerInfoCache($retailerUniqueId, $responseArray);

    return $responseArray;
}

function fetchFullfillmentTimes($airportIataCode, $locationId, $retailerId = 0, $requestedFullFillmentTimestamp = 0)
{

    if (empty($retailerId)) {
        // Search retailers for the airport
        $obj = new ParseQuery("Retailers");
        $objRetailersHasDelivery = parseSetupQueryParams(array(
            "airportIataCode" => $airportIataCode,
            "isActive" => true,
            "hasDelivery" => true
        ), $obj);

        $obj = new ParseQuery("Retailers");
        $objRetailersHasPickup = parseSetupQueryParams(array(
            "airportIataCode" => $airportIataCode,
            "isActive" => true,
            "hasPickup" => true
        ), $obj);
    } else {

        $obj = new ParseQuery("Retailers");
        $objRetailersHasDelivery = parseSetupQueryParams(array(
            "uniqueId" => $retailerId,
            "isActive" => true,
            "hasDelivery" => true
        ), $obj);

        $obj = new ParseQuery("Retailers");
        $objRetailersHasPickup = parseSetupQueryParams(array(
            "uniqueId" => $retailerId,
            "isActive" => true,
            "hasPickup" => true
        ), $obj);
    }

    $includeKeys = array(
        "location",
        "retailerType",
        "retailerPriceCategory",
        "retailerPriceCategory",
        "retailerFoodSeatingType"
    );

    $objRetailersMainQuery = ParseQuery::orQueries([$objRetailersHasDelivery, $objRetailersHasPickup]);

    foreach ($includeKeys as $keyName) {

        $objRetailersMainQuery->includeKey($keyName);
    }
    $objRetailers = $objRetailersMainQuery->find();


    // No retailers found
    if (count_like_php5($objRetailers) < 1) {

        json_error("AS_511", "", "No Retailers found! " . $airportIataCode, 3);
    }

    $responseArray = array();

    // If no location is provided, get default location
    if (empty($locationId)) {

        $objLocation = getTerminalGateMapDefaultLocation($airportIataCode);
    } else {

        // Get object for provided location
        $objLocation = getTerminalGateMapByLocationId($airportIataCode, $locationId);
    }

    if (empty($objLocation)) {

        json_error("AS_519", "", "Location not found - " . $locationId, 1);
    }


    // $GLOBALS['lastcheckin'] = $time_start = microtime(true);
    list($isDeliveryAvailableAt, $deliveryAvailabilityErrorMessage) = isAnyDeliveryFromSlackAvailable($objLocation,
        time());

    // $cacheWasFound = true;
    $responseArrayFromCache = [];
    foreach ($objRetailers as $obj) {

        // Look in cache
        // if ($cacheWasFound == true) {

        // $responseArrayFromCache = getFullfillmentInfoCache($obj, $objLocation);
        // }
        $responseArrayFromCache = "";

        if (!empty($responseArrayFromCache)) {

            $responseArray[$obj->get('uniqueId')] = $responseArrayFromCache;
            // $responseArrayFromCache = [];
        } else {

            // $cacheWasFound = false;
            $responseArray[$obj->get('uniqueId')] = packageFullfillmentInfo($obj, $objLocation, $isDeliveryAvailableAt,
                $deliveryAvailabilityErrorMessage, $requesteorder_ops_partial_refund_requestdFullFillmentTimestamp);

            // Save to cache for 2 mins
            // setFullfillmentInfoCache($obj, $objLocation, $responseArray[$obj->get('uniqueId')]);
        }
    }

    // echo(microtime(true)-$time_start);exit;
    return $responseArray;
}

function countActiveRetailersInFullfillment($fullfillmentTimes)
{

    $countDelivery = 0;
    $count = 0;
    foreach ($fullfillmentTimes as $uniqueId => $fullfillmentTime) {

        if ($fullfillmentTime["d"]["isAvailable"] == true) {

            $countDelivery++;
        }

        if ($fullfillmentTime["d"]["isAvailable"] == true
            || $fullfillmentTime["p"]["isAvailable"] == true
        ) {

            $count++;
        }
    }

    return ["total" => $count, "delivery" => $countDelivery];
}

function packageFullfillmentInfo(
    $retailer,
    $location,
    $isDeliveryAvailableAt,
    $deliveryAvailabilityErrorMessage,
    $requestedFullFillmentTimestamp
) {

    // Get Fullfillment info
    if ($retailer->get('hasDelivery')
        || $retailer->get('hasPickup')
    ) {

        return getFullfillmentInfoWithoutOrder($retailer, $location, $isDeliveryAvailableAt,
            $deliveryAvailabilityErrorMessage, $requestedFullFillmentTimestamp);
    } else {

        return getFullfillmentInfoEmpty($retailer->get('uniqueId'));
    }
}

function byDistance(
    $airportIataCode,
    $locationId,
    $terminalForSort = "",
    $concourseForSort = "",
    $gateForSort = "",
    $retailerType = "",
    $limit = 0
) {

    // Verify Airport Iata Code
    if (count_like_php5(getAirportByIataCode($airportIataCode)) == 0) {

        json_error("AS_511", "", "Invalid Airport Code provided for Airport - " . $airportIataCode);
    }

    // Check if From Terminal and From Gate values are valid
    list($airportIataCode, $terminalForDist, $concourseForDist, $gateForDist) = getGateLocationDetails($airportIataCode,
        $locationId);

    if (empty($airportIataCode)
        || empty($terminalForDist)
        || empty_zero_allowed($gateForDist)
    ) {

        json_error("AS_514", "", "Provided location Ids are invalid");
    }

    $objLocation = getTerminalGateMapByLocationId($airportIataCode, $locationId);

    // Sort array is not provided, mark the flag
    $sortByProvidedLocationFlag = false;
    if (!empty($terminalForSort)
        && !empty($concourseForSort)
        && !empty_zero_allowed($gateForSort)
    ) {

        $sortByProvidedLocationFlag = true;

        // Check if From Terminal and From Gate values are valid
        isGateCorrect($airportIataCode, $terminalForSort, $concourseForSort, $gateForSort);
    }

    $retailersWithDistance = array();
    $retailersWithDistanceSorted = array();

    // Find Retailers by airport code
    $retailerSearchQueryArray = ["airportIataCode" => $airportIataCode, "isActive" => true];

    $retailerType = trim($retailerType);

    if (!empty($retailerType)) {

        // $objParseQueryRetailerTypeInner = parseExecuteQuery(array("retailerType" => $retailerType), "RetailerType");
        // $retailerSearchQueryArray["retailerType"] = $objParseQueryRetailerTypeInner[0];

        $retailerTypeRefObj = new ParseQuery("RetailerType");
        $retailerTypeObj = parseSetupQueryParams(["retailerType" => $retailerType], $retailerTypeRefObj);

        $retailerSearchQueryArray["__MATCHESQUERY__retailerType"] = $retailerTypeObj;
    }

    /*
    $objParseQueryRetailersResults = parseExecuteQuery($retailerSearchQueryArray, "Retailers", "", "", array("location", "retailerType", "retailerPriceCategory", "retailerPriceCategory", "retailerFoodSeatingType"));
    */

    $retailerRefObj = new ParseQuery("Retailers");
    $retailerObj = parseSetupQueryParams($retailerSearchQueryArray, $retailerRefObj);

    // Find all retailers with active Ping
    $objParseQueryRetailersResults = parseExecuteQuery(
        [
            "__MATCHESQUERY__retailer" => $retailerObj,
            "continousPingCheck" => true
        ],
        "RetailerPOSConfig", "", "",
        [
            "retailer",
            "retailer.location",
            "retailer.retailerType",
            "retailer.retailerPriceCategory",
            "retailer.retailerPriceCategory",
            "retailer.retailerFoodSeatingType"
        ]
    );

    // If no retailers are found
    if (count_like_php5($objParseQueryRetailersResults) < 1) {

        $descriptiveError = "No records matching airport code = " . $airportIataCode . " and Retailer Type = " . $retailerType . " were not found on Parse in Retailers Class";

        json_error("AS_504", "", "No Retailers found! " . $descriptiveError, 3, 1);

        return [];
    }

    // Calculate distance for all Retailers
    foreach ($objParseQueryRetailersResults as $objParseQueryRetailerPOSConfig) {

        $objParseQueryRetailersResultOne = $objParseQueryRetailerPOSConfig->get("retailer");
        $lastSuccessfulPingTimestamp = getRetailerPingTimestamp($objParseQueryRetailersResultOne->get('uniqueId'));

        if ($lastSuccessfulPingTimestamp < getRetailerPingTimestampThreshold()) {

            continue;
        }

        $temp = array();

        $retailerId = $objParseQueryRetailersResultOne->get('uniqueId');

        /*
        Full Directions
        $fromLocation = getTerminalGateMapByLocationRef($airportIataCode, $terminalForDist, $concourseForDist, $gateForDist);
        $toLocation = getTerminalGateMapByLocationRef($airportIataCode, $objParseQueryRetailersResultOne->get('location')->get('terminal'), $objParseQueryRetailersResultOne->get('location')->get('concourse'), $objParseQueryRetailersResultOne->get('location')->get('gate'));

        $tempDistanceArrayResponse = getDirections($airportIataCode, $fromLocation->getObjectId(), $toLocation->getObjectId());
        $temp[$retailerId] = $tempDistanceArrayResponse["totalDistanceMetricsForTrip"];
        */

        ////////
        // Remove when using Full Directions
        $temp[$retailerId] = getDistanceMetrics($terminalForDist, $concourseForDist, $gateForDist,
            $objParseQueryRetailersResultOne->get('location')->get('terminal'),
            $objParseQueryRetailersResultOne->get('location')->get('concourse'),
            $objParseQueryRetailersResultOne->get('location')->get('gate'), true, $airportIataCode);
        ////////

        $temp[$retailerId]["uniqueId"] = $objParseQueryRetailersResultOne->get('uniqueId');

        // Used for Sorting and then unset before sending back
        $temp[$retailerId]["terminal"] = $objParseQueryRetailersResultOne->get('location')->get('terminal') . $objParseQueryRetailersResultOne->get('location')->get('concourse');

        // Get distances from the Sort Gate
        if ($sortByProvidedLocationFlag) {

            /*
            Full Directions
            $fromLocation = getTerminalGateMapByLocationRef($airportIataCode, $terminalForSort, $concourseForDist, $gateForSort);
            $toLocation = getTerminalGateMapByLocationRef($airportIataCode, $objParseQueryRetailersResultOne->get('location')->get('terminal'), $objParseQueryRetailersResultOne->get('location')->get('concourse'), $objParseQueryRetailersResultOne->get('location')->get('gate'));
            $tempArray = getDirections($airportIataCode, $fromLocation->getObjectId(), $toLocation->getObjectId());

            $temp[$retailerId]["sortWalkingTime"] = $tempArray["totalDistanceMetricsForTrip"]["walkingTime"];
            */

            ////////
            // Remove when using Full Directions
            $tempArray = getDistanceMetrics($terminalForSort, $concourseForDist, $gateForSort,
                $objParseQueryRetailersResultOne->get('location')->get('terminal'),
                $objParseQueryRetailersResultOne->get('location')->get('concourse'),
                $objParseQueryRetailersResultOne->get('location')->get('gate'), true, $airportIataCode);

            $temp[$retailerId]["sortWalkingTimeToGate"] = $tempArray["walkingTimeToGate"];
            ////////
        }

        ////////
        // Remove when using Full Directions
        if (isset($temp[$retailerId]["pathToDestination"])
            && strcasecmp($temp[$retailerId]["pathToDestination"], "NOP") == 0
        ) {

            continue;
        }

        if (isset($temp[$retailerId]["pathToDestination"])) {

            unset($temp[$retailerId]["pathToDestination"]);
        }

        if (!isset($temp[$retailerId]["differentTerminalFlag"])) {

            $temp[$retailerId]["differentTerminalFlag"] = "N";
        }
        ////////

        // Add fullfillment info
        // $isDeliveryAvailableAt = isAnyDeliveryFromSlackAvailable($objLocation, time());
        // $temp[$retailerId]["fullfillmentInfo"] = packageFullfillmentInfo($objParseQueryRetailersResultOne, $objLocation, $isDeliveryAvailableAt);

        /*
        // Add fullfillment info
        if($objParseQueryRetailersResultOne->get('hasDelivery')
            || $objParseQueryRetailersResultOne->get('hasPickup')) {

            $retailersWithDistanceSorted[$retailerId]["fullfillmentInfo"] = getFullfillmentInfo($objParseQueryRetailersResultOne, $objLocation);
        }
        else {

            $retailersWithDistanceSorted[$retailerId]["fullfillmentInfo"] = getFullfillmentInfoEmpty();
        }
        */

        // Add full retailer info
        // $retailersWithDistance[$retailerId] = array_merge($temp[$retailerId], getRetailerInfo($objParseQueryRetailersResultOne->get('uniqueId'), $objParseQueryRetailersResultOne));

        /*
        Full Directions
        // Change names of indexes to match original response keys
        $tempKeysUpdate = $temp[$retailerId];
        $tempKeysUpdate["distanceStepsToGate"] = $temp[$retailerId]["distanceSteps"];
        unset($tempKeysUpdate["distanceSteps"]);

        $tempKeysUpdate["distanceMilesToGate"] = $temp[$retailerId]["distanceMiles"];
        unset($tempKeysUpdate["distanceMiles"]);

        $tempKeysUpdate["walkingTimeToGate"] = $temp[$retailerId]["walkingTime"];
        unset($tempKeysUpdate["walkingTime"]);

        $tempKeysUpdate["differentTerminalFlag"] = $temp[$retailerId]["reEnterSecurityFlag"];
        unset($tempKeysUpdate["reEnterSecurityFlag"]);

        $retailersWithDistance[$retailerId] = $tempKeysUpdate;
        */

        ////////
        // Remove when using Full Directions
        $retailersWithDistance[$retailerId] = $temp[$retailerId];
        ////////

        unset($retailersWithDistance[$retailerId]["uniqueId"]);
    }

    // Potentially Turn it off sorting as iOS has to do it again anyway
    // $retailersWithDistanceSorted = $retailersWithDistance;

    $limit = intval($limit);

    // If set to 0, then return all records
    if ($limit == 0) {

        $limit = count_like_php5($retailersWithDistance);
    }

    if (count_like_php5($retailersWithDistance) > 0) {

        // Sort by walkingTime FROM a given location
        if ($sortByProvidedLocationFlag) {

            $retailersWithDistanceSorted = array_slice(array_sort_by_two_keys($retailersWithDistance,
                'sortWalkingTimeToGate', 'terminal'), 0, $limit);
            //$retailersWithDistanceSorted = array_slice(array_sort($retailersWithDistance, 'sortWalkingTimeToGate', SORT_ASC), 0, $limit);
        } // Sort by distanceStepsToGate FROM current location
        else {

            $retailersWithDistanceSorted = array_slice(array_sort_by_two_keys($retailersWithDistance,
                'distanceStepsToGate', 'terminal'), 0, $limit);
            //$retailersWithDistanceSorted = array_slice(array_sort($retailersWithDistance, 'distanceStepsToGate', SORT_ASC), 0, $limit);
        }
    }

    // Set a sequence number so it can be sorted at App level if needed
    $counterRank = 0;
    foreach ($retailersWithDistanceSorted as $key => $value) {

        // Sort index
        $counterRank++;

        // Add index for sorting at the app level
        $retailersWithDistanceSorted[$key]["sortedSequence"] = $counterRank;

        // unset terminal index, since location index has location info
        unset($retailersWithDistanceSorted[$key]["terminal"]);

        // remove this index as it is used for sorting only
        if (isset($retailersWithDistanceSorted[$key]["sortWalkingTimeToGate"])) {

            unset($retailersWithDistanceSorted[$key]["sortWalkingTimeToGate"]);
        }
    }

    return $retailersWithDistanceSorted;
}

function getRetailerInfoForMenu($retailerId)
{

    // Find Retailer
    $objParseRetailerResults = parseExecuteQuery(array("uniqueId" => $retailerId, "isActive" => true), "Retailers", "",
        "",
        array("location", "retailerType", "retailerPriceCategory", "retailerPriceCategory", "retailerFoodSeatingType"),
        1);

    if (count_like_php5($objParseRetailerResults) == 0) {

        json_error("AS_506", "", "Retailer not found! Unique Id: " . $retailerId, 1);
    }

    return getRetailerInfo($retailerId, $objParseRetailerResults);
}

function getRetailerMenu($retailerId, $time, $skip86Items = false)
{

    // Check if Retailer exists
    // isCorrectObject(array("uniqueId" => $retailerId, "isActive" => true), "Retailers", "Retailer", "uniqueId", 0);

    // Get Retailer Info
    $retailerInfo = getRetailerInfo($retailerId);

    // Find Menu
    $objParseQueryRetailersItemsResults = parseExecuteQuery(array(
        "uniqueRetailerId" => $retailerId,
        "isActive" => true
    ), "RetailerItems", "itemDisplaySequence");

    // If Menu is NOT found, let caller know
    if (count_like_php5($objParseQueryRetailersItemsResults) < 1) {

        json_error("AS_508", "", "Retailer not found for uniqueRetailerId (" . $retailerId . ")", 1);
    } else {

        $pre_responseArray = array();
        $categoriesList = array();

        // Get Day of the Week and Seconds since Midnight, Airport time
        list($dayOfWeekAtAirport, $secondsSinceMidnight) = getDayOfWeekAndSecsSinceMidnight($retailerInfo["airportIataCode"],
            $time);

        for ($i = 0; $i < count_like_php5($objParseQueryRetailersItemsResults); $i++) {

            if (isItem86isedFortheDay($objParseQueryRetailersItemsResults[$i]->get("uniqueId")) && $skip86Items == true) {

                continue;
            }

            $itemDetails = array();

            $categoryName = $objParseQueryRetailersItemsResults[$i]->get("itemCategoryName");
            $categorySecondName = $objParseQueryRetailersItemsResults[$i]->get("itemCategorySecondName");
            $categoryThirdName = $objParseQueryRetailersItemsResults[$i]->get("itemCategoryThirdName");

            $itemDetails["itemCategoryName"] = $categoryName;
            $itemDetails["itemCategorySecondName"] = $categorySecondName;
            $itemDetails["itemCategoryThirdName"] = $categoryThirdName;

            // JMD
            $itemDetails["itemId"] = $objParseQueryRetailersItemsResults[$i]->get("uniqueId");
            $itemDetails["extItemId"] = $objParseQueryRetailersItemsResults[$i]->get("itemId");
            $itemDetails["itemName"] = !empty($objParseQueryRetailersItemsResults[$i]->get("itemDisplayName")) ? $objParseQueryRetailersItemsResults[$i]->get("itemDisplayName") : $objParseQueryRetailersItemsResults[$i]->get("itemPOSName");
            $itemDetails["itemDescription"] = $objParseQueryRetailersItemsResults[$i]->get("itemDisplayDescription");
            $itemDetails["itemPrice"] = $objParseQueryRetailersItemsResults[$i]->get("itemPrice");
            $itemDetails["itemPriceDisplay"] = dollar_format($objParseQueryRetailersItemsResults[$i]->get("itemPrice"));
            $itemDetails["itemImageURL"] = !empty($objParseQueryRetailersItemsResults[$i]->get("itemImageURL")) ? preparePublicS3URL($objParseQueryRetailersItemsResults[$i]->get("itemImageURL"),
                getS3KeyPath_ImagesRetailerItem($retailerInfo["airportIataCode"]), $GLOBALS['env_S3Endpoint']) : "";

            $itemDetails["itemImageThumbURL"] = !empty($objParseQueryRetailersItemsResults[$i]->get("itemImageURL")) ? preparePublicS3URL('thumb_' . $objParseQueryRetailersItemsResults[$i]->get("itemImageURL"),
                getS3KeyPath_ImagesRetailerItem($retailerInfo["airportIataCode"]), $GLOBALS['env_S3Endpoint']) : "";

            $itemDetails["itemTags"] = $objParseQueryRetailersItemsResults[$i]->get("itemTags");
            $itemDetails["allowedThruSecurity"] = !is_bool($objParseQueryRetailersItemsResults[$i]->get("allowedThruSecurity")) ? true : $objParseQueryRetailersItemsResults[$i]->get("allowedThruSecurity");

            ////////////////////////////////////////////////////////////////////////////
            // Item Order Time Restrictions

            /*
            list($restrictOrderTimeInSecsStart, $restrictOrderTimeInSecsEnd) = getItemRestrictTimes($objParseQueryRetailersItemsResults[$i]->get("uniqueId"), $dayOfWeekAtAirport);
            $itemDetails["restrictOrderTimeInSecsStart"] = $restrictOrderTimeInSecsStart;
            $itemDetails["restrictOrderTimeInSecsEnd"] = $restrictOrderTimeInSecsEnd;
            */
            $retailerItemTimeRestrictionService = \App\Consumer\Services\RetailerItemTimeRestrictionServiceFactory::create();
            $retailerItemTimeRestrictions = $retailerItemTimeRestrictionService->getTimeRestrictionByRetailerItemUniqueIdAndDay(
                $objParseQueryRetailersItemsResults[$i]->get("uniqueId"),
                $dayOfWeekAtAirport
            );

            if ($retailerItemTimeRestrictions->isAvailableForDay() === false) {
                continue;
            }


            $itemDetails = array_merge($itemDetails, $retailerItemTimeRestrictions->asArray());

            /*
            Uncomment this section, if you want to filter the menu items that are not available for now or today
            // Item is not available for this day
            if($restrictOrderTimeInSecsStart == -1) {

                continue;
            }
            // Item is not available at this time
            else if($restrictOrderTimeInSecsStart != 0 && $restrictOrderTimeInSecsEnd !=0
                && ($secondsSinceMidnight < $restrictOrderTimeInSecsStart
                    || $secondsSinceMidnight > $restrictOrderTimeInSecsEnd)) {

                continue;
            }
            */

            ////////////////////////////////////////////////////////////////////////////

            $pre_responseArray[$categoryName][] = $itemDetails;

            if (!in_array($categoryName, $categoriesList)) {

                $categoriesList[$categoryName] = 1;
            }

            // 2nd Category Name
            if (!empty($categorySecondName)) {

                $pre_responseArray[$categorySecondName][] = $itemDetails;

                if (!in_array($categorySecondName, $categoriesList)) {

                    $categoriesList[$categorySecondName] = 1;
                }
            }

            // 3rd Category Name
            if (!empty($categoryThirdName)) {

                $pre_responseArray[$categoryThirdName][] = $itemDetails;

                if (!in_array($categoryThirdName, $categoriesList)) {

                    $categoriesList[$categoryThirdName] = 1;
                }
            }
        }

        // Get Categories and generate an ordered response array
        $responseArray = array();

        $objParseQueryItemCategoriesResults = parseExecuteQuery(array(), "RetailerItemCategories", "sequence");

        for ($i = 0; $i < count_like_php5($objParseQueryItemCategoriesResults); $i++) {

            $categoryName = $objParseQueryItemCategoriesResults[$i]->get("categoryName");

            if (isset($pre_responseArray[$categoryName])) {

                unset($categoriesList[$categoryName]);
                $responseArray[][$categoryName] = $pre_responseArray[$categoryName];
            }
        }

        // Do this for any Category was not picked up earlier from the Order Category table
        foreach ($categoriesList as $categoryName => $value) {

            $responseArray[][$categoryName] = $pre_responseArray[$categoryName];
        }
    }

    return $responseArray;
}

function getRetailerMenuItem($retailerId, $uniqueItemId)
{

    // Check if Retailer exists
    //$uniqueRetailerId = isCorrectObject(array("uniqueId" => $retailerId, "isActive" => true), "Retailers", "Retailer", "uniqueId", 0);

    // Find Item
    $objParseQueryItemModifiersResults = parseExecuteQuery(array(
        "uniqueRetailerItemId" => $uniqueItemId,
        "isActive" => true
    ), "RetailerItemModifiers", "modifierDisplaySequence");

    /*
    $objParseQueryItemModifiers = new ParseQuery("RetailerItemModifiers");
    $objParseQueryItemModifiers->equalTo("uniqueRetailerItemId", $uniqueItemId);
    $objParseQueryItemModifiers->equalTo("isActive", true);
    $objParseQueryItemModifiersResults = $objParseQueryItemModifiers->find();
    */

    // If Menu is NOT found, let caller know
    if (count_like_php5($objParseQueryItemModifiersResults) == 0) {

        $responseArray = array("found" => "0");
    } else {

        for ($i = 0; $i < count_like_php5($objParseQueryItemModifiersResults); $i++) {

            $modifierDetails = array();

            // This is ID is not needed for ordering, so no point sending it to App
            //$modifierDetails["modifierId"] = $objParseQueryItemModifiersResults[$i]->get("uniqueId");

            $modifierDetails["modifierDescription"] = $objParseQueryItemModifiersResults[$i]->get("modifierDescription");

            $modifierDetails["maxQuantity"] = $objParseQueryItemModifiersResults[$i]->get("maxQuantity");
            $modifierDetails["minQuantity"] = $objParseQueryItemModifiersResults[$i]->get("minQuantity");
            $modifierDetails["isRequired"] = $objParseQueryItemModifiersResults[$i]->get("isRequired");
            $modifierDetails["displaySequence"] = intval($objParseQueryItemModifiersResults[$i]->get("modifierDisplaySequence"));

            // If min quantity is greater than max, set it to match max
            // This happens because of data quality problems
            if ($modifierDetails["minQuantity"] > $modifierDetails["maxQuantity"]) {

                $modifierDetails["minQuantity"] = $modifierDetails["maxQuantity"];
            }

            // Get Option Details
            $objParseQueryItemModifiersOptionsResults = parseExecuteQuery(array(
                "uniqueRetailerItemModifierId" => $objParseQueryItemModifiersResults[$i]->get("uniqueId"),
                "isActive" => true
            ), "RetailerItemModifierOptions", "optionDisplaySequence");

            /*
            $objParseQueryItemModifiersOptions = new ParseQuery("RetailerItemModifierOptions");
            $objParseQueryItemModifiersOptions->equalTo("uniqueRetailerItemModifierId", $modifierDetails["modifierId"]);
            $objParseQueryItemModifiersOptions->equalTo("isActive", true);
            $objParseQueryItemModifiersOptionsResults = $objParseQueryItemModifiersOptions->find();
            */

            // If max > total options, then set max to match count of total options
            if ($modifierDetails["maxQuantity"] > count_like_php5($objParseQueryItemModifiersOptionsResults)) {

                $modifierDetails["maxQuantity"] = count_like_php5($objParseQueryItemModifiersOptionsResults);
            }

            $optionDetails = array();
            $optionCount = 0;
            for ($j = 0; $j < count_like_php5($objParseQueryItemModifiersOptionsResults); $j++) {

                $optionDetails[$optionCount]["optionId"] = $objParseQueryItemModifiersOptionsResults[$j]->get("uniqueId");
                $optionDetails[$optionCount]["optionName"] = !empty($objParseQueryItemModifiersOptionsResults[$j]->get("optionDisplayName")) ? $objParseQueryItemModifiersOptionsResults[$j]->get("optionDisplayName") : $objParseQueryItemModifiersOptionsResults[$j]->get("optionPOSName");
                $optionDetails[$optionCount]["optionDescription"] = $objParseQueryItemModifiersOptionsResults[$j]->get("optionDescription");
                $optionDetails[$optionCount]["pricePerUnit"] = $objParseQueryItemModifiersOptionsResults[$j]->get("pricePerUnit");
                $optionDetails[$optionCount]["pricePerUnitDisplay"] = dollar_format($objParseQueryItemModifiersOptionsResults[$j]->get("pricePerUnit"));

                $optionCount++;
            }

            $modifierDetails["options"] = $optionDetails;

            $modifierName = !empty($objParseQueryItemModifiersResults[$i]->get("modifierDisplayName")) ? $objParseQueryItemModifiersResults[$i]->get("modifierDisplayName") : $objParseQueryItemModifiersResults[$i]->get("modifierPOSName");

            $responseArray[$modifierName] = $modifierDetails;
        }
    }

    return $responseArray;
}

/**
 * get "Name" of the delivery (zDeliverySlackUser) that is connected to the order
 * if order is pickup order it returns empty string
 *
 * @param ParseObject $order
 * @return string
 */
function getCurrentDeliveryNameByOrder(ParseObject $order)
{

    // If not a delivery order
    // Or not yet submitted
    if ($order->get('fullfillmentType') !== 'd'
        || in_array($order->get('status'), listStatusesForCart())
    ) {
        return '';
    }

    $delivery = getCurrentDeliveryByOrder($order);
    if ($delivery === null) {
        return '';
    }

    return $delivery->get('deliveryName');
}

/**
 * gets delivery (Parse zDeliverySlackUser class) that is assigned to the order
 *
 * @param ParseObject $order
 *
 * @return ParseObject|null
 */
function getCurrentDeliveryByOrder(ParseObject $order)
{
    $deliverySlackOrderAssignments = parseExecuteQuery([
        "order" => $order,
    ],
        "zDeliverySlackOrderAssignments", '', '', ['deliveryUser'], 1, true
    );
    if (empty($deliverySlackOrderAssignments)) {
        return null;
    }

    return $deliverySlackOrderAssignments->get('deliveryUser');
}

function logAccountDuplicateUsage($currentUser, $duplicateUser, $typeOfUsage)
{

    if (count_like_php5($duplicateUser) > 0
        && isset($duplicateUser[0])
    ) {

        $duplicateUser = $duplicateUser[0];
    }

    $zDuplicateUsage = new ParseObject("zDuplicateUsage");
    $zDuplicateUsage->set("currentUser", $currentUser);

    if (!empty($duplicateUser)) {

        $zDuplicateUsage->set("duplicateUser", $duplicateUser);
    }

    $zDuplicateUsage->set("typeOfUsage", $typeOfUsage);
    $zDuplicateUsage->save();
}

function logInvalidSignupCoupons($parseUser, $parseCoupon = "", $couponCode = "")
{

    $parseInvalidAttempts = new ParseObject("zLogInvalidSignupCoupons");
    $parseInvalidAttempts->set("user", $parseUser);

    if (!empty($parseCoupon)) {

        $parseInvalidAttempts->set("coupon", $parseCoupon);
    }

    $parseInvalidAttempts->set("couponCode", $couponCode);
    $parseInvalidAttempts->save();

    return $parseInvalidAttempts;
}

function isOrderCancellable($order)
{

    $orderProcessingErrorHalted = parseExecuteQuery(["order" => $order, "processStatusFlag" => 0],
        "OrderProcessingErrors", "", "updatedAt", [], 1);

    // Is in Submitted state and was stopped by OrderProcessingErrors
    if (in_array($order->get("status"),
            listStatusesForSubmitted()) && count_like_php5($orderProcessingErrorHalted) > 0
    ) {

        return true;
    }
    // Or orders that have passed payment processing stage
    // But not canceled or completed
    else {
        if (in_array($order->get("status"), listStatusesForCancellableOrder())) {

            return true;
        } else {

            return false;
        }
    }

    /*
    Cancel only orders not yet accepted by Retailer
    // Awaiting response from retailer
    if(in_array($order->get("status"), listStatusesForAwaitingConfirmation())) {

        return true;
    }
    else {

        $orderProcessingErrorHalted = parseExecuteQuery(["order" => $order, "processStatusFlag" => 0], "OrderProcessingErrors", "", "updatedAt", [], 1);

        // Is in Submitted state and was stopped by OrderProcessingErrors
        if(in_array($order->get("status"), listStatusesForSubmitted())
            && count_like_php5($orderProcessingErrorHalted) > 0) {

            return true;
        }
        else {

            return false;
        }
    }
    */

    return $isCancellable;
}

//////////////////////////// TEMP METHODS /////////////////////////////////

function isOrderManualPushable($order)
{

    // Ensure queue worker was up in last 3 mins
    if (getDynoLastSeenByTypeFromCache("queueworker") < (time() - 3 * 60)) {

        return false;
    }

    // If Pushed within last 60 secs, ignore
    if (wasOrderManuallyPushedInLast60Seconds($order->getObjectId())) {

        return false;
    }

    // Is a Submitted Status of 2 only
    // And its been at least 5 mins since submission
    else {
        if (in_array($order->get("status"), [2])
            && ((time() - $order->get("submitTimestamp")) / 60) > 5
        ) {

            return true;
        } else {

            $orderProcessingErrorHalted = parseExecuteQuery(["order" => $order, "processStatusFlag" => 0],
                "OrderProcessingErrors", "", "updatedAt", [], 1);

            // Else in any of the submitted statuses but halted via Order Processing Errors
            // This status includes Scheduled
            if (in_array($order->get("status"), listStatusesForSubmitted())
                && count_like_php5($orderProcessingErrorHalted) > 0
            ) {

                return true;
            } else {

                return false;
            }
        }
    }
}

function manuallyPushStuckOrder($order)
{

    if (!isOrderManualPushable($order)) {

        return [0, "Order can not be pushed."];
    }

    try {

        // Clear all existing pending errors
        $orderProcessingErrorHalted = parseExecuteQuery(["order" => $order], "OrderProcessingErrors", "", "updatedAt");

        foreach ($orderProcessingErrorHalted as $orderProcessingError) {

            // Clear the Order Processing errors
            if ($orderProcessingError->get("processStatusFlag") == 0) {

                $orderProcessingErrorClear = new ParseObject("OrderProcessingErrors",
                    $orderProcessingError->getObjectId());
                $orderProcessingErrorClear->set("processStatusFlag", 2);
                $orderProcessingErrorClear->save();
            }
        }

        // Mark that we are manually pushing this order so it can't be pushed again in next minute
        markOrderManuallyPushed($order->getObjectId());

        // Log the pushed order
        logManuallyPushedOrder($order->getObjectId());

        // If submission attempt of Order is set 2, decrement it
        if ($order->get("submissionAttempt") > 1) {

            decrementSubmissionAttempt($order);
        }

        // Push order
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue->sendMessage(
            array(
                "action" => "order_submission_process",
                "processAfter" => ["timestamp" => time()],
                "content" =>
                    array(
                        "orderId" => $order->getObjectId(),
                        "backOnQueue" => false
                    )
            ),
            7
        );
    } catch (Exception $ex) {

        $response = json_decode($ex->getMessage(), true);

        json_error($response["error_code"], "", $response["error_message_log"] . " OrderId - " . $order->getObjectId(),
            2, 1);

        return [0, "Order push failed. " . $response["error_message_log"]];
    }

    return [1, "Order pushed!"];
}

function wasOrderManuallyPushedInLast60Seconds($orderId)
{

    $cacheKey = "__OPS_PUSHORDER_" . $orderId;

    if (doesCacheExist($cacheKey)) {

        return true;
    } else {

        return false;
    }
}

function markOrderManuallyPushed($orderId)
{

    $cacheKey = "__OPS_PUSHORDER_" . $orderId;

    setCache($cacheKey, "1", 0, 60);
}

function logManuallyPushedOrder($orderId)
{

    if ($GLOBALS['env_LogQueueTransactionsToDB'] == true) {

        try {
            $logsPdoConenction = new PDO('mysql:host=' . $GLOBALS['env_mysqlLogsDataBaseHost'] . ';port=' . $GLOBALS['env_mysqlLogsDataBasePort'] . ';dbname=' . $GLOBALS['env_mysqlLogsDataBaseName'],
                $GLOBALS['env_mysqlLogsDataBaseUser'], $GLOBALS['env_mysqlLogsDataBasePassword'],
                [PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../cert/rds-combined-ca-bundle.pem']);
            $GLOBALS['logsPdoConnection'] = $logsPdoConenction;
        } catch (Exception $e) {
            $GLOBALS['logsPdoConnection'] = null;
            // @todo logging lack of connection
        }

        if ($GLOBALS['logsPdoConnection'] instanceof PDO) {
            //$pingLogRepository = new PingLogMysqlRepository($GLOBALS['logsPdoConnection']);
            $pingLogService = \App\Background\Services\LogServiceFactory::create();
            $pingLogService->logQueueMessageTracffic($orderId, 'order_push', 'push', '', '', ''
            );
        }
    }
}

function getSubTotalInCentsForOrder($order)
{

    return intval(round(json_decode($order->get("totalsWithFees"), true)["subTotal"]));
}

function getOrderPaidAmountInCents($order)
{

    return intval(round(json_decode($order->get("totalsWithFees"), true)["Total"]));
}

function getOrderPaidAmountInCredits($order)
{

    return 0;
    return json_decode($order->get("totalsWithFees"), true)["CreditsAppliedInCents"];
}

function isItemCommentToBeTreated($user)
{

    // Only remove when env variable is set and for v1.6.0 of iOS
    if ($GLOBALS['env_RemoveSpecialCharsFromItemComment'] == true
        && !empty($user)
    ) {

        $lastSessionDevice = getCurrentSessionDevice($GLOBALS['user']->getSessionToken());
        $isIos = $lastSessionDevice->get('userDevice')->get('isIos');
        $appVersion = $lastSessionDevice->get('userDevice')->get('appVersion');
        $appVersion = intval(str_replace('.', '', $appVersion));

        if ($isIos == true && $appVersion == 160) {

            return true;
        }
    }

    return false;
}

function getOrderTaxes($itemList, $retailerTaxRate)
{

    // Total Cart Tax
    $taxTotal = 0;

    // Cart Total pre-tax
    $subTotal = 0;

    foreach ($itemList as $itemOrderId => $itemDetails) {

        if ($itemDetails["itemTaxRate"] < 0) {

            $taxRate = $retailerTaxRate;
        } else {

            $taxRate = $itemDetails["itemTaxRate"];
        }

        $itemList[$itemOrderId]["itemAppliedTaxRate"] = $taxRate;

        // Item Only tax Per Unit
        $itemList[$itemOrderId]["itemTax"] = dollar_format_float($itemDetails["itemTaxablePrice"] * $taxRate);
        $itemList[$itemOrderId]["itemTaxDisplay"] = dollar_format($itemList[$itemOrderId]["itemTax"]);

        // With Quantities => Item + Modifiers Total => tax
        $itemList[$itemOrderId]["itemTotalTaxWithModifiers"] = dollar_format_float($itemDetails["itemTotalPriceWithModifiersTaxble"] * $taxRate);
        $itemList[$itemOrderId]["itemTotalTaxWithModifiersDisplay"] = dollar_format($itemList[$itemOrderId]["itemTotalTaxWithModifiers"]);

        if (isset($itemDetails["options"])) {
            foreach ($itemDetails["options"] as $key => $optionDetails) {

                // Option Only tax Per unit
                $itemList[$itemOrderId]["options"][$key]["taxPerUnit"] = dollar_format_float($optionDetails["pricePerUnitTaxable"] * $taxRate);
                $itemList[$itemOrderId]["options"][$key]["taxPerUnitDisplay"] = dollar_format($itemList[$itemOrderId]["options"][$key]["taxPerUnit"]);
            }
        }

        $taxTotal += $itemList[$itemOrderId]["itemTotalTaxWithModifiers"];
        $subTotal += $itemDetails["itemTotalPriceWithModifiersTaxble"];
    }

    return [$itemList, $taxTotal, $subTotal];
}

function getOrderTaxes3rdParty($itemList, $retailerTaxRate, $orderPOSConfig, $order)
{

    // JMD
    // JMD
    if (strcasecmp($orderPOSConfig->get('dualPartnerConfig')->get('partner'), 'hmshost') == 0) {

        try {

            // Check cache
            list($subTotal, $taxes) = getCacheHMSHostTaxForOrder($order->getObjectId());

            if (empty($taxes)) {

                $tempItems = [];
                foreach ($itemList as $item) {

                    $tempItems["items"][] = $item;
                }

                // If there are items in the cart
                if (count_like_php5($tempItems) > 0) {

                    $hmshost = new HMSHost($orderPOSConfig->get('dualPartnerConfig')->get('airportId'),
                        $orderPOSConfig->get('dualPartnerConfig')->get('retailerId'),
                        $order->get('retailer')->get('uniqueId'), 'order');

                    list($subTotal, $taxes) = $hmshost->get_taxes_and_subtotal($order->get('orderSequenceId'),
                        $tempItems);

                    if (empty($taxes) || $taxes < 0) {

                        throw new Exception($order->getObjectId() . " taxes returned as 0 or negative (" . $taxes . ")");
                    }
                } else {

                    $taxes = 0;
                }

                // set for cache
                setCacheHMSHostTaxForOrder($order->getObjectId(), [$subTotal, $taxes]);
            }
        } catch (Exception $ex) {

            json_error("AS_896", "", "Tax calculation for HMSHost failed - " . $ex->getMessage(), 1);
        }

        // Total Cart Tax
        $taxTotal = $taxes;

        // Cart Total pre-tax
        // $subTotal = $;

        // Since we don't get taxes at item level
        // We will not be able to refund these orders by items
        // Setting -1 tax rate, ensuring we can't refund these at item level
        // Either the whole order has to be canceled or refunded, or courtesy refund only
        foreach ($itemList as $itemOrderId => $itemDetails) {

            $itemList[$itemOrderId]["itemAppliedTaxRate"] = -1;

            // Item Only tax Per Unit
            $itemList[$itemOrderId]["itemTax"] = -1;
            $itemList[$itemOrderId]["itemTaxDisplay"] = dollar_format($itemList[$itemOrderId]["itemTax"]);

            // With Quantities => Item + Modifiers Total => tax
            $itemList[$itemOrderId]["itemTotalTaxWithModifiers"] = -1;
            $itemList[$itemOrderId]["itemTotalTaxWithModifiersDisplay"] = dollar_format($itemList[$itemOrderId]["itemTotalTaxWithModifiers"]);

            if (isset($itemDetails["options"])) {
                foreach ($itemDetails["options"] as $key => $optionDetails) {

                    // Option Only tax Per unit
                    $itemList[$itemOrderId]["options"][$key]["taxPerUnit"] = -1;
                    $itemList[$itemOrderId]["options"][$key]["taxPerUnitDisplay"] = dollar_format($itemList[$itemOrderId]["options"][$key]["taxPerUnit"]);
                }
            }

            $subTotal += $itemDetails["itemTotalPriceWithModifiersTaxble"];
        }

        return [$itemList, $taxTotal, $subTotal];
    } else {

        json_error("AS_897", "", "Tax calculation for failed - Invalid partner", 1);
    }
}

function getOrderSummaryItemlist($order, $dayOfWeekAtAirport)
{

    $subTotal = 0;
    $taxesAtItemLevel = 0;
    $subTotalAlreadyTaxedAtItemLevel = 0;
    $itemQuantityCount = 0;
    $orderNotAllowedThruSecurity = false;
    $preResponseArray = array();
    $retailerObjectResults = $order->get('retailer');

    $orderModifierObjectResults = parseExecuteQuery(array("order" => $order), "OrderModifiers", "", "",
        array("retailerItem", "retailerItem.taxCategory"));

    $itemsDeactivated = [];
    for ($i = 0; $i < count_like_php5($orderModifierObjectResults); $i++) {

        $itemLevelSubTotalTemp = 0;
        $itemLevelTax = 0;
        $itemLevelTaxRate = -1;

        // Item was deactivated after it was put in cart
        if (!$orderModifierObjectResults[$i]->has('retailerItem') || $orderModifierObjectResults[$i]->get('retailerItem')->get('isActive') == false) {

            $itemsDeactivated[] = $orderModifierObjectResults[$i]->get('retailerItem')->get('uniqueId');

            // Remove item from cart
            $orderModifierObjectResults[$i]->destroy();
            $orderModifierObjectResults[$i]->save();
            continue;
        }

        // if(!$orderModifierObjectResults[$i]->has('retailerItem')) {

        //     continue;
        // }

        // If tax category exists at item level, then set tax Rate
        if ($orderModifierObjectResults[$i]->get('retailerItem')->has('taxCategory')
            && !empty($orderModifierObjectResults[$i]->get('retailerItem')->get('taxCategory'))
        ) {

            $itemLevelTaxRate = $orderModifierObjectResults[$i]->get('retailerItem')->get('taxCategory')->get('taxRate');
        }

        $itemDetails = array();

        ////////////////////////////////////////////////////
        $itemDetails["allowedThruSecurity"] = !is_bool($orderModifierObjectResults[$i]->get('retailerItem')->get('allowedThruSecurity')) ? true : $orderModifierObjectResults[$i]->get('retailerItem')->get('allowedThruSecurity');

        // This sets the order level property indicating that at least one item in the order is NOT allowed through security
        if ($itemDetails["allowedThruSecurity"] == false
            && $orderNotAllowedThruSecurity != true
        ) {

            $orderNotAllowedThruSecurity = true;
        }
        ////////////////////////////////////////////////////

        $orderItemId = $itemDetails["itemOrderId"] = $orderModifierObjectResults[$i]->getObjectId();
        $itemDetails["itemCategoryName"] = $orderModifierObjectResults[$i]->get('retailerItem')->get('itemCategoryName');
        $itemDetails["itemSecondCategoryName"] = $orderModifierObjectResults[$i]->get('retailerItem')->get('itemSecondCategoryName');
        $itemDetails["itemThirdCategoryName"] = $orderModifierObjectResults[$i]->get('retailerItem')->get('itemThirdCategoryName');
        $itemDetails["itemId"] = $orderModifierObjectResults[$i]->get('retailerItem')->get('uniqueId');
        $itemDetails["extItemId"] = $orderModifierObjectResults[$i]->get('retailerItem')->get('itemId');
        $itemDetails["itemQuantity"] = $orderModifierObjectResults[$i]->get('itemQuantity');
        $itemDetails["itemComment"] = html_entity_decode(removeAllSpecialCharactersManuallyForCart($orderModifierObjectResults[$i]->get('itemComment'),
            ENT_QUOTES));

        $itemQuantityCount += $itemDetails["itemQuantity"];

        // Get Item details
        $objParseQueryRetailersItemsResults[0] = $orderModifierObjectResults[$i]->get('retailerItem');

        if (count_like_php5($objParseQueryRetailersItemsResults) == 0 && !isset($objParseQueryRetailersItemsResults[0])) {

            json_error("AS_846", "", "RetailerItem not found! Item Id: " . $itemDetails["itemId"], 1);
        }

        $itemDetails["itemName"] = !empty($objParseQueryRetailersItemsResults[0]->get("itemDisplayName")) ? $objParseQueryRetailersItemsResults[0]->get("itemDisplayName") : $objParseQueryRetailersItemsResults[0]->get("itemPOSName");
        $itemDetails["itemDescription"] = $objParseQueryRetailersItemsResults[0]->get("itemDisplayDescription");
        $itemDetails["itemImageURL"] = !empty($objParseQueryRetailersItemsResults[0]->get("itemImageURL")) ? preparePublicS3URL($objParseQueryRetailersItemsResults[0]->get("itemImageURL"),
            getS3KeyPath_ImagesRetailerItem($retailerObjectResults->get('airportIataCode')),
            $GLOBALS['env_S3Endpoint']) : "";

        $itemDetails["itemImageThumbURL"] = !empty($objParseQueryRetailersItemsResults[0]->get("itemImageURL")) ? preparePublicS3URL('thumb_' . $objParseQueryRetailersItemsResults[0]->get("itemImageURL"),
            getS3KeyPath_ImagesRetailerItem($retailerObjectResults->get('airportIataCode')),
            $GLOBALS['env_S3Endpoint']) : "";

        $itemDetails["itemPrice"] = dollar_format_float($objParseQueryRetailersItemsResults[0]->get("itemPrice"));
        $itemDetails["itemPriceDisplay"] = dollar_format($objParseQueryRetailersItemsResults[0]->get("itemPrice"));
        $itemDetails["itemTotalPrice"] = dollar_format_float($objParseQueryRetailersItemsResults[0]->get("itemPrice") * $orderModifierObjectResults[$i]->get('itemQuantity'));
        $itemDetails["itemTotalPriceDisplay"] = dollar_format($objParseQueryRetailersItemsResults[0]->get("itemPrice") * $orderModifierObjectResults[$i]->get('itemQuantity'));

        /////////////////////////////////////////////////////////////////////////////////////////////////
        // Item Order Time Restrictions
        /*
        list($restrictOrderTimeInSecsStart, $restrictOrderTimeInSecsEnd) = getItemRestrictTimes($objParseQueryRetailersItemsResults[0]->get("uniqueId"),
            $dayOfWeekAtAirport);

        $itemDetails["restrictOrderTimeInSecsStart"] = $restrictOrderTimeInSecsStart;
        $itemDetails["restrictOrderTimeInSecsEnd"] = $restrictOrderTimeInSecsEnd;
        */


        $retailerItemTimeRestrictionService = \App\Consumer\Services\RetailerItemTimeRestrictionServiceFactory::create();
        $retailerItemTimeRestrictions = $retailerItemTimeRestrictionService->getTimeRestrictionByRetailerItemUniqueIdAndDay(
            $objParseQueryRetailersItemsResults[0]->get("uniqueId"),
            $dayOfWeekAtAirport
        );

        $itemDetails = array_merge($itemDetails, $retailerItemTimeRestrictions->asArray());


        /////////////////////////////////////////////////////////////////////////////////////////////////

        $preResponseArray[$orderItemId] = $itemDetails;

        // Item Price * Quantity
        $itemLevelSubTotalTemp = ($objParseQueryRetailersItemsResults[0]->get("itemPrice") * $orderModifierObjectResults[$i]->get('itemQuantity'));

        // Check if need modifiers
        $needModifier = doesItemHaveModifiers($itemDetails["itemId"]);

        if ($needModifier && !empty($orderModifierObjectResults[$i]->get('modifierOptions'))) {

            // Get Modifier info
            $modifierOptions = json_decode($orderModifierObjectResults[$i]->get('modifierOptions'), true);

            for ($k = 0; $k < count_like_php5($modifierOptions); $k++) {

                $subTotalAtItemModifier = 0;

                //$objParseQueryItemModifiersOptionsResults = parseExecuteQuery(array("uniqueId" => $modifierOptions[$k]["id"]), "RetailerItemModifierOptions");
                $objParseQueryItemModifiersOptionsResults = parseExecuteQuery(array(
                    "uniqueId" => $modifierOptions[$k]["id"],
                    'isActive' => true
                ), "RetailerItemModifierOptions");

                if (count_like_php5($objParseQueryItemModifiersOptionsResults) == 0 && !isset($objParseQueryItemModifiersOptionsResults[0])) {

                    // continue;
                    json_error("AS_852", "",
                        "RetailerItemModifierOptions not found! Modifier Option Unique Id: " . $modifierOptions[$k]["id"],
                        1);
                }

                $objParseQueryItemModifiersResults = parseExecuteQuery(array("uniqueId" => $objParseQueryItemModifiersOptionsResults[0]->get("uniqueRetailerItemModifierId")),
                    "RetailerItemModifiers");

                if (count_like_php5($objParseQueryItemModifiersResults) == 0 && !isset($objParseQueryItemModifiersResults[0])) {

                    // continue;
                    json_error("AS_853", "",
                        "RetailerItemModifiers not found! Modifier Unique Id: " . $objParseQueryItemModifiersOptionsResults[0]->get("uniqueRetailerItemModifierId"),
                        1);
                }

                $optionDetails["optionId"] = $objParseQueryItemModifiersOptionsResults[0]->get("uniqueId");
                $optionDetails["extOptionId"] = $objParseQueryItemModifiersOptionsResults[0]->get("optionId");
                $optionDetails["optionName"] = !empty($objParseQueryItemModifiersOptionsResults[0]->get("optionDisplayName")) ? $objParseQueryItemModifiersOptionsResults[0]->get("optionDisplayName") : $objParseQueryItemModifiersOptionsResults[0]->get("optionPOSName");
                $optionDetails["modifierName"] = !empty($objParseQueryItemModifiersResults[0]->get("modifierDisplayName")) ? $objParseQueryItemModifiersResults[0]->get("modifierDisplayName") : $objParseQueryItemModifiersResults[0]->get("modifierPOSName");
                $optionDetails["optionQuantity"] = $modifierOptions[$k]["quantity"];
                $optionDetails["pricePerUnit"] = $objParseQueryItemModifiersOptionsResults[0]->get("pricePerUnit");
                $optionDetails["pricePerUnitDisplay"] = dollar_format($objParseQueryItemModifiersOptionsResults[0]->get("pricePerUnit"));
                $optionDetails["priceTotal"] = dollar_format_float($objParseQueryItemModifiersOptionsResults[0]->get("pricePerUnit") * $modifierOptions[$k]["quantity"] * $orderModifierObjectResults[$i]->get('itemQuantity'));
                $optionDetails["priceTotalDisplay"] = dollar_format($objParseQueryItemModifiersOptionsResults[0]->get("pricePerUnit") * $modifierOptions[$k]["quantity"] * $orderModifierObjectResults[$i]->get('itemQuantity'));

                // Calculate Modifier subtotal - Modifier Option Price * Modifier Quant
                $itemLevelSubTotalTemp += ($objParseQueryItemModifiersOptionsResults[0]->get("pricePerUnit") * $modifierOptions[$k]["quantity"] * $orderModifierObjectResults[$i]->get('itemQuantity'));

                $preResponseArray[$orderItemId]["options"][] = $optionDetails;
            }
        }

        $preResponseArray[$orderItemId]["itemTotalPriceWithModifiers"] = dollar_format_float($itemLevelSubTotalTemp);
        $preResponseArray[$orderItemId]["itemTotalPriceWithModifiersDisplay"] = dollar_format($itemLevelSubTotalTemp);

        // Add to oveerall subtotal
        $subTotal += $itemLevelSubTotalTemp;

        // If we have tax rate at item level, then at add the item sub total level as well
        // and then calculate tax on it
        if ($itemLevelTaxRate >= 0) {

            $subTotalAlreadyTaxedAtItemLevel += $itemLevelSubTotalTemp;
            $taxesAtItemLevel += dollar_format_float($itemLevelTaxRate * $itemLevelSubTotalTemp);
        }
    }

    return [
        $preResponseArray,
        $subTotalAlreadyTaxedAtItemLevel,
        $taxesAtItemLevel,
        $itemQuantityCount,
        $orderNotAllowedThruSecurity,
        $subTotal,
        $itemsDeactivated
    ];
}

function getOrderSummaryApplyCoupon(
    $order,
    $couponObj,
    $availableUserCoupon,
    $skipCouponValidationForPromocode,
    $totalsSoFar,
    $fullfillmentType = '',
    $userCreditsAppliedMap
) {

    $couponOrderMinMet = false;
    $couponForFee = false;
    $invalidReasonLog = '';

    // if $skipCouponValidationForPromocode == true, means that coupon is populated automatically from User Coupons
    // those coupons are already checked and are valid
    if ($skipCouponValidationForPromocode == true) {

        $isValid = true;
        $couponObj = $availableUserCoupon->get('coupon');
        $couponAppliedByDefault = true;
    } // Else let's verify User provided coupon
    else {

        list($couponObj, $isValid, $invalidReasonUser, $invalidReasonLog) = fetchValidCoupon($couponObj, "",
            $order->get('retailer'), $order->get('user'), false, true, $userCreditsAppliedMap);
        $couponAppliedByDefault = false;
    }

    // Check if this limited for a fullfillmentType
    $isFullfillmentRestrictionApplied = false;

    // For Delivery only
    // But if the order is not for delivery
    if (strcasecmp($couponObj->get('fullfillmentTypeRestrict'), 'd') == 0
        && !empty($fullfillmentType)
        && strcasecmp($fullfillmentType, 'd') != 0
    ) {

        $isValid = false;
        $isFullfillmentRestrictionApplied = true;
    }

    // For Pickup only
    // But if the order is not for pickup
    else {
        if (strcasecmp($couponObj->get('fullfillmentTypeRestrict'), 'p') == 0
            && !empty($fullfillmentType)
            && strcasecmp($fullfillmentType, 'p') != 0
        ) {

            $isValid = false;
            $isFullfillmentRestrictionApplied = true;
        }
    }

    // If the coupon is Valid
    if ($isValid == true) {

        $couponSavings = 0;
        $couponSavingsDisplayDefault = '';
        if ($couponObj->has('savingsTextDisplay')
            && !empty($couponObj->get('savingsTextDisplay'))
        ) {

            $couponSavingsDisplayDefault = ' ' . $couponObj->get('savingsTextDisplay');
        }

        $couponSavingsDisplay = dollar_format($couponSavings) . $couponSavingsDisplayDefault;

        $isRetailerCompensated = $couponObj->get('isRetailerCompensated');

        $couponDiscountForFeeCents = intval($couponObj->get('couponDiscountForFeeCents'));
        $couponDiscountForFeePCT = $couponObj->get('couponDiscountForFeePCT');
        $couponDiscountPCT = floatval($couponObj->get('couponDiscountPCT'));
        $couponDiscountPCTMaxCents = intval($couponObj->get('couponDiscountPCTMaxCents'));
        $couponDiscountCents = intval($couponObj->get('couponDiscountCents'));
        $applyDiscountToOrderMinOfInCents = intval($couponObj->get('applyDiscountToOrderMinOfInCents'));
        $couponForFeeFixed = 0;
        $couponForFeePCT = 0;

        // Verify if Order minimum is met for this coupon
        if ($applyDiscountToOrderMinOfInCents > 0
            && $totalsSoFar < $applyDiscountToOrderMinOfInCents
        ) {

            $couponOrderMinMet = false;
            $couponSavings = 0;
            $couponSavingsDisplay = dollar_format($couponSavings) . $couponSavingsDisplayDefault;
            $couponForFeeFixed = 0;
            $couponForFeePCT = 0;
            $couponForFee = false;
        } else {

            $totals["CouponOrderMinMet"] = true;
            $couponOrderMinMet = true;

            // Fixed fullfillment fee
            if (!empty($couponDiscountForFeeCents)) {

                $couponForFee = true;
                $couponSavings = 0;
                $couponForFeeFixed = $couponDiscountForFeeCents;
                $couponForFeePCT = 0;
                $couponSavingsDisplay = 'Discounted delivery!' . $couponSavingsDisplayDefault;
            } // PCT fullfillment fee
            else {
                if (!empty($couponDiscountForFeePCT)) {

                    $couponForFee = true;
                    $couponSavings = 0;
                    $couponForFeeFixed = 0;

                    if ($couponDiscountForFeePCT == 1) {

                        $couponSavingsDisplay = 'Free Delivery!' . $couponSavingsDisplayDefault;
                        $couponForFeePCT = $couponDiscountForFeePCT;
                    } else {

                        $couponSavingsDisplay = 'Discounted delivery!' . $couponSavingsDisplayDefault;
                        $couponForFeePCT = $couponDiscountForFeePCT;
                    }
                }
            }

            // If Coupon is of % off Type
            if (!empty($couponDiscountPCT)) {

                $couponSavings = $couponDiscountPCT * $totalsSoFar;

                // Ensure the savings aren't more than the max allowed for a coupon with percentage savings
                if ($couponDiscountPCTMaxCents > 0
                    && $couponSavings > $couponDiscountPCTMaxCents
                ) {

                    $couponSavings = $couponDiscountPCTMaxCents;
                }

                $couponSavingsDisplay = '-' . dollar_format($couponSavings) . $couponSavingsDisplayDefault;
            } // If Coupon is of $ off Type
            else {
                if (!empty($couponDiscountCents)) {

                    $couponSavings = $couponDiscountCents;
                    $couponSavingsDisplay = '-' . dollar_format($couponSavings) . $couponSavingsDisplayDefault;
                }
            }
        }

        return [
            $couponSavings,
            $couponSavingsDisplay,
            $couponForFeeFixed,
            $couponForFeePCT,
            $couponObj->get('couponCode'),
            $couponForFee,
            $couponAppliedByDefault,
            $couponOrderMinMet,
            $isFullfillmentRestrictionApplied
        ];
    } else {

        // Non-existing error
        json_error("AS_894", "",
            "Coupon found in order but not in available list or not applicable for user after added to cart. Coupon Id = (" . $couponObj->get('couponCode') . " - " . $invalidReasonLog,
            2, 1);

        return [
            0,
            dollar_format(0),
            0,
            0,
            $couponObj->get('couponCode'),
            false,
            false,
            false,
            $isFullfillmentRestrictionApplied
        ];
    }
}

//////////////// Referral Award ///////////////

function generateReferralCode($user, $checkUserReferral = true)
{

    if ($checkUserReferral) {

        // Check if this user already has a code attributed to it
        $results = parseExecuteQuery(["user" => $user], "UserReferral");
        if (count_like_php5($results) > 0) {

            return $results[0]->get("referralCode");
        }
    }

    // Initialize the code with the stem
    $createSequence = new ParseQuery("Sequences");
    $createSequence->equalTo('keyName', 'referral');
    $sequenceObject = $createSequence->first();

    $randomNumber = mt_rand(1, 15);

    for ($i = 0; $i <= $randomNumber; $i++) {

        $sequenceObject->increment('sequenceNumber');
    }

    $sequenceObject->save();
    $sequenceId = $sequenceObject->get('sequenceNumber');

    $codeStem = generateReferralCodeStem($user);
    $codeSuffix = generateReferralCodeSuffix($user);
    $referralCode = strtoupper($codeStem . $sequenceId . $codeSuffix);

    // Attribute the code to the user
    $userReferral = new ParseObject("UserReferral");
    $userReferral->set("user", $user);
    $userReferral->set("referralCode", $referralCode);
    $userReferral->save();

    /*
        $increment = "";
        $referralCode = $codeStem . $increment;
        $uniqueCodeAttributed = false;

        while($uniqueCodeAttributed == false) {

            // Check if no one else is holding this code in the last 5 mins
            while(getNewReferralCodeCounter($referralCode) > 1) {

                if(empty($increment)) {

                    $increment = 0;
                }

                $increment++;
                $referralCode = $codeStem . $increment;
            }

            // Check the database if this code is available
            $results = parseExecuteQuery(["referralCode" => strtoupper($referralCode)], "UserReferral");

            if(count_like_php5($results) == 0) {

                // Attribute the code to the user
                $userReferral = new ParseObject("UserReferral");
                $userReferral->set("user", $user);
                $userReferral->set("referralCode", strtoupper($referralCode));
                $userReferral->save();

                $uniqueCodeAttributed = true;
            }
        }
    */

    return $referralCode;
}

function isCodeOfReferralType($code)
{

    if (preg_match("/^" . generateReferralCodeStem() . "(.*)/si", $code)) {

        return true;
    }

    return false;
}

function generateReferralCodeStem($user = "")
{

    return "Z";
    // return "Z" . replaceSpecialChars($user->get('firstName')) . substr(replaceSpecialChars($user->get('lastName')), 0, 1);
}

function generateReferralCodeSuffix($user)
{

    $firstCharacter = substr(replaceSpecialChars($user->get('firstName')), 0, 1);

    if (empty($firstCharacter)) {

        $firstCharacter = substr(replaceSpecialChars($user->get('lastName')), 0, 1);
    }

    return $firstCharacter;
}

function getReferralCode($user, $userReferral = '')
{

    if (empty($userReferral)) {

        // Check if this user already has a code attributed to it
        $results = parseExecuteQuery(["user" => $user], "UserReferral", "", "", [], 1);
        if (count_like_php5($results) > 0) {

            return $results->get("referralCode");
        }
    } else {

        return $userReferral->get("referralCode");
    }

    return "";
}

function getTotalReferralRewardEarned($userOfReferral)
{

    $rewardInCents = 0;

    // $referralRewards = parseExecuteQuery(["user" => $userOfReferral, "__SW__reasonForCredit" => "Referral Reward", "__GTE__expireTimestamp" => time()], "UserCredits");
    $referralRewards = parseExecuteQuery([
        "user" => $userOfReferral,
        "reasonForCreditCode" => getUserCreditReasonCode('ReferralReward'),
        "__GTE__expireTimestamp" => time()
    ], "UserCredits");

    foreach ($referralRewards as $reward) {

        $rewardInCents += $reward->get("creditsInCents");
    }

    return $rewardInCents;
}

// When the Order is completed
// Qualify: Step 1 Parent, schedule queue message
function referralEarningValidationProcessing($order)
{

    // If the user was acquired through referral
    // And we have not yet awardd the earnings for him/her
    if (wasUserBeenAcquiredViaReferral($order->get('user')) == true
        && hasReferralAlreadyBeenEarned($order->get('user')) == false
    ) {

        // Send a message to verify award earn
        try {

            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue->sendMessage(
                array(
                    "action" => "referral_reward_earn_qualify",
                    "content" =>
                        array(

                            "orderId" => $order->getObjectId(),
                            "objectIdReferred" => $order->get('user')->getObjectId()

                        )
                )
            );
        } catch (Exception $ex) {

            return json_decode($ex->getMessage(), true);
        }
    }

    return "";
}

// Qualify Reward: Step 1
function wasUserBeenAcquiredViaReferral($user)
{

    // $userCredit = parseExecuteQuery(["reasonForCredit" => "Signup Referral Code", "user" => $user], "UserCredits", "", "createdAt", ["userReferral.user"], 1);
    $userCredit = parseExecuteQuery([
        "reasonForCreditCode" => getUserCreditReasonCode('ReferralSignup'),
        "user" => $user
    ], "UserCredits", "", "createdAt", ["userReferral.user"], 1);

    if (count_like_php5($userCredit) > 0
        && !empty($userCredit->get("userReferral"))
    ) {

        return true;
    }

    return false;
}

// Qualify Reward: Step 2
function hasReferralAlreadyBeenEarned($user)
{

    // Find the user who was the referrer
    // $userWhoWasReferred = parseExecuteQuery(["reasonForCredit" => "Signup Referral Code", "user" => $user], "UserCredits", "", "createdAt", ["userReferral.user"], 1);
    $userWhoWasReferred = parseExecuteQuery([
        "reasonForCreditCode" => getUserCreditReasonCode('ReferralSignup'),
        "user" => $user
    ], "UserCredits", "", "createdAt", ["userReferral.user"], 1);

    // Check if UserReferralUsage.rewardAwarded = true
    $userWhoReferred = parseExecuteQuery([
        "userReferral" => $userWhoWasReferred->get('userReferral'),
        "userReferred" => $user,
        "rewardAwarded" => true
    ], "UserReferralUsage");

    if (count_like_php5($userWhoReferred) > 0) {

        return true;
    }

    return false;
}

// Qualify Reward: Step 3
function scheduleRewardEarn($order, $userWhoReferred, $overrideReward, $overrideRewardReason)
{

    // Schedule queue message to award user
    try {

        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue->sendMessage(
            array(
                "action" => "referral_reward_process",
                "processAfter" => ["timestamp" => (time() + getUserReferralWaitInSecsBeforeAward())],
                "content" =>
                    array(
                        "orderId" => $order->getObjectId(),
                        "userWhoReferred" => $userWhoReferred->getObjectId(),
                        "overrideReward" => $overrideReward,
                        "overrideRewardReason" => $overrideRewardReason
                    )
            ),
            getUserReferralWaitInSecsBeforeAward()
        );
    } catch (Exception $ex) {

        $response = json_decode($ex->getMessage(), true);
        json_error($response["error_code"], "", "referral_reward_process failed! " . $response["error_message_log"], 1);
    }
}

// Qualify Reward: Step 3.1
function getUserReferralWaitInSecsBeforeAward()
{

    return $GLOBALS['env_UserReferralWaitInSecsBeforeAward'];
}

// When wait time passes and the queue message is picked up
// Process Reward: Step 1 - hasReferralAlreadyBeenEarned
// Process Reward: Step 2
function logOrderAndValidateReferralEarning($order, $userWhoReferred)
{

    // Return = 1 => Not yet earned or already earned
    // Return = 2 => Qualified so award credit

    // Fetch the userReferral row
    $userReferral = parseExecuteQuery(["user" => $userWhoReferred], "UserReferral", "", "", [], 1);

    // Check if this order was already marked as qualified
    $userReferralUsage = parseExecuteQuery(["qualifiedOrder" => $order, "userReferred" => $userReferral],
        "UserReferralUsage");

    // Order was already logged
    if (count_like_php5($userReferralUsage) > 0) {

        return 1;
    }

    // If not,
    // We log all orders, but don't award an earning if it was canceled
    // verify if adding this order meets the min spend
    $earned = false;
    if (doesOrderEarnsReferralReward($userReferral, $order)) {

        $earned = true;
    }

    // Log referral usage
    $userReferralUsage = new ParseObject("UserReferralUsage");
    $userReferralUsage->set('userReferral', $userReferral);
    $userReferralUsage->set('userReferred', $order->get('user'));
    $userReferralUsage->set('order', $order);

    if ($earned) {

        $userReferralUsage->set('rewardInCents', getUserRewardPromised($order->get('user'), $userReferral));
        $userReferralUsage->set('rewardAwarded', true);
    } else {

        $userReferralUsage->set('rewardInCents', 0);
        $userReferralUsage->set('rewardAwarded', false);
    }

    $userReferralUsage->save();

    if ($earned) {

        return 2;
    } else {

        return 1;
    }
}

// Process Reward: Step 3
function rewardReferralCredit($order, $userWhoReferred, $overrideReward, $overrideRewardReason)
{

    // Check reward was not already earned for this user
    // $referralRewards = parseExecuteQuery(["user" => $userWhoReferred, "__SW__reasonForCredit" => "Referral Reward - " . $order->get('user')->getObjectId()], "UserCredits", "", "", [], 1);
    $referralRewards = parseExecuteQuery([
        "user" => $userWhoReferred,
        "reasonForCreditCode" => getUserCreditReasonCode('ReferralReward')
    ], "UserCredits", "", "", ["fromOrder", "fromOrder.user"]);

    foreach ($referralRewards as $referralReward) {

        if (strcasecmp($referralReward->get('fromOrder')->get('user')->getObjectId(),
                $order->get('user')->getObjectId()) == 0
        ) {

            // Already earned for this user
            if (count_like_php5($referralRewards) > 0) {

                return $referralReward->get('creditsInCents');
            }
        }
    }

    // If reward value was overriden (e.g. for disqualified users this will be 0)
    // We will still enter the row in the table but not inform the user if it was 0
    $additionalReason = "";
    if ($overrideReward == -1) {

        $userReferral = parseExecuteQuery(["user" => $userWhoReferred], "UserReferral", "", "", [], 1);
        $rewardInCents = getUserRewardPromised($order->get('user'), $userReferral);
    } else {

        $rewardInCents = $overrideReward;
        $additionalReason = $overrideRewardReason;

        // Slack it
        if ($overrideReward == 0) {

            $slack = createOrderNotificationSlackMessage($order->getObjectId());
            //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
            $slack->setText("Referral Reward Denied");

            $attachment = $slack->addAttachment();
            $attachment->addField("Customer:",
                $userWhoReferred->get("firstName") . " " . $userWhoReferred->get("lastName"), false);
            $attachment->addField("For Order Id:", $order->get('orderSequenceId'), false);
            $attachment->addField("Reason:", $overrideRewardReason, false);

            try {

                $slack->send();
            } catch (Exception $ex2) {

                return json_error_return_array("AS_1016", "",
                    "Slack post for customer referral denied failed = " . $order->get('orderSequenceId') . " - " . $ex2->getMessage(),
                    3, 1);
            }
        }
    }

    // Credit the user
    $userCredit = new ParseObject("UserCredits");
    $userCredit->set('user', $userWhoReferred);
    $userCredit->set('fromOrder', $order);
    $userCredit->set('reasonForCredit',
        getUserCreditReason('ReferralReward') . ' - ' . $order->get('user')->getObjectId() . ' - ' . $additionalReason);
    $userCredit->set('reasonForCreditCode', getUserCreditReasonCode('ReferralReward'));
    $userCredit->set('creditsInCents', $rewardInCents);
    $userCredit->set('expireTimestamp', time() + $GLOBALS['env_UserReferralRewardExpireInSeconds']);
    $userCredit->set('minOrderTotalField', getMinOrderTotalFieldForCredits());
    $userCredit->set('minOrderTotalValue', $GLOBALS['env_UserReferralMinSpendInCentsForReward']);
    $userCredit->save();

    return $rewardInCents;
}

// Process Reward: Step 4
function informUserOfReferralReward($userWhoReferred, $order, $rewardInDollarsFormatted)
{

    // Clear Referral Cache for user
    clearRewardStatusCache($userWhoReferred);

    // Information via email
    $response = informViaEmailAboutRewardEarn($userWhoReferred, $order, $rewardInDollarsFormatted);

    if (is_array($response)) {

        json_error($response["error_code"], "", $response["error_message_log"], 1, 1);
    }

    // Information via push and SMS
    $response = informViaPushAndSMSAboutRewardEarn($userWhoReferred, $rewardInDollarsFormatted);

    if (is_array($response)) {

        json_error($response["error_code"], "", $response["error_message_log"], 1, 1);
    }

    return "";
}

// Process Reward: Step 2.1
function doesOrderEarnsReferralReward($userReferral, $order)
{

    // Check if the order was canceled
    if (in_array($order->get('status'), listStatusesForCancelled())) {

        return false;
    }

    return true;

    // Calculate if we have met the minimum spend required
    /*
    // Seed with this order
    $totalWithCoupon = json_decode($order->get('totalsWithFees'), true)["TotalWithCoupon"];

    if(hasTotalMetReferralMin($totalWithCoupon)) {

        return true;
    }

    // Find all other orders for this userReferral and user
    $orderList = parseExecuteQuery(["userReferral" => $userReferral, "userReferred" => $order->get('user')], "UserReferralUsage");
    foreach($orderList as $orderOne) {

        // Verify it is not canceled
        if(in_array($orderOne->get('qualifiedOrder')->get('status'), listStatusesForCancelled())) {

            continue;
        }

        // Add the totalWithCoupon
        $totalWithCoupon += json_encode($orderOne->get('qualifiedOrder')->get('totalsWithFees'), true)["TotalWithCoupon"];

        if(hasTotalMetReferralMin($totalWithCoupon)) {

            return true;
        }
    }
    */
}

// Process Reward: Step 2.2
/*
function hasTotalMetReferralMin($totalWithCoupon) {

    if($totalWithCoupon > $GLOBALS['env_UserReferralMinSpendInCentsForReward']) {

        return true;
    }

    return false;
}
*/

// Process Reward: Step 4.1
function clearRewardStatusCache($userWhoReferred)
{

    $namedCacheKey = generateReferStatusCacheKey($userWhoReferred);

    // Clear any reward cache for this user so new credits can be shown
    $cacheKeyList[] = [$namedCacheKey];
    resetCache($cacheKeyList);
}

// Process Reward: Step 4.2
function informViaEmailAboutRewardEarn($userWhoReferred, $order, $rewardInDollarsFormatted)
{

    // Send email
    try {

        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue->sendMessage(
            array(
                "action" => "reward_earn_via_email",
                "content" =>
                    array(

                        "objectId" => $userWhoReferred->getObjectId(),
                        "objectIdReferred" => $order->get('user')->getObjectId(),
                        "rewardInDollarsFormatted" => $rewardInDollarsFormatted

                    )
            )
        );
    } catch (Exception $ex) {

        return json_error_return_array("AS_1063", "",
            "Email Reward Notification failed! User = " . $user->getObjectId() . " - " . $ex->getMessage(), 2);
    }

    return "";
}

// Process Reward: Step 4.3
function informViaPushAndSMSAboutRewardEarn($user, $rewardInDollarsFormatted)
{

    // Push notification
    $message = "You just earned " . $rewardInDollarsFormatted . " from your referral.";
    $response = sendRewardNotification($user, $message);

    if (is_array($response)) {

        return $response;
    }

    return "";
}

// Process Reward: Step 4.3.1
function sendRewardNotification($user, $message)
{

    // Find latest session device for User
    $sessionDevice = getLatestSessionDevice($user);

    // Prepare message
    $messagePrepped = getRewardCustomMessage($message, $user->get('firstName'),
        $sessionDevice->get('timezoneFromUTCInSeconds'));

    // Get user's Phone Id
    $objUserPhone = parseExecuteQuery(array("user" => $user, "isActive" => true, "phoneVerified" => true), "UserPhones",
        "", "updatedAt", [], 1);

    // Send SMS notification
    if (count_like_php5($objUserPhone) > 0
        && $objUserPhone->get('SMSNotificationsEnabled') == true
        && ($objUserPhone->has('SMSNotificationsOptOut') && $objUserPhone->get('SMSNotificationsOptOut') == false)
    ) {

        try {

            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue->sendMessage(
                array(
                    "action" => "reward_earn_via_sms",
                    "content" =>
                        array(
                            "userPhoneId" => $objUserPhone->getObjectId(),
                            "message" => $messagePrepped
                        )
                )
            );
        } catch (Exception $ex) {

            return json_error_return_array("AS_1062", "",
                "SMS Reward Notification failed! PhoneId = " . $objUserPhone->getObjectId() . " - " . $ex->getMessage(),
                2);
        }
    }

    // Fetch last known user device of user
    if ($sessionDevice->has('userDevice')) {

        $userDevice = getLatestUserDevice($user);
    } else {

        return json_error_return_array("AS_1063", "", "Push Reward Notification failed! User = " . $user->getObjectId(),
            2);
    }

    // Send push notification
    list($oneSignalId, $isPushNotificationEnabled) = getPushNotificationInfo($userDevice);

    if (!empty($oneSignalId)
        && $isPushNotificationEnabled == true
    ) {

        // Send push notification via Queue
        try {

            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueSlackNotificationConsumerName']);

            $workerQueue->sendMessage(
                array(
                    "action" => "reward_earn_via_push_notification",
                    "content" =>
                        array(
                            "userDeviceId" => $userDevice->getObjectId(),
                            "oneSignalId" => $userDevice->get('oneSignalId'),
                            "message" => [
                                "title" => "Cha-Ching",
                                "text" => $messagePrepped,
                                "data" => ["deepLinkId" => 'referral_reward']
                            ]
                        )
                )
            );
        } catch (Exception $ex) {

            return json_error_return_array("AS_1063", "",
                "Push Reward Notification failed! User Device Id = " . $userDevice->getObjectId() . " - " . $ex->getMessage(),
                2);
        }
    }

    return "";
}

function sortRetailersByFullfillmentTimesAndAddRetailerInfo($responseArray)
{

    $fullfillmentTimeEstimateInSeconds = [];
    $fullfillmentTimeEstimateInSecondsSequenced = [];
    $counter = 0;
    $responseArraySequenced = [];
    foreach ($responseArray as $uniqueId => $fulfillmentInfo) {

        // Add retailer info
        $responseArraySequenced[$counter] = getRetailerInfo($uniqueId);

        // Just retailer estimates
        $responseArraySequenced[$counter]['fulfillmentData'] = $fulfillmentInfo;

        // Add retailer info
        // $responseArraySequenced[$counter]['fulfillmentData']['i'] = array_merge($responseArraySequenced[$counter]['i'], getRetailerInfo($uniqueId));


        // Only pickup
        if ($fulfillmentInfo['d']['isAvailable'] == false
            && $fulfillmentInfo['d']['isAvailable'] == true
        ) {

            $fullfillmentTimeEstimateInSeconds[$uniqueId] = $fullfillmentTimeEstimateInSecondsSequenced[$counter] = 9999 + $fulfillmentInfo['p']['fullfillmentTimeEstimateInSeconds'];
        } // Delivery
        else {
            if ($fulfillmentInfo['d']['isAvailable'] == true) {

                $fullfillmentTimeEstimateInSeconds[$uniqueId] = $fullfillmentTimeEstimateInSecondsSequenced[$counter] = $fulfillmentInfo['d']['fullfillmentTimeEstimateInSeconds'];
            } // Neither available
            else {

                $fullfillmentTimeEstimateInSeconds[$uniqueId] = $fullfillmentTimeEstimateInSecondsSequenced[$counter] = 99999;
            }
        }

        // Increase counter
        $counter++;
    }

    // Sequenced with keys as 0, 1, 2
    array_multisort($fullfillmentTimeEstimateInSecondsSequenced, SORT_ASC, $responseArraySequenced);

    // Orignal key structure with uniqueIds
    array_multisort($fullfillmentTimeEstimateInSeconds, SORT_ASC, $responseArray);

    return [$responseArraySequenced, $responseArray];
}

function fetchRetailerListSequenced($airportIataCode, $locationId, $requestedFullFillmentTimestamp)
{

    list($requestedFullFillmentTimestamp, $futureFullfillment) = requestedFullFillmentTimestampForEstimates($requestedFullFillmentTimestamp);

    if ($futureFullfillment == false) {

        $namedCacheKeySequenced = '__FULLFILLMENTINFO__sq__' . $airportIataCode . '__' . $locationId;
    } else {

        $namedCacheKeySequenced = '__FULLFILLMENTINFO__sq__' . $airportIataCode . '__' . $locationId . ' __' . $requestedFullFillmentTimestamp;
    }

    // Check if the cache is available (from looper)
    $sequencedRetailers = getCache(getNamedRouteCacheName($namedCacheKeySequenced), 0, true);

    if (!is_bool($sequencedRetailers)
        && !empty($sequencedRetailers)
    ) {

        return json_decode($sequencedRetailers, true);
    }

    // cache not found, so generate it (typically when looper is down)
    // or when future fullfillment is requested
    $responseArray = fetchFullfillmentTimes($airportIataCode, $locationId, $requestedFullFillmentTimestamp);

    list($responseArraySequenced, $responseArray) = sortRetailersByFullfillmentTimesAndAddRetailerInfo($responseArray);

    setRouteCache([
        "cacheSlimRouteNamedKey" => $namedCacheKeySequenced,
        "jsonEncodedString" => json_encode($responseArraySequenced),
        "expireInSeconds" => intval($GLOBALS['env_PingRetailerIntervalInSecs']) * 3,
        "compressed" => true
    ]);

    return $responseArraySequenced;
}

function fetchCuratedList($airportIataCode, $listId, $locationId, $flightId, $requestedFullFillmentTimestamp, $listType)
{

    list($requestedFullFillmentTimestamp, $futureFullfillment) = requestedFullFillmentTimestampForEstimates($requestedFullFillmentTimestamp);

    // Full list or just preview per location per list
    $namedCacheKey = getCuratedListCacheKeyName($airportIataCode, $listId, $locationId, $requestedFullFillmentTimestamp,
        $futureFullfillment);

    // Check if the cache is available (from looper)
    $curatedList = getCache(getNamedRouteCacheName($namedCacheKey), 0, true);

    if (!is_bool($curatedList)
        && !empty($curatedList)
    ) {

        $curatedList = json_decode($curatedList, true);

        if (!isset($curatedList[$listType])) {

            return [];
        }

        return fetchListType($curatedList, $listType);
    }

    // cache not found, so generate it (typically when looper is down)
    // or when future fullfillment is requested
    $responseArray = buildCuratedList($airportIataCode, $listId, $locationId, $flightId,
        $requestedFullFillmentTimestamp);

    setRouteCache([
        "cacheSlimRouteNamedKey" => $namedCacheKey,
        "jsonEncodedString" => json_encode($responseArray),
        "expireInSeconds" => intval($GLOBALS['env_PingRetailerIntervalInSecs']) * 3,
        "compressed" => true
    ]);

    if (count_like_php5($responseArray) > 0) {

        return fetchListType($responseArray, $listType);
    }

    return [];
}

function requestedFullFillmentTimestampForEstimates($requestedFullFillmentTimestamp)
{

    $futureFullfillment = false;

    // Future fullfillment requested
    if ($requestedFullFillmentTimestamp > time()) {

        $futureFullfillment = true;
    } // Mark it not required to check
    else {

        $requestedFullFillmentTimestamp = 0;
    }

    return [$requestedFullFillmentTimestamp, $futureFullfillment];
}

function fetchListType($responseArrayDecoded, $listType)
{

    return $responseArrayDecoded[$listType];
}

function buildCuratedList(
    $airportIataCode,
    $listId,
    $deliveryLocationId,
    $flightId,
    $requestedFullFillmentTimestamp,
    $beingCached = false
) {

    $boardingTimestamp = $lastKnownTimestamp = 0;
    $airportSide = $flightLocationId = "";
    $currentTime = time();

    // Get flight gate info
    if (!empty($flightId)) {

        list($airportSide, $flightLocationId, $lastKnownTimestamp, $boardingTimestamp) = getFlightLocationInfo($flightId,
            $airportIataCode);
    }

    $curatedListResponse = $curatedList = [];

    // Max element count sent for homescreen preview call
    $previewItems = 5;

    $list = parseExecuteQuery(["objectId" => $listId], "List", "", "", [], 1);

    $obj = new ParseQuery("List");
    $listObj = parseSetupQueryParams(["objectId" => $listId], $obj);
    $listDetails = parseExecuteQuery(["__MATCHESQUERY__list" => $listObj], "ListDetails", "displaySequence", "",
        ["list", "retailer", "retailerItem"]);

    ////////////////////////////////////////////////////////////////////////
    // Pickup, on the way to Gate
    ////////////////////////////////////////////////////////////////////////
    if (preg_match("/custom_pickup_oyw_gate/si", $list->get('uniqueId'))) {

        if ((empty($flightLocationId) && $beingCached)
            || (!empty($flightLocationId) && strcasecmp($airportSide, 'departure') == 0)
        ) {

            // Use the location as departure gate (for caching only)
            if (!empty($flightLocationId)) {

                $deliveryLocationId = $flightLocationId;
            }

            // Logic
            // Narrow down retailers that are in the same Terminal
        }
    }
    ////////////////////////////////////////////////////////////////////////

    ////////////////////////////////////////////////////////////////////////
    // Pickup, on the way out at Arrival
    ////////////////////////////////////////////////////////////////////////
    else {
        if (preg_match("/custom_pickup_oyw_arrival/si", $list->get('uniqueId'))) {

            if ((empty($flightLocationId) && $beingCached)
                || (!empty($flightLocationId) && strcasecmp($airportSide, 'arrival') == 0)
            ) {

                // Use the location as departure gate (for caching only)
                if (!empty($flightLocationId)) {

                    $deliveryLocationId = $flightLocationId;
                }

                // Logic
                // Narrow down retailers that are in the same Terminal
                // But check with closing time upon arrival + 30 mins
            }
        }
        ////////////////////////////////////////////////////////////////////////

        ////////////////////////////////////////////////////////////////////////
        // Delivery, before boarding time (won't work for future request fullfillment timestamps)
        ////////////////////////////////////////////////////////////////////////
        else {
            if (preg_match("/custom_delivery_b4boarding/si", $list->get('uniqueId'))
                && $requestedFullFillmentTimestamp == 0
            ) {

                // If flight is available
                // boarding timestamp - current time > 60 mins
                if (!empty($flightLocationId) && strcasecmp($airportSide, 'departure') == 0
                    && ($boardingTimestamp - $currentTime) > 60 * 60
                ) {

                    // Logic using $boardingTimestamp
                    // Take all retailers that have fullfillment estimate less than boardingTimestamp-currentTime
                    $deliveryTimeUnderInSecs = $boardingTimestamp - $currentTime;
                    $curatedListResponse = formatCustomCuratedListDelivery($deliveryTimeUnderInSecs,
                        $list->get('airportIataCode'), $deliveryLocationId);
                    if (count_like_php5($curatedListResponse) > 0) {

                        // List level details
                        $curatedListResponse["id"] = $list->getObjectId();
                        $curatedListResponse["type"] = $list->get('type');
                        $curatedListResponse["name"] = $list->get('name');
                        $curatedListResponse["description"] = $list->get('description');
                        $curatedListResponse["cardType"] = $list->get('cardType');
                    }
                }
            }
            ////////////////////////////////////////////////////////////////////////

            ////////////////////////////////////////////////////////////////////////
            // Delivery, in X mins (won't work for future request fullfillment timestamps)
            ////////////////////////////////////////////////////////////////////////
            else {
                if (preg_match("/custom_delivery_u/si", $list->get('uniqueId'))
                    && $requestedFullFillmentTimestamp == 0
                ) {

                    if ((empty($flightLocationId))
                        || (!empty($flightLocationId) && strcasecmp($airportSide, 'departure') == 0
                            && ($boardingTimestamp - $currentTime) <= 60 * 60)
                    ) {

                        // Use the location as arrival gate
                        if (!empty($flightLocationId)) {

                            $deliveryLocationId = $flightLocationId;
                        }

                        // Logic using $boardingTimestamp
                        // Take all retailers that have fullfillment estimate less than X mins
                        $deliveryTimeUnderInMins = intval(str_ireplace($list->get('airportIataCode') . '_', '',
                            str_ireplace('custom_delivery_u', '', $list->get('uniqueId'))));
                        $curatedListResponse = formatCustomCuratedListDelivery($deliveryTimeUnderInMins * 60,
                            $list->get('airportIataCode'), $deliveryLocationId);
                        if (count_like_php5($curatedListResponse) > 0) {

                            // List level details
                            $curatedListResponse["id"] = $list->getObjectId();
                            $curatedListResponse["type"] = $list->get('type');
                            $curatedListResponse["name"] = $list->get('name');
                            $curatedListResponse["description"] = $list->get('description');
                            $curatedListResponse["cardType"] = $list->get('cardType');
                        }
                    }
                }
                ////////////////////////////////////////////////////////////////////////

                ////////////////////////////////////////////////////////////////////////
                // All other lists with pings
                ////////////////////////////////////////////////////////////////////////
                else {

                    $curatedListResponse = formatCuratedList($list, $listDetails, $requestedFullFillmentTimestamp,
                        $deliveryLocationId);
                }
            }
        }
    }
    ////////////////////////////////////////////////////////////////////////

    if (count_like_php5($curatedListResponse) > 0) {

        $curatedList["full"] = $curatedListResponse;

        $curatedListResponse["elements"] = $elements = array_slice($curatedListResponse["elements"], 0, $previewItems);
        $curatedList["preview"] = $curatedListResponse;
    }

    return $curatedList;
}

function getRetailerMenuItemDetails($itemId, $airportIataCode)
{

    $itemDetails = array();

    $item = parseExecuteQuery(array("uniqueId" => $itemId, "isActive" => true), "RetailerItems", "", "", [], 1);

    if (count_like_php5($item) > 0) {

        $itemDetails["itemCategoryName"] = $item->get("itemCategoryName");
        $itemDetails["itemSecondCategoryName"] = $item->get("itemSecondCategoryName");
        $itemDetails["itemThirdCategoryName"] = $item->get("itemThirdCategoryName");

        $itemDetails["itemId"] = $item->get("uniqueId");
        $itemDetails["itemName"] = !empty($item->get("itemDisplayName")) ? $item->get("itemDisplayName") : $item->get("itemPOSName");
        $itemDetails["itemDescription"] = $item->get("itemDisplayDescription");
        $itemDetails["itemPriceDisplay"] = dollar_format($item->get("itemPrice"));
        $itemDetails["itemPrice"] = $item->get("itemPrice");
        $itemDetails["itemImageURL"] = !empty($item->get("itemImageURL")) ? preparePublicS3URL($item->get("itemImageURL"),
            getS3KeyPath_ImagesRetailerItem($airportIataCode), $GLOBALS['env_S3Endpoint']) : "";
        $itemDetails["itemImageThumbURL"] = !empty($item->get("itemImageURL")) ? preparePublicS3URL('thumb_' . $item->get("itemImageURL"),
            getS3KeyPath_ImagesRetailerItem($airportIataCode), $GLOBALS['env_S3Endpoint']) : "";

        $itemDetails["itemTags"] = $item->get("itemTags");
        $itemDetails["allowedThruSecurity"] = !is_bool($item->get("allowedThruSecurity")) ? true : $item->get("allowedThruSecurity");
    }

    return $itemDetails;
}

function formatCuratedList(
    $list,
    $listDetails,
    $requestedFullFillmentTimestamp,
    $locationId,
    $fullfillmentTypeMustHave = []
) {

    $element = 0;
    foreach ($listDetails as $i => $listDetail) {

        $itemInfo = "";

        if ($i == 0) {

            // List level details
            $responseArray["id"] = $list->getObjectId();
            $responseArray["type"] = $list->get('type');
            $responseArray["name"] = $list->get('name');
            $responseArray["description"] = $list->get('description');
            $responseArray["cardType"] = $list->get('cardType');
            // $responseArray["infoTextDisplay"] = $list->get('infoTextDisplay');
        }

        // Item info
        if (!empty($listDetail->get('retailerItem'))) {

            $itemInfo = getRetailerMenuItemDetails($listDetail->get('retailerItem')->get('uniqueId'),
                $list->get('airportIataCode'));

            if (count_like_php5($itemInfo) == 0) {

                continue;
            }
        }

        // Retailer Ping info
        $retailerPing = getRetailerFullfillmentInfoFromCache($list->get('airportIataCode'), $locationId,
            $listDetail->get('retailer')->get('uniqueId'));

        // Delivery not available so check if it is needed
        if ($retailerPing["d"]["isAvailable"] == false
            && in_array("d", $fullfillmentTypeMustHave)
        ) {

            continue;
        } // Pickup not available so check if it is needed
        else {
            if ($retailerPing["p"]["isAvailable"] == false
                && in_array("p", $fullfillmentTypeMustHave)
            ) {

                continue;
            }
        }

        $responseArray["elements"][$element]["fullfillmentEstimateTextDisplay"] = getFullfillmentEstimateTextDisplay($retailerPing);
        $responseArray["elements"][$element]["ping"] = $retailerPing["i"]["ping"];

        // List details info
        $responseArray["elements"][$element]["spotlight"] = $listDetail->get('spotlight');
        $responseArray["elements"][$element]["spotlightIcon"] = $listDetail->get('spotlightIcon');
        $responseArray["elements"][$element]["spotlightIconURL"] = getAppIconURL($responseArray["elements"][$element]["spotlightIcon"]);
        $responseArray["elements"][$element]["description"] = $listDetail->get('description');

        // Retailer Info
        $retailerInfo = getRetailerInfo($listDetail->get('retailer')->get('uniqueId'));

        $responseArray["elements"][$element]["retailerId"] = $listDetail->get('retailer')->get('uniqueId');
        $responseArray["elements"][$element]["retailerName"] = $retailerInfo["retailerName"];
        $responseArray["elements"][$element]["retailerLocationDisplay"] = $retailerInfo["location"]["locationDisplayName"];
        $responseArray["elements"][$element]["retailerLogoImageURL"] = $retailerInfo["imageLogo"];

        // Display image
        if (strcasecmp($listDetail->get('imageType'), 'retailerLogo') == 0) {

            $imageURL = $retailerInfo["imageLogo"];
        } else {
            if (strcasecmp($listDetail->get('imageType'), 'item') == 0) {

                $imageURL = $itemInfo["itemImageURL"];
            } else {

                $imageURL = $retailerInfo["imageBackground"];
            }
        }

        $responseArray["elements"][$element]["imageURL"] = $imageURL;

        // If item level, item level info
        if (strcasecmp($list->get('type'), 'item') == 0) {

            $responseArray["elements"][$element]["itemId"] = $listDetail->get('retailerItem')->get('uniqueId');
            $responseArray["elements"][$element]["itemName"] = $itemInfo["itemName"];
            $responseArray["elements"][$element]["itemPriceDisplay"] = $itemInfo["itemPriceDisplay"];
            $responseArray["elements"][$element]["itemPrice"] = $itemInfo["itemPrice"];
            $responseArray["elements"][$element]["allowedThruSecurity"] = $itemInfo["allowedThruSecurity"];
            $responseArray["elements"][$element]["itemImageURL"] = !empty($itemInfo["itemImageURL"]) ? $itemInfo["itemImageURL"] : $retailerInfo["imageLogo"];
            $responseArray["elements"][$element]["itemImageThumbURL"] = $itemInfo["itemImageThumbURL"];
            $responseArray["elements"][$element]["itemTags"] = $itemInfo["itemTags"];
        } else {

            $responseArray["elements"][$element]["itemId"] = null;
            $responseArray["elements"][$element]["itemName"] = null;
            $responseArray["elements"][$element]["itemPriceDisplay"] = null;
            $responseArray["elements"][$element]["itemPrice"] = null;
            $responseArray["elements"][$element]["allowedThruSecurity"] = null;
            $responseArray["elements"][$element]["itemImageURL"] = null;
            $responseArray["elements"][$element]["itemImageThumbURL"] = null;
            $responseArray["elements"][$element]["itemTags"] = null;
        }

        $element++;
    }

    if (isset($responseArray["elements"])
        && count_like_php5($responseArray["elements"]) > 0
    ) {

        $responseArray["elementCount"] = count_like_php5($responseArray["elements"]);

        // TODO:
        // Sort by fullfillmentTimestamp
        // Ignore fullfillmentTimestamp for now, utilize displaySequnce provided
        // Use a new column "mandateDisplaySequence"
        // If set to true then use displaySequence else use fullfillmentTimestamp

        return $responseArray;
    }

    return [];
}

function getAppIconURL($iconName)
{

    // Is Font Awesome
    if (preg_match("/^fa\-/si", $iconName)) {

        $iconFileName = substr($iconName, 3, strlen($iconName));

        return preparePublicS3URL($iconFileName . '.png', getS3KeyPath_ImagesAppIcon(), $GLOBALS['env_S3Endpoint']);
    } else {

        return "";
    }
}

function getFlightLocationInfo($flightId, $airportIataCode)
{

    ///////////////////////////////////////////////////////////////
    // Check if there is flight gate posted for custom lists
    ///////////////////////////////////////////////////////////////
    $flightLocationId = "";
    $airportSide = "";
    $boardingTimestamp = $lastKnownTimestamp = 0;
    if (!empty($flightId)) {

        $flight = getFlightInfoFromCacheOrAPI($flightId);

        // If flight object found
        if (!empty($flight)) {

            if (strcasecmp($flight->get("departure")->getAirportInfo()["airportIataCode"], $airportIataCode) == 0) {

                $airportSide = "departure";
            } else {
                if (strcasecmp($flight->get("arrival")->getAirportInfo()["airportIataCode"], $airportIataCode) == 0) {

                    $airportSide = "arrival";
                } else {

                    $airportSide = "";
                }
            }

            if (!empty($airportSide)
                && $flight->get($airportSide)->isReadyAirport() == true
            ) {

                if (is_object($flight->get($airportSide)->getTerminalGateMapLocation(true))) {

                    $flightLocationId = $flight->get($airportSide)->getTerminalGateMapLocation(true)->getObjectId();
                    $lastKnownTimestamp = $flight->get($airportSide)->getLastKnownTimestamp();
                    $boardingTimestamp = $flight->get($airportSide)->getBoardingTimestamp();
                } else {
                    if ($flight->get($airportSide)->isReadyAirport() == true) {

                        $flightLocationId = "";
                        $lastKnownTimestamp = 0;
                        $boardingTimestamp = 0;
                    }
                }
            }
        }
    }

    return [$airportSide, $flightLocationId, $lastKnownTimestamp, $boardingTimestamp];
}

function getFullfillmentEstimateTextDisplay($retailerPing)
{

    if ($retailerPing["d"]["isAvailable"]) {

        return "Delivery " . floor($retailerPing["d"]["fullfillmentTimeEstimateInSeconds"] / 60) . " mins";
    } else {
        if ($retailerPing["p"]["isAvailable"]) {

            return "Pickup " . floor($retailerPing["p"]["fullfillmentTimeEstimateInSeconds"] / 60) . " mins";
        } else {

            return "";
        }
    }
}

function generateCuratedList(
    $airportIataCode,
    $deliveryLocationId,
    $flightId,
    $requestedFullFillmentTimestamp,
    $listId = ""
) {

    $responseArray = ["curatedList" => [], "retailers" => []];

    ///////////////////////////////////////////////////////////////
    // Validate Delivery location if one is provided
    ///////////////////////////////////////////////////////////////
    if (strcasecmp($deliveryLocationId, "0") == 0) {

        list($airportIataCode, $toTerminal, $toConcourse, $toGate) = getGateLocationDetails($airportIataCode,
            $deliveryLocationId);

        if (empty($airportIataCode)
            || empty($toTerminal)
            || empty_zero_allowed($toGate)
        ) {

            json_error("AS_514", "", "Provided location Ids are invalid");
        }
    } // Else, use a default location id
    else {

        $deliveryLocation = getTerminalGateMapDefaultLocation($airportIataCode);
        $deliveryLocationId = $deliveryLocation->getObjectId();
    }

    ///////////////////////////////////////////////////////////////
    // Set default requested timestamp
    ///////////////////////////////////////////////////////////////
    $requestedFullFillmentTimestamp = intval($requestedFullFillmentTimestamp);
    if ($requestedFullFillmentTimestamp == 0) {

        $requestedFullFillmentTimestamp = time();
    }

    list($dayOfWeekAtAirport, $secondsSinceMidnight) = getDayOfWeekAndSecsSinceMidnight($airportIataCode,
        $requestedFullFillmentTimestamp);

    if (empty($listId)) {

        ///////////////////////////////////////////////////////////////
        // Fetch curated lists for the airport
        ///////////////////////////////////////////////////////////////
        $curatedLists = parseExecuteQuery(["isActive" => true, "airportIataCode" => $airportIataCode], "List",
            "displaySequence", "");
        $listType = "preview";
    } else {

        ///////////////////////////////////////////////////////////////
        // Fetch curated lists for the requested id
        ///////////////////////////////////////////////////////////////
        $curatedLists = parseExecuteQuery(["objectId" => $listId], "List");
        $listType = "full";
    }


    if (count_like_php5($curatedLists) == 0) {
        throw new Exception("AS_520");
        // json_error("AS_520", "", "No curated list found for the airport " . $airportIataCode, 1);
    }

    $listsCreated = [];
    foreach ($curatedLists as $list) {

        $restrictListTimeInSecsStart = $list->get('restrictListTimeInSecsStart');
        $restrictListTimeInSecsEnd = $list->get('restrictListTimeInSecsEnd');

        ///////////////////////////////////////////////////////////////
        // Verify if this list is to be shown given the time restriction
        ///////////////////////////////////////////////////////////////
        if ($restrictListTimeInSecsStart > 0
            || $restrictListTimeInSecsEnd > 0
        ) {

            $restrictListTimeInSecsStart = $restrictListTimeInSecsStart - getBufferBeforeOrderTimeInSecondsRange();
            $restrictListTimeInSecsEnd = $restrictListTimeInSecsEnd - getBufferBeforeOrderTimeInSecondsRange();
        }

        // Reset to 0 if a specific listId was requested
        if (!empty($listId)) {

            $restrictListTimeInSecsStart = $restrictListTimeInSecsEnd = 0;
        }

        if ($restrictListTimeInSecsStart == 0 ||
            ($restrictListTimeInSecsStart > 0 && $restrictListTimeInSecsEnd > 0
                && ($secondsSinceMidnight >= $restrictListTimeInSecsStart
                    && $secondsSinceMidnight <= $restrictListTimeInSecsEnd)
            )
        ) {

            // Fetch the list JSON from Cache
            $curatedList = fetchCuratedList(
                $list->get('airportIataCode'),
                $list->getObjectId(),
                $deliveryLocationId,
                $flightId,
                $requestedFullFillmentTimestamp,
                $listType
            );

            // If a list was found
            if (count_like_php5($curatedList) > 0) {

                // Put the ping = false at the back of the list
                $backOftheList = [];
                $elementsRemoved = 0;
                foreach ($curatedList["elements"] as $key => $element) {

                    if ($element["ping"] == false) {

                        // remove element from the array
                        array_splice($curatedList["elements"], $key - $elementsRemoved, 1);

                        // add to back of the list
                        $backOftheList[] = $element;

                        // Number of elements removed
                        $elementsRemoved++;
                    }
                }

                // Merge the arrays
                if ($elementsRemoved > 0) {

                    $curatedList["elements"] = array_merge($curatedList["elements"], $backOftheList);
                }

                $responseArray["curatedList"][] = $curatedList;
            }
        }
    }

    return $responseArray;
}

function formatCustomCuratedListDelivery($deliveryTimeUnderInSecs, $airportIataCode, $deliveryLocationId)
{

    // Get all retailers fullfillment info
    $retailers = getAllRetailerFullfillmentInfoFromCache($airportIataCode, $deliveryLocationId);

    $element = 0;
    foreach ($retailers as $i => $retailerInfo) {

        // Delivery not available or beyond the time required
        if ($retailerInfo['fulfillmentData']["d"]["isAvailable"] == false
            || $retailerInfo['fulfillmentData']["d"]["fullfillmentTimeEstimateInSeconds"] > $deliveryTimeUnderInSecs
        ) {

            continue;
        }

        $responseArray["elements"][$element]["fullfillmentEstimateTextDisplay"] = getFullfillmentEstimateTextDisplay($retailerInfo['fulfillmentData']);
        $responseArray["elements"][$element]["ping"] = $retailerInfo['fulfillmentData']["i"]["ping"];

        // List details info
        $responseArray["elements"][$element]["spotlight"] = "Speedy delivery";
        $responseArray["elements"][$element]["spotlightIcon"] = "fa-rocket";
        $responseArray["elements"][$element]["spotlightIconURL"] = getAppIconURL($responseArray["elements"][$element]["spotlightIcon"]);
        $responseArray["elements"][$element]["description"] = "";

        // Retailer Info
        $responseArray["elements"][$element]["retailerId"] = $retailerInfo["uniqueId"];
        $responseArray["elements"][$element]["retailerName"] = $retailerInfo["retailerName"];
        $responseArray["elements"][$element]["retailerLocationDisplay"] = $retailerInfo["location"]["locationDisplayName"];
        $responseArray["elements"][$element]["retailerLogoImageURL"] = $retailerInfo["imageLogo"];

        $responseArray["elements"][$element]["imageURL"] = $retailerInfo["imageBackground"];

        $responseArray["elements"][$element]["itemId"] = null;
        $responseArray["elements"][$element]["itemName"] = null;
        $responseArray["elements"][$element]["itemPriceDisplay"] = null;
        $responseArray["elements"][$element]["itemTags"] = null;
        $responseArray["elements"][$element]["itemPrice"] = null;
        $responseArray["elements"][$element]["allowedThruSecurity"] = null;
        $responseArray["elements"][$element]["itemImageURL"] = null;
        $responseArray["elements"][$element]["itemImageThumbURL"] = null;

        $element++;
    }

    if (isset($responseArray["elements"])
        && count_like_php5($responseArray["elements"]) > 0
    ) {

        $responseArray["elementCount"] = count_like_php5($responseArray["elements"]);

        return $responseArray;
    }

    return [];
}

function refundOrderToSource($orderId, $braintreeTransactionId, $amountInDollars = 0, $delayedAttemptId = "")
{

    $refundFailedException = "";
    $refund = "";

    try {

        $transaction = Braintree_Transaction::find($braintreeTransactionId);

        // Refund
        if (strcasecmp($transaction->status, "settling") == 0
            || strcasecmp($transaction->status, "settled") == 0
        ) {

            // Partial refund
            if ($amountInDollars > 0) {

                $refund = Braintree_Transaction::refund($braintreeTransactionId, $amountInDollars);
            } // Full refund
            else {

                $refund = Braintree_Transaction::refund($braintreeTransactionId);
            }
        } // Not settled yet, so void it
        else {
            if (isset($transaction->status)) {

                // Partial refund
                if ($amountInDollars > 0) {

                    // Transaction can't be partially refunded
                    $order = parseExecuteQuery(["orderSequenceId" => $orderId], "Order", "", "", [], 1);

                    if (empty($delayedAttemptId)) {

                        $orderDelayedRefund = new ParseObject("OrderDelayedRefund");
                        $orderDelayedRefund->set("order", $order);
                        $orderDelayedRefund->set("amount", $amountInDollars);
                        $orderDelayedRefund->set("isCompleted", false);
                        $orderDelayedRefund->set("attempt", 0);
                        $orderDelayedRefund->save();

                        $delayedAttemptId = $orderDelayedRefund->getObjectId();
                    }

                    try {

                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "order_refund_to_source_post_settle",
                                "content" =>
                                    array(
                                        "orderDelayedRefundObjectId" => $delayedAttemptId,
                                        "timestamp" => time()
                                    )
                            ), 60 * 60
                        );
                    } catch (Exception $ex) {

                        $response = json_decode($ex->getMessage(), true);
                        json_error($response["error_code"], "",
                            "Delayed Refund queue message failed " . $response["error_message_log"], 1, 1);
                    }

                    return json_error_return_array("AS_330", "",
                        "Refund to Braintree failed - " . $orderId . " - Partial Refund can't be done for a settling transaction. It will be reattempted later.",
                        1);
                } // Full refund
                else {

                    $refund = Braintree_Transaction::void($braintreeTransactionId);
                }
            }
        }
    } catch (Exception $ex) {

        $refundFailedException = $ex->getMessage();
    }

    if (!empty($refundFailedException)
        || (isset($refund->success) && $refund->success == false)
    ) {

        return json_error_return_array("AS_327", "",
            "Refund to Braintree failed - " . $orderId . " - " . braintreeErrorCollect($refund) . " - Exception: " . $refundFailedException,
            1);
    }

    return "";
}

function refundOrderWithCredits($order, $creditsInCents = 0, $partialRefund = false)
{

    if ($creditsInCents == 0) {

        $creditsInCents = getOrderPaidAmountInCents($order);
    }

    if ($creditsInCents <= 0) {

        return "";
    }

    $userCredits = new ParseObject("UserCredits");
    $userCredits->set("user", $order->get("user"));
    $userCredits->set("fromOrder", $order);
    $userCredits->set("creditsInCents", $creditsInCents);
    // Refund credits don't expire
    $userCredits->set('expireTimestamp', -1);

    if ($partialRefund == true) {

        // $userCredits->set("reasonForCredit", "Order Cancellation by Ops - Partial refund");
        $userCredits->set("reasonForCredit", getUserCreditReason('OrderCancelByOpsPartialRefund'));
        $userCredits->set("reasonForCreditCode", getUserCreditReasonCode('OrderCancelByOpsPartialRefund'));
    } else {

        // $userCredits->set("reasonForCredit", "Order Cancellation by Ops - Full refund");    
        $userCredits->set("reasonForCredit", getUserCreditReason('OrderCancelByOpsFullRefund'));
        $userCredits->set("reasonForCreditCode", getUserCreditReasonCode('OrderCancelByOpsFullRefund'));
    }

    try {

        $userCredits->save();
    } catch (Exception $ex) {

        $refundFailedException = $ex->getMessage();
    }

    if (!empty($refundFailedException)) {

        return json_error_return_array("AS_329", "",
            "Refund to Credits failed - OrderId: " . $orderId . " - Exception: " . $refundFailedException, 1);
    }

    // Clear any carts for this user so new credits can be loaded
    $cacheKeyList[] = ['cart' . '__u__' . $order->get('user')->getObjectId()];
    resetCache($cacheKeyList);

    return "";
}

/**
 * @param $user
 * @return int
 *
 * Gets pending credits for the consumer user
 */
function getAvailableUserCreditsViaMap(
    $user,
    $maxCreditNeeded = 0,
    $totals = [],
    $expireTimestamp = 0,
    $creditsUntilTimestamp = 0
) {

    if (empty($user)) {

        return [[], 0, false];
    }

    // If none provided
    if (empty($expireTimestamp)) {

        $expireTimestamp = time();
    }

    // Find all non-canceled orders
    $appliedToOrderRefObject = new ParseQuery("Order");
    $appliedToOrder = parseSetupQueryParams([
        "user" => $user,
        "__CONTAINEDIN__status" => listStatusesForGreaterThanSubmittedOrder()
    ], $appliedToOrderRefObject);

    // Find all valid uses of credits WITH orders
    $userCreditsAppliedMap = parseExecuteQuery(array(
        "user" => $user,
        "__MATCHESQUERY__appliedToOrder" => $appliedToOrder
    ), "UserCreditsAppliedMap", "createdAt", "", array("userCredit", "appliedToOrder"));

    list($userCreditsUsed, $userCreditsAvailable) = getUserCreditMapUsage($userCreditsAppliedMap);
    /////////////////

    // Find all valid uses of credits WITHOUT orders
    $userCreditsAppliedMap = parseExecuteQuery(array("user" => $user, "__NE__appliedToOrder" => true),
        "UserCreditsAppliedMap", "createdAt", "", array("userCredit", "appliedToOrder"));

    list($userCreditsUsed, $userCreditsAvailable) = getUserCreditMapUsage($userCreditsAppliedMap, $userCreditsUsed,
        $userCreditsAvailable);
    /////////////////

    $creditAppliedMap = [];

    $appliedInCents = 0;
    $wereReferralSignupCreditAppliedToThisOrder = false;

    // Get all credits for the user ascending createdAt
    if ($creditsUntilTimestamp > 0) {

        $userCredits = parseExecuteQuery(array(
            "user" => $user,
            "__LTE__createdAt" => DateTime::createFromFormat("Y-m-d H:i:s",
                gmdate("Y-m-d H:i:s", $creditsUntilTimestamp))
        ), "UserCredits", "createdAt", "");
    } else {

        $userCredits = parseExecuteQuery(array("user" => $user), "UserCredits", "createdAt", "");
    }

    foreach ($userCredits as $userCredit) {

        // Skip used credits
        if (in_array($userCredit->getObjectId(), array_keys($userCreditsUsed))) {

            $creditAppliedMap[$userCredit->getObjectId()] =
                [
                    "userCredit" => $userCredit->getObjectId(),
                    "userCreditReasonCode" => $userCredit->get('reasonForCreditCode'),
                    "availableCreditsInCents" => $userCredit->get("creditsInCents"),
                    "appliedCreditsInCents" => 0
                ];

            continue;
        } // Usable credit
        else {

            // Skip if expired
            if ($userCredit->has('expireTimestamp')
                && !empty($userCredit->get('expireTimestamp'))
                && ($userCredit->get('expireTimestamp') < $expireTimestamp
                    && $userCredit->get('expireTimestamp') != -1)
            ) {

                continue;
            }

            // Skip if minOrderTotalField rule is not met
            // TotalWithCoupon
            if (!empty($userCredit->get("minOrderTotalField"))
                && is_array($totals)
                && count_like_php5($totals) > 0
                && isset($totals[$userCredit->get("minOrderTotalField")])
                && $totals[$userCredit->get("minOrderTotalField")] < $userCredit->get("minOrderTotalValue")
            ) {

                continue;
            }

            // Available credit

            // Partially unused Credit available
            if (in_array($userCredit->getObjectId(), array_keys($userCreditsAvailable))) {

                $creditsAvailableInCents = $userCreditsAvailable[$userCredit->getObjectId()];
            } // Full credit available
            else {

                $creditsAvailableInCents = $userCredit->get("creditsInCents");
            }

            // Apply running total credit
            $appliedInCents = $appliedInCents + $creditsAvailableInCents;

            // Applied Credit more than MaxCreditNeeded
            if ($appliedInCents > $maxCreditNeeded
                && $maxCreditNeeded != 0
            ) {

                $balanceInCents = $appliedInCents - $maxCreditNeeded;
                $appliedInCentsForRow = $creditsAvailableInCents - $balanceInCents;
                $appliedInCents = $maxCreditNeeded;
                $flag = true;
            } else {

                $balanceInCents = 0;
                $appliedInCentsForRow = $creditsAvailableInCents;
                $flag = false;
            }

            // Is referral credit applied
            if (isUserCreditReferralSignup($userCredit)) {

                $wereReferralSignupCreditAppliedToThisOrder = true;
            }

            $creditAppliedMap[$userCredit->getObjectId()] =
                [
                    "userCredit" => $userCredit->getObjectId(),
                    "userCreditReasonCode" => $userCredit->get('reasonForCreditCode'),
                    "availableCreditsInCents" => $balanceInCents,
                    "appliedCreditsInCents" => $appliedInCentsForRow
                ];

            if ($flag == true) {

                break;
            }
        }
    }

    return [$creditAppliedMap, $appliedInCents, $wereReferralSignupCreditAppliedToThisOrder];
}

function getUserCreditMapUsage($userCreditsAppliedMap, $userCreditsUsed = [], $userCreditsAvailable = [])
{

    $userCreditsUsed = [];
    $userCreditsAvailable = [];
    if (count_like_php5($userCreditsAppliedMap) > 0) {

        foreach ($userCreditsAppliedMap as $userCreditApplied) {

            // If canceled order then assume all credits are left
            if ($userCreditApplied->has('appliedToOrder')
                && in_array($userCreditApplied->get('appliedToOrder')->get('status'), listStatusesForCancelled())
            ) {

                continue;
            } // No credits left
            else {
                if ($userCreditApplied->get('availableCreditsInCents') <= 0) {

                    $userCreditsUsed[$userCreditApplied->get("userCredit")->getObjectId()] = 0;
                } // Available credit, since ordered by createdAt ascending, latest row will be last
                else {

                    $userCreditsAvailable[$userCreditApplied->get("userCredit")->getObjectId()] = $userCreditApplied->get('availableCreditsInCents');
                }
            }
        }
    }

    return [$userCreditsUsed, $userCreditsAvailable];
}

function isUserCreditReferralSignup($userCredit)
{

    // if(preg_match("/^Signup Referral Code/si", $userCredit->get("reasonForCredit"))) {
    if (strcasecmp($userCredit->get("reasonForCreditCode"), getUserCreditReasonCode('ReferralSignup')) == 0) {

        return true;
    }

    return false;
}

function getSignupCodeAppliedCounter($user)
{

    // Check UserCredits
    $rows1 = parseExecuteQuery([
        "__CONTAINEDIN__reasonForCreditCode" => getUserCreditReasonCodesForSignup(),
        "user" => $user
    ], "UserCredits");

    // Check UserCoupons
    $rows2 = parseExecuteQuery(["addedOnStep" => "signup", "user" => $user], "UserCoupons");

    return (count_like_php5($rows1) + count_like_php5($rows2));
}

function getUserCreditReasonCodesForSignup()
{

    $codes = [];
    foreach ($GLOBALS['reasonForCredit'] as $codeArray) {

        if (preg_match("/Signup/si", $codeArray["reason"])) {

            $codes[] = $codeArray["code"];
        }
    }

    return $codes;
}

function getUserCreditReasonCode($callString)
{

    if (isset($GLOBALS['reasonForCredit'][$callString])) {

        return $GLOBALS['reasonForCredit'][$callString]["code"];
    }

    throw new Exception("Invalid call string - " . $callString);
}

function getUserCreditReason($callString)
{

    if (isset($GLOBALS['reasonForCredit'][$callString])) {

        return $GLOBALS['reasonForCredit'][$callString]["reason"];
    }

    throw new Exception("Invalid call string - " . $callString);
}

function hasUserOrderedBetweenTimes($user, $startTimestamp, $tillTimestamp, $fullfillmentTypes = ['d'])
{

    $ordersPlaced = parseExecuteQuery([
        "user" => $user,
        "status" => array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled(),
            listStatusesForInProgress()),
        "__LTE__submitTimestamp" => $tillTimestamp,
        "__GTE__submitTimestamp" => $startTimestamp,
        "__CONTAINEDIN__fullfillmentType" => $fullfillmentTypes
    ], "Order");

    if (count_like_php5($ordersPlaced) > 0) {

        return true;
    } else {

        return false;
    }
}

function getUserCreditAppliedMapViaCache($order, $checkCacheOnly = true)
{

    $responseArray = [];
    $namedCacheKey = 'cartv2raw' . '__u__' . $order->get('user')->getObjectId() . '__o__' . $order->getObjectId();

    $responseArray = getCache($namedCacheKey, 1);

    if (empty($responseArray)
        && $checkCacheOnly == false
    ) {

        list($responseArray, $retailerTotals) = getOrderSummary($order, 1);
    }

    if (isset($responseArray["internal"]["creditAppliedMap"])) {

        return $responseArray["internal"]["creditAppliedMap"];
    } else {

        return [];
    }

    return $responseArray;
}

function doesCartIncludeUserCreditType($userCreditReasonCodes, $creditAppliedMap)
{

    if (isset($creditAppliedMap["default"])) {

        $map = $creditAppliedMap["default"]["map"];
    } else {
        if (isset($creditAppliedMap["d"])) {

            $map = $creditAppliedMap["d"]["map"];
        } else {
            if (isset($creditAppliedMap["p"])) {

                $map = $creditAppliedMap["p"]["map"];
            } else {

                $map = $creditAppliedMap;
            }
        }
    }

    foreach ($map as $mapEntry) {

        if (in_array($mapEntry["userCreditReasonCode"], $userCreditReasonCodes)) {

            return true;
        }
    }

    return false;
}

function hasMaxReferralRewardsEarned($userWhoReferred)
{

    // TODO
    // Need two global variables
    // env_UserReferraMaxEarningsPerTime = e.g. 10
    // env_UserReferralMaxEarningTimeWindowInSecs = e.g. 302400 (1 month)
    // Skip any userCredit entries with rewardInCents = 0
    return false;
}

function logReferralOffer($userReferred, $userReferral, $offeredAmount, $rewardAmountPromised)
{

    $userReferralOffer = new ParseObject("UserReferralOffer");
    $userReferralOffer->set("userReferred", $userReferred);
    $userReferralOffer->set("userReferral", $userReferral);
    $userReferralOffer->set("offeredAmountInCents", $offeredAmount);
    $userReferralOffer->set("rewardAmountPromisedInCents", $rewardAmountPromised);
    $userReferralOffer->save();
}

function getUserRewardPromised($userReferred, $userReferral)
{

    $userReferralOffer = parseExecuteQuery(["userReferral" => $userReferral, "userReferred" => $userReferred],
        "UserReferralOffer", "", "", [], 1);

    if (count_like_php5($userReferralOffer) > 0) {

        return $userReferralOffer->get('rewardAmountPromisedInCents');
    } else {

        return $GLOBALS['env_UserReferralRewardInCents'];
    }
}

function getMinOrderTotalFieldForCredits()
{

    return 'PreCreditTotal';
}

function cartPingRetailer($retailer)
{

    list($isAcceptingOrders, $isClosed, $error, $pingStatusDescription) = pingRetailer($retailer);

    if (!$isClosed) {

        // If closed early
        if (isRetailerCloseEarlyForNewOrders($retailer->get('uniqueId'))) {

            $isAcceptingOrders = false;
            $isClosed = true;
            $pingStatusDescription = "The retailer has closed for the day.";
        }
    }

    $responseArray = [
        "available" => $isAcceptingOrders, // Added for backward compatibility; change to available
        "isAccepting" => $isAcceptingOrders,
        "isClosed" => $isClosed,
        "pingStatusDescription" => $pingStatusDescription
    ];


    return $responseArray;
}

function isOrderRatingRequestAttempted($order)
{

    $ratingRequestAllowed = true;
    $ratingRequestNotAllowedReason = '';
    $orderRatingRequests = parseExecuteQuery(["order" => $order], "OrderRatingRequests", "", "", [], 1);

    if (count_like_php5($orderRatingRequests) > 0) {

        if ($orderRatingRequests->get('wasRequestSent') == true) {

            $ratingRequestAllowed = false;
            $ratingRequestNotAllowedReason = 'Already sent';
        } else {

            $ratingRequestAllowed = false;
            $ratingRequestNotAllowedReason = $orderRatingRequests->get('requestSkippedReason');
        }
    }

    return [$ratingRequestAllowed, $ratingRequestNotAllowedReason];
}

function isOrderRatingRequestable($order)
{

    $ratingRequestAllowed = true;
    $ratingRequestNotAllowedReason = '';

    if (!in_array($order->get('status'), listStatusesForSuccessCompleted())) {

        return [false, "Order not complete or canceled"];
    }

    $orderRatingRequests = parseExecuteQuery(["order" => $order], "OrderRatingRequests", "", "", [], 1);
    $rowFound = false;

    if (count_like_php5($orderRatingRequests) > 0) {

        if ($orderRatingRequests->get('wasRequestSent') == true) {

            $ratingRequestAllowed = false;
            $ratingRequestNotAllowedReason = 'Already sent';
        } else {

            $ratingRequestAllowed = false;
            $ratingRequestNotAllowedReason = $orderRatingRequests->get('requestSkippedReason');
        }

        $rowFound = true;
    }

    if ($ratingRequestAllowed == true) {

        // Check if maxs have been reached for the day or per user
        $orderRatingRequests = parseExecuteQuery(["user" => $order->get('user'), "wasRequestSent" => true],
            "OrderRatingRequests");

        // Max per User reached?
        if (count_like_php5($orderRatingRequests) > 0
            && count_like_php5($orderRatingRequests) >= $GLOBALS['env_OrderRatingRequestsMaxPerUser']
        ) {

            $ratingRequestNotAllowedReason = 'Max per lifetime user reached';
            $ratingRequestAllowed = false;
        } // Max per day per user reached?
        else {

            $countRatingRequestsPerUserPerDay = 0;
            $last24HoursTimestamp = strtotime("now - 24 hours");
            foreach ($orderRatingRequests as $ratingRequest) {

                // If this was in last 24 hours count it
                if ($ratingRequest->getCreatedAt()->getTimestamp() >= $last24HoursTimestamp) {

                    $countRatingRequestsPerUserPerDay++;
                }

                if ($countRatingRequestsPerUserPerDay >= $GLOBALS['env_OrderRatingRequestsMaxPerUserPerDay']) {

                    $ratingRequestNotAllowedReason = 'Max per day per user reached';
                    $ratingRequestAllowed = false;
                    break;
                }
            }
        }
    }

    // If request is not allowed log it
    if ($ratingRequestAllowed == false
        && $rowFound == false
    ) {

        $orderRatingRequest = new ParseObject("OrderRatingRequests");
        $orderRatingRequest->set("order", $order);
        $orderRatingRequest->set("wasRequestSent", false);
        $orderRatingRequest->set("requestSkippedReason", $ratingRequestNotAllowedReason);
        $orderRatingRequest->set("user", $order->get('user'));
        $orderRatingRequest->save();
    }

    return [$ratingRequestAllowed, $ratingRequestNotAllowedReason];
}

function orderRatingRequestEilibility($order)
{

    list($ratingRequestAllowed, $ratingRequestNotAllowedReason) = isOrderRatingRequestable($order);

    return "";
}

// Methods to call after an order is marked complete status
function orderCompleteMethods($order)
{

    // Referral Earning Validation  
    $responseReferral = referralEarningValidationProcessing($order);

    // Rating Request eligibility
    $responseRatingRequest = orderRatingRequestEilibility($order);

    if (!empty($responseReferral)) {

        return $responseReferral;
    }

    if (!empty($responseRatingRequest)) {

        return $responseRatingRequest;
    }

    return "";
}

function getRatingURL($ratingRequestId)
{
    $domain = '';
    $env = $GLOBALS['env_EnvironmentDisplayCode'];
    switch ($env){
        case 'TEST':
            $domain = 'https://wp-test.atyourgate.com';
            break;
        case 'PROD':
            $domain = 'https://atyourgate.com';
            break;
    }


    return $domain.'/rate/?r=' . $ratingRequestId;
}

function couponAddedToOrderViaCart($order)
{

    // If the order is found in the UserCoupons class
    $userCoupon = parseExecuteQuery(["appliedToOrder" => $order], "UserCoupons");

    if (count_like_php5($userCoupon) > 0) {

        return false;
    }

    return true;
}

function wasCouponAddedAtSignup($coupon, $user)
{

    // If the order is found in the UserCoupons class
    $userCoupon = parseExecuteQuery(["coupon" => $coupon, "user" => $user], "UserCoupons");

    if (count_like_php5($userCoupon) > 0) {

        return true;
    }

    // If the coupon is found in the UserCredits class
    $userCredit = parseExecuteQuery(["signupCoupon" => $coupon, "user" => $user], "UserCredits");

    if (count_like_php5($userCredit) > 0) {

        return true;
    }

    return false;
}

//////////////////////////// TEMP METHODS /////////////////////////////////
