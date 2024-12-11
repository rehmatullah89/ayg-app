<?php

require_once 'dirpath.php';

require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Httpful\Request;

function statusFlightCheckWithNotifications($messageContent) {

	$flightId = $messageContent["flightId"];

	// Mark message completion
	saveFlightNotifyToCacheFlightEvent($flightId, 24*60*60, 'checkpoints_complete', $messageContent["typeOfSchedule"], time(), $messageContent["callbackId"]);

	// Fetch current object before status check
	$flightOld = getFlightInfoCache($flightId);

	// Verify if this flight is still in at least one user's trip
	if(isFlightNeededTobeTracked($flightId) == false) {

		// If no user has this flight in their trips anymore

		// Taking flight off schedule
		$lastKownArrivalTimestamp = 24*60*60;
		if(!empty($flightOld)) {

			$lastKownArrivalTimestamp = $flightOld->get('arrival')->getLastKnownTimestamp();
		}

		// Take the flight status checks off the message queue and mark it in the tracker log
		saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', 'off-schedule', time());

		// set a cache value indicating when we took this flight off the tracker schedule
		setFlightOffScheduleMarker($flightId, $lastKownArrivalTimestamp+24*60*60);

		return "";
	}

	$flightNew = statusFlightCheck($flightId, $messageContent["typeOfSchedule"]);

	if(is_array($flightNew)
		&& isset($flightNew["error_code"])) {

		// Return errors
		return $flightNew;
	}

	// If no old flight cache is available
	if(empty($flightOld)) {

		return "";
	}

	// Check for Flight change conditions to notify users
	$response = flightChangeNotifications($flightNew, $flightOld);

	if(!empty($response)) {

		return $response;
	}

	// Check for reminder notifications to notify users
	$response = flightReminderNotifications($flightNew);

	if(!empty($response)) {

		return $response;
	}

	return "";
}

function statusFlightCheckWithoutNotifications($messageContent) {

	$flightId = $messageContent["flightId"];

	// Mark message completion
	saveFlightNotifyToCacheFlightEvent($flightId, 24*60*60, 'checkpoints', 'flight_added', time());

	$flight = statusFlightCheck($flightId);

	if(is_array($flight)
		&& isset($flight["error_code"])) {

		// Return errors
		return $flight;
	}

	// Set markers
	// $response = flightReminderSetMarkers($flight);
	// if(!empty($response)) {

	// 	return $response;
	// }

	return "";
}

function isFlightNeededTobeTracked($flightId) {

	$flightsObject = new ParseQuery("Flights");
	$flightParseObject = parseSetupQueryParams(["uniqueId" => $flightId], $flightsObject);

	$flightTrips = parseExecuteQuery(["__MATCHESQUERY__flight" => $flightParseObject, "isActive" => true], "FlightTrips");

	// If users with flight are found
	if(count_like_php5($flightTrips) > 0) {

		return true;
	}

	return false;
}

function flightReminderNotifications($flight) {

	$reminderTypeSent = 'reminders-sent';

	// Only send notifications up to 5 hours before
	// Also don't send if the flight has already departed
	// Also don't send if the flight has already landed
	// Also don't send if a reminder was sent earlier
	if((time()-3*60*60) > $flight->get('departure')->getLastKnownTimestamp()
		|| checkFlightNotifyHasBeenSent($flight, 'reminders', $reminderTypeSent)
		|| $GLOBALS['flightStats_status'][$flight->get('info')->get('statusInterpreted')]["completed_status"] == true
		|| $GLOBALS['flightStats_status'][$flight->get('info')->get('statusInterpreted')]["departed_status"] == true) {

		return "";
	}

	$messageArray = [];
	$flightId = $flight->get('info')->get('uniqueId');

	//////////////////////////////////////////////////////////////////////////
	// Within 1 to 3 hours
	//////////////////////////////////////////////////////////////////////////
	// Check if departure time is between 1 and 3 hours before departure
	if(($flight->get('departure')->getLastKnownTimestamp()-3*60*60) <= time()
		&& ($flight->get('departure')->getLastKnownTimestamp()-1*60*60) > time()) {

		$reminderTypeLongDurations = 'long-duration-ready-airport';
		$reminderAllGood = 'all-good';
		$reminderTypePurchase = 'purchase';

		//////////////////////////////////////////////////////////////////////////
		// Long-duration flight
		//////////////////////////////////////////////////////////////////////////
		// Check this reminder was NOT already sent
		// Check if departure airport is from a ready airport
		// Not cancelled
		// Flight duration > 180 mins
		if($flight->get('departure')->isReadyAirport() == true
			&& $GLOBALS['flightStats_status'][$flight->get('info')->get('statusInterpreted')]["canceled_status"] == false
			&& !checkFlightNotifyHasBeenSent($flight, 'reminders', $reminderTypeLongDurations)
			&& $flight->get('info')->get('scheduledBlockMinutes') >= 180) {

			// Send notification
			$customMessage = "Your upcoming flight " . $flight->get('info')->getFlightDetails() . " to " . $flight->get('arrival')->getAirportInfo()["airportIataCode"] . " is longer than 3 hours. We will deliver to your gate so don't fly on a hungry stomach.";

			// Save that this notifcation was sent
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'reminders', $reminderTypeSent, '');

			// Save that this notifcation was sent
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'reminders', $reminderTypeLongDurations, $customMessage);
		}

		//////////////////////////////////////////////////////////////////////////
		// delivery purchase
		//////////////////////////////////////////////////////////////////////////
		// Airport Ready
		// Not cancelled
		else if($flight->get('departure')->isReadyAirport() == true
			// && $flight->get('departure')->getLastKnownTimestamp()-30*60) > time()
			&& $GLOBALS['flightStats_status'][$flight->get('info')->get('statusInterpreted')]["canceled_status"] == false
			&& !checkFlightNotifyHasBeenSent($flight, 'reminders', $reminderTypePurchase)) {

			// Send notification
			$customMessage = "You have an upcoming flight " . $flight->get('info')->getFlightDetails() . " to " . $flight->get('arrival')->getAirportInfo()["airportIataCode"] . ". Don't fly hungry! We will deliver to your gate.";

			// Save that this notifcation was sent
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'reminders', $reminderTypeSent, '');

			// Save that this notifcation was sent
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'reminders', $reminderTypePurchase, $customMessage);
		}

		//////////////////////////////////////////////////////////////////////////
		// All good remider
		//////////////////////////////////////////////////////////////////////////
		// No delays 
		// and Status is not canceled or of delayed type
		else if($flight->get('departure')->get('gateDelayMinutes') <= 0
			&& $GLOBALS['flightStats_status'][$flight->get('info')->get('statusInterpreted')]["departed_status"] == false
			&& $GLOBALS['flightStats_status'][$flight->get('info')->get('statusInterpreted')]["canceled_status"] == false
			&& !checkFlightNotifyHasBeenSent($flight, 'reminders', $reminderAllGood)) {

			// Send notification
			if(empty($flight->get('departure')->getGateDisplayName())) {
		
				$customMessage = "All good! Your upcoming flight " . $flight->get('info')->getFlightDetails() . " to " . $flight->get('arrival')->getAirportInfo()["airportIataCode"] . " is On Time";
			}
			else {

				$customMessage = "All good! Your upcoming flight " . $flight->get('info')->getFlightDetails() . " to " . $flight->get('arrival')->getAirportInfo()["airportIataCode"] . " is On Time and will depart from " . $flight->get('departure')->getGateDisplayName();
			}

			// Save that this notifcation was sent
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'reminders', $reminderTypeSent, '');

			// Save that this notifcation was sent
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'reminders', $reminderAllGood, $customMessage);
		}

		//////////////////////////////////////////////////////////////////////////
		// Regular Departure reminder
		//////////////////////////////////////////////////////////////////////////
		else {

			// To do in future, coordinate with delay notifications to ensure they don't overlap
		}
	}	
	//////////////////////////////////////////////////////////////////////////


	if(!empty($customMessage)) {

		$messageArray[] = $customMessage;
	}
	//////////////////////////////////////////////////////////////////////////

	return putOnQueueFlightNotifications($flightId, $messageArray);
}

