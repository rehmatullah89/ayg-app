<?php

require 'dirpath.php';
require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

use App\Consumer\Helpers\QueueMessageHelper;
use App\Consumer\Services\QueueServiceFactory;

$allowedOrigins = [
    "http://ayg-deb.test",
];

if (isset($_SERVER["HTTP_REFERER"]) && in_array(trim($_SERVER["HTTP_REFERER"],'/'), $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . trim($_SERVER["HTTP_REFERER"],'/'));
}


$app->get('/data/update/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', 'apiAuthForOpsAPI',
    function ($apikey, $epoch, $sessionToken, $airportIataCode) {

        try {
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_RabbitMQConsumerDataEdit']);
            $workerQueue->sendMessage(
                array("action" => "data_update",
                    "content" =>
                        array(
                            "airportIataCode" => $airportIataCode,
                        )
                )
            );
            $responseArray = ['success'=>1];
        }
        catch (Exception $ex) {
            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", $response["error_message_log"] . " airportIataCode - " . $airportIataCode, 1, 1);
            $responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!",'error'=>$ex->getMessage());
        }

        json_echo(json_encode($responseArray));
    });

$app->get('/retailers/update/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', 'apiAuthForOpsAPI',
    function ($apikey, $epoch, $sessionToken, $airportIataCode) {

        try {
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_RabbitMQConsumerRetailersEdit']);
            $workerQueue->sendMessage(
                array("action" => "retailers_update",
                    "content" =>
                        array(
                            "airportIataCode" => $airportIataCode,
                        )
                )
            );
            $responseArray = ['success'=>1];
        }
        catch (Exception $ex) {
            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", $response["error_message_log"] . " airportIataCode - " . $airportIataCode, 1, 1);
            $responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!",'error'=>$ex->getMessage());
        }

        json_echo(json_encode($responseArray));
    });

$app->get('/coupons/update/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
    function ($apikey, $epoch, $sessionToken) {

        try {
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_RabbitMQConsumerCouponsEdit']);
            $workerQueue->sendMessage(
                array("action" => "coupons_update",
                    "content" =>[]
                )
            );
            $responseArray = ['success'=>1];
        }
        catch (Exception $ex) {
            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", $response["error_message_log"] . " airportIataCode - " . $airportIataCode, 1, 1);
            $responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!",'error'=>$ex->getMessage());
        }

        json_echo(json_encode($responseArray));
    });

// Add Ops Token to saved list
$app->get('/token/add/a/:apikey/e/:epoch/u/:sessionToken/token/:token', 'apiAuthForOpsAPI',
		function ($apikey, $epoch, $sessionToken, $token) {

		incrDashboardTokenCounter($token);

		json_echo(
			json_encode(['success'=>1])
		);
});

// Check Ops Token from saved list
$app->get('/token/check/a/:apikey/e/:epoch/u/:sessionToken/token/:token', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $token) {

	$responseArray = array("count" => getDashboardTokenCounter($token));

	json_echo(
		json_encode($responseArray)
	);
});

// Beta Activation
$app->get('/user/beta/action/a/:apikey/e/:epoch/u/:sessionToken/userObjectId/:userObjectId/activate/:activate', 'apiAuthForOpsAPI',
    function ($apikey, $epoch, $sessionToken, $userObjectId, $activate) {

	// activate = 1  -> Activate user
	// activate = -1 -> Deactivate user

	$activate = intval($activate);

	// Find if the user is in beta and is actve (i.e. phone was added)
	if($activate == 1) {

		$activateNegativeStatus = 'inactive';
		$activateActionName = 'Activated!';
		$activateAction = true;
		$objBetaPending = parseExecuteQuery(array("objectId" => $userObjectId, "isBetaActive" => false), "_User", "", "", [], 1);
	}
	else {

		$activateNegativeStatus = 'active';
		$activateActionName = 'Deactivated!';
		$activateAction = false;
		$objBetaPending = parseExecuteQuery(array("objectId" => $userObjectId, "isBetaActive" => true, "isActive" => true), "_User", "", "", [], 1);
	}

	// If the user wasn't found, let caller know
	if(count_like_php5($objBetaPending) < 1) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "No " . $activateNegativeStatus . " user found!");
	}
	else if($objBetaPending->get('isActive') == false) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "User registration not completed");
	}
	else {

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => $activateActionName);

		// Update user's beta status
		$objBetaPending->set("isBetaActive", $activateAction);
		$objBetaPending->save(true);

		// If activation, send notification	to user
		if($activateAction == true) {

			sendBetaActivationNotification($objBetaPending);
		}

		// Rebuild cache
		rebuildBetaCache();

		// Post to wh-contact-form
		$slack = new SlackMessage($GLOBALS['env_SlackWH_contactForm'], 'env_SlackWH_contactForm');
		$slack->setText("Beta " . $activateActionName . " (" . date("M j, g:i a", time()) . ")");

		$attachment = $slack->addAttachment();
		$attachment->addField("ENV:", $GLOBALS['env_EnvironmentDisplayCode'], false);
		$attachment->addField("Name:", $objBetaPending->get('firstName') . ' ' . $objBetaPending->get('lastName'), false);
		$attachment->addField("Username:", $objBetaPending->get('username'), false);

		try {

			$slack->send();
		}
		catch (Exception $ex) {

			json_error("AS_1054", "", "Slack post failed informing Beta " . $activateActionName . "! Post Array=" . json_encode($attachment->getAttachment()) ." -- " . $ex->getMessage(), 1, 1);
		}
	}

	json_echo(
		json_encode($responseArray)
	);
});

// List of users by type beta type
$app->get('/user/beta/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken) {

	global $env_EnvironmentDisplayCode;

	rebuildBetaCache();

	// No Beta
	$betaListTotalUsers = getCache('__BETALIST_TOTALUSERS__');

	if(empty($betaListTotalUsers)) {

		rebuildBetaCache();
		$betaListTotalUsers = getCache('__BETALIST_TOTALUSERS__');
	}

	$responseArray["totalUsers"] = $betaListTotalUsers;

	$betaListNoBeta = getCache('__BETALIST_NOBETA__', 1);
	$responseArray["discrpenacy"] = $betaListNoBeta;

	$betaListActiveUsers = getCache('__BETALIST_ACTIVEUSERS__', 1);
	$responseArray["active"] = $betaListActiveUsers;

	$betaListInactiveUsers = getCache('__BETALIST_INACTIVEUSERS__', 1);
	$responseArray["inactive"] = $betaListInactiveUsers;

	json_echo(
		json_encode($responseArray)
	);
});

