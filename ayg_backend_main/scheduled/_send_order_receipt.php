<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'scheduled/_send_email.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Httpful\Request;


function send_order_receipt($orderId) {

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch Order object and get initial info
	///////////////////////////////////////////////////////////////////////////////////////
	$orderObject = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "user", "sessionDevice", "sessionDevice.userDevice", "retailer.location", "coupon", "coupon.applicableUser", "deliveryLocation"), 1);
	$orderIdBeingProcessed = $orderObject;

	// Is order cancelled, if so skip processing
	if(isOrderCancelled($orderObject)) {

		json_error("AS_3011", "", "Order Email receipt skipped as order was cancelled.", 3, 1);
		return "";
	}

	///////////////////////////////////////////////////////////////////////////////////////

	list($responseArray, $retailerTotals) = getOrderSummary($orderObject, 1);

	// Collect discounts (Military and Airport Employee)
	$discounts = 0;
	if($responseArray["totals"]["AirEmployeeDiscount"] > 0) {

		$discounts = $discounts + $responseArray["totals"]["AirEmployeeDiscount"];
	}

	if($responseArray["totals"]["MilitaryDiscount"] > 0) {

		$discounts = $discounts + $responseArray["totals"]["MilitaryDiscount"];
	}

	// Discounts add negative sign
	$responseArray["totals"]["DiscountsTotalDisplay"] = dollar_format($discounts);
	if($discounts > 0) {

		$responseArray["totals"]["DiscountsTotalDisplay"] = "-" . $responseArray["totals"]["DiscountsTotalDisplay"];
	}

	// Airport Sherpa fees, add Service fees
	if($responseArray["totals"]["ServiceFee"] > 0) {

		$responseArray["totals"]["AirportSherpaFee"] = $responseArray["totals"]["AirportSherpaFee"] + $responseArray["totals"]["ServiceFee"];
		$responseArray["totals"]["AirportSherpaFeeDisplay"] = dollar_format($responseArray["totals"]["AirportSherpaFee"]);
	}

	// Credits add negative sign
	if($responseArray["totals"]["CreditsAppliedInCents"] > 0) {

		$responseArray["totals"]["CreditsAppliedDisplay"] = "-" . $responseArray["totals"]["CreditsAppliedDisplay"];
	}

	// TAG
	// Referrer
    $userReferralCode = '';
	$userReferral = parseExecuteQuery(array("user" => $orderObject->get('user')), "UserReferral", "", "", [], 1);


	// Get Order ETA timestamp per Airport timezone
	$currentTimeZone = date_default_timezone_get();
	$airporTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'), date_default_timezone_get());
	date_default_timezone_set($airporTimeZone);
	$fullfillment_time_estimate_formatted = date('g:i a', $orderObject->get('etaTimestamp'));

	//$fullfillment_time_estimate_range_formatted = getOrderFullfillmentTimeRangeEstimateDisplay($orderObject->get('etaTimestamp')-$orderObject->get('submitTimestamp'))[0];
    $fullfillment_time_estimate_range_formatted =
        \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
            $orderObject->get('etaTimestamp')-(new DateTime('now'))->getTimestamp(),
            $GLOBALS['env_fullfillmentETALowInSecs'],
            $GLOBALS['env_fullfillmentETAHighInSecs'],
            $airporTimeZone
        )[0];

	date_default_timezone_set($currentTimeZone);


    if (empty($userReferral)){
        $userReferralReferralCode=null;
    }else{
        $userReferralReferralCode=$userReferral->get('referralCode');
    }

    //payment check for 0$
    if($responseArray["payment"]["paymentTypeName"] == ""){
		$paymentDetail = "";
	}else{
		$paymentDetail = "Paid for using ".$responseArray["payment"]["paymentTypeName"]." ending in ".$responseArray["payment"]["paymentTypeId"];
	}

	// Pickup Order
	if(strcasecmp($orderObject->get('fullfillmentType'), "p")==0) {

		$templateName = 'email_order_receipt_pickup';
		if($GLOBALS['env_UserReferralRewardEnabled'] == true) {

			$templateName = 'email_order_receipt_pickup_with_referral_ad';
		}

        if($GLOBALS['env_LyftPromoEnabled'] == true) {

            $templateName = 'email_order_receipt_pickup_with_referral_ad_with_lyft';
        }

        if($GLOBALS['env_ClearPromoEnabled'] == true) {

            $templateName = 'email_order_receipt_pickup_with_referral_ad_with_clear';
        }

        $env_invoice_cc_email = $GLOBALS['env_invoice_cc_email'];

        $orderObject->get('retailer')->get('location')->fetch();

		$processEmailResponse = emailSend(
			$orderObject->get('user')->get('first_name') . ' ' . $orderObject->get('user')->get('last_name'), 
			createEmailFromUsername($orderObject->get('user')->get('username'),$orderObject->get('user')),
			"Order Confirmation", 
			$templateName, 
					array_merge([
					"greeting" => randomGreeting('',false,false),
					"first_name" => $orderObject->get('user')->get('firstName').",",
					"order_sequence_number" => $orderObject->get('orderSequenceId'),
					"retailer_name" => $orderObject->get('retailer')->get('retailerName'),
					"airport_iata_code" => $orderObject->get('retailer')->get('airportIataCode'),
					"fullfillment_time_estimate_in_mins" => round(($orderObject->get('etaTimestamp')-$orderObject->get('submitTimestamp'))/60),
					"fullfillment_time_estimate_formatted" => $fullfillment_time_estimate_formatted,
					"fullfillment_time_estimate_range_formatted" => $fullfillment_time_estimate_range_formatted,
					"fullfillment_location" => $orderObject->get('retailer')->get('location')->get('gateDisplayName'),
					"reward_in_dollars_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralRewardInCents']),
					"referral_offer_in_dollars_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']),
					"referral_offer_in_dollars_min_spend_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']),
					"referral_rules_url" => $GLOBALS['env_UserReferralRulesLink'],
					"user_referral_code" => $userReferralReferralCode,
					"payment_details" => $paymentDetail,
                    "copyright_year" => date('Y')
					],
					$responseArray["totals"], $responseArray["payment"]
					),

					[
					"items" => $responseArray["items"]
					],
            		$GLOBALS['env_invoice_cc_email']

		);
	}

	// Delivery Order
	else {

		$templateName = 'email_order_receipt_delivery';
		if($GLOBALS['env_UserReferralRewardEnabled'] == true) {

			$templateName = 'email_order_receipt_delivery_with_referral_ad';
		}

        if($GLOBALS['env_LyftPromoEnabled'] == true) {

            $templateName = 'email_order_receipt_delivery_with_referral_ad_with_lyft';
        }

        if($GLOBALS['env_ClearPromoEnabled'] == true) {

            $templateName = 'email_order_receipt_delivery_with_referral_ad_with_clear';
        }

        $env_invoice_cc_email = $GLOBALS['env_invoice_cc_email'];

		// tips, separate for txt and html

        $tipsDetailsTxt = $tipsDetailsHtml = '';
		if (in_array($orderObject->get('tipAppliedAs'),\App\Consumer\Entities\Order::TIP_APPLIED_AS_OPTIONS)){
            $tipsDisplay = '$' . number_format((float)($orderObject->get('tipCents')/100), 2, '.', '');
            $tipsDetailsTxt = "Tips: ".$tipsDisplay;
            $tipsDetailsHtml = '
                                        <tr style="border-bottom:1px solid #cfcfcf;">
                                            <td style="width: 10%; padding-top: 10px;" class="padding-copy">&nbsp;</td>
                                            <td style="width: 60%; font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #333333; padding-top: 5px; padding-right: 10px;" class="padding-copy" align="right">Tips</td>
                                            <td style="width: 30%; font-size: 14px; font-family: Helvetica, Arial, sans-serif; color: #333333; padding-top: 5px;" class="padding-copy" align="right">'.$tipsDisplay.'</td>
                                        </tr>';
		}


		$processEmailResponse = emailSend(
			$orderObject->get('user')->get('first_name') . ' ' . $orderObject->get('user')->get('last_name'), 
			createEmailFromUsername($orderObject->get('user')->get('username'),$orderObject->get('user')),
			"Order Confirmation", 
			$templateName, 
					array_merge([
					"greeting" => randomGreeting('',false,false),
					"first_name" => $orderObject->get('user')->get('firstName').",",
					"order_sequence_number" => $orderObject->get('orderSequenceId'),
					"retailer_name" => $orderObject->get('retailer')->get('retailerName'),
					"airport_iata_code" => $orderObject->get('retailer')->get('airportIataCode'),
					"fullfillment_time_estimate_in_mins" => round(($orderObject->get('etaTimestamp')-$orderObject->get('submitTimestamp'))/60),
					"fullfillment_time_estimate_formatted" => $fullfillment_time_estimate_formatted,
					"fullfillment_time_estimate_range_formatted" => $fullfillment_time_estimate_range_formatted,
					"fullfillment_location" => $orderObject->get('deliveryLocation')->get('gateDisplayName'),
					"reward_in_dollars_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralRewardInCents']),
					"referral_offer_in_dollars_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']),
					"referral_offer_in_dollars_min_spend_formatted" => dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']),
					"referral_rules_url" => $GLOBALS['env_UserReferralRulesLink'],
					"user_referral_code" => $userReferralReferralCode,
                    "payment_details" => $paymentDetail,
                    "tips_details_txt" => $tipsDetailsTxt,
                    "tips_details_html" => $tipsDetailsHtml,
					"copyright_year" => date('Y')
					], $responseArray["totals"], $responseArray["payment"]),

					[
					"items" => $responseArray["items"]
					],
				$GLOBALS['env_invoice_cc_email']
		);
	}

	return $processEmailResponse;
}

?>