function flightChangeNotifications($flightNew, $flightOld) {

	// Only send notifications up to 3 days before
	// And until the flight has not yet departed or not yet landed
	if((time()-3*24*60*60) > $flightNew->get('departure')->getLastKnownTimestamp()
		|| $GLOBALS['flightStats_status'][$flightNew->get('info')->get('statusInterpreted')]["completed_status"] == true
		|| $GLOBALS['flightStats_status'][$flightNew->get('info')->get('statusInterpreted')]["departed_status"] == true) {

		return "";
	}

	$messageArray = [];
	$flightId = $flightNew->get('info')->get('uniqueId');
	$lastKownArrivalTimestamp = $flightNew->get('arrival')->getLastKnownTimestamp();


	//////////////////////////////////////////////////////////////////////////
	// Capture Status changes first
	//////////////////////////////////////////////////////////////////////////

	$hasStatusChangedArray = flightComp_hasStatusChanged($flightNew, $flightOld);


	//////////////////////////////////////////////////////////////////////////
	// Time changes
	//////////////////////////////////////////////////////////////////////////

	$customMessage = "";
	$hasTimestampChangedArray_Departure = flightComp_hasTimestampChanged('departure', $flightNew, $flightOld);
	$hasTimestampChangedArray_Arrival = flightComp_hasTimestampChanged('arrival', $flightNew, $flightOld);

	$alertForStatusChangeSent = false;
	// Departure timestamp changes
	if($hasTimestampChangedArray_Departure["hasChanged"]) {

		// Was the status also changed
		// And the new status of delayed
		// And the status is inform_user type
		if($hasStatusChangedArray["hasChanged"] == true
			&& ($GLOBALS['flightStats_status'][$hasStatusChangedArray["statusInterpreted"]]["delayed_status"] == true
				&& $flightNew->get("departure")->get("gateDelayMinutes") > 0)) {

			// Delay is MORE than last known
			if($hasTimestampChangedArray_Departure["diffInMins"] > 0) {

				$customMessage = "Delay alert: Your flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " has been delayed by " . $flightNew->get("departure")->get("gateDelayMinutes") . " mins, with a new departure time of " . $flightNew->get("departure")->getLastKnownTimestampFormatted();
			}
			// Delay is LESS than last known
			else {

				$customMessage = "Departure alert: New departure time for flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " is " . $flightNew->get("departure")->getLastKnownTimestampFormatted();
			}

			// Ensure no status alerts go out since we sent a combined alert
			$alertForStatusChangeSent = true;
		}
		// Standard departure timestamp change
		else {

			$customMessage = "Departure alert: New departure time for flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " is " . $flightNew->get("departure")->getLastKnownTimestampFormatted();

			// TEMP
			// Ensure no status alerts go out since we sent a combined alert
			// $alertForStatusChangeSent = true;
		}

		saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'departureTime', $customMessage);
	}

	// Arrival timestamp changes
	/*
	else if($hasTimestampChangedArray_Arrival["hasChanged"]) {

		$customMessage = "Flight alert: New arrival time for flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('departure')->getAirportInfo()["airportIataCode"] . " is " . $flightNew->get("arrival")->getLastKnownTimestampFormatted();

		saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'arrivalTime', $customMessage);
	}
	*/

	if(!empty($customMessage)) {

		$messageArray[] = $customMessage;
	}
	//////////////////////////////////////////////////////////////////////////


	//////////////////////////////////////////////////////////////////////////
	// Gate changes
	//////////////////////////////////////////////////////////////////////////
	$customMessage = "";
	$hasGateChangedArray_Departure = flightComp_hasGateChanged('departure', $flightNew, $flightOld);
	$hasGateChangedArray_Arrival = flightComp_hasGateChanged('arrival', $flightNew, $flightOld);

	// Departure Gate changes
	if($hasGateChangedArray_Departure["hasChanged"]) {

		if($hasGateChangedArray_Departure["hasGatePosted"] == true) {

			$customMessage = "The departure gate for your flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " has been posted as " . $hasGateChangedArray_Departure["new"]["gateDisplayName"];

			if($flightNew->get('departure')->getAirportInfo()["airportIsReady"] == true) {

				flight_gate_change_order_impact($flightId, "Gate has been posted as " . $hasGateChangedArray_Departure["new"]["gateDisplayName"]);
			}

			saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'departureGate', $customMessage);
		}
		else {

			$customMessage = "Gate alert: The departure gate for flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " has changed from " . $hasGateChangedArray_Departure["old"]["gateDisplayName"] . " to " . $hasGateChangedArray_Departure["new"]["gateDisplayName"];

			if($flightNew->get('departure')->getAirportInfo()["airportIsReady"] == true) {

				flight_gate_change_order_impact($flightId, "Gate has changed from " . $hasGateChangedArray_Departure["old"]["gateDisplayName"] . " to " . $hasGateChangedArray_Departure["new"]["gateDisplayName"]);
			}

			saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'departureGate', $customMessage);
		}

		if(!empty($customMessage)) {

			$messageArray[] = $customMessage;
		}
	}

	// Arrival Gate changes
	else if($hasGateChangedArray_Arrival["hasChanged"]) {

		if($hasGateChangedArray_Arrival["hasGatePosted"] == true) {

			$customMessage = "Your arrival gate for flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " has been posted as " . $hasGateChangedArray_Arrival["new"]["gateDisplayName"];

			if($flightNew->get('arrival')->getAirportInfo()["airportIsReady"] == true) {

				flight_gate_change_order_impact($flightId, "Gate has been posted as " . $hasGateChangedArray_Arrival["new"]["gateDisplayName"]);
			}

			saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'arrivalGate', $customMessage);
		}
		else {

			$customMessage = "Gate alert: The arrival gate for flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " has changed from " . $hasGateChangedArray_Arrival["old"]["gateDisplayName"] . " to " . $hasGateChangedArray_Arrival["new"]["gateDisplayName"];

			if($flightNew->get('arrival')->getAirportInfo()["airportIsReady"] == true) {

				flight_gate_change_order_impact($flightId, "Gate has changed from " . $hasGateChangedArray_Arrival["old"]["gateDisplayName"] . " to " . $hasGateChangedArray_Arrival["new"]["gateDisplayName"]);
			}

			saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'arrivalGate', $customMessage);
		}

		if(!empty($customMessage)) {

			$messageArray[] = $customMessage;
		}
	}
	//////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////
	// Send Status changes
	//////////////////////////////////////////////////////////////////////////
	$hasStatusChangedArray = flightComp_hasStatusChanged($flightNew, $flightOld);

	$customMessage = "";
	// Status changes for inform user statuses only
	if($hasStatusChangedArray["hasChanged"] == true) {

		// Is the new status of type canceled?
		// Ensure no other canceled status was sent earlier
		if($GLOBALS['flightStats_status'][$hasStatusChangedArray["statusInterpreted"]]["canceled_status"] == true
			&& !checkFlightNotifyHasBeenSent($flightNew, 'reminders', 'status-canceled')) {

			$customMessage = "Status alert: We are sorry but your flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " has been canceled. More details can be found by calling the airline.";

			saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'status-canceled', $customMessage);
		}

		// Is the new status of inform_user type
		// Ensure status alert in this run wasn't already sent
		else if($GLOBALS['flightStats_status'][$hasStatusChangedArray["statusInterpreted"]]["inform_user"] == true
			&& $alertForStatusChangeSent == false) {

			$customMessage = "Status alert: The status for your flight " . $flightNew->get('info')->getFlightDetails() . " to " . $flightNew->get('arrival')->getAirportInfo()["airportIataCode"] . " has changed to " . $GLOBALS['flightStats_status'][$hasStatusChangedArray["statusInterpreted"]]["desc"];

			saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'notifications', 'status', $customMessage);
		}
	}

	if(!empty($customMessage)) {

		$messageArray[] = $customMessage;
	}
	//////////////////////////////////////////////////////////////////////////

	return putOnQueueFlightNotifications($flightId, $messageArray);
}