// List of POS Status
$app->get('/status/pos/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken) {

	getRouteCache();

	$allAirports = getAirportsCache();

	// Check if we have this in cache
	$retailerTabletUserListKeyName = "__RETAILERTABLETUSER";
	$retailerTabletUserList = [];
	if(count_like_php5(hGetAllCache($retailerTabletUserListKeyName)) == 0) {

		// Build Tablet Retailer User list
		// JMD
		$obj = new ParseQuery("_User");
		$retailersUsers = parseSetupQueryParams(array("hasTabletPOSAccess" => true, "retailerUserType" => 1), $obj);
		$retailerTabletUsers = parseExecuteQuery(array("__MATCHESQUERY__tabletUser" => $retailersUsers), "RetailerTabletUsers", "", "", ["tabletUser", "retailer"]);

		foreach($retailerTabletUsers as $tabletUser) {

			hSetCache($retailerTabletUserListKeyName, $tabletUser->get("retailer")->get("uniqueId"), $tabletUser->get("tabletUser"), 1, 60*60);

			$retailerTabletUserList[$tabletUser->get("retailer")->get("uniqueId")] = $tabletUser->get("tabletUser");
		}
	}
	else {

		$retailerTabletUserList = hGetAllCache($retailerTabletUserListKeyName);
		array_walk($retailerTabletUserList, 'unserailize_all_array_items');
	}


	// Fetch Mobilock data for all devices from cache
	$mobilockData = getMobilockDeviceDataCache();


	$objRetailerPOSConfig = parseExecuteQuery([], "RetailerPOSConfig", "", "", ["retailer", "retailer.location"]);

	if(count_like_php5($objRetailerPOSConfig) == 0) {

		$responseArray = [];
	}
	else {

		foreach($objRetailerPOSConfig as $obj) {

			// on test / dev env it might be data problem (config exists but no retailer)
			if ($obj->get("retailer") == null){
				continue;
			}

			// Pull details if the retailer is accepting orders
			// list($isAcceptingOrders, $isClosed, $error, $notAcceptingOrderDesc) = pingRetailer($obj->get("retailer"), "", time(), 0, $obj);

			// Pull details if the Tablet is online
			if(isRetailerPingActive($obj->get("retailer"), $obj)) {

				$isTabletOnline = true;
			}
			else {

				$isTabletOnline = false;
			}

			// Last Seen
			$lastSuccessfulPingTimestamp = getRetailerPingTimestamp($obj->get("retailer")->get('uniqueId'));
			// $lastSuccessfulPingTimestamp = $obj->get("lastSuccessfulPingTimestamp");

			if(!empty($lastSuccessfulPingTimestamp)) {

				$lastSuccessfulPingTimestampFormatted = formatDateTimeRelative($lastSuccessfulPingTimestamp);
			}
			else {

				$lastSuccessfulPingTimestamp = 0;
				$lastSuccessfulPingTimestampFormatted = 'Never';
			}

			// Not Slack
			$isLoggedIn = false;
			$sessionDevicesActive = [];
			$appVersion = "";
			$posType = retailerPOSType($obj);
			if(strcasecmp($posType, "slack")!=0
				&& isset($retailerTabletUserList[$obj->get("retailer")->get('uniqueId')])) {

				// Fetch the most recent record for the retailer user
				$sessionDevicesLatest = parseExecuteQuery(["user" => $retailerTabletUserList[$obj->get("retailer")->get('uniqueId')]], "SessionDevices", "", "checkinTimestamp", ["userDevice"], 1);

				if(count_like_php5($sessionDevicesLatest) > 0) {

					$appVersion = $sessionDevicesLatest->get("userDevice")->get("appVersion");
					$isLoggedIn = $sessionDevicesLatest->get("isActive");
				}
			}

			// Is Closed
			list($isClosed, $error) = isRetailerClosed($obj->get("retailer"), 0, 0);

			$isClosedEarlyUntil = "";
			$isBeingClosedEarly = false;
			$isClosedEarly = false;

			// If closed
			if($isClosed == true) {

				$isClosedEarly = (isRetailerClosedEarly($obj->get("retailer")->get('uniqueId')) ? true : false);

				if($isClosedEarly) {

					$closedUntilInSecs = getRetailerClosedEarlyUntil($obj->get("retailer")->get('uniqueId'));

					// Is the retailer closed beyond current day
					if(time()+$closedUntilInSecs > strtotime("Tomorrow 12:00:01 am")) {

						$isClosedEarlyUntil = date("M-j", time()+$closedUntilInSecs);
					}
				}

				$isBeingClosedEarly = ($isClosedEarly == false && isRetailerCloseEarlyForNewOrders($obj->get("retailer")->get('uniqueId')) ? true : false);
			}

			$responseArray[$obj->get('retailer')->get('uniqueId')]["ping"] = [
									// "isAcceptingOrders" => $isAcceptingOrders,
									// "notAcceptingOrderDesc" => $notAcceptingOrderDesc,
									"isPingBeingChecked" => $obj->get("continousPingCheck"),
									"isTabletOnline" => $isTabletOnline,
									"lastSeenTimestampFormatted" => $lastSuccessfulPingTimestampFormatted,
									"lastSeenTimestamp" => $lastSuccessfulPingTimestamp,
									"isLoggedIn" => $isLoggedIn,
									"isBeingClosedEarly" => $isBeingClosedEarly,
									"isClosedEarly" => $isClosedEarly,
									"isClosedEarlyUntil" => $isClosedEarlyUntil,
									"isClosed" => ($isClosed == 0 ? false : true)
								];

			// Retailer Info
			$mobiBatteryLevelPct = 0;
			$mobiBatteryCharging = false;
			$mobiIsLockedIn = false;
			$mobiLastSeen = 0;
			$mobiLicenseExpiryAlert = "";
			$isTabletAtAirport = false;

			if(isset($mobilockData[$obj->get("tabletMobilockId")])) {

				$mobiBatteryLevelPct = $mobilockData[$obj->get("tabletMobilockId")]["battery_status"];
				$mobiBatteryCharging = $mobilockData[$obj->get("tabletMobilockId")]["battery_charging"];

				// Not updated yet
				$mobiIsLockedIn = $mobilockData[$obj->get("tabletMobilockId")]["isLocked"];
				$mobiLastSeen = $mobilockData[$obj->get("tabletMobilockId")]["lastSeen"];

				// Is mobi lock license has expired
				if($mobilockData[$obj->get("tabletMobilockId")]["licence_expires_at"] < time()) {

					$mobiLicenseExpiryAlert = "Mobilock expired!";
				}
				// Is mobi lock license is expiring in next 7 days
				else if($mobilockData[$obj->get("tabletMobilockId")]["licence_expires_at"] < (time()-7*24*60*60)) {

					$mobiLicenseExpiryAlert = "Mobilock will expire on " . date("Y-m-d", $mobilockData[$obj->get("tabletMobilockId")]["licence_expires_at"]);
				}

				// Find if the tablet is at the airport
				if(is_null($mobilockData[$obj->get("tabletMobilockId")]["location"]["lat"])
					|| is_null($mobilockData[$obj->get("tabletMobilockId")]["location"]["lng"])
					|| empty($mobilockData[$obj->get("tabletMobilockId")]["location"]["lat"])
					|| empty($mobilockData[$obj->get("tabletMobilockId")]["location"]["lng"])) {

					$isTabletAtAirport = "U";
				}
				else {

					$isTabletAtAirport = isAtAirport(
						$mobilockData[$obj->get("tabletMobilockId")]["location"],
						["lat" => $allAirports["byAirportIataCode"][$obj->get("retailer")->get("airportIataCode")]->get("geoPointLocation")->getLatitude(),
						"lng" => $allAirports["byAirportIataCode"][$obj->get("retailer")->get("airportIataCode")]->get("geoPointLocation")->getLongitude()]);
				}
			}

			$responseArray[$obj->get('retailer')->get('uniqueId')]["info"] = [
									"retailerName" => $obj->get("retailer")->get("retailerName"),
									"location" => $obj->get("retailer")->get("location")->get("gateDisplayName"),
									"posType" => $posType,
									"appVersion" => trim(preg_replace("/\(.*\)/", "", $appVersion)),
									"airportIataCode" => $obj->get("retailer")->get("airportIataCode"),

									// Mobilock fetched data
									"batteryLevelPct" => $mobiBatteryLevelPct,
									"batteryCharging" => $mobiBatteryCharging,
									"isLockedInMobilock" => $mobiIsLockedIn,
									"lastSeenByMobilock" => $mobiLastSeen,
									"isTabletAtAirport" => $isTabletAtAirport,
									"mobilockLicenseAlert" => $mobiLicenseExpiryAlert
								];
		}
	}

	// Cache for 5 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// Close POS
$app->get('/close/pos/a/:apikey/e/:epoch/u/:sessionToken/uniqueId/:uniqueId/closeUntilDate/:closeUntilDate', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $uniqueId, $closeUntilDate) {

	$responseArray = [];
	$retailer = parseExecuteQuery(['uniqueId' => $uniqueId], "Retailers", "", "", [], 1);

	$tabletOrderRepository = new App\Tablet\Repositories\OrderParseRepository();
	$orderListCount = $tabletOrderRepository->getBlockingEarlyCloseOrdersForOpsDashboardCountByRetailerIdList([$retailer->getObjectId()]);

	if($orderListCount > 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Retailer has unaccepted orders. They must be accepted before closing the POS.");
	}
	else if(isRetailerClosedEarly($uniqueId)) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "POS Terminal is already closed.");
	}
	else if(isRetailerCloseEarlyForNewOrders($uniqueId)) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "There is an existing POS Terminal closing request. No new orders will be accepted during this time.");
	}
	else {

		if(strcasecmp($closeUntilDate, "0")!=0) {

			if(strlen($closeUntilDate) != 8) {

				$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Invalid date format.");
			}

			$timestamp = strtotime(substr($closeUntilDate, -4) . '-' . substr($closeUntilDate, 0, 2) . '-' . substr($closeUntilDate, 2, 2) . " 11:59:59 PM");

			if($timestamp == false || time() > $timestamp) {

				$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Must be a future date.");
			}

			$closeForSecs = $timestamp-time();

			// Request can be in place for up to 14 days
			if($closeForSecs > (14*24*60*60)) {

				$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Max future date can be 14 days out.");
			}

			// If no errors
			if(count_like_php5($responseArray) == 0) {

				// setRetailerClosedEarly($uniqueId, $closeForSecs);
				$closeInSecs = setRetailerClosedEarlyTimerMessage($uniqueId, getTabletOpenCloseLevelFromDashboard(), $closeForSecs);
				$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");
			}
		}
		else {

			$closeInSecs = setRetailerClosedEarlyTimerMessage($uniqueId, getTabletOpenCloseLevelFromDashboard());
			$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");
		}
	}

	json_echo(
		json_encode($responseArray)
	);
});

