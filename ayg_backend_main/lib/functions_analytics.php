<?php

use Parse\ParseObject;
use Parse\ParseQuery;

function getDeliveryPingBetweenTimestamp($startTimestamp, $endTimestamp, $deliveryIds) {

        $stmt = $GLOBALS['logsPdoConenction']->prepare("SELECT timestamp FROM ping_logs WHERE 
            `timestamp` >= :startTimestamp AND
            `timestamp` < :endTimestamp AND
            `object_type` = 'delivery' AND
            `object_id` IN (" . "'" . implode("','", $deliveryIds) . "'" . ")
            ORDER BY `timestamp` ASC
            LIMIT 100000
        ");

    $stmt->bindParam(':startTimestamp', $startTimestamp, \PDO::PARAM_INT);
    $stmt->bindParam(':endTimestamp', $endTimestamp, \PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($result)) {

        return null;
    }

    return $result;
}

function getEligibleUserActions($startTimestamp, $endTimestamp, $actionForRetailerAirportIataCode, $actions) {

        $actionsString = "'" . implode("','", $actions) . "'";
        $stmt = $GLOBALS['logsPdoConenction']->prepare("SELECT objectId,action,timestamp FROM user_action_logs WHERE 
            `timestamp` >= :startTimestamp AND
            `timestamp` < :endTimestamp AND
            `action` IN (" . $actionsString . ") AND
            `actionForRetailerAirportIataCode` = :actionForRetailerAirportIataCode
            ORDER BY `objectId`,`timestamp` ASC
            LIMIT 100000
        ");

    $stmt->bindParam(':startTimestamp', $startTimestamp, \PDO::PARAM_INT);
    $stmt->bindParam(':endTimestamp', $endTimestamp, \PDO::PARAM_INT);
    $stmt->bindParam(':actionForRetailerAirportIataCode', $actionForRetailerAirportIataCode, \PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($result)) {
        return [];
    }

    return $result;
}

function getUserPlatformDevice($objectId) {

        $stmt = $GLOBALS['logsPdoConenction']->prepare("SELECT signupAppPlatform FROM rpt_daily__User WHERE 
            `objectId` = :objectId
        ");

    $stmt->bindParam(':objectId', $objectId, \PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($result)) {
        return null;
    }

    return $result;
}

// JMD
function didUserConvert($startTimestamp, $endTimestamp, $userObjectId) {

	// Count the nuber of times this user ordered between startime and endtime
    $stmt = $GLOBALS['logsPdoConenction']->prepare("SELECT COUNT(1) cnt FROM rpt_daily__Order WHERE 
            `submitTimestamp` >= :startTimestamp AND
            `submitTimestamp` < :endTimestamp AND
            `userObjectId` = :userObjectId
    ");

    $stmt->bindParam(':startTimestamp', $startTimestamp, \PDO::PARAM_INT);
    $stmt->bindParam(':endTimestamp', $endTimestamp, \PDO::PARAM_INT);
    $stmt->bindParam(':userObjectId', $userObjectId, \PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($result)) {
        return null;
    }

    // Find the time when the user eventually orderd (even if next day)
    $stmt = $GLOBALS['logsPdoConenction']->prepare("SELECT submitTimestamp FROM rpt_daily__Order WHERE 
            `submitTimestamp` >= :startTimestamp AND
            `userObjectId` = :userObjectId
            ORDER by submitTimestamp ASC
            LIMIT 1
    ");

    $stmt->bindParam(':startTimestamp', $startTimestamp, \PDO::PARAM_INT);
    $stmt->bindParam(':userObjectId', $userObjectId, \PDO::PARAM_STR);
    $stmt->execute();

    $result2 = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    if (empty($result2)) {
        return null;
    }

    $laterTimestamp = 0;
    if(count_like_php5($result2) > 0) {

    	$laterTimestamp = $result2[0]["submitTimestamp"];
    }

    return [$result[0]["cnt"], $laterTimestamp];
}

function findGapsInCoverage($rows, $startTimestamp, $endTimestamp) {

	$gaps = [];
	$lastTimestamp = $startTimestamp;

	if(count_like_php5($rows) > 0)
	foreach($rows as $row) {

		if($lastTimestamp+intval(($GLOBALS['env_PingSlackDeliveryIntervalInSecs']*$GLOBALS['env_PingSlackGraceMultiplier'])) < $row["timestamp"]) {

			$gaps[] = ["b" => $lastTimestamp, "e" => $row["timestamp"]];
		}

		$lastTimestamp = $row["timestamp"];
	}

	$gaps[] = ["b" => $lastTimestamp, "e" => $endTimestamp];

	return $gaps;
}

function wasGapDuringTimestamp($timestamp, $gaps) {

	foreach($gaps as $gap) {

		if($gap["b"] < $timestamp
			&& $gap["e"] > $timestamp) {

			return true;
		}
	}

	return false;
}

function identifyGapsPerHour($gaps, $startTimestamp) {

	$date = date("Y-m-d", $startTimestamp);
	$gapsPerHour = [];

	foreach($GLOBALS['hours'] as $hour) {

		$timestamp = strtotime($date . " " . $hour . ":00:00");
		$timestampPlus60Mins = $timestamp + 60*60 - 1;
		
		$coverageOffTimeStart = 0;
		$gapTime = 0;
		$lastETimestamp = 0;
		foreach($gaps as $gap) {

			// Gap found between starting time
			if($gap["b"] <= $timestamp
				&& $gap["e"] > $timestamp) {

				$coverageOffTimeStart = $timestamp; // 

				// Gap was more than an hour
				if($gap["e"] > $timestampPlus60Mins) {

					$gapTime = $timestampPlus60Mins - $coverageOffTimeStart;
					break;
				}
				else {

					// Save the last e timestamp
					$lastETimestamp = $gap["e"];
					continue;
				}
			}

			if($coverageOffTimeStart == 0) {

				continue;
			}

			// Was the next break after plus 60 time, if so use the last e timestamp
			// JMD
			if($gap["b"] > $timestampPlus60Mins) {

				$gapTime = $timestampPlus60Mins - $lastETimestamp;
				break;
			}
			// Was this e timestamp greater than plus 60, then that means in the middle of this gap was our coverage gap period
			else if($gap["e"] > $timestampPlus60Mins) {

				$gapTime = $timestampPlus60Mins - $gap["b"];
				break;
			}
			// Else look at the next period (this could happen this gap was like 2, 3 mins)
			else {

				$lastETimestamp = $gap["e"];
			}
		}

		$gapsPerHour[$hour] = round($gapTime / 60);
	}

	return $gapsPerHour;
}

function ordersLostPerHour($ordersLostPerDay, $hour) {

	$count = 0;
	$ordersLostByPlatform = [];
    $ordersByHour = [];
    // JMD
	foreach($ordersLostPerDay as $userId => $details) {

		// Without leading zeros
		$hourOfPotentialOrder = date("G", $details["timestamp"]);

		// Does the hour match
		if($hourOfPotentialOrder == $hour) {

            $ordersByHour[$userId] = $details;

			// Get User's device platform
			$platformDevice = "unknown";
			$platform = getUserPlatformDevice($userId);
			if($platform != null) {

				$platformDevice = $platform[0]["signupAppPlatform"];
			}

			// if($platformDevice == "") {

			// 	print_r($platform);exit;
			// }

            $ordersByHour[$userId]["platform"] = $platformDevice;
			if(!isset($ordersLostByPlatform[$platformDevice])) {

				$ordersLostByPlatform[$platformDevice] = 1;
			}
			else {

				$ordersLostByPlatform[$platformDevice]++;
			}

			// Count lost order count
			$count++;
		}
	}

	return [$count, $ordersLostByPlatform, $ordersByHour];
}

function addFlightInfo($orders, $date, $airportIataCode) {

    $currentTimeZone = date_default_timezone_get();
    $airportTimeZone = '';
    if(!empty($airportIataCode)) {

        $airportTimeZone = fetchAirportTimeZone($airportIataCode, $currentTimeZone);
        if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

            // Set Airport Timezone
            date_default_timezone_set($airportTimeZone);
        }
    }

    foreach($orders as $userId => $details) {

        $startTimestamp = strtotime($date . " 12:00:00 AM");
        $midnightTimestamp = strtotime($date . " 11:59:59 PM");

        // Existing departure flights times are between Current flights Arrival timestamp+5 hrs and Current flights Arrival timestamp
        $flightRefObjectDeparture = new ParseQuery("Flights");
        $flightsBeforeDeparture = parseSetupQueryParams(["__GTE__lastKnownArrivalTimestamp" => $startTimestamp, "__LTE__lastKnownArrivalTimestamp" => $midnightTimestamp], $flightRefObjectDeparture);

        // Existing departure flights times are between Current flights Arrival timestamp+5 hrs and Current flights Arrival timestamp
        $flightRefObjectArrival = new ParseQuery("Flights");
        $flightAfterArrival = parseSetupQueryParams(["__LTE__lastKnownDepartureTimestamp" => $midnightTimestamp, "__GTE__lastKnownDepartureTimestamp" => $startTimestamp], $flightRefObjectArrival);

        // Find connecting flights
        $connectingFlights = ParseQuery::orQueries([$flightsBeforeDeparture, $flightAfterArrival]);


        // $flightRefObjectArrival = new ParseQuery("Flights");
        // $flight = parseSetupQueryParams(["__LTE__lastKnownDepartureTimestamp" => $midnightTimestamp, "__GTE__lastKnownDepartureTimestamp" => $startTimestamp, "arrival"], $flightRefObjectArrival);

        $userRefObject = new ParseQuery("_User");
        $user = parseSetupQueryParams(["objectId" => $userId], $userRefObject);

        // // Find user's trips
        // $userTripsRefObject = new ParseQuery("UserTrips");
        // $userTripsAssociation = parseSetupQueryParams(["user" => $user], $userTripsRefObject);

        $flightTrips = parseExecuteQuery(["__MATCHESQUERY__flight" => $connectingFlights, "__MATCHESQUERY__user" => $user, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);

        $orders[$userId]["flightId"] = "";
        $orders[$userId]["flightDepatureTimestamp"] = "";
        $orders[$userId]["flightArrivalTimestamp"] = "";
        $orders[$userId]["flightStatus"] = "";
        $orders[$userId]["flightTimestampDiffInMins"] = "";
        // JMD
        $orders[$userId]["flightDepartureIataMatch"] = "";
        $orders[$userId]["flightArrivalIataMatch"] = "";

        $orders[$userId]["otherFlightId"] = "";
        $orders[$userId]["otherFlightDepatureTimestamp"] = "";
        $orders[$userId]["otherFlightArrivalTimestamp"] = "";
        $orders[$userId]["otherFlightStatus"] = "";
        $orders[$userId]["otherFlightDepartureIataMatch"] = "";
        // JMD
        $orders[$userId]["otherFlightArrivalIataMatch"] = "";

        if(count_like_php5($flightTrips) > 0) {

            foreach($flightTrips as $i => $flightTrip) {

                $flag = 0;

                if(strcasecmp($flightTrip->get("flight")->get('arrivalAirportIataCode'), $airportIataCode)==0) {

                    $flag = 1;
                    $orders[$userId]["flightArrivalIataMatch"] = $airportIataCode;
                }
                else if(strcasecmp($flightTrip->get("flight")->get('departureAirportIataCode'), $airportIataCode)==0){

                    $flag = 1;
                    $orders[$userId]["flightDepartureIataMatch"] = $airportIataCode;
                }

                if($flag == 1) {

                    // Take the first flight
                    $orders[$userId]["flightId"] = $flightTrip->get("flight")->get("uniqueId");
                    $orders[$userId]["flightDepatureTimestamp"] = $flightTrip->get("flight")->get("lastKnownDepartureTimestamp");
                    $orders[$userId]["flightArrivalTimestamp"] = $flightTrip->get("flight")->get("lastKnownArrivalTimestamp");
                    $orders[$userId]["flightStatus"] = $flightTrip->get("flight")->get("lastKnownStatusCode");
                    $orders[$userId]["flightTimestampDiffInMins"] = floor(($orders[$userId]["flightDepatureTimestamp"] - $details["timestamp"])/60);
                }
                else {

                    // Take the first flight
                    $orders[$userId]["otherFlightId"] = $flightTrip->get("flight")->get("uniqueId");
                    $orders[$userId]["otherFlightDepatureTimestamp"] = $flightTrip->get("flight")->get("lastKnownDepartureTimestamp");
                    $orders[$userId]["otherFlightArrivalTimestamp"] = $flightTrip->get("flight")->get("lastKnownArrivalTimestamp");
                    $orders[$userId]["otherFlightStatus"] = $flightTrip->get("flight")->get("lastKnownStatusCode");
                }
            }
        }
    }

    if(!empty($airportIataCode) 
        && strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

        date_default_timezone_set($currentTimeZone);
    }

    return $orders;
}

function getDeliveryUserIdsForAirport($airport) {

	// JMD
	$deliveryUsers = parseExecuteQuery(["airportIataCode" => $airport], "zDeliverySlackUser");

	foreach($deliveryUsers as $deliveryUser) {

		$deliveryIds[] = $deliveryUser->get('slackUsername');
	}

	return $deliveryIds;
}

function setupDeliveryUpNotification($airportIataCode, $deliveryUpTime, $lastActionInMins = 45, $userIds = []) {
	
    $currentTimeZone = date_default_timezone_get();

    // Set Airport Timezone
    $airportTimeZone = fetchAirportTimeZone($airportIataCode, $currentTimeZone);
    if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

        date_default_timezone_set($airportTimeZone);
    }

	$usersNotified = $errors = [];
	$todayDate = date("Y-m-d", $deliveryUpTime);
    $timestampMidnight = strtotime($todayDate . " 12:00 am");

	// No userIds provided, so find users who were online within threshold
	if(count_like_php5($userIds) == 0) {

		$userIds = getDeliveryMissedUserIds($airportIataCode, $deliveryUpTime-($lastActionInMins*60), $deliveryUpTime, ['add_flight', 'retailer_list', 'retailer_menu', 'add_cart', 'checkout_cart', 'checkout_start', 'payment_add']);
	}

	foreach($userIds as $objectId => $lastSeenTimestamp) {

		$lastSeenTimestampFormatted = date("M-d G:i:s T", $lastSeenTimestamp);

	    $user = parseExecuteQuery(array("objectId" => $objectId, "isLocked" => false), "_User", "", "", [], 1);
	    
	    if(hasUserIdNotifiedForDeliveryUpTime($todayDate, $objectId)) {

			continue;
	    }

        if(hasUserOrderedBetweenTimes($user, $timestampMidnight, $deliveryUpTime, ['d'])) {

            continue;
        }

	    try {

	    	$message = getDeliveryUpNotificationMessage();
			sendDeliveryIsUpNotification($user, $message);
	    }
        // JMD
	    catch (Exception $ex) {

	    	$errors[$objectId] = ["customerName" => $user->get("firstName") . " " . $user->get("lastName"), "message" => $ex->getMessage(), "lastSeenTimestamp" => $lastSeenTimestamp, "lastSeenTimestampFormatted" => $lastSeenTimestampFormatted];
	    }

	    $usersNotified[$objectId] = ["customerName" => $user->get("firstName") . " " . $user->get("lastName"), "message" => $message, "lastSeenTimestamp" => $lastSeenTimestamp, "lastSeenTimestampFormatted" => $lastSeenTimestampFormatted];

	    logUserIdForDeliveryUpTimeNotification($todayDate, $objectId);
	}

    // Reset current timezone
    if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

        date_default_timezone_set($currentTimeZone);
    }

	return ["notified" => $usersNotified, "errors" => $errors];
}

function getDeliveryUpNotificationMessage() {

	$deliveryUpNotificationMessage = [
		"We are ready to deliver to you. Go ahead, order for speedy delivery! (from your friends at AtYourGate)",
		"Looking to get food or coffee delivered? We are ready to deliver! (from your friends at AtYourGate)"
	];

	return $deliveryUpNotificationMessage[mt_rand(0,count_like_php5($deliveryUpNotificationMessage)-1)];
}

function getDeliveryMissedUserIds($airportIataCode, $startTimestamp, $endTimestamp, $actions = ['add_cart', 'checkout_cart', 'checkout_start']) {
	
	$userIds = [];

	// JMD
	$actions = getEligibleUserActions($startTimestamp, $endTimestamp, $airportIataCode, $actions);

	foreach($actions as $action) {

		$userIds[$action["objectId"]] = $action["timestamp"];
	}

	return $userIds;
}

function sendDeliveryIsUpNotification($user, $message) {

    // Find latest session device for User
    $sessionDevice = getLatestSessionDevice($user);

    // Prepare message
    $messagePrepped = getRewardCustomMessage($message, $user->get('firstName'), $sessionDevice->get('timezoneFromUTCInSeconds'));

    // Get user's Phone Id
    $objUserPhone = parseExecuteQuery(array("user" => $user, "isActive" => true, "phoneVerified" => true), "UserPhones", "", "updatedAt", [], 1);

    // Send SMS notification
    if (count_like_php5($objUserPhone) > 0
        && $objUserPhone->get('SMSNotificationsEnabled') == true
        && ($objUserPhone->has('SMSNotificationsOptOut') && $objUserPhone->get('SMSNotificationsOptOut') == false)
    ) {

        try {

            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue->sendMessage(
                array("action" => "delivery_up_via_sms",
                    "content" =>
                        array(
                            "userPhoneId" => $objUserPhone->getObjectId(),
                            "message" => $messagePrepped
                        )
                )
            );
        } catch (Exception $ex) {

        	throw new Exception("SMS Delivery Up Notification failed! User = " . $user->getObjectId() . " - " . $ex->getMessage());
        }
    }

    // Fetch last known user device of user
    if($sessionDevice->has('userDevice')) {
    
        $userDevice = getLatestUserDevice($user);
    }
    else {

       	throw new Exception("Push Delivery Up Notification failed! No device found for User = " . $user->getObjectId());
    }

    // Send push notification
    list($oneSignalId, $isPushNotificationEnabled) = getPushNotificationInfo($userDevice);

    if (!empty($oneSignalId)
        && $isPushNotificationEnabled == true
    ) {

        // Send push notification via Queue
        try {

            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueuePushAndSmsConsumerName']);
            $workerQueue->sendMessage(
                array("action" => "delivery_up_via_push_notification",
                    "content" =>
                        array(
                            "userDeviceId" => $userDevice->getObjectId(),
                            "oneSignalId" => $userDevice->get('oneSignalId'),
                            "message" => ["title" => "Get Delivery Now!", "text" => $messagePrepped, "data" => ["deepLinkId" => 'delivery_available']]
                        )
                )
            );
        }
        catch (Exception $ex) {

	       	throw new Exception("Push Delivery Up Notification failed! User = " . $user->getObjectId() . " - " . $ex->getMessage());
        }
    }

    return "";
}

?>