function putOnQueueFlightNotifications($flightId, $messageArray) {

	if(count_like_php5($messageArray) == 0) {

		return "";
	}

	// Find the Flight object
	$flightsObject = new ParseQuery("Flights");
	$flightParseObject = parseSetupQueryParams(["uniqueId" => $flightId], $flightsObject);

	$flightTrips = parseExecuteQuery(["__MATCHESQUERY__flight" => $flightParseObject, "isActive" => true], "FlightTrips", "", "", ["user", "userTrip"]);

	// If users with flight are found
	if(count_like_php5($flightTrips) > 0) {

		$receipients = [];
		foreach($flightTrips as $trip) {

			$receipients[$trip->get('user')->getObjectId()]["user"] = $trip->get('user');
			$receipients[$trip->get('user')->getObjectId()]["tripId"] = $trip->get('userTrip')->getObjectId();
		}

		foreach($receipients as $userObjectId => $data) {

			// Send Push notifications to all session devices
			$response = putOnQueueFlightNotificationsPush($data["user"], $messageArray, $flightId, $data["tripId"]);

			if(!empty($response)) {

				return $response;
			}

			// Send SMS / User Phone
			$response = putOnQueueFlightNotificationsSMS($data["user"], $messageArray, $flightId);

			if(!empty($response)) {

				return $response;
			}
		}
	}

	return "";
}