// Open POS
$app->get('/open/pos/a/:apikey/e/:epoch/u/:sessionToken/uniqueId/:uniqueId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $uniqueId) {

	$retailer = parseExecuteQuery(['uniqueId' => $uniqueId], "Retailers", "", "", [], 1);

	if(!isRetailerClosedEarly($uniqueId)
		|| !isRetailerCloseEarlyForNewOrders($uniqueId)) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "POS Terminal is not closed.");
	}
	if(!canRetailerOpenAfterClosedEarly($uniqueId, getTabletOpenCloseLevelFromDashboard())) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "POS Terminal cannot be opened from the Dashboard.");
	}
	else {

		// JMD
		setRetailerOpenAfterClosedEarly($uniqueId, getTabletOpenCloseLevelFromDashboard());
		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");
	}

	json_echo(
		json_encode($responseArray)
	);
});

// List of orders in last X hours
$app->get('/order/list/a/:apikey/e/:epoch/u/:sessionToken/l/:limitInHours', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $limitInHours) {

	$limitInHours = intval($limitInHours);


	// Orders submitted in last limitInHours hours
	$obj = new ParseQuery("Order");
	$objOrderCompleted = parseSetupQueryParams(["__GTE__submitTimestamp" => (time()-$limitInHours*60*60), "__CONTAINEDIN__status" => array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled())], $obj);

	$obj = new ParseQuery("Order");
	//$objOrderActive = parseSetupQueryParams(["__GTE__submitTimestamp" => (time()-$limitInHours*60*60),"__CONTAINEDIN__status" => array_merge(listStatusesForSubmitted(), listStatusesForPendingInProgress())], $obj);
    $objOrderActive = parseSetupQueryParams(["__CONTAINEDIN__status" => array_merge(listStatusesForSubmitted(), listStatusesForPendingInProgress())], $obj);

    $includeKeys = array("retailer", "retailer.location", "deliveryLocation", "user", "flightTrip.flight", "sessionDevice", "sessionDevice.userDevice");

    $objOrderMainQuery = ParseQuery::orQueries([$objOrderCompleted, $objOrderActive]);

    foreach ($includeKeys as $keyName) {

        $objOrderMainQuery->includeKey($keyName);
    }

    $objOrderMainQuery->limit(1000);
    $objOrder = $objOrderMainQuery->find();

	if(count_like_php5($objOrder) == 0) {

		$responseArray = ["active" => [], "completed" => []];
	}
	else {

		$responseArray = ["active" => [], "completed" => []];

		foreach($objOrder as $obj) {

			$status = $obj->get("status");
			$statusDelivery = $obj->get("statusDelivery");
			$promisedVsActualDeliveryTimeDiffInMins = false;
			$ratingRequestAllowed = false;
			$ratingRequestNotAllowedReason = '';

			$delayedByFormatted = "";

			// Completed order
			if($GLOBALS['statusNames'][$status]['is_completed'] == true) {

				$activeOrCompleted = 'completed';
				$delayInMins = 0;
				$delayedByFormatted = "";

				// Get how ahead or behind were we on delivery time in mins
				if($obj->get("fullfillmentType") == "d") {

					$orderStatus = parseExecuteQuery(["order" => $obj, "status" => listStatusesForSuccessCompleted()], "OrderStatus", "", "", [], 1);
					if(count_like_php5($orderStatus) > 0) {

						$promisedVsActualDeliveryTimeDiffInMins = ceil(($obj->get('etaTimestamp')-$orderStatus->getCreatedAt()->getTimestamp())/60);
					}
				}

				if(in_array($obj->get('status'), listStatusesForCancelled())) {

					$ratingRequestAllowed = false;
					$ratingRequestNotAllowedReason = 'Order is Canceled';
				}
				else {

					list($ratingRequestAllowed, $ratingRequestNotAllowedReason) = isOrderRatingRequestAttempted($obj);
                    list($ratingRequestAllowed, $ratingRequestNotAllowedReason) = [true,''];
				}
			}
			else {

				$activeOrCompleted = 'active';

				// Find latest delays
				$orderDelay = parseExecuteQuery(["order" => $obj], "OrderDelays", "", "createdAt", [], 1);

				if(count_like_php5($orderDelay) > 0) {

					$delayInMins = $orderDelay->get("delayInMins");
					$delayedByFormatted = $orderDelay->get("delayType") . " (" . $orderDelay->get("delayInMins") . " mins)";
				}
				else {

					$delayInMins = 0;
					$delayedByFormatted = "";
				}
			}

			// Find customer's phone
			$userPhoneNumberFormatted = "";

			if ($obj->get("user")===null){
			}else{
                $userPhone = parseExecuteQuery(["user" => $obj->get("user"), "phoneVerified" => true, "isActive" => true], "UserPhones", "", "", [], 1);
                if(count_like_php5($userPhone) > 0) {
                    $userPhoneNumberFormatted = $userPhone->get("phoneNumberFormatted");
                }
			}


			// Find Delivery Person's name
			if($obj->get("fullfillmentType") == "p") {

				$deliveryName = "";
			}
			else {

				// JMD
				$zDeliverySlackOrderAssignments = parseExecuteQuery(["order" => $obj], "zDeliverySlackOrderAssignments", "", "", ["deliveryUser"], 1);

				if(count_like_php5($zDeliverySlackOrderAssignments) > 0) {

					$deliveryName = $zDeliverySlackOrderAssignments->get("deliveryUser")->get("deliveryName");
				}
				else {

					$deliveryName = "";
				}
			}

			$flightNum = $flightArrivalIataCode = $flightDepartureTimeFormatted = $flightDepartureGate = "";
			// Fetch flight data
			if($obj->has('flightTrip')
				&& $obj->get('flightTrip')->has('flight')
				&& !empty($obj->get('flightTrip')->get('flight')->get('uniqueId'))) {

				$flight = getFlightInfoCache($obj->get('flightTrip')->get('flight')->get('uniqueId'));

				if(!empty($flight)) {

					$flightNum = $flight->get("info")->get("airlineIataCode") . ' ' . $flight->get("info")->get("flightNum");
					$flightArrivalIataCode = $flight->get('arrival')->getAirportInfo()['airportIataCode'];
					$flightDepartureTimeFormatted = $flight->get('departure')->getLastKnownTimestampFormatted();
					$flightDepartureGate = $flight->get('departure')->getGateDisplayName();
				}
			}

            $customerName = $obj->get("user") === null ? '' : $obj->get("user")->get("firstName") . " " . $obj->get("user")->get("lastName");
            $customerEmail = $obj->get("user") === null ? '' : $obj->get("user")->get("email");





			$responseArray[$activeOrCompleted][$obj->getObjectId()] =
				array(
					"airportIataCode" => $obj->get("retailer")->get("airportIataCode"),
					"statusCategory" => $GLOBALS['statusNames'][$status]["statusCategoryCode"],
					"status" => $GLOBALS['statusNames'][$status]["internal"],
					"statusDelivery" => !empty($statusDelivery) ? $GLOBALS['statusDeliveryNames'][$statusDelivery]["internal"] : "",
					"retailerName" => $obj->get("retailer")->get("retailerName"),
					"retailerLocation" => $obj->get("retailer")->get("location")->get("locationDisplayName"),
					"fullfillmentType" => $obj->get("fullfillmentType"),
					"objectId" => $obj->getObjectId(),
					"orderSequenceId" => $obj->get("orderSequenceId"),
					"submitTimestamp" => $obj->get("submitTimestamp"),
					"submitTimestampFormatted" => formatDateTimeRelative($obj->get("submitTimestamp")),
					"etaTimestamp" => $obj->get("etaTimestamp"),
					"etaTimestampFormatted" => formatDateTimeRelative($obj->get("etaTimestamp")),
					"etaTimestampRangeFormatted" =>
                        //getOrderFullfillmentTimeRangeEstimateDisplay(($obj->has("fullfillmentTimeInSeconds") ? $obj->get("fullfillmentTimeInSeconds") : 0))[0],

	                    \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                            	$obj->get("etaTimestamp") - (new DateTime('now'))->getTimestamp(),
                                $GLOBALS['env_fullfillmentETALowInSecs'],
                                $GLOBALS['env_fullfillmentETAHighInSecs'],
								fetchAirportTimeZone($obj->get('retailer')->get('airportIataCode'), date_default_timezone_get())
                            )[0],

					"etaTimestampRangeShown" => wasUserShownFullfillmentTimeRange($obj->get('sessionDevice')->get('userDevice')),
					"deliveryLocation" => $obj->get("fullfillmentType") == 'd' ? $obj->get("deliveryLocation")->get("gateDisplayName") : "",
					"customerName" => $customerName,
					"customerEmail" => $customerEmail,
					"customerPhone" => $userPhoneNumberFormatted,
					"deliveryName" => $deliveryName,
					"delayInMins" => $delayInMins,
					"delayedByFormatted" => $delayedByFormatted,
					"flightNum" => $flightNum,
					"flightArrivalIataCode" => $flightArrivalIataCode,
					"flightDepartureTimeFormatted" => $flightDepartureTimeFormatted,
					"flightDepartureGate" => $flightDepartureGate,
					"isCancellable" => isOrderCancellable($obj),
					"isPushable" => isOrderManualPushable($obj),
					"amountPaid" => dollar_format(getOrderPaidAmountInCents($obj)),
					"ratingRequestAllowed" => $ratingRequestAllowed,
					"ratingRequestNotAllowedReason" => $ratingRequestNotAllowedReason,
					);

					if(!is_bool($promisedVsActualDeliveryTimeDiffInMins)) {

						if($promisedVsActualDeliveryTimeDiffInMins==-0) {

							$promisedVsActualDeliveryTimeDiffInMins = 0;
						}

						$responseArray[$activeOrCompleted][$obj->getObjectId()]["promisedVsActualDeliveryTimeDiffInMins"] = $promisedVsActualDeliveryTimeDiffInMins;
					}
		}
	}

	// Cache for 5 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// Set item as 86