function putOnQueueFlightNotificationsSMS($user, $messageArray, $flightId) {

	// $GLOBALS['sqs_client'] = getSQSClientObject();

	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueuePushAndSmsConsumerName']);
	}
	catch (Exception $ex) {

		return json_decode($ex->getMessage(), true);
	}

	// Find User Phone
	$userPhone = parseExecuteQuery(["user" => $user, "phoneVerified" => true, "isActive" => true], "UserPhones", "", "updatedAt", [], 1);

	// SMS Notification
	if(count_like_php5($userPhone) > 0) {

		foreach($messageArray as $message) {

			// Send push notification via Queue
			try {

				$workerQueue->sendMessage(
						array("action" => "flight_notify_via_sms", 
							  "content" => 
							  	array(
							  		"userPhoneId" => $userPhone->getObjectId(),
							  		"message" => prepareFlightSMSMessage($message)
					  			)
							)
						);
			}
			catch (Exception $ex) {

				return json_decode($ex->getMessage(), true);
			}
		}
	}
	else {

		// Log Flight schedule status not sent because phone not found
		json_error("AS_3005", "", "Flight Id ($flightId) update via SMS was not sent for " . $user->getObjectId() . " - " . json_encode($message), 3, 1);
	}

	return "";
}

function putOnQueueFlightNotificationsPush($user, $messageArray, $flightId, $tripId) {

	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueuePushAndSmsConsumerName']);
	}
	catch (Exception $ex) {

		return json_decode($ex->getMessage(), true);
	}

	// Find the latest Session Devices
	$sessionDevice = parseExecuteQuery(["user" => $user, "isActive" => true], "SessionDevices", "", "createdAt", ["userDevice"], 1);

	// Push Notification
	if(count_like_php5($sessionDevice) > 0) {

		$isPushNotificationEnabled = $sessionDevice->get('userDevice')->get('isPushNotificationEnabled');
		$oneSignalId = $sessionDevice->get('userDevice')->get('oneSignalId');

		// If flag is set to true, then send push notification
		if($isPushNotificationEnabled == true
			&& !empty($oneSignalId)) {

			foreach($messageArray as $message) {

				// Send push notification via Queue
				try {

					$workerQueue->sendMessage(
							array("action" => "flight_notify_via_push_notification", 
								  "content" => 
								  	array(
								  		"userDeviceId" => $sessionDevice->get('userDevice')->getObjectId(),
								  		"oneSignalId" => $sessionDevice->get('userDevice')->get('oneSignalId'),
								  		"message" => ["text" => $message, "title" => "Flight Update", "id" => $tripId . "-" . $flightId, "data" => ["flightId" => $flightId, "tripId" => $tripId]]
						  			)
								)
							);
				}
				catch (Exception $ex) {

					return json_decode($ex->getMessage(), true);
				}
			}
		}
	}

	return "";
}

/*
function getChangedGateInfo($hasGateChangedArray, $oldOrNew) {

	if(!isset($hasGateChangedArray[$oldOrNew]["type"])) {

		if(!isset($oldNew)) {

			$oldNew = '';
		}

		json_error("AS_WWW", "", json_encode($hasGateChangedArray) . " - " . $oldNew . " - " . getBackTrace(), 1, 1);
		return "";
	}

	if(strcasecmp($hasGateChangedArray[$oldOrNew]["type"], "loc")==0) {

		return $hasGateChangedArray[$oldOrNew]["location"]->get('gateDisplayName');
	}
	else {

		return 'Gate ' . $hasGateChangedArray[$oldOrNew]["location"]["terminalConcourse"] . $hasGateChangedArray[$oldOrNew]["location"]["gate"];
	}
}
*/

function flightComp_getGateNamesToDisplay($flightSide, $flightNew, $flightOld, $hasGatePosted) {

	// Check if Terminal and Concourse have also changed, if use pre-constructed display name
	// Or the gate was just posted
	if(strcasecmp(($flightNew->get($flightSide)->getTerminal() . $flightNew->get($flightSide)->getConcourse()), ($flightOld->get($flightSide)->getTerminal() . $flightOld->get($flightSide)->getConcourse())) != 0
		|| $hasGatePosted == true) {

		$oldGateDisplayName = $flightOld->get($flightSide)->getGateDisplayName();
		$newGateDisplayName = $flightNew->get($flightSide)->getGateDisplayName();
	}
	// Else, construct a short one here for New Gate
	else {

		$oldGateDisplayName = $flightOld->get($flightSide)->getGateDisplayName();

		// This ensures that the notifcation text sent WON'T say message like:
		// "Your gate has changed from Gate 20 in Terminal C to Gate 21 in Terminal C" ... instead it will say
		// "Your gate has changed from Gate 20 in Terminal C to Gate 21"
		// $newGateDisplayName = $flightNew->get($flightSide)->getGate();

		$newGateDisplayName = $flightNew->get($flightSide)->getGateDisplayName();
	}

	return [$oldGateDisplayName, $newGateDisplayName];
}