// JMD
$app->get('/retailer/set86/a/:apikey/e/:epoch/u/:sessionToken/uniqueRetailerItemId/:uniqueRetailerItemId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $uniqueRetailerItemId) {

	$retailerItem = parseExecuteQuery(array("uniqueId" => $uniqueRetailerItemId, "isActive" => true), "RetailerItems", "", "", [], 1);

	if(count_like_php5($retailerItem) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Item not found!");
	}
	else if(isItem86isedFortheDay($uniqueRetailerItemId)) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Item already 86sed!");
	}
	else {

		$retailer = parseExecuteQuery(array("uniqueId" => $retailerItem->get("uniqueRetailerId")), "Retailers", "", "", ["location"], 1);

		$itemDetails = [
						"retailerName" => $retailer->get("retailerName"),
						"location" => $retailer->get("location")->get("locationDisplayName"),
						"itemName" => !empty($retailerItem->get("itemDisplayName")) ? $retailerItem->get("itemDisplayName") : $retailerItem->get("itemPOSName"),
						"uniqueRetailerItemId" => $uniqueRetailerItemId
						];

		setItem86isedFortheDay($uniqueRetailerItemId, $itemDetails);
		notifyOnSlackMenuUpdates($retailerItem->get("uniqueRetailerId"), "Item 86isd", ["Unique Id" => $itemDetails["uniqueRetailerItemId"], "Item Name" => $itemDetails["itemName"]]);

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Item has been 86sed!");
	}

	json_echo(
		json_encode($responseArray)
	);
});

// Remove item as 86
$app->get('/retailer/remove86/a/:apikey/e/:epoch/u/:sessionToken/uniqueRetailerItemId/:uniqueRetailerItemId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $uniqueRetailerItemId) {

	$retailerItem = parseExecuteQuery(array("uniqueId" => $uniqueRetailerItemId, "isActive" => true), "RetailerItems", "", "", [], 1);

	if(count_like_php5($retailerItem) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Item not found!");
	}
	else if(!isItem86isedFortheDay($uniqueRetailerItemId)) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Item is not 86sed!");
	}
	else {

		delItem86isedFortheDay($uniqueRetailerItemId);
		notifyOnSlackMenuUpdates($retailerItem->get("uniqueRetailerId"), "Item 86 Removed", ["Unique Id" => $uniqueRetailerItemId, "Item Name" => (!empty($retailerItem->get("itemDisplayName")) ? $retailerItem->get("itemDisplayName") : $retailerItem->get("itemPOSName"))]);

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "86 has been removed!");
	}

	json_echo(
		json_encode($responseArray)
	);
});

// List of all 86ed orders
$app->get('/retailer/list86/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
	\App\Dashboard\Controllers\DashboardController::class . ':getAll86Items'
);
/*$app->get('/retailer/list86/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken) {

	$responseArray = [];

	$all86Items = fetchAll86Items();

	foreach($all86Items as $keyName) {

		// $responseArray[] = getCache($keyName, 1);
		$responseArray[] = json_decode($keyName, true);
	}

	// Cache for 5 secs
	json_echo(
		json_encode($responseArray)
	);
});*/

// Get Order Invoice
$app->get('/order/invoice/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId) {

	$order = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", [], 1);

	if(count_like_php5($order) == 0) {

		json_echo(json_encode([false, "Order not found!"]));exit;
	}
	else if(empty($order->get("invoicePDFURL"))) {

		json_echo(json_encode([false, "Invoice not found!"]));exit;
	}
	else {

		  $filename = $order->get("invoicePDFURL");
		  $getTempInvoiceURL = S3GetPrivateFile(getS3ClientObject(), $GLOBALS['env_S3BucketName'], getS3KeyPath_FilesInvoice() . '/' . $filename, 1);
  		  json_echo(json_encode([true, $getTempInvoiceURL]));exit;
	}
});