function flightComp_hasGateChanged($flightSide, $flightNew, $flightOld) {

	// If the Airport is Ready
	if($flightNew->get($flightSide)->isReadyAirport() == true) {

		$newObjectId = "";
		$oldObjectId = "";

		// If gate location of NEW flight is available
		if(!is_null($flightNew->get($flightSide)->getTerminalGateMapLocation(true))) {

			$newObjectId = $flightNew->get($flightSide)->getTerminalGateMapLocation(true)->getObjectId();
		}

		// If gate location of OLD flight is available
		if(!is_null($flightOld->get($flightSide)->getTerminalGateMapLocation(true))) {

			$oldObjectId = $flightOld->get($flightSide)->getTerminalGateMapLocation(true)->getObjectId();
		}

		$hasGatePosted = false;
		// Old flight had estimated gate, but New flight has posted gate
		// Or, old flight gate info was empty, but new isn't
		if(
			($flightOld->get($flightSide)->get("isGateInfoEstimated") == true
			&& $flightNew->get($flightSide)->get("isGateInfoEstimated") == false)
			|| (empty(trim($flightOld->get($flightSide)->getGateDisplayName()))
				&& !empty(trim($flightNew->get($flightSide)->getGateDisplayName())))
				) {

			$hasGatePosted = true;
		}

		// Is Gate location diff
		if(strcasecmp($newObjectId, $oldObjectId)!=0) {

			// json_error("AS_WWW", "", json_encode(serialize($flightOld)) . " - " . json_encode(serialize($flightNew)), 1, 1);

			list($oldGateDisplayName, $newGateDisplayName) = flightComp_getGateNamesToDisplay($flightSide, $flightNew, $flightOld, $hasGatePosted);

			return ["hasChanged" => true, 
					"new" => ["gateDisplayName" => $newGateDisplayName],
					"old" => ["gateDisplayName" => $oldGateDisplayName],
					"hasGatePosted" => $hasGatePosted,
					"type" => 'loc'];
		}
	}
	else {

		$gateArrayNew = $flightNew->get($flightSide)->getExtTerminalGateMapLocation(true);
		$gateArrayOld = $flightOld->get($flightSide)->getExtTerminalGateMapLocation(true);

		$hasGatePosted = false;
		// Old flight had estimated gate, but New flight has posted gate
		// Or, old flight gate info was empty, but new isn't
		if(
			($flightOld->get($flightSide)->get("isGateInfoEstimated") == true
			&& $flightNew->get($flightSide)->get("isGateInfoEstimated") == false)
			|| (empty(trim($flightOld->get($flightSide)->getGateDisplayName()))
				&& !empty(trim($flightNew->get($flightSide)->getGateDisplayName())))
				) {

			$hasGatePosted = true;
		}

		// If external gate info is difference
		if(strcasecmp(json_encode($flightNew->get($flightSide)->getExtTerminalGateMapLocation(true)), 
						json_encode($flightOld->get($flightSide)->getExtTerminalGateMapLocation(true))) != 0) {

			list($oldGateDisplayName, $newGateDisplayName) = flightComp_getGateNamesToDisplay($flightSide, $flightNew, $flightOld, $hasGatePosted);

			return ["hasChanged" => true, 
					"new" => ["gateDisplayName" => $newGateDisplayName],
					"old" => ["gateDisplayName" => $oldGateDisplayName],
					"hasGatePosted" => $hasGatePosted,
					"type" => 'ext'];
		}
	}

	return ["hasChanged" => false, "new" => ["gateDisplayName" => ''], "old" => ["gateDisplayName" => ''], "hasGatePosted" => false, "type" => ''];
}

function flightComp_hasTimestampChanged($flightSide, $flightNew, $flightOld) {

	// Diff in lastKnownTimestamps is greater than 1 minute
	$diffInMins = round(($flightNew->get($flightSide)->getLastKnownTimestamp() - $flightOld->get($flightSide)->getLastKnownTimestamp()) / 60);

	// Timestamps have changed
	if($flightNew->get($flightSide)->getLastKnownTimestamp() != $flightOld->get($flightSide)->getLastKnownTimestamp()
		&& abs($diffInMins) > 0) {

		return ["hasChanged" => true, "diffInMins" => $diffInMins];
	}

	return ["hasChanged" => false, "diffInMins" => 0];
}

function flightComp_hasStatusChanged($flightNew, $flightOld) {

	// Status have changed
	if(strcasecmp($flightNew->get('info')->get('statusInterpreted'), $flightOld->get('info')->get('statusInterpreted'))!=0) {

		return ["hasChanged" => true, "statusInterpreted" => $flightNew->get('info')->get('statusInterpreted')];
	}

	return ["hasChanged" => false, "statusInterpreted" => $flightNew->get('info')->get('statusInterpreted')];
}

function statusFlightCheck($flightId, $typeOfSchedule="") {

	// Get flight info from cache
	$flight = getFlightInfoCache($flightId);

	if(empty($flight)) {

		$flight = updateStatusOfFlight($flightId, false, false, "", true);

		if(is_array($flight)
			&& isset($flight["error_code"])) {

			// Return errors
			return $flight;
		}
	}

	else {

		// Identify with API will be called
		$apiCallType = identifyWhichFlightStatsMethodToCall(
								$flight->get('info')->get('airlineIataCode'), 
								$flight->get('info')->get('flightNum'), 
								$flight->get('info')->get('flightYear'), 
								$flight->get('info')->get('flightMonth'), 
								$flight->get('info')->get('flightDate'), 
								$flight->get('info')->get('extFlightId')
						);

		// Check if this flight API call will be for status check (no schedule calls)
		if(preg_match("/^status/si", $apiCallType)) {

			// Add to cache that the API call
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'api-call', $apiCallType, time());

			// Update Contents of the flight
			$flight = updateStatusOfFlight($flightId, true, false, $flight);

			if(is_array($flight)
				&& isset($flight["error_code"])) {

				// Return errors
				return $flight;
			}
		}
	}

	// If we can't find flight, gracefully exit
	if(empty($flight)
		|| count_like_php5($flight) == 0) {

		// Flight was possibly taken offschedule by flight stats
		json_error("AS_WWW", "", "Flight Id ($flightId) Not found during queue processing!!!", 1, 1);
		// return json_error_return_array("AS_201", "", "Flight not found!", 1);
	}

	// If gate info is not available
	// And if departure timestamp - 7 days must be less than time(), meaning flight is not departing beyond 7 days of today (aka, there is a potential of another flight that has already departed in 5 days before departure timestamp)
	// And departure timestamp - 24 hours must be greater than time(), i.e. not in last 24 hours, don't check for historic if we don't already have it
	// But if historic check was already done in past, then skip
	if((!isLocationInfoAvailable($flight, 'departure') || !isLocationInfoAvailable($flight, 'arrival'))
		// && !checkFlightNotifyHasBeenSent($flight, 'validations', 'historic')
		&& (time()<$flight->get('departure')->getLastKnownTimestamp()-1*24*60*60)
		&& (time()>$flight->get('departure')->getLastKnownTimestamp()-7*24*60*60)) {

		// Find interim gate info from previous flights
		list($flight, $historicFound) = findhistoricGateInfo($flightId, $flight);

		// Add to cache that the historic check was done
		saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'validations', 'historic', $historicFound);
	}

	// Schedule future status checks
	$response = scheduleFlightStatusCheck($flight);

	if(is_array($response)) {

		return $response;
	}

	// Return updated flight object
	return $flight;
}

/*
function statusFlightMarkerNotification($messageContent) {

	$flightId = $messageContent["flightId"];

	// Mark message completion
	saveFlightNotifyToCacheFlightEvent($flightId, 24*60*60, 'checkpoints_complete', $messageContent["typeOfSchedule"], time(), $messageContent["callbackId"]);

	// Update Contents of the flight
	$flight = updateStatusOfFlight($flightId, true);

	if(is_array($flight)
		&& isset($flight["error_code"])) {

		// Return errors
		return $flight;
	}

	// Send marker messages
	

	return "";
}
*/

function findhistoricGateInfo($flightId, $flight) {

	$historicFound = 'not-found';

	// Get flightDate from the object
	// Identify flight departure information from flight unique id
	list($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate) = parseFlightInfoFromUniqueId($flightId);

	$flightHistoricCounter = 0;

	// Try to find 7 past available flights
	while($flightHistoricCounter < 7) {

		$flightHistoricCounter++;

		$historicLocationFound = false;
		$timestamp = mktime(0, 0, 0, $flightMonth, $flightDate-$flightHistoricCounter, $flightYear);

		// Get new dates
		$newFlightYear = date("Y", $timestamp);
		$newFlightMonth = date("n", $timestamp);
		$newFlightDate = date("j", $timestamp);

		// Generate new flight id
		$newFlightId = generateFlightUniqueId($flight->get('departure')->get('airportIataCode'), $flight->get('arrival')->get('airportIataCode'), $airlineIataCode, $flightNumber, $newFlightYear, $newFlightMonth, $newFlightDate);

		// Check database
		$flightFind = parseExecuteQuery(array("uniqueId" => $newFlightId), "Flights");

		// flight found
		if(count_like_php5($flightFind) > 0) {

			$flightHistoric = $flightFind[0];

			$departureAirport = getAirportByIataCode($flightHistoric->get('departureAirportIataCode'));
			$arrivalAirport = getAirportByIataCode($flightHistoric->get('arrivalAirportIataCode'));

			$historicLocationFoundDeparture = setHistoricGateLocationFromDb($departureAirport, $flightHistoric, $flight, 'departure');
			$historicLocationFoundArrival = setHistoricGateLocationFromDb($arrivalAirport, $flightHistoric, $flight, 'arrival');
		}
		else {

			$apiCallType = identifyWhichFlightStatsMethodToCall($airlineIataCode, $flightNumber, $newFlightYear, $newFlightMonth, $newFlightDate, "");

			// Check if this flight API call will be for status check (no schedule calls)
			if(!preg_match("/^status/si", $apiCallType)) {

				continue;
			}

			// Add to cache that the API call
			saveFlightNotifyToCacheFlightEvent($flightId, $flight->get('arrival')->getLastKnownTimestamp(), 'api-call', 'historic', $newFlightId);

			// fetch information again
			$flightFind = fetchFlights($airlineIataCode, $flightNumber, $newFlightYear, $newFlightMonth, $newFlightDate, $newFlightId);

			if(isset($flightFind["error_code"])
				|| count_like_php5($flightFind) == 0) {

				continue;
			}

			updateFlightDetails($flightFind[0]);

			$flightHistoric = $flightFind[0];

			$historicLocationFoundDeparture = setHistoricGateLocationFromCache($flightHistoric, $flight, 'departure');
			$historicLocationFoundArrival = setHistoricGateLocationFromCache($flightHistoric, $flight, 'arrival');
		}

		if($historicLocationFoundDeparture
				|| $historicLocationFoundArrival) {

			// cache it
			$historicFound = $newFlightId;

			setFlightInfoCache($flightId, $flight, ($flight->get('arrival')->getLastKnownTimestamp()-time())+(7*24*60*60));

			break;
		}
	}

	return [$flight, $historicFound];
}