// List of users online in last X mins
$app->get('/user/online/a/:apikey/e/:epoch/u/:sessionToken/withinMins/:withinMins', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $withinMins) {

	$withinMins = intval($withinMins);

	// default it to 30 mins
	if($withinMins <= 0) {

		$withinMins = 30;
	}

	// Users online
	$objUser = parseExecuteQuery(["__GTE__checkinTimestamp" => (time()-($withinMins*60)), "isActive" => true], "SessionDevices", "", "", ["user", "userDevice"], 10000, false, [], 'find', true);

	if(count_like_php5($objUser) == 0) {

		$responseArray = [];
	}
	else {

		foreach($objUser as $obj) {

			// Skip all but consumer type users
			if($obj->get('user')->get('typeOfLogin') != 'c') {

				continue;
			}

	        list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationForSession($obj);

			try {
                $email = createEmailFromUsername($obj->get('user')->get('username'),$obj->get('user'));
			}catch (Exception $exception){
                $email = 'not set (guest user)';
			}

	        $phone = '';
	        $userPhone = getLatestUserPhone($obj->get('user'));
	        if(count_like_php5($userPhone) > 0) {

	        	$phone = $userPhone->get('phoneNumberFormatted');
	        }

			$responseArray[$obj->getObjectId()] =
				array(
					"firstName" => $obj->get('user')->get('firstName'),
					"lastName" => $obj->get('user')->get('lastName'),
					"loggedInFromCountry" => $obj->get('country'),
					"isOnWifi" => $obj->get('isOnWifi'),
					"deviceModel" => $obj->get('userDevice')->get('deviceModel'),
					"deviceOS" => $obj->get('userDevice')->get('deviceOS'),
					"deviceType" => $obj->get('userDevice')->get('isIos') == true ? "iOS" : "Android",
					"appVersion" => $obj->get('userDevice')->get('appVersion'),
					"checkinTimestamp" => $obj->get('checkinTimestamp'),
					"checkinTimestampFormatted" => formatDateTimeRelative($obj->get('checkinTimestamp')),
					"locationNearAirportIataCode" => $nearAirportIataCode,
					"locationState" => $locationState,
					"locationCountry" => $locationCountry,
					"locationSource" => $locationSource,
					"phone" => $phone,
					"email" => $email,
					);
		}
	}

	// Cache for 5 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// Send Ping Message to POS/Tablet
$app->get('/testMsg/pos/a/:apikey/e/:epoch/u/:sessionToken/uniqueId/:uniqueId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $uniqueId) {

	// Get from cache if available
	getRouteCache();

	$retailerRefObject = new ParseQuery("Retailers");
	$retailerAssociation = parseSetupQueryParams(["uniqueId" => $uniqueId, "isActive" => true], $retailerRefObject);

	$objRetailerPOSConfig = parseExecuteQuery(["__MATCHESQUERY__retailer" => $retailerAssociation], "RetailerPOSConfig", "", "", ["retailer", "retailer.location"], 1);

	if(count_like_php5($objRetailerPOSConfig) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Retailer not found");
	}
	else if(empty($objRetailerPOSConfig->get("tabletSlackURL"))) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Retailer not Tablet type.");
	}
	else {

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Sent!");

		$retailerName = $objRetailerPOSConfig->get("retailer")->get("retailerName");
		$tabletSlackURL = $objRetailerPOSConfig->get("tabletSlackURL");

		// Slack it
		$slack = new SlackMessage($tabletSlackURL, 'tabletSlackURL - ' . $retailerName);
		$slack->setText("...SYSTEM TEST...");

		$attachment = $slack->addAttachment();
		$attachment->addField("Connectivity test:", "You may ignore this message", false);

		try {

			$slack->send();
		}
		catch (Exception $ex) {

			$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Slack error!");
		}
	}

	// Cache for 5 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// Send Ping Message to Delivery person
$app->get('/testMsg/delivery/a/:apikey/e/:epoch/u/:sessionToken/deliveryId/:deliveryId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $deliveryId) {

	// JMD
	// Get from cache if available
	getRouteCache();

	$objzDeliverySlackUser = parseExecuteQuery(["objectId" => $deliveryId], "zDeliverySlackUser", "", "", [], 1);

	if(count_like_php5($objzDeliverySlackUser) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Delivery Person not found");
	}
	else if(empty($objzDeliverySlackUser->get("slackURL"))) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Slack not found.");
	}
	else {

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Sent!");

		$deliveryName = $objzDeliverySlackUser->get("deliveryName");
		$slackURL = $objzDeliverySlackUser->get("slackURL");

		// Slack it
		$slack = new SlackMessage($slackURL, 'slackURL - ' . $deliveryName);
		$slack->setText("...SYSTEM TEST...");

		$attachment = $slack->addAttachment();
		$attachment->addField("Connectivity test:", "You may ignore this message", false);

		try {

			$slack->send();
		}
		catch (Exception $ex) {

			$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Slack error!");
		}
	}

	// Cache for 5 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// List of Delivery Status
$app->get('/status/delivery/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken) {

	getRouteCache();

	// Get all
	$objDelivery = parseExecuteQuery(["isDeleted" => false], "zDeliverySlackUser", "airportIataCode");

	if(count_like_php5($objDelivery) == 0) {

		$responseArray = [];
	}
	else {

		foreach($objDelivery as $deliveryUser) {

			// Pull details if the delivery person is online
			$isDeliveryOnline = isDeliveryPingActive($deliveryUser->getObjectId());

			$lastSuccessfulPingTimestamp = getSlackDeliveryPingTimestamp($deliveryUser->getObjectId());

			if(!empty($lastSuccessfulPingTimestamp)) {

				$lastSuccessfulPingTimestampFormatted = formatDateTimeRelative($lastSuccessfulPingTimestamp);
			}
			else {

				$lastSuccessfulPingTimestamp = 0;
				$lastSuccessfulPingTimestampFormatted = 'Never';
			}

			// Get counts of active orders
			$countOfActiveOrders = getDeliveryActiveOrderCount($deliveryUser->get('airportIataCode'), $deliveryUser);

			$responseArray["users"][$deliveryUser->getObjectId()]["ping"] = array("isDeliveryOnline" => $isDeliveryOnline, "lastSeenTimestampFormatted" => $lastSuccessfulPingTimestampFormatted, "lastSeenTimestamp" => $lastSuccessfulPingTimestamp);

			// Delivery Person Info
			$responseArray["users"][$deliveryUser->getObjectId()]["info"] = array(
				"deliveryName" => $deliveryUser->get('deliveryName'),
				"SMSPhoneNumber" => $deliveryUser->get('SMSPhoneNumber'),
				"airportIataCode" => $deliveryUser->get('airportIataCode'),
				"slackChannelName" => $deliveryUser->get('slackChannelName'),
				"slackUserId" => $deliveryUser->get('slackUserId'),
				"slackUsername" => $deliveryUser->get('slackUsername'),
				"isActive" => $deliveryUser->get('isActive'),
				"countOfActiveOrders" => $countOfActiveOrders);
		}

		// Get coverage hours for the day for the airport
		$objCoveragePeriod = parseExecuteQuery(["dayOfWeek" => strval((date("w")+1))], "zDeliveryCoveragePeriod", "airportIataCode");
	    $currentTimeZone = date_default_timezone_get();
		foreach($objCoveragePeriod as $coverageByTheDay) {

		    $airporTimeZone = fetchAirportTimeZone($coverageByTheDay->get('airportIataCode'), $currentTimeZone);

		    if(strcasecmp($airporTimeZone, $currentTimeZone)!=0) {

		        date_default_timezone_set($airporTimeZone);
		    }

		    $todayMidnightAirportTimestamp = (strtotime("Yesterday 11:59:59 PM") + 1);

		    $startCoverage = date("g:i:s A T", $todayMidnightAirportTimestamp + $coverageByTheDay->get("secsSinceMidnightStart"));
		    $stopCoverage = date("g:i:s A T", $todayMidnightAirportTimestamp + $coverageByTheDay->get("secsSinceMidnightEnd"));

		    if(strcasecmp($airporTimeZone, $currentTimeZone)!=0) {

		        date_default_timezone_set($currentTimeZone);
		    }

			$responseArray["airports"][$coverageByTheDay->get("airportIataCode")]["startCoverage"] = $startCoverage;
			$responseArray["airports"][$coverageByTheDay->get("airportIataCode")]["stopCoverage"] = $stopCoverage;
		}
	}

	// Cache for 10 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 10
		])
	);
});

// Request order cancellation
$app->get('/order/cancel/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/cancelReasonCode/:cancelReasonCode/cancelReason/:cancelReason/partialRefundAmount/:partialRefundAmount/refundType/:refundType/refundRetailer/:refundRetailer', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId, $cancelReasonCode, $cancelReason, $partialRefundAmount, $refundType, $refundRetailer) {

	$orderObject = parseExecuteQuery(["orderSequenceId" => intval($orderId)], "Order", "", "", ["retailer", "user", "sessionDevice", "sessionDevice.userDevice"], 1);
	///////////////////////////////////////////////////////////////////////////////////////

	if(count_like_php5($orderObject) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Order cannot be canceled!");
	}
	else {

		$orderPaidAmount = getOrderPaidAmountInCents($orderObject);

		if($orderPaidAmount < $partialRefundAmount) {

			$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Refund amount can't be greater than paid amount.");
		}
		else if(count_like_php5($orderObject) > 0
			&& isOrderCancellable($orderObject)) {

			$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");

			////////////////////////////////////////////////////////////////////////////////////
			// Put on queue for processing
			////////////////////////////////////////////////////////////////////////////////////
			try {

		        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
		        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
				$workerQueue->sendMessage(
						array("action" => "order_ops_cancel_request",
							  "content" =>
							  	array(
							  		"orderId" => $orderObject->getObjectId(),
							  		"cancelOptions" => json_encode(["cancelReasonCode" => $cancelReasonCode, "cancelReason" => $cancelReason, "refundType" => $refundType, "partialRefundAmount" => $partialRefundAmount, "refundRetailer" => $refundRetailer])
								)
							),
							1
						);
			}
			catch (Exception $ex) {

				$response = json_decode($ex->getMessage(), true);
				json_error($response["error_code"], "", $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), 1, 1);

				$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!");
			}
		}
		else {

			$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Order cannot be canceled!");
		}
	}

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray)
		])
	);
});