function scheduleFlightStatusCheck($flight) {

	$flightId = $flight->get('info')->get('uniqueId');
	$lastKownDepartureTimestamp = $flight->get('departure')->getLastKnownTimestamp();
	$lastKownArrivalTimestamp = $flight->get('arrival')->getLastKnownTimestamp();
	$statusInterpreted = $flight->get('info')->get('statusInterpreted');

	$oneAndHalfHoursBefore = getTimestampBeforeHours($lastKownDepartureTimestamp, 1.5);
	$threeHoursBefore = getTimestampBeforeHours($lastKownDepartureTimestamp, 3);
	$fiveHoursBefore = getTimestampBeforeHours($lastKownDepartureTimestamp, 5);
	$oneDayBefore = getTimestampBeforeHours($lastKownDepartureTimestamp, 24);
	$threeDaysBefore = getTimestampBeforeHours($lastKownDepartureTimestamp, 24*3);
	$sevenDaysBefore = getTimestampBeforeHours($lastKownDepartureTimestamp, 24*7);

	try {

        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
	}
	catch (Exception $ex) {

		return json_decode($ex->getMessage(), true);
	}

	// Flight has arrived
	if((time()>=$lastKownArrivalTimestamp
		// && $GLOBALS['flightStats_status'][$statusInterpreted]['completed_status']==true
		)) {

		// Stop the scheduling process
		$processAfterTimestamp = 0;

		$typeOfSchedule = 'landed';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => time(), "displayTime" => date("Y-m-d H:i:s T", time()), "delay" => 0, "completed" => time()]);

		// Save flight object in the database
		updateFlightInDB($flight, $flightId, true);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Arrived, no more checks performed", 3, 1);
	}

	// Flight has departed
	// Time > lastKnownDepartureTimestamp
	else if(time()>=$lastKownDepartureTimestamp) {

		// If ActualTimestamp has NOT been posted
		// or Status is NOT departed
		// Then it means there is a delay that weren't reported yet in our estimatedGateTimestamp
		// So check every mins
		// And its NOT been 45 mins after expected departure
		if((empty($flight->get('departure')->get('actualGateTimestamp'))
			|| $GLOBALS['flightStats_status'][$statusInterpreted]['departed_status']==false)
			&& (time()<$lastKownDepartureTimestamp+45*60)) {

			// Every minute
			$typeOfSchedule = 'departed-awaiting-delayinfo';
			$processAfterTimestamp = time() + 1*60;
		}

		// If this triggered that means the first check (flight has arrived) didn't occur
		// Hence that arrival time should have been updated (i.e. delays) but hasn't been yet
		// So check 5 mins later
		/*
		else if(time()>$lastKownArrivalTimestamp) {

			// Check again in 5 mins
			$typeOfSchedule = 'departed-awaiting-arrivalinfo';
			$processAfterTimestamp = time() + 5*60;
		}
		*/
	
		// If flight just departed, check at arrival timestamp
		else {

			// Check after Arrival timestamp + 2 mins (buffer)
			$typeOfSchedule = 'departed-check-after-arrival';
			$processAfterTimestamp = $lastKownArrivalTimestamp + 2*60;
		}

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Departed, $typeOfSchedule - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}

	// Less than 90 mins before departure
	// Every 5 minutes
	else if(time()>=$oneAndHalfHoursBefore) {

		$processAfterTimestamp = time()+5*60;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'oneAndHalfHoursBefore';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Less than 1.5 hrs, Every 5 mins - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}
	// Less than 3 hours
	// Every 15 minutes
	else if(time()>$threeHoursBefore) {

		$processAfterTimestamp = time()+15*60;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'threeHoursBefore';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Less than 3, Every 15 - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}
	// Within 15 mins of three hours before departure
	// At 3 hour mark
	else if(time()>$threeHoursBefore+15*60) {

		$processAfterTimestamp = $threeHoursBefore;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'threeHoursMarker';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Within 15 of 3 hours, At 3 hours mark - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}
	// Less than 5 hours before departure
	// Every 30 minutes
	else if(time()>$fiveHoursBefore) {

		$processAfterTimestamp = time()+30*60;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'fiveHoursBefore';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Less than 6, Every 30 - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}
	// Less than 1 day before departure
	// Every 6 hours
	else if(time()>$oneDayBefore) {

		$processAfterTimestamp = time()+6*60*60;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'oneDayBefore';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Less than 1 day, Every 6 hour - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}
	// Less than 3 days before departure
	// Every 24 hours
	else if(time()>$threeDaysBefore) {

		$processAfterTimestamp = time()+24*60*60;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'threeDaysBefore';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Less than 3 days, Every 24 hours - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}
	// Less than 7 days before departure
	// Every 48 hours
	else if(time()>$sevenDaysBefore) {

		$processAfterTimestamp = time()+48*60*60;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'sevenDaysBefore';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Less than 7 days, Every 48 hour - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}
	// Else
	// Set it for 7 days before departure
	else {

		$processAfterTimestamp = $lastKownDepartureTimestamp-7*24*60*60;

		$getWaitTimeForQueue = $workerQueue->getWaitTimeForDelay($processAfterTimestamp);

		$typeOfSchedule = 'outsideSevenDays';
		$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, 'checkpoints', $typeOfSchedule, ["scheduled" => $processAfterTimestamp, "displayTime" => date("Y-m-d H:i:s T", $processAfterTimestamp), "delay" => $getWaitTimeForQueue, "completed" => ""]);

		// Log Flight schedule status check
		json_error("AS_3004", "", "Flight Id ($flightId) Greater than 7 days, Check one week before departure - ProcessAfter: " . $processAfterTimestamp, 3, 1);
	}

	// If a process timestamp is calculated
	if($processAfterTimestamp != 0) {

		// Log Flight schedule status check
		// json_error("AS_3004_temp", "", "Flight Id ($flightId) Status Queue being created", 3, 1);
		return putStatusCheckMessageOnQueue($flightId, $processAfterTimestamp, $typeOfSchedule, $callbackId, $getWaitTimeForQueue);
	}

	return "";
}

function getTimestampBeforeHours($timestamp, $hoursBefore) {

	return $timestamp-($hoursBefore*60*60);
}

function isLocationInfoAvailable($flight, $flightSide) {

	// If the Airport is Ready
	if($flight->get($flightSide)->isReadyAirport() == true) {

		// If gate location is available
		if(is_object($flight->get($flightSide)->getTerminalGateMapLocation(true))) {

			return true;
		}
		else {

			return false;
		}		
	}
	else {

		// If gate location is available
		if(!empty($flight->get($flightSide)->getExtTerminalGateMapLocation(true)['terminalConcourse'])
			&& !empty($flight->get($flightSide)->getExtTerminalGateMapLocation(true)['gate'])
			) {

			return true;
		}
		else {

			return false;
		}
	}
}

function setHistoricGateLocationFromCache($flightHistoric, &$flight, $flightSide) {

	$historicLocationFound = false;

	if($flightHistoric->get($flightSide)->isReadyAirport() == true) {

		// If gate location is available
		if(is_object($flightHistoric->get($flightSide)->getTerminalGateMapLocation())) {

			$historicLocationFound = true;
			$flight->get($flightSide)->setTerminalGateHistoricLocation($flightHistoric->get($flightSide)->getTerminalGateMapLocation(true));
		}
	}
	else {

		// If gate location is available
		if(!empty($flightHistoric->get($flightSide)->getExtTerminalGateMapLocation(true)['terminalConcourse'])
			&& !empty($flightHistoric->get($flightSide)->getExtTerminalGateMapLocation(true)['gate'])
			) {

			$historicLocationFound = true;
			$flight->get($flightSide)->setTerminalGateHistoricExternal(
					json_encode($flightHistoric->get($flightSide)->getExtTerminalGateMapLocation(true))
				);
		}
	}

	return $historicLocationFound;
}

function setHistoricGateLocationFromDb($airport, $flightHistoric, &$flight, $flightSide) {

	$historicLocationFound = false;
	$flightSideInProperCase = ucfirst(strtolower($flightSide));

	// If Airport isReady
	if(count_like_php5($airport) > 0
		&& strcasecmp($airport->get('isReady'), true)==0) {

		if(!empty($flightHistoric->get('lastKnown' . $flightSideInProperCase . 'Location'))) {

			$historicLocationFound = true;
			$flight->get($flightSide)->setTerminalGateHistoricLocation($flightHistoric->get('lastKnown' . $flightSideInProperCase . 'Location'));
		}
	}
	else {

		if(!empty($flightHistoric->get('ext' . $flightSideInProperCase . 'LocationInfo'))) {
	
			$historicLocationFound = true;
			$flight->get($flightSide)->setTerminalGateHistoricExternal(
					$flightHistoric->get('ext' . $flightSideInProperCase . 'LocationInfo')
				);
		}
	}

	return $historicLocationFound;
}

function checkFlightNotifyHasBeenSent($flight, $type, $reminderType) {

	$flightNotifyTracker = getFlightNotifyTrackerCache($flight->get('info')->get('uniqueId'));

	if(is_array($flightNotifyTracker)
		&& isset($flightNotifyTracker[$type][$reminderType])) {

		return true;
	}

	return false;
}

function logFlightAPICalls($messageContent) {

	if(!isset($messageContent["userObjectId"])
		|| empty($messageContent["userObjectId"])) {

		$user = null;
	}
	else {

		$user = parseExecuteQuery(["objectId" => $messageContent["userObjectId"]], "_User", "", "", [], 1);

		// If user is not found, attribute to a null user
		if(count_like_php5($user) == 0) {

			$messageContent['backtrace'] .= ' - Original user (not found): ' . $messageContent["userObjectId"];
			$user = null;
		}
	}

	$logFlightAPI = new ParseObject("zLogFlightAPI");
	$logFlightAPI->set('callType', $messageContent['callType']);
	$logFlightAPI->set('callUrl', $messageContent['callUrl']);
	$logFlightAPI->set('isWorker', $messageContent['isWorker'] == 1 ? true : false);
	$logFlightAPI->set('backtrace', $messageContent['backtrace']);
	$logFlightAPI->set('user', $user);
	$logFlightAPI->set('callTimestamp', $messageContent['callTimestamp']);
	$logFlightAPI->save();

	return "";
}

function prepareFlightSMSMessage($message) {

	return $message . " (from your friends at AtYourGate)";
}

function gateChangeOrderImpactAnalysis($flightId, $typeOfChange) {

	try {

        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueFlightConsumerName']);
		$workerQueue->sendMessage(
				array("action" => "flight_gate_change_order_impact", 
					  "content" => 
					  	array(
					  		"flightId" => $flightId,
					  		"typeOfChange" => $typeOfChange
			  			)
					)
				);
	}
	catch (Exception $ex) {

		return json_decode($ex->getMessage(), true);
	}
}

function flight_gate_change_order_impact($flightId, $typeOfChange) {

	$flight = getFlightInfoCache($flightId);

	// Find flight object
	$flightObject = parseExecuteQuery(["uniqueId" => $flightId], "Flights", "", "", [], 1);

	// Find users with flight
	$flightTrips = parseExecuteQuery(["flight" => $flightObject], "FlightTrips", "", "", ["user"]);

	// Find any delivery orders for this user that may be in progress
	foreach($flightTrips as $flightTrip) {

		$orders = parseExecuteQuery(["user" => $flightTrip->get('user'), "fullfillmentType" => "d", "status" => listStatusesForPendingInProgress()], "Order", "", "", ["user", "retailer", "deliveryLocation"]);

		foreach($orders as $order) {

			$sendMessageOnSlack = false;

			// Departure side matches order airport
			// And flight gate doesn't match delivery location
			if(strcasecmp($flight->get('departure')->getAirportInfo()["airportIataCode"], $order->get('retailer')->get('airportIataCode'))==0
				&& strcasecmp($order->get('deliveryLocation')->getObjectId(), $flight->get('departure')->getTerminalGateMapLocation(true)->getObjectId())!=0) {

				$sendMessageOnSlack = true;
			}
			// Arrival side matches order airport
			// And flight gate doesn't match delivery location
			else if(strcasecmp($flight->get('arrival')->getAirportInfo()["airportIataCode"], $order->get('retailer')->get('airportIataCode'))==0
				&& strcasecmp($order->get('deliveryLocation')->getObjectId(), $flight->get('arrival')->getTerminalGateMapLocation(true)->getObjectId())!=0) {

				$sendMessageOnSlack = true;
			}

			if($sendMessageOnSlack) {

				// Post to Slack Channel
				$submissionDateTime = date("M j, g:i a", time());

				// Slack it
				$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
				$slack->setText("Gate Change - Customer Impacted (" . $submissionDateTime . ")");
				
				$attachment = $slack->addAttachment();
				$attachment->addField("Customer:", $order->get('user')->get("firstName") . " " . $order->get('user')->get("lastName"), false);
				$attachment->addField("Flight:", $flightId, false);
				$attachment->addField("Change:", $typeOfChange, true);
				$attachment->addField("Order Id:", $order->get('orderSequenceId'), true);
				
				try {
					
					$slack->send();
				}
				catch (Exception $ex) {
					
					return json_error_return_array("AS_1016", "", "Slack post for customer flight gate change order ipmact failed = " . $flightId ." - " . $ex->getMessage(), 3, 1);
				}
			}
		}
	}

	return "";
}

?>