// Request order cancellation with admin
$app->get('/order/cancelWithAdmin/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/cancelReasonCode/:cancelReasonCode/cancelReason/:cancelReason/partialRefundAmount/:partialRefundAmount/refundType/:refundType/refundRetailer/:refundRetailer', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId, $cancelReasonCode, $cancelReason, $partialRefundAmount, $refundType, $refundRetailer) {

	$orderObject = parseExecuteQuery(["orderSequenceId" => intval($orderId)], "Order", "", "", ["retailer", "user", "sessionDevice", "sessionDevice.userDevice"], 1);
	///////////////////////////////////////////////////////////////////////////////////////

	if(count_like_php5($orderObject) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Order not found!");
	}
	else {

		$orderPaidAmount = getOrderPaidAmountInCents($orderObject);

		if($orderPaidAmount < $partialRefundAmount) {

			$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Refund amount can't be greater than paid amount.");
		}
		else {

			$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");

			////////////////////////////////////////////////////////////////////////////////////
			// Put on queue for processing
			////////////////////////////////////////////////////////////////////////////////////
			try {

		        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
		        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
				$workerQueue->sendMessage(
						array("action" => "order_ops_cancel_admin_request",
							  "content" =>
							  	array(
							  		"orderId" => $orderObject->getObjectId(),
							  		"cancelOptions" => json_encode(["cancelReasonCode" => $cancelReasonCode, "cancelReason" => $cancelReason, "refundType" => $refundType, "partialRefundAmount" => $partialRefundAmount, "refundRetailer" => $refundRetailer])
								)
							),
							1
						);
			}
			catch (Exception $ex) {

				$response = json_decode($ex->getMessage(), true);
				json_error($response["error_code"], "", $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), 1, 1);

				$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!");
			}
		}
	}

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray)
		])
	);
});

// Refund partial amount with canceling
$app->get('/order/partialRefund/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/refundType/:refundType/inCents/:inCents/reason/:reason', 'apiAuthForOpsAPI',
	\App\Dashboard\Controllers\DashboardController::class . ':orderPartialRefund'
);

/*$app->get('/order/partialRefund/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/refundType/:refundType/inCents/:inCents/reason/:reason', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId, $refundType, $inCents, $reason) {

	$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");

	////////////////////////////////////////////////////////////////////////////////////
	// Put on queue for processing
	////////////////////////////////////////////////////////////////////////////////////
	try {

	    // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
	    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
		$workerQueue->sendMessage(
				array("action" => "order_ops_partial_refund_request",
					  "content" =>
					  	array(
					  		"orderId" => $orderId,
					  		"options" => json_encode(["orderSequenceId" => intval($orderId),
						"refundType" => $refundType,
						"inCents" => intval($inCents),
						"reason" => $reason])
						)
					),
					1
				);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		json_error($response["error_code"], "", $response["error_message_log"] . " OrderId - " . $orderId, 1, 1);

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!");
	}

	//////////////////////////////////////////////////////////////////////////////////////////

	json_echo(
		json_encode($responseArray)
	);
});*/

// Complete order
$app->get('/order/completeWithAdmin/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId) {

	$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Requested!");

	$orderId = intval($orderId);

	////////////////////////////////////////////////////////////////////////////////////
	// Put on queue for processing
	////////////////////////////////////////////////////////////////////////////////////
	try {

	    // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
	    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
		$workerQueue->sendMessage(
				array("action" => "order_ops_complete",
					  "content" =>
					  	array(
					  		"orderId" => $orderId,
						)
					),
					1
				);
	}
	catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		json_error($response["error_code"], "", $response["error_message_log"] . " OrderId - " . $orderId, 1, 1);

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed!");
	}

	//////////////////////////////////////////////////////////////////////////////////////////

	json_echo(
		json_encode($responseArray)
	);
});

// Request order manually push
$app->get('/order/push/a/:apikey/e/:epoch/u/:sessionToken/objectId/:objectId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $objectId) {

	$order = parseExecuteQuery(["objectId" => $objectId], "Order", "", "", ["retailer", "user", "sessionDevice", "sessionDevice.userDevice"], 1);
	///////////////////////////////////////////////////////////////////////////////////////

	list($json_resp_status, $json_resp_message) = manuallyPushStuckOrder($order);

	$responseArray = array("json_resp_status" => $json_resp_status, "json_resp_message" => $json_resp_message);

	json_echo(
		json_encode($responseArray)
	);
});

// Enable Delivery
$app->get('/status/delivery/activate/a/:apikey/e/:epoch/u/:sessionToken/deliveryId/:deliveryId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $deliveryId) {

	getRouteCache();

	$objDelivery = parseExecuteQuery(["objectId" => $deliveryId, "isActive" => false], "zDeliverySlackUser");

	if(count_like_php5($objDelivery) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Delivery already activated or not found!");
	}
	else {

		$updateDelivery = new ParseObject("zDeliverySlackUser", $objDelivery[0]->getObjectId());
		$updateDelivery->set("isActive", true);
		$updateDelivery->save();

	    try {

	        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
	        $workerQueue->sendMessage(
	            array("action" => "delivery_activated",
	                "content" =>
	                    array(
	                        "airportIataCode" => $objDelivery[0]->get("airportIataCode"),
	                        "timestamp" => time()
	                    )
	            ), 300 // secs later
	        );

	    }
	    catch (Exception $ex) {

	        json_error("AS_1062", "", "Delivery Activation event - " . $ex->getMessage(), 1, 1);
	    }

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Delivery activated!");

		// Log Successful Activation
		$queueService = QueueServiceFactory::create();

	    $logDeliveryActivatedMessage = QueueMessageHelper::getLogDeliveryActivatedMessage($objDelivery[0]->get('slackUsername'), time());
		$queueService->sendMessage($logDeliveryActivatedMessage, 0);

		$logActiveDelivery = QueueMessageHelper::getLogActiveDelivery($objDelivery[0]->get("airportIataCode"), 'delivery_on', time());
		$queueService->sendMessage($logActiveDelivery, 0);
	}

	$keyList[]  = $GLOBALS['redis']->keys("*zDeliverySlackUser*");

	foreach($keyList as $keyArray) {

		foreach($keyArray as $keyName) {

			delCacheByKey($keyName);
		}
	}

	// Cache for 1 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 1
		])
	);
});

// Disable Delivery
$app->get('/status/delivery/deactivate/a/:apikey/e/:epoch/u/:sessionToken/deliveryId/:deliveryId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $deliveryId) {

	getRouteCache();

	$objDelivery = parseExecuteQuery(["objectId" => $deliveryId, "isActive" => true], "zDeliverySlackUser");

	if(count_like_php5($objDelivery) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Delivery already deactivated or not found!");
	}
	else {

		$updateDelivery = new ParseObject("zDeliverySlackUser", $objDelivery[0]->getObjectId());
		$updateDelivery->set("isActive", false);
		$updateDelivery->save();

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Delivery deactivated!");

		// Log Successful Deactivation
		$queueService = QueueServiceFactory::create();

	    $logDeliveryDeactivatedMessage = QueueMessageHelper::getLogDeliveryDeactivatedMessage($objDelivery[0]->get('slackUsername'), time());
		$queueService->sendMessage($logDeliveryDeactivatedMessage, 0);

		$dateTime = new DateTimeImmutable('now', new \DateTimeZone('UTC'));

		$logInActiveDelivery = QueueMessageHelper::getLogInActiveDelivery($objDelivery[0]->get("airportIataCode"), 'delivery_off', time());
		$queueService->sendMessage($logInActiveDelivery, 0);
	}

	$keyList[]  = $GLOBALS['redis']->keys("*zDeliverySlackUser*");

	foreach($keyList as $keyArray) {

		foreach($keyArray as $keyName) {

			delCacheByKey($keyName);
		}
	}

	// Cache for 1 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 1
		])
	);
});

// Request order info
// JMD
$app->get('/order/info/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId) {

	$orderObject = parseExecuteQuery(["orderSequenceId" => intval($orderId)], "Order", "", "", ["retailer", "retailer.location", "user", "sessionDevice", "sessionDevice.userDevice"], 1);
	///////////////////////////////////////////////////////////////////////////////////////

	if(count_like_php5($orderObject) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Order not found!");
	}
	else if(getOrderPaidAmountInCents($orderObject) == 0) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Order total is $0");
	}
	else {

		$orderDelayedRefund = parseExecuteQuery(["order" => $orderObject], "OrderDelayedRefund", "", "", ["order"], 1) ;

		$orderRefundSourceAmount = 0;
		$orderRefundSourceStatus = "";
		if(is_object($orderDelayedRefund)){
			$orderRefundSourceAmount = $orderDelayedRefund->get('amount');
			$orderRefundSourceStatus = $orderDelayedRefund->get('isCompleted');
		}

		$responseArray = [
			"orderId" => $orderObject->get('orderSequenceId'),
			"objectId" => $orderObject->getObjectId(),
			"orderRefundSourceAmount" => $orderRefundSourceAmount,
			"orderRefundSourceStatus" => $orderRefundSourceStatus,
			"reasonForPartialRefund" => $orderObject->get('reasonForPartialRefund'),
			"alreadyRefunded" => dollar_format($orderObject->get('totalsOfItemsNotFulfilledByRetailerInCents')+$orderObject->get('totalsRefundedByAS')+$orderObject->get('totalsCreditedByAS')),
			"totalPaid" => dollar_format(getOrderPaidAmountInCents($orderObject)),
			"customerName" => $orderObject->get('user')->get('firstName') . ' ' . $orderObject->get('user')->get('lastName'),
			"retailerName" => $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')'
 		];
	}

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray)
		])
	);
	// JMD
});

// Send Rating Request to Customer
$app->get('/sendAppRatingRequest/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId) {

	$order = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "coupon.applicableUser", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	list($ratingRequestAllowed, $ratingRequestNotAllowedReason) = isOrderRatingRequestable($order);
        list($ratingRequestAllowed, $ratingRequestNotAllowedReason) = [true,''];

        $ratingRequestAllowed=true;
	// Get from cache if available
	if($ratingRequestAllowed == false) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => 'Failed. ' . $ratingRequestNotAllowedReason);
	}
	else {

		// Get user device type
		$userDevice = $order->get('sessionDevice')->get('userDevice');

        // Log it
        $orderRatingRequest = new ParseObject("OrderRatingRequests");
        $orderRatingRequest->set("order", $order);
        $orderRatingRequest->set("wasRequestSent", true);
        $orderRatingRequest->set("requestSkippedReason", '');
        $orderRatingRequest->set("user", $order->get('user'));
        $orderRatingRequest->set("userDevice", $userDevice);
        $orderRatingRequest->save();

		// iOS message
		if($userDevice->get('isIos') == true) {

			if((time()-30*60) > $order->get('etaTimestamp')) {

				$templateFilePrefix = 'ratingRequestPastiOS';
			}
			else {

				$templateFilePrefix = 'ratingRequestRecentiOS';
			}
		}
		else {

			if((time()-30*60) > $order->get('etaTimestamp')) {

				$templateFilePrefix = 'ratingRequestPastAndroid';
			}
			else {

				$templateFilePrefix = 'ratingRequestRecentAndroid';
			}
		}

		//$templateContentText = emailFetchFileContent($templateFilePrefix . '.txt');
        $file = __DIR__ . '/../../' . $GLOBALS['env_HerokuSystemPath'] . $GLOBALS['env_EmailTemplatePath'] . $templateFilePrefix . '.txt';
        $templateContentText = file_get_contents($file);

		$message = str_ireplace('[customer_first_name]', $order->get('user')->get('firstName'), $templateContentText);
		$message = str_ireplace('[rating_url]', getRatingURL($orderRatingRequest->getObjectId()), $message);

		// Get message to be sent
		$userPhone = parseExecuteQuery(["user" => $order->get('user'), "isActive" => true], "UserPhones", "", "createdAt", [], 1);

		$response = send_sms_notification(
			$userPhone->getObjectId(),
			$message,
			'',
			true
		);

		if(!empty($response)) {

			// delete logged request
			$orderRatingRequest->destroy();
			$orderRatingRequest->save();

            json_error($response["error_code"], $response["error_message_user"], $response["error_message_log"], $response["error_severity"], 2, 1);

			$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Failed. " . $response["error_message_log"]);
		}
		else {

			$responseArray = array("json_resp_status" => 1, "json_resp_message" => "Sent!");

			// Slack it
	        $slack = new SlackMessage($GLOBALS['env_SlackWH_userActions'], 'env_SlackWH_userActions');
	        $slack->setText($order->get('user')->get('firstName') . ' ' . $order->get('user')->get('lastName'));

	        $attachment = $slack->addAttachment();
	        $attachment->addField("Customer:", $order->get('user')->get('firstName') . ' ' . $order->get('user')->get('lastName'), false);
	        $attachment->addField("Action:", "Rating Request Sent", false);

	        try {

	            // Post to user actions channel
	            $slack->send();
	        }
	        catch (Exception $ex) {

	            json_error("AS_1054", "", "Slack post failed informig rating request sent action!, Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(), 1, 1);
	        }
		}
	}

	// Cache for 5 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// Send Rating Request to Customer
$app->get('/skipAppRatingRequest/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $orderId) {
// JMD
	$order = parseExecuteQuery(array("objectId" => $orderId), "Order", "", "", array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "coupon.applicableUser", "user", "sessionDevice", "sessionDevice.userDevice"), 1);

	if(count_like_php5($order) > 0) {

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => 'Skipped.');

	    $orderRatingRequests = parseExecuteQuery(["order" => $order], "OrderRatingRequests", "", "", [], 1) ;

    	if(count_like_php5($orderRatingRequests) == 0) {

		    // Log it
		    $orderRatingRequest = new ParseObject("OrderRatingRequests");
		    $orderRatingRequest->set("order", $order);
		    $orderRatingRequest->set("wasRequestSent", false);
		    $orderRatingRequest->set("requestSkippedReason", 'Ops skipped');
		    $orderRatingRequest->set("user", $order->get('user'));
		    $orderRatingRequest->set("userDevice", $order->get('sessionDevice')->get('userDevice'));
		    $orderRatingRequest->save();
		}
	}
	else {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => 'Order not found.');
	}

	// Cache for 5 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// Airport List
$app->post('/delivery/adjusttimes/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken) use ($app) {

	// Fetch Post variables
	$airportIataCode = $app->request()->post('select-airports');
	$retailerIds = explode(",", $app->request()->post('select-retailers'));
	$terminalconcourses = explode(",", $app->request()->post('select-terminalconcourses'));
	$adjustment_direction = $app->request()->post('select-adjustment-direction');
	$adjustment_minutes = intval($app->request()->post('select-adjustment-minutes'));

	if(empty($airportIataCode)
		|| count_like_php5($retailerIds) == 0
		|| count_like_php5($terminalconcourses)
		|| empty($adjustment_direction)
		|| empty($adjustment_minutes)
	) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Incorrect data selected.");
	}

	$details = [
		"airportIataCode" => $airportIataCode,
		"retailerIds" => $retailerIds,
		"terminalconcourses" => $terminalconcourses,
		"adjustment_direction" => $adjustment_direction,
		"adjustment_minutes" => $adjustment_minutes,
		"requested_at_timestamp" => time()
	];

	$delivery_notification_set_id = $airportIataCode . '_' . time();
	setCacheOverrideAdjustmentForDeliveryRequest($delivery_notification_set_id, $details);

    try {

        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

        $workerQueue->sendMessage(
                array("action" => "override_adjustment_for_delivery_set",
                      "content" =>
                        array(
                        	"delivery_notification_set_id" => $delivery_notification_set_id,
                            "timestamp" => time()
                        )
                    )
                );
    }
    catch (Exception $ex) {

		$response = json_decode($ex->getMessage(), true);
		json_error($response["error_code"], "", "Process adjustment delivery time request message failed " . $response["error_message_log"], 1, 1);
	}

	$responseArray = array("json_resp_status" => 1, "json_resp_message" => "");

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5
		])
	);
});

// Airport List
$app->get('/metadata/airports/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken) {
// JMD

	getRouteCache();

	$activeAirportList = getAirportList(true);

	$counter = 0;
	$response = [];
	foreach($activeAirportList as $airport) {

		$response[$counter]["id"] = $airport["airportIataCode"];
		$response[$counter]["name"] = $airport["airportName"];

		$counter++;
	}

	$responseArray = array("json_resp_status" => 0, "json_resp_message" => $response);

	// Cache for EOW
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOW"
		])
	);
});

// Retailers List
$app->get('/metadata/retailers/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $airportIataCode) {

// JMD
	getRouteCache();

	$objRetailerPOSConfig = parseExecuteQuery([], "RetailerPOSConfig", "", "", ["retailer", "retailer.airportIataCode", "retailer.location"]);

	if(count_like_php5($objRetailerPOSConfig) == 0) {

		$responseArray = [];
	}

	$counter = 0;
	$response = [];
	$groups = [];
	$groupsAdded = [];
	foreach($objRetailerPOSConfig as $retailer) {

		if(is_object($retailer->get('retailer')) && strcasecmp($airportIataCode, $retailer->get('retailer')->get('airportIataCode'))==0) {

			$response[$counter]["id"] = $retailer->get('retailer')->get('uniqueId');
			$response[$counter]["name"] = $retailer->get('retailer')->get('retailerName') . ' - ' . $retailer->get('retailer')->get('location')->get('locationDisplayName');

			$groupid = $retailer->get('retailer')->get('location')->get('terminal') . '-' . $retailer->get('retailer')->get('location')->get('concourse');
			$response[$counter]["groupid"] = $groupid;

			if(!isset($groupsAdded[$groupid])) {

				$groupsAdded[$groupid] = 1;

				$groups[] = ["groupid" => $groupid, "groupname" => $retailer->get('retailer')->get('location')->get('terminalDisplayName') . '-' . $retailer->get('retailer')->get('location')->get('concourseDisplayName')];
			}


			$counter++;
		}
	}

	$responseArray = array("json_resp_status" => 0, "json_resp_message" => ["options" => $response, "optgroups" => $groups]);

	// Cache for EOW
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOW"
		])
	);
});

// Terminal Concourse List for Airport
$app->get('/metadata/terminalConcourses/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode', 'apiAuthForOpsAPI',
	function ($apikey, $epoch, $sessionToken, $airportIataCode) {
// JMD

	getRouteCache();

	list($locationsByTerminalConcourse, $namesByTerminalConcourse) = getLocationIdsAndNamesByTerminalConcoursePairing($airportIataCode);

	$response = [];
	foreach($namesByTerminalConcourse as $id => $name) {

		$response[] = ['id' => $id, 'name' => $name];
	}

	$responseArray = array("json_resp_status" => 0, "json_resp_message" => $response);

	// Cache for EOW
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOW"
		])
	);
});

$app->notFound(function () {

	json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

function rebuildBetaCache() {

	// List of all users
	$listOfAllUsers = [];
	$responseArray = ["active" => [], "inactive" => []];

	$objParseQueryAllUsers = parseExecuteQuery([], "_User", "", "createdAt");
	foreach($objParseQueryAllUsers as $oneUser) {

		// Find count of verified phones
		$verifiedPhoneCount = parseExecuteQuery(array("user" => $oneUser, "isActive" => true, "phoneVerified" => true), "UserPhones", "", "", [], 1, false, [], 'count');

		$userEmail = createEmailFromUsername($oneUser->get('username'),$oneUser);

		$listOfAllUsers[$userEmail]["id"] = $oneUser->getObjectId();
		$listOfAllUsers[$userEmail]["name"] = $oneUser->get("firstName") . ' ' . $oneUser->get("lastName");
		$listOfAllUsers[$userEmail]["isActive"] = $oneUser->get("isActive") == true ? true : false;
		$listOfAllUsers[$userEmail]["type"] = createTypeFromUsername($oneUser->get('username'));
		$listOfAllUsers[$userEmail]["isBetaActive"] = $oneUser->get("isBetaActive") == true ? true : false;
		$listOfAllUsers[$userEmail]["phoneVerified"] = $verifiedPhoneCount > 0 ? 'Y' : 'N';
		$listOfAllUsers[$userEmail]["formattedTime"] = formatDateTimeRelative($oneUser->getCreatedAt()->getTimestamp());
		$listOfAllUsers[$userEmail]["timestamp"] = $oneUser->getCreatedAt()->getTimestamp();

		if($oneUser->get("isActive")
			&& $oneUser->get("isBetaActive")) {

			$responseArray["active"][$userEmail] = $listOfAllUsers[$userEmail];
		}
		else {

			$responseArray["inactive"][$userEmail] = $listOfAllUsers[$userEmail];
		}
	}

	// List of Beta users
	/*
	$listOfBetaUsers = array();

	$objParseQueryBeta = parseExecuteQuery(array(), "BetaInvites");
	foreach($objParseQueryBeta as $betaUser) {

		$listOfBetaUsers[$betaUser->get('userEmail')]["id"] = $betaUser->getObjectId();
		$listOfBetaUsers[$betaUser->get('userEmail')]["source"] = $betaUser->get('source');
		$listOfBetaUsers[$betaUser->get('userEmail')]["isActive"] = $betaUser->get("isActive");
		$listOfBetaUsers[$betaUser->get('userEmail')]["formattedTime"] = formatDateTimeRelative($betaUser->getCreatedAt()->getTimestamp());
		$listOfBetaUsers[$betaUser->get('userEmail')]["timestamp"] = $betaUser->getCreatedAt()->getTimestamp();
	}
	*/

	$responseArray["totalUsers"] = count_like_php5($listOfAllUsers);
	$responseArray["discrpenacy"] = array("webbeta" => array());

	// Get list of all Users who simply applied from the Web
	$objParseQueryWebBeta = parseExecuteQuery(array("source" => "Website"), "BetaInvites");
	foreach($objParseQueryWebBeta as $webBeta) {

		$webBetaEmail = trim($webBeta->get('userEmail'));

		// In BetaInvites but not _User
		if(!isset($listOfAllUsers[$webBetaEmail])) {

			$responseArray["discrpenacy"]["webbeta"][$webBetaEmail] = array( "name" => "",
												"id" => $webBeta->getObjectId(),
												"email" => $webBetaEmail,
												"status" => "inactive (request)",
												"formattedTime" => formatDateTimeRelative($webBeta->getCreatedAt()->getTimestamp()),
												"timestamp" => $webBeta->getCreatedAt()->getTimestamp()
											);
		}
	}

	// Create list of all Active accounts
	/*
	foreach($listOfAllUsers as $email => $user) {

		$betaSource = "";

		// User found in BetaInvites
		if(isset($listOfBetaUsers[$email])) {

			$betaSource = $listOfBetaUsers[$email]["source"];
			$betaId = $listOfBetaUsers[$email]["id"];
		}

		// Get list of all Users who created account but never Applied
		// For those who didn't apply, aka In _USER but not in BetaInvites
		/*
		else {

			$responseArray["discrpenacy"]["nobeta"][$user["id"]] = array( "name" => $user["name"],
												"id" => $user["id"],
												"email" => $email,
												"formattedTime" => $user["formattedTime"],
												"timestamp" => $user["timestamp"]
											);

			// These won't be listed in the Active and Inactive lists so just continue
			continue;
		}

		// If Active account
		if($user["isActive"]
			&& $user["isBetaActive"]) {

			$responseArray["active"][$user["id"]] = array( "name" => $user["name"],
												"id" => $user["id"],
												"email" => $email,
												"betaId" => $betaId,
												"betaSource" => $betaSource,
												"formattedTime" => $user["formattedTime"],
												"timestamp" => $user["timestamp"]
											);
		}
		// If Inactive account
		else {

			$responseArray["inactive"][$user["id"]] = array( "name" => $user["name"],
												"id" => $user["id"],
												"email" => $email,
												"betaId" => $betaId,
												"betaSource" => $betaSource,
												"formattedTime" => $user["formattedTime"],
												"timestamp" => $user["timestamp"]
											);
		}
	}
	*/

	setCache('__BETALIST_NOBETA__', $responseArray["discrpenacy"], 1);
	setCache('__BETALIST_TOTALUSERS__', $responseArray["totalUsers"]);
	setCache('__BETALIST_ACTIVEUSERS__', $responseArray["active"], 1);
	setCache('__BETALIST_INACTIVEUSERS__', $responseArray["inactive"], 1);
}

?>
