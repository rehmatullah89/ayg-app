<?php

use Parse\ParseObject;
use Parse\ParseQuery;

$flightStats_status = [
				"D" =>	["desc" => "Status unknown", 
							"inform_user" => false, 
							"completed_status" => false, 
							"delayed_status" => false, 
							"canceled_status" => true,
							"departed_status" => false
						], // "Diverted",
				"DN" =>	["desc" => "Status unknown", 
							"inform_user" => false, 
							"completed_status" => true, 
							"delayed_status" => false, 
							"canceled_status" => true,
							"departed_status" => false
						], // "Data source needed",
				"NO" =>	["desc" => "Status unknown", 
							"inform_user" => false, 
							"completed_status" => false, 
							"delayed_status" => false, 
							"canceled_status" => true,
							"departed_status" => false
						], // "Not Operational",
				"R" =>	["desc" => "Status unknown", 
							"inform_user" => false, 
							"completed_status" => false, 
							"delayed_status" => false, 
							"canceled_status" => true,
							"departed_status" => false
						], // "Redirected",
				"U" =>	["desc" => "Status unknown", 
							"inform_user" => false, 
							"completed_status" => true, 
							"delayed_status" => false, 
							"canceled_status" => true,
							"departed_status" => false
						], // "Unknown",

				"A" =>	["desc" => "En route", 
							"inform_user" => false, 
							"completed_status" => false, 
							"delayed_status" => false, 
							"canceled_status" => false,
							"departed_status" => true
						], // Active
				"C" =>	["desc" => "Cancelled", 
							"inform_user" => true, 
							"completed_status" => true, 
							"delayed_status" => false, 
							"canceled_status" => true,
							"departed_status" => false
						],
				"L" =>	["desc" => "Arrived", 
							"inform_user" => false, 
							"completed_status" => true, 
							"delayed_status" => false, 
							"canceled_status" => false,
							"departed_status" => false
						],
				"S" =>	["desc" => "Scheduled", 
							"inform_user" => true, 
							"completed_status" => false, 
							"delayed_status" => false, 
							"canceled_status" => false,
							"departed_status" => false
						],

				"_S_TDY_" => ["desc" => "On time", 
								"inform_user" => false, 
								"completed_status" => false, 
								"delayed_status" => false, 
								"canceled_status" => false,
								"departed_status" => false
							], // Scheduled status on the same day of travel (at the airport)
				"_S_DLY_" => ["desc" => "Delayed", 
								"inform_user" => true, 
								"completed_status" => false, 
								"delayed_status" => true, 
								"canceled_status" => false,
								"departed_status" => false
							],
				"_L_DLY_" => ["desc" => "Landed (late)", 
								"inform_user" => false, 
								"completed_status" => true, 
								"delayed_status" => true, 
								"canceled_status" => false,
								"departed_status" => false
							],
				"_L_ELY_" => ["desc" => "Landed (early)", 
								"inform_user" => false, 
								"completed_status" => true, 
								"delayed_status" => true, 
								"canceled_status" => false,
								"departed_status" => false
							]
	];

function fetchFlights($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, $flightUniqueId="", $flightObjectToUse="") {

	try {

		$type = identifyWhichFlightStatsMethodToCall($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, "");
	}
	catch (Exception $ex) {

		// Json array formatted
		return json_error_return_array("AS_1064", "", "Fetch Flights failed! airlineIataCode = " . $airlineIataCode . ", flightNumber = " . $flightNumber . ", flightYear flightMonth flightDate = " . $flightYear . $flightMonth . $flightDate . " - " . $ex->getMessage(), 2);
		// return json_decode($ex->getMessage(), true);
	}

	return fetchFlightInformation($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, $flightUniqueId, $type, $flightObjectToUse);
}

function fetchFlightsWithExternalFlightId($flightUniqueId, $extFlightId, $flightObjectToUse) {

	try {

		$type = identifyWhichFlightStatsMethodToCall("", "", "", "", "", $extFlightId);
	}
	catch (Exception $ex) {

		// Json array formatted
		return json_error_return_array("AS_1065", "", "Fetch Flights with external id failed! extFlightId = " . $extFlightId . " - " . $ex->getMessage(), 2);

		// return json_decode($ex->getMessage(), true);
	}

	return fetchFlightInformationWithExternalFlightId($flightUniqueId, $extFlightId, $type, $flightObjectToUse);
}

function identifyWhichFlightStatsMethodToCall($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, $extFlightId) {

	if(!empty($extFlightId)) {

		return 'status_by_flightId';
	}	

	$flightYear = intval($flightYear);
	$flightMonth = intval($flightMonth);
	$flightDate = intval($flightDate);

	if(!checkdate($flightMonth, $flightDate, $flightYear)) {

		throw new Exception (json_encode(json_error_return_array("AS_203", "Departure date is not correct.", "Invalid Departure Date. Must be a valid YYYY MM DD. Parameters used: $flightMonth $flightDate $flightYear", 2)));
	}
	
	$flightTimeStamp = mktime(0, 0, 0, $flightMonth, $flightDate, $flightYear);
	$plusTwoDaysTimeStamp = time() + (2 * 24 * 60 * 60);
	$plus11MonthsTimeStamp = strtotime("+11 months midnight");
	$minusSevenDaysTimeStamp = time() - (7 * 24 * 60 * 60);
	
	// if date more than 1 year away
	if($flightTimeStamp > $plus11MonthsTimeStamp) {

		throw new Exception (json_encode(json_error_return_array("AS_203", "Departure date is not correct.", "Departure date more than 11 months away. Parameters used: $flightMonth $flightDate $flightYear", 2)));
	}

	// Use Flight Schedule when Flight Time is >+2 days and <-7 days
	if($flightTimeStamp > $plusTwoDaysTimeStamp ||
		$flightTimeStamp < $minusSevenDaysTimeStamp) {
			
		return 'schedule';
	}
	// Else call status
	else {

		return 'status';
	}
}

function fetchFlightInformationWithExternalFlightId($flightUniqueId, $flightId, $type, $flightObjectToUse) {

	$parameters = buildParams("", "", "", "", "", $flightId, $type);

	try {

		list($flightArray, $airports, $airlines) = fetchFlightContents($parameters, $type);
	}
	catch (Exception $ex) {

		// Json array formatted
		return json_error_return_array("AS_1066", "", "Fetch Flights with external id failed! extFlightId = " . $flightId . " $type = " . $type . " - " . $ex->getMessage(), 2);

		// return json_decode($ex->getMessage(), true);
	}

	// print_r($flightArray);exit;

	list($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate) = parseFlightInfoFromUniqueId($flightUniqueId);

	$flightArray = correctCodeShareForAllFlights($airlineIataCode, $flightNumber, $flightArray);

	return getFlightObjectsForAllFlights("", "", "", $flightArray, $airports, $airlines, $type, "", $flightObjectToUse);
}


function correctCodeShareForAllFlights($airlineIataCode, $flightNumnber, $flightArray) {

	for($i=0;$i<count_like_php5($flightArray);$i++) {

		$flightArray[$i]["carrierFsCode"] = $airlineIataCode;
		$flightArray[$i]["flightNumber"] = $flightNumnber;
	}

	return $flightArray;
}

function fetchFlightInformation($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, $flightUniqueId, $type, $flightObjectToUse) {

	$parameters = buildParams($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, "", $type);

	// JMD
	try {
		list($flightArray, $airports, $airlines) = fetchFlightContents($parameters, $type);
	}
	catch (Exception $ex) {

		// Json array formatted
		return json_error_return_array("AS_201", "Flight not found. Please check the entered information.", "Fetch Flight information failed! airlineIataCode = " . $airlineIataCode . ", flightNumber = " . $flightNumber . ", flightYear flightMonth flightDate = " . $flightYear . $flightMonth . $flightDate . ", type = " . $type . " - " . $ex->getMessage(), 3);
		// return json_decode($ex->getMessage(), true);
	}

	$flightArray = correctCodeShareForAllFlights($airlineIataCode, $flightNumber, $flightArray);

	return getFlightObjectsForAllFlights($flightYear, $flightMonth, $flightDate, $flightArray, $airports, $airlines, $type, $flightUniqueId, $flightObjectToUse);	
}

function buildParams($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, $flightId, $type) {

	if(strcasecmp($type, 'schedule')==0) {

		return $airlineIataCode . "/" . $flightNumber . "/departing/" . $flightYear . "/" . $flightMonth . "/" . $flightDate;
	}
	else if(strcasecmp($type, 'status')==0) {

		return $airlineIataCode . "/" . $flightNumber . "/dep/" . $flightYear . "/" . $flightMonth . "/" . $flightDate;
	}
	else if(strcasecmp($type, 'status_by_flightId')==0) {

		return $flightId;
	}
}

function fetchFlightContents($parameters, $type) {

	if(strcasecmp($type, 'schedule')==0) {

		return fetchContentsFromFlightStats('env_FlightStatsAPIURLPrefix_Schedule', $parameters, "scheduledFlights", $type);
	}
	else if(strcasecmp($type, 'status')==0) {

		return fetchContentsFromFlightStats('env_FlightStatsAPIURLPrefix_Status', $parameters, "flightStatuses", $type);
	}
	else if(strcasecmp($type, 'status_by_flightId')==0) {

		return fetchContentsFromFlightStats('env_FlightStatsAPIURLPrefix_Status', $parameters, "flightStatus", $type);
	}
}

function fetchContentsFromFlightStats($flightStatsAPIURLPrefixKeyName, $parameters, $responseKeyName, $type) {

	$url = $GLOBALS[$flightStatsAPIURLPrefixKeyName] . $parameters . "?appId=" . $GLOBALS['env_FlightStatsAppId'] . "&appKey=" . $GLOBALS['env_FlightStatsAppKey'] . "&utc=false&codeType=IATA";

	$userObjectId = 0;
	if(!empty($GLOBALS['user'])) {

		$userObjectId = $GLOBALS['user']->getObjectId();
	}

	$logArray = array("action" => "flight_log_api_call", 
					  "content" => 
					  	array(
					  		"callType" => $type,
					  		"callUrl" => $url,
					  		"callTimestamp" => time(),
					  		"isWorker" => defined("WORKER") ? 1 : 0,
					  		"backtrace" => getBackTrace(),
					  		"userObjectId" => $userObjectId
			  			)
				);

	// Log the API call
	// Connect to Worker Queue
	try {

		// $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
		$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName'], 20, 0, true);
		$workerQueue->sendMessage(
					$logArray
				);
	}
	catch (Exception $ex) {

		// No exiting error
		json_error("AS_212", "", "Flight Stats API call not logged " . json_encode($logArray) . " - " . $ex->getMessage(), 3);
	}

	// Call Flight API
	$response = getpage($url);
	try {

		$responseInterim = json_decode($response, true);
	}
	catch (Exception $ex) {

		throw new Exception (json_encode(json_error_return_array("AS_204", "", "Flight Stats response was not parseable " . json_encode($responseInterim) . " - " . $ex->getMessage(), 2)));
	}

	// Check if any error was returned
	if(isset($responseInterim["error"]["errorCode"])) {
		
		throw new Exception (json_encode(json_error_return_array("AS_205", "", $responseInterim["error"]["errorCode"] . " " . $responseInterim["error"]["errorMessage"] . " - Flight Stats response :: " . json_encode($responseInterim), 2)));
	}
	
	// Check Status is available
	else if(
			(!isset($responseInterim[$responseKeyName][0]) && !isset($responseInterim[$responseKeyName]))
				|| (isset($responseInterim[$responseKeyName]) && count_like_php5($responseInterim[$responseKeyName]) == 0)
		) {
		
		throw new Exception (json_encode(json_error_return_array("AS_201", "Flight not found. Please check the flight details entered.", "Flight Stats $responseKeyName was not available. Flight Stats response dump: " . json_encode($responseInterim), 3)));
	}

	$airports = buildAirportCodeMap($responseInterim["appendix"]["airports"]);

	$airlines = buildAirlineCodeMap($responseInterim["appendix"]["airlines"]);

	return [isset($responseInterim[$responseKeyName][0]) ? $responseInterim[$responseKeyName] : array($responseInterim[$responseKeyName]), $airports, $airlines];
}

function getFlightObjectsForAllFlights($flightYear, $flightMonth, $flightDate, $flightArray, $airports, $airlines, $type, $flightUniqueId, $flightObjectToUse) {
	
	$responseArray = array();

	foreach($flightArray as $index => $flightDetails) {

		$flight = formatFlightObject($flightYear, $flightMonth, $flightDate, $flightDetails, $airports, $airlines, $type, $flightUniqueId, $flightObjectToUse);

		if(count_like_php5($flight) > 0) {

			$responseArray[] = $flight;
		}
	}

	return $responseArray;
	// JMD
}

/*
function getFlightObjectByUniqueId($flightYear, $flightMonth, $flightDate, $flightArray, $airports, $airlines, $type, $flightUniqueId, $flightObjectToUse) {
	
	$responseArray = array();

	foreach($flightArray as $index => $flightDetails) {

		$flight = formatFlightObject($flightYear, $flightMonth, $flightDate, $flightDetails, $airports, $airlines, $type, $flightUniqueId, $flightObjectToUse);

		if(count_like_php5($flight) > 0) {

			$responseArray[] = $flight;
		}
	}

	return $responseArray;
}
*/

function formatFlightObject($flightYear, $flightMonth, $flightDate, &$flightDetails, &$airports, &$airlines, $type, $flightUniqueId, $flightObjectToUse) {

	if( (strcasecmp($type, 'schedule')==0 && !in_array($flightDetails["serviceType"], array("J", "S", "U")))
		|| (isset($flightDetails["schedule"]) && preg_match('/^status/i', $type) && !in_array($flightDetails["schedule"]["flightType"], array("J", "S", "U")))
		) {

		return [];			
	}

	$flight = new Flight();

	if(empty($flightYear)
		|| empty($flightMonth)
			|| empty($flightDate)) {

		$flight->get("info")->parseAndSetFlightDate($flightDetails["departureDate"]["dateUtc"]);
	}
	else {

		$flight->get("info")->set("flightYear", $flightYear);
		$flight->get("info")->set("flightMonth", intval($flightMonth));
		$flight->get("info")->set("flightDate", intval($flightDate));
	}

	$flight->get("info")->setByRef("airlineIataCode", $flightDetails, ["carrierFsCode"]);

	$flight->get("info")->setByRef("flightNum", $flightDetails, ["flightNumber"]);

	$flight->get("arrival")->setExtAirportInfo($airports["byFs"][$flightDetails["arrivalAirportFsCode"]]);

	$flight->get("departure")->setExtAirportInfo($airports["byFs"][$flightDetails["departureAirportFsCode"]]);

	// Generate uniqueId
	$flight->generateUniqueId();

	// If requesting a specific flight unique id but doesn't match
	if(!empty($flightUniqueId)
		&& strcasecmp($flightUniqueId, $flight->get("info")->get('uniqueId'))!=0) {

		return [];
	}

	if(empty($flightObjectToUse)) {

		$flightObjectToUse = $flight;
	}

	$flight = completeFlightInfoLookup($flightObjectToUse, $flightDetails, $airports, $airlines, $type);

	// Set to cache
	setFlightInfoCache($flight->get("info")->get("uniqueId"), $flight);	

	return $flight;
}

function completeFlightInfoLookup($flight, $flightDetails, $airports, $airlines, $type) {

	/////////////////////////////////////////////////////////////////////////////////
	$flight->get("info")->setByRef("extAirlineName", $airlines["byIata"], [$flight->get("info")->get("airlineIataCode"), "name"]);

	if(strcasecmp($type, 'schedule')==0) {

		// Status
		$flight->get("info")->set("status", "S");

		// Scheduled Departure time
		$flight->get("departure")->setByRef("scheduledGateLocal", $flightDetails, ["departureTime"]);

		// Scheduled Arrival time
		$flight->get("arrival")->setByRef("scheduledGateLocal", $flightDetails, ["arrivalTime"]);
	
		$flight->get("departure")->setByRef("terminal", $flightDetails, ["departureTerminal"]);
		$flight->get("arrival")->setByRef("terminal", $flightDetails, ["arrivalTerminal"]);
	}
	// Status
	else {

		// Status
		$flight->get("info")->setByRef("status", $flightDetails, ["status"]);

		// External Id
		$flight->get("info")->setByRef("extFlightId", $flightDetails, ["flightId"]);

		// Duration
		$flight->get("info")->setByRef("scheduledAirMinutes", $flightDetails, ["flightDurations", "scheduledAirMinutes"]);

		$flight->get("info")->setByRef("scheduledBlockMinutes", $flightDetails, ["flightDurations", "scheduledBlockMinutes"]);

		// Posted delay
		$flight->get("departure")->setByRef("postedDelayMinutes", $flightDetails, ["delays", "departureGateDelayMinutes"]);


		//////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Scheduled Departure time
		$flight->get("departure")->setByRef("scheduledGateLocal", $flightDetails, ["operationalTimes", "scheduledGateDeparture", "dateLocal"]);

		// Estimated Departure time
		$flight->get("departure")->setByRef("estimatedGateLocal", $flightDetails, ["operationalTimes", "estimatedGateDeparture", "dateLocal"]);

		// Planned Departure time
		$flight->get("departure")->setByRef("flightPlanPlannedGateLocal", $flightDetails, ["operationalTimes", "flightPlanPlannedDeparture", "dateLocal"]);

		// Actual Departure time
		$flight->get("departure")->setByRef("actualGateLocal", $flightDetails, ["operationalTimes", "actualGateDeparture", "dateLocal"]);

		// Departure Terminal
		$flight->get("departure")->setByRef("terminal", $flightDetails, ["airportResources", "departureTerminal"]);

		// Departure Gate
		$flight->get("departure")->setByRef("gate", $flightDetails, ["airportResources", "departureGate"]);

		// Posted delay
		$flight->get("departure")->setByRef("gateDelayMinutes", $flightDetails, ["delays", "departureGateDelayMinutes"]);
		//////////////////////////////////////////////////////////////////////////////////////////////////////////


		//////////////////////////////////////////////////////////////////////////////////////////////////////////
		// Scheduled Arrival time
		$flight->get("arrival")->setByRef("scheduledGateLocal", $flightDetails, ["operationalTimes", "scheduledGateArrival", "dateLocal"]);

		// Estimated Arrival time
		$flight->get("arrival")->setByRef("estimatedGateLocal", $flightDetails, ["operationalTimes", "estimatedGateArrival", "dateLocal"]);

		// Planned Departure time
		$flight->get("arrival")->setByRef("flightPlanPlannedGateLocal", $flightDetails, ["operationalTimes", "flightPlanPlannedArrival", "dateLocal"]);

		// Actual Arrival time
		$flight->get("arrival")->setByRef("actualGateLocal", $flightDetails, ["operationalTimes", "actualGateArrival", "dateLocal"]);

		// Arrival Terminal
		$flight->get("arrival")->setByRef("terminal", $flightDetails, ["airportResources", "arrivalTerminal"]);

		// Arrival Gate
		$flight->get("arrival")->setByRef("gate", $flightDetails, ["airportResources", "arrivalGate"]);
		//////////////////////////////////////////////////////////////////////////////////////////////////////////

		// Baggage
		$flight->get("arrival")->setByRef("baggage", $flightDetails, ["operationalTimes", "airportResources", "baggage"]);

		// Posted delay
		$flight->get("arrival")->setByRef("gateDelayMinutes", $flightDetails, ["delays", "arrivalGateDelayMinutes"]);
	}

	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Set Departure info
	//////////////////////////////////////////////////////////////////////////////////////////////////////////

	$flight->get("departure")->setFlightDateTime();
	//////////////////////////////////////////////////////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////////////////////////////////////////////////////
	// Set Arrival info
	//////////////////////////////////////////////////////////////////////////////////////////////////////////

	$flight->get("arrival")->setFlightDateTime();
	//////////////////////////////////////////////////////////////////////////////////////////////////////////


	// Evaluate Delays and interpret status
	$flight->get("departure")->evaluateUTCAndTimestamps();
	$flight->get("arrival")->evaluateUTCAndTimestamps();
	$flight->setDelaysAndInterpretStatus();

	// Status description
	$flight->get("info")->setStatusDescription();

	return $flight;	
}

function buildAirportCodeMap($airportList) {

	$responseArray = [];

	foreach($airportList as $airport) {

		$responseArray["byFs"][$airport["fs"]] = $airport;
		$responseArray["byIata"][$airport["fs"]] = $airport;
	}

	return $responseArray;
}

function buildAirlineCodeMap($airlineList) {

	$responseArray = [];

	foreach($airlineList as $airline) {

		$responseArray["byFs"][$airline["fs"]] = $airline;
		$responseArray["byIata"][$airline["fs"]] = $airline;
	}

	return $responseArray;
}

function parseFlightInfoFromUniqueId($flightUniqueId) {

	$flightAttributes = explode('_', $flightUniqueId);

	return [$flightAttributes[3], $flightAttributes[4], $flightAttributes[6], $flightAttributes[7], $flightAttributes[8]];
}

function applyPartialFlightRules(&$flight) {

	// Evaluate timestamps
	$flight->get("departure")->evaluateUTCAndTimestamps();
	$flight->get("arrival")->evaluateUTCAndTimestamps();

	$flight->setDelaysAndInterpretStatus();	
}

function updateFlightDetails(&$flight, $user="") {

	if(empty($user)
		&& is_object($GLOBALS['user'])) {

		$user = $GLOBALS['user'];
	}

	$preUpdateDepartureTimestamp = $flight->get('departure')->getLastKnownTimestamp();
	$preUpdateArrivalTimestamp = $flight->get('arrival')->getLastKnownTimestamp();
    //json_echo(json_encode($flight->get("arrival")));
	$flight->get("info")->getAirlineInfo();

	// Evaluate timestamps
	$flight->get("departure")->evaluateUTCAndTimestamps();
	$flight->get("arrival")->evaluateUTCAndTimestamps();

	// Set default Terminals
	$flight->get("departure")->setBoardingTime();

	// Apply Terminal gate rules
	$flight->get("departure")->applyTerminalGateRules();
	$flight->get("arrival")->applyTerminalGateRules();

	// Departure - Find Terminal & Gate locations
	$flight->get("departure")->getTerminalGateMapLocation();

	// Arrival - Find Terminal & Gate location
	$flight->get("arrival")->getTerminalGateMapLocation();

	$flight->setDelaysAndInterpretStatus();	

	// Check if timestamps were updated
	if($preUpdateDepartureTimestamp != $flight->get('departure')->getLastKnownTimestamp()
		|| $preUpdateArrivalTimestamp != $flight->get('arrival')->getLastKnownTimestamp()) {

		if(!empty($user)) {

			// Find flight object
			$flightRefObject = new ParseQuery("Flights");
			$flightBeingUpdated = parseSetupQueryParams(["uniqueId" => $flight->get('info')->get('uniqueId')], $flightRefObject);

			// Find Trips object for this flight
			$flightTrip = parseExecuteQuery(["__MATCHESQUERY__flight" => $flightBeingUpdated, "user" => $user, "isActive" => true], "FlightTrips", "", "", ["userTrip"], 1);

			// Update UserTrips table
			updateUserTripTimestamps($flightTrip->get('userTrip'), $flight);
		}
	}
}

function prepareFlightInfo($flight) {

	$responseArray["info"] = $flight->get("info")->getAirlineInfo();
	$responseArray["info"]["uniqueId"] = $flight->get("info")->get("uniqueId");
	$responseArray["info"]["flightNum"] = $flight->get("info")->get("flightNum");
	$responseArray["info"]["statusDescription"] = $flight->get("info")->get("statusDescription");
	$responseArray["info"]["statusInterpreted"] = $flight->get("info")->get("statusInterpreted");
	$responseArray["info"]["scheduledAirMinutes"] = $flight->get("info")->get("scheduledAirMinutes");
	$responseArray["info"]["scheduledBlockMinutes"] = $flight->get("info")->get("scheduledBlockMinutes");

	// Departure
	$airport = $flight->get("departure")->getAirportInfo();
	$responseArray["departure"] = $airport;
	$responseArray["departure"]["flightTime"] = $flight->get("departure")->get("flightTime");
	$responseArray["departure"]["flightDate"] = $flight->get("departure")->get("flightDate");
	$responseArray["departure"]["scheduledGateTimestamp"] = $flight->get('departure')->get('scheduledGateTimestamp');
	$responseArray["departure"]["lastKnownTimestamp"] = $flight->get('departure')->getLastKnownTimestamp();
	$responseArray["departure"]["lastKnownTimestampFormatted"] = $flight->get('departure')->getLastKnownTimestampFormatted();
	$responseArray["departure"]["delayMinutes"] = $flight->get("departure")->get("gateDelayMinutes");
	$responseArray["departure"]["earlyMinutes"] = $flight->get("departure")->get("gateEarlyMinutes");

	$responseArray["departure"]["boardingTimestamp"] = $flight->get("departure")->get("boardingTimestamp");
	$responseArray["departure"]["deliveryAlertTimestamp"] = $flight->get("departure")->get("deliveryAlertTimestamp");

	if($flight->get('departure')->isReadyAirport() == true) {

		if(is_object($flight->get('departure')->getTerminalGateMapLocation(true))) {

			$responseArray["departure"]["location"] = $flight->get('departure')->getTerminalGateMapLocation(true)->getObjectId();
		}
		else if($flight->get('departure')->isReadyAirport() == true) {

			$responseArray["departure"]["location"] = "";
		}
	}

	else if($flight->get('departure')->isReadyAirport() == false) {

		$responseArray["departure"]["locationExt"] = $flight->get('departure')->getExtTerminalGateMapLocation(true);
	}

	$responseArray["departure"]["isGateInfoEstimated"] = $flight->get("departure")->get("isGateInfoEstimated");

	// Arrival
	$airport = $flight->get("arrival")->getAirportInfo();
	$responseArray["arrival"] = $airport;
	$responseArray["arrival"]["flightTime"] = $flight->get("arrival")->get("flightTime");
	$responseArray["arrival"]["flightDate"] = $flight->get("arrival")->get("flightDate");
	$responseArray["arrival"]["scheduledGateTimestamp"] = $flight->get('arrival')->get('scheduledGateTimestamp');
	$responseArray["arrival"]["lastKnownTimestamp"] = $flight->get('arrival')->getLastKnownTimestamp();
	$responseArray["arrival"]["lastKnownTimestampFormatted"] = $flight->get('arrival')->getLastKnownTimestampFormatted();
	$responseArray["arrival"]["delayMinutes"] = $flight->get("arrival")->get("gateDelayMinutes");
	$responseArray["arrival"]["earlyMinutes"] = $flight->get("arrival")->get("gateEarlyMinutes");

	$responseArray["arrival"]["baggage"] = $flight->get("arrival")->get("baggage");

	if(is_object($flight->get('arrival')->getTerminalGateMapLocation(true))) {

		$responseArray["arrival"]["location"] = $flight->get('arrival')->getTerminalGateMapLocation(true)->getObjectId();
	}
	else if($flight->get('arrival')->isReadyAirport() == true) {

		$responseArray["arrival"]["location"] = "";
	}

	if($flight->get('arrival')->isReadyAirport() == false) {

		$responseArray["arrival"]["locationExt"] = $flight->get('arrival')->getExtTerminalGateMapLocation(true);
	}

	$responseArray["arrival"]["isGateInfoEstimated"] = $flight->get("arrival")->get("isGateInfoEstimated");

	return $responseArray;
}

function updateStatusOfFlight($flightId, $updateDBAfterStatusCheck=false, $dontPullFromAPIIfInCache=false, $flightObjectFromCache="", $applyFullFlightRules=true, $applyPartialFlightRules=false) {

	$flightFound = false;

	// If cache object is provided, use it else pull from cache
	if(empty($flightObjectFromCache)) {

		// Get flight info from cache
		$flightFromCache = getFlightInfoCache($flightId);
	}
	else {

		$flightFromCache = $flightObjectFromCache;
	}

	// If the request is to just find the flight
	// dontPullFromAPIIfInCache = true means if available cache use it, don't fetch updates from API
	if($dontPullFromAPIIfInCache == true
		&& !empty($flightFromCache)) {

		$flightFound = true;
		$flight[] = $flightFromCache;
	}

	// if flight id is provided, call the API
	else if(!empty($flightFromCache)
		&& !empty($flightFromCache->get('info')->get('extFlightId'))) {

		// Check if flight info with external flight id is available
		$flight = fetchFlightsWithExternalFlightId($flightId, $flightFromCache->get('info')->get('extFlightId'), $flightFromCache);

		if(isset($flight["error_code"])) {

			$flightFound = false;
		}
		else {

			$flightFound = true;
		}
	}

	// Else try to find the flight by its departure information
	if(!$flightFound) {

		// Identify flight departure information from flight unique id
		list($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate) = parseFlightInfoFromUniqueId($flightId);

		// fetch information again
		$flight = fetchFlights($airlineIataCode, $flightNumber, $flightYear, $flightMonth, $flightDate, $flightId, $flightFromCache);

		if(is_array($flight)
			&& isset($flight["error_code"])) {

			return $flight;
		}

		// Ensure the flight was found
		if(count_like_php5($flight) > 0) {

			$flightFound = true;
		}
	}

	if($flightFound) {

		// Pull first record which would match the flightId
		$flight = $flight[0];

		if($applyFullFlightRules) {

			updateFlightDetails($flight);
		}
		else if($applyPartialFlightRules) {

			applyPartialFlightRules($flight);
		}

		// cache it
		setFlightInfoCache($flightId, $flight, ($flight->get('arrival')->getLastKnownTimestamp()-time())+(7*24*60*60));

		// Update the flight info in the DB
		if($updateDBAfterStatusCheck == true) {

			updateFlightInDB($flight, $flightId);
		}

		return $flight;
	}
	else {

		if(!empty($flightFromCache)) {

			// return $flightFromCache;
		}

		return json_error_return_array("AS_215", "", "Flight not found by flightId, flightId=" . $flightId, 3);
	}
}

function updateUserTripTimestamps(&$userTrip, $flight, $tripName="", $saveUserTrip=true) {

	// Update User Trips
	$updateFlag = 0;

	// Update Trip Name if provided and different than in the table
	if(!empty($tripName)
		&& strcasecmp($tripName, $userTrip->get('tripName'))!=0) {

		$updateFlag = 1;
		$userTrip->set('tripName', $tripName);
	}

	// Find the earliest departure, if this is it update
	if($userTrip->get('firstFlightDepartureTimestamp') > $flight->get('departure')->getLastKnownTimestamp()) {

		$updateFlag = 1;
		$userTrip->set('firstFlightDepartureTimestamp', $flight->get('departure')->getLastKnownTimestamp());
		$userTrip->set('firstFlightDepartureTimezone', $flight->get('departure')->getAirportInfo()["airportTimezone"]);
		$userTrip->set('firstFlightDepartureAirportIataCode', $flight->get('departure')->getAirportInfo()["airportIataCode"]);
	}

	// Find the latest arrival, if this is it update
	if($userTrip->get('lastFlightArrivalTimestamp') < $flight->get('arrival')->getLastKnownTimestamp()) {

		$updateFlag = 1;
		$userTrip->set('lastFlightArrivalTimestamp', $flight->get('arrival')->getLastKnownTimestamp());
		$userTrip->set('lastFlightArrivalTimezone', $flight->get('arrival')->getAirportInfo()["airportTimezone"]);
		$userTrip->set('lastFlightArrivalAirportIataCode', $flight->get('arrival')->getAirportInfo()["airportIataCode"]);

		// Only update if not TripIt trip
		if(empty($userTrip->get('extTripItId'))) {

			$userTrip->set('tripName', $flight->getTripName());
		}
	}

	if($updateFlag == 1
		&& $saveUserTrip == true) {

		$userTrip->save();
	}
}

function resetUserTripTimestamps(&$userTrip) {

	$userTrip->set('firstFlightDepartureTimestamp', 9999999999999999999);
	$userTrip->set('lastFlightArrivalTimestamp', 0);
}

function deleteTrip($flightTrip) {

	$userTripToDelete = [];

	// If diff user trip then add to list
	if(!in_array($flightTrip->get('userTrip')->getObjectId(), array_keys($userTripToDelete))) {

		$userTripToDelete[$flightTrip->get('userTrip')->getObjectId()] = $flightTrip->get('userTrip');
	}

	// Mark flight trip as inactive			
	$flightTrip->set("isActive", false);
	$flightTrip->save();

	// Mark the trip as inactive
	foreach($userTripToDelete as $userTrip) {

		$userTrip->set("isActive", false);
		$userTrip->save();
	}
}

function fetchFlightObject($flightId) {

	// Check row doesn't exist
	return parseExecuteQuery(array("uniqueId" => $flightId), "Flights", "", "", [], 1);
}

function addNewFlight(&$flight) {

	$flightId = $flight->get('info')->get("uniqueId");
	$duplicateCounter = getNewFlightDuplicateCounter($flightId);

	// No exit log
	if($duplicateCounter > 1) {

		json_error("AS_3007", "", "Duplicate flight counter occurred for " . $flight->get('info')->get("uniqueId"), 3, 1);
	}

	// Find the flight if the flag parameter is set
	// Or duplicateCounter > 1 meaning another process added it
	if($duplicateCounter > 1) {

		sleep(1);
		$flightFind = fetchFlightObject($flightId);

		if(count_like_php5($flightFind) > 0) {

			return [false, $flightFind];
		}

		// Flight was not found
		// And duplicateCounter > 1, then that means race condition occurred, sleep for a second try again
		if($duplicateCounter > 1) {

			sleep(1);
			$flightFind = fetchFlightObject($flightId);

			if(count_like_php5($flightFind) > 0) {

				return [false, $flightFind];
			}
			else {

				// return an error
				return[false, json_error_return_array("AS_214", "", "Flight Insert failed, race condition occurred", 1)];	
			}
		}
	}

	$flightInsertRow = new ParseObject("Flights");

	$flightTripsInsertRow = prepareFlightObjectForUpdateInDB($flight, $flightInsertRow);

	$flightInsertRow->save();

	return [true, $flightInsertRow];
}

function updateFlightInDB($flight, $flightId, $saveFlightObject=false) {

	// update flight info in the database
	$flightRow = parseExecuteQuery(array("uniqueId" => $flightId), "Flights", "", "", [], 1);

	if(count_like_php5($flightRow) > 0) {

		$flightRow = prepareFlightObjectForUpdateInDB($flight, $flightRow);

		if($saveFlightObject) {

			$flightRow->set('flightCacheObject', json_encode(serialize($flight)));
		}

		$flightRow->save();
	}
}

function prepareFlightObjectForUpdateInDB(&$flight, $flightRow) {

	$flightRow->set('uniqueId', $flight->get('info')->get('uniqueId'));
	$flightRow->set('airlineFlightNum', $flight->get('info')->get('flightNum')); 
	$flightRow->set('departureAirportIataCode', $flight->get('departure')->get('airportIataCode')); 
	$flightRow->set('arrivalAirportIataCode', $flight->get('arrival')->get('airportIataCode')); 
	$flightRow->set('airlineIataCode', $flight->get('info')->get('airlineIataCode')); 
	$flightRow->set('scheduledDepartureTimestamp', $flight->get('departure')->get('scheduledGateTimestamp')); 
	$flightRow->set('scheduledArrivalTimestamp', $flight->get('arrival')->get('scheduledGateTimestamp')); 
	$flightRow->set('departureDate', strval($flight->get('info')->get('flightDate')));
	$flightRow->set('departureMonth', strval($flight->get('info')->get('flightMonth')));
	$flightRow->set('departureYear', strval($flight->get('info')->get('flightYear')));
	$flightRow->set('lastKnownDepartureLocation', $flight->get('departure')->getTerminalGateMapLocation()); 
	$flightRow->set('lastKnownArrivalLocation', $flight->get('arrival')->getTerminalGateMapLocation()); 
	$flightRow->set('lastKnownDepartureTimestamp', $flight->get('departure')->getLastKnownTimestamp()); 
	$flightRow->set('lastKnownArrivalTimestamp', $flight->get('arrival')->getLastKnownTimestamp()); 
	$flightRow->set('lastKnownStatusCode', $flight->get('info')->get('status')); 
	$flightRow->set('lastKnownStatusInterpreted', $flight->get('info')->get('statusInterpreted'));

	$flightRow->set('extFlightId', strval($flight->get('info')->get('extFlightId')));

	// Check if we need to save external information
	if($flight->get('info')->getIsKnownAirline() == false) {

		$flightRow->set('extAirlineInfo', json_encode($flight->get('info')->getExtAirlineInfo()));
	}

	if($flight->get('departure')->getIsKnownAirport() == false) {

		$flightRow->set('extDepartureAirportInfo', json_encode($flight->get('departure')->getExtAirportInfo()));
	}

	if($flight->get('arrival')->getIsKnownAirport() == false) {

		$flightRow->set('extArrivalAirportInfo', json_encode($flight->get('arrival')->getExtAirportInfo()));
	}

	if($flight->get('departure')->isReadyAirport() == false) {

		$flightRow->set('extDepartureLocationInfo', json_encode($flight->get('departure')->getExtTerminalGateMapLocation()));
	}

	if($flight->get('arrival')->isReadyAirport() == false) {

		$flightRow->set('extArrivalLocationInfo', json_encode($flight->get('arrival')->getExtTerminalGateMapLocation()));
	}

	return $flightRow;
}

function addNewTripForTripIt(&$flight, $extTripItId, $extTripName="") {

	// Check if this trip already exists
	$userTrips = parseExecuteQuery(["user" => $GLOBALS['user'], "extTripItId" => $extTripItId, "isActive" => true], "UserTrips");

	// If found, return the first object
	if(count_like_php5($userTrips) > 0) {

		return [false, $userTrips[0]];
	}

	// Add new trip
	return [true, addNewTrip($flight, $extTripItId, $extTripName)];
}

function addNewTrip($flight, $extTripItId="", $extTripName="") {

	$userTrip = new ParseObject("UserTrips");
	$userTrip->set('firstFlightDepartureTimestamp', $flight->get('departure')->getLastKnownTimestamp()); 
	$userTrip->set('firstFlightDepartureTimezone', $flight->get('departure')->getAirportInfo()["airportTimezone"]);
	$userTrip->set('firstFlightDepartureAirportIataCode', $flight->get('departure')->getAirportInfo()["airportIataCode"]);

	$userTrip->set('lastFlightArrivalTimezone', $flight->get('arrival')->getAirportInfo()["airportTimezone"]);
	$userTrip->set('lastFlightArrivalAirportIataCode', $flight->get('arrival')->getAirportInfo()["airportIataCode"]);
	$userTrip->set('lastFlightArrivalTimestamp', $flight->get('arrival')->getLastKnownTimestamp()); 

	$userTrip->set('user', $GLOBALS['user']);
	$userTrip->set('isActive', true);

	// If TripIt trip being added
	if(!empty($extTripItId)) {

		$userTrip->set('extTripItId', $extTripItId);
	}

	if(!empty($extTripName)) {

		$userTrip->set('tripName', $extTripName);
	}
	else {

		$userTrip->set('tripName', $flight->getTripName());
	}

	$userTrip->save();

	return $userTrip;
}

function generateFlightUniqueId($departureAirportIataCode, $arrivalAirportIataCode, $airlineIataCode, $flightNum, $flightYear, $flightMonth, $flightDate) {

	return ($departureAirportIataCode . '_' 
	. $arrivalAirportIataCode . '__'
	. $airlineIataCode . '_'
	. $flightNum . '__'
	. $flightYear . '_' 
	. $flightMonth . '_' 
	. $flightDate);
}

// Trip Details
function fetchTripItFlightInfo($authenticatedTripIt, $tripId) {
	
	$response = $authenticatedTripIt->list_air($tripId);
	
	// Check if the response is valid JSON response
	try {

		validateTripitResponse($response);
	}
	catch (Exception $ex) {

		throw new Exception($ex->getMessage());
	}

	$airObject = json_decode($response, true);
	
	$conciseAirObject = array();
	$i = 0;
	
	$arrayToParse = array();
	if(isset($airObject["AirObject"]["Segment"])) {
		
		// If only one segment is found without subarrays
		if(isset($airObject["AirObject"]["Segment"]["Status"])) {
			
			$arrayToParse[] = $airObject["AirObject"]["Segment"];
		}
		else {
			
			$arrayToParse = $airObject["AirObject"]["Segment"];
		}
	}
	else if(isset($airObject["AirObject"][0]["Segment"])) {
		
		foreach($airObject["AirObject"] as $airObjectElement) {
			
			// If only one segment is found without subarrays
			if(isset($airObjectElement["Segment"]["Status"])) {
				
				$arrayToParse[] = $airObjectElement["Segment"];
			}
			else {
				
				foreach($airObjectElement["Segment"] as $segment) {
					
					$arrayToParse[] = $segment;
				}
			}
		}
	}
	else {
		
		// no exit
		json_error("AS_105", "", "There are no flights in this TripIt Trip. -" . $tripId . " - " . json_encode($airObject) . " - user: " . $GLOBALS['user']->getObjectId(), 3, 1);

		return [];
	}
	
	$currentTimezone = date_default_timezone_get();

	foreach($arrayToParse as $segment) {
		
		//$conciseAirObject[$i]["internal_flight_id"] = getFlightId($segment["marketing_airline_code"], $segment["marketing_flight_number"], $segment["StartDateTime"]);
		// $conciseAirObject[$i]["EndDateTime"] = $segment["EndDateTime"];
		// $conciseAirObject[$i]["start_city_name"] = $segment["start_city_name"];
		// $conciseAirObject[$i]["end_city_name"] = $segment["end_city_name"];
		// $conciseAirObject[$i]["airline_name"] = $segment["marketing_airline"];
		// $conciseAirObject[$i]["seat_id"] = isset($segment["seats"]) ? $segment["seats"] : "";

		if(!empty($segment["StartDateTime"]["timezone"])
			&& !empty($segment["StartDateTime"]["date"])
			&& isset($segment["start_airport_code"])
			&& !empty($segment["start_airport_code"])
			&& isset($segment["end_airport_code"])
			&& !empty($segment["end_airport_code"])
			&& isset($segment["marketing_airline_code"])
			&& !empty($segment["marketing_airline_code"])
			&& isset($segment["marketing_flight_number"])
			&& !empty($segment["marketing_flight_number"])) {

			// Set Airport Timezone
			date_default_timezone_set($segment["StartDateTime"]["timezone"]);

			// $timestamp = strtotime($segment["StartDateTime"]["date"] . " " . $segment["StartDateTime"]["time"]);
			$timestamp = strtotime($segment["StartDateTime"]["date"]);

			// echo($timestamp);exit;
			// $dateAttributes = explode("-", $segment["StartDateTime"]["date"]);

			if(isFlightTimeInFuture($timestamp)) {

				$conciseAirObject[$i] = generateFlightUniqueId($segment["start_airport_code"],
																$segment["end_airport_code"],
																$segment["marketing_airline_code"],
																$segment["marketing_flight_number"],
																date("Y", $timestamp),
																date("n", $timestamp),
																date("j", $timestamp)
										);
			}
		}
		
		$i++;
	}

	// Set Default Timezone
	date_default_timezone_set($currentTimezone);

	return $conciseAirObject;	
}

function addNewFlightTrip($flightInsert, $userTrip) {

	$flightId = $flightInsert->get('uniqueId');

	// if off schedule marker exists
	if(!empty(getFlightOffScheduleMarker($flightId))) {

		// generate a duplicate counter to ensure only one message goes on schedule to avoid race condition
		$duplicateCounter = getFlightOffScheduleMarkerDuplicateCounter($flightId);

		// If the increment created a value of 1
		if($duplicateCounter == 1) {

			$callbackId = saveFlightNotifyToCacheFlightEvent($flightId, $flightInsert->get('lastKownArrivalTimestamp'), 'checkpoints', 'offschedule_putback', ["scheduled" => time(), "displayTime" => date("Y-m-d H:i:s T", time()), "delay" => 0, "completed" => ""]);

			// put status check back on schedule
			$response = putStatusCheckMessageOnQueue($flightId, time(), 'offschedule-putback', $callbackId, 0);

			if(is_array($response)) {

				return $response;
			}

			// Delete marker
			delFlightOffScheduleMarker($flightId);
		}
	}

	$flightTripsInsertRow = new ParseObject("FlightTrips");
	$flightTripsInsertRow->set('flight', $flightInsert);
	$flightTripsInsertRow->set('userTrip', $userTrip); 
	$flightTripsInsertRow->set('user', $GLOBALS['user']);
	$flightTripsInsertRow->set('isActive', true); 
	$flightTripsInsertRow->save();

	return $flightTripsInsertRow;
}

function validateTripitResponse($response) {

	$error_code = "";
	$error_desc_code = "";
	$error_desc = "";
	
	if(!is_array($response)) {

		$tripit_error = array();
		$tripit_error_index = array();
		
		$p = xml_parser_create();
		xml_parse_into_struct($p, $response, $tripit_error, $tripit_error_index);
		xml_parser_free($p);
		
		// It wasn't XML
		if(count_like_php5($tripit_error)==0) {

			// Try its JSON
			$tripit_error = json_decode($response, true);
			
			if(isset($tripit_error["Error"])) {
			
				$error_code = isset($tripit_error["Error"]["code"]) ? $tripit_error["Error"]["code"] : "";
				$error_desc_code = isset($tripit_error["Error"]["detailed_error_code"]) ? $tripit_error["Error"]["detailed_error_code"] : "";
				$error_desc = isset($tripit_error["Error"]["description"]) ? $tripit_error["Error"]["description"] : "";
			}
		}
		else {
			
			foreach($tripit_error as $response_item) {
				
				if(strtoupper($response_item["tag"]) == "CODE") {
					
					$error_code = $response_item["value"];
				}
				else if(strtoupper($response_item["tag"]) == "DETAILED_ERROR_CODE") {
					
					$error_desc_code = $response_item["value"];
				}
				else if(strtoupper($response_item["tag"]) == "DESCRIPTION") {
					
					$error_desc = $response_item["value"];
				}
			}
		}
	}
	
	if(!empty($error_code)) {

		// Token is not longer valid
		if($error_code == 401) {
			
			revokeTripItAccess();

			throw new Exception (json_encode(json_error_return_array("AS_106", "AtYourGate's access to your TripIt account is no longer valid. Please reauthorize by tapping Change TripIt Account.", "Invalid TripIt token found in DB. TripIt Response dump: " . json_encode($response), 2)));
		}
		
		// Trip not found
		else if($error_code == 404) {
			
			throw new Exception (json_encode(json_error_return_array("AS_109", "Trip is no longer available from TripIt. Please resync with TripIt.", "Invalid TripIt token found in DB. TripIt Response dump: " . json_encode($response), 2)));
		}
		else {
			
			throw new Exception (json_encode(json_error_return_array("AS_110", "", "TripIt Error Code: " . $error_code . ", Error Desc Code: " . $error_desc_code . ", Desc: " . $error_desc . ", Response dump: " . json_encode($response), 2)));
		}
	}

	return true;
}

function revokeTripItAccess($user="") {
	
	if(empty($user)) {

		$user = $GLOBALS['user'];
	}

	// Find user's trips
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $user, "__E__extTripItId" => "", "isActive" => true], $userTripsRefObject);

	$flightTrips = parseExecuteQuery(["__MATCHESQUERY__userTrip" => $userTripsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);


	if(count_like_php5($flightTrips) > 0) {

		foreach($flightTrips as $flightTrip) {

			// Delete FlightTrips and UserTrips
			deleteTrip($flightTrip);
		}
	}
	
	// Delete TripIt token
	$tripItTokens = parseExecuteQuery(["user" => $user, "isActive" => true], "TripItTokens");
	
	foreach($tripItTokens as $token) {

		$token->set("isActive", false);
		$token->save();
	}
}

function fetchTripItTrips($oauthCredential="") {
	
	if(empty($oauthCredential)) {

		$oauthCredential = connectToTripIt();
	}

	// Connect to TripIt
	$tripIt = new TripIt($oauthCredential);
	$response = $tripIt->list_trip();

	// Check if the response is valid JSON response
	try {

		validateTripitResponse($response);
	}
	catch (Exception $ex) {

		throw new Exception($ex->getMessage());
	}

	// Find list of Trips
	$tripItObject = json_decode($response, true);

	$conciseTripObject = array();
	$tripList = array();
	
	if(isset($tripItObject["Trip"][0])) {
		
		$tripList = $tripItObject["Trip"];
	}
	else if(isset($tripItObject["Trip"])) {
		
		$tripList[0] = $tripItObject["Trip"];
	}
	else {
		
		throw new Exception (json_encode(json_error_return_array("AS_104", "You currently have no TripIt trips. Please add new trips via TripIt.", "No valid index found in the TripIt response: " . json_encode($tripItObject), 3)));
	}
	////////////////////////////////////////////////////////////
	
	///////////////////////////////////////////////////////////////////////////
	// Get Flight Trips that are from TripIt before we add any new ones

	// Find user's trips
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "__E__extTripItId" => "", "isActive" => true], $userTripsRefObject);

	// Find flights's that haven't landed or landed within 1 hour
	$flightsRefObject = new ParseQuery("Flights");
	$flightsAssociation = parseSetupQueryParams(["__GTE__lastKnownArrivalTimestamp" => time()-60*60], $flightsRefObject);

	// Check if the user has this flight already in their trips
	$existingFlightTripsWithTripItFlights = parseExecuteQuery(["__MATCHESQUERY__userTrip" => $userTripsAssociation, "__MATCHESQUERY__flight" => $flightsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);
	////////////////////////////////////////////////////////////////////////////

	// Process Trips to find flights and then add
	$i = 0;
	$flightIdListMaster = [];
	foreach($tripList as $trip) {
		
		if(isset($trip["id"])
			&& !empty($trip["id"])) {

			// Generate the list of all flights in TripIt
			try {

				$flightIdList = fetchTripItFlightInfo($tripIt, $trip["id"]);
			}
			catch (Exception $ex) {

				// no exit
				json_error("AS_105", "", "Trip details not found, id=" . $trip["id"] . ", user=" . $GLOBALS['user']->getObjectId() . " " . $ex->getMessage(), 3, 1);
				continue;
			}

			// Find and add flights
			foreach($flightIdList as $flightId) {

				// Create a Flight Id and TripIt pairing
				$flightIdListMaster[$flightId][] = $trip["id"];

				$flight = updateStatusOfFlight($flightId, false, true, "", false);

				// Incorrect object returned
				if(is_array($flight)
					&& isset($flight["error_code"])) {

					// non exit
					json_error($flight["error_code"], "", $flight["error_message_log"] . " - Call from TripIt fetch", 3, 1);
					continue;
				}

				$newFlightAddedFlag = false;

				// Do we need to add a new flight?
				$flightInsertRow = fetchFlightObject($flightId);

				if(count_like_php5($flightInsertRow) == 0) {

					// Add flight
					list($newFlightAddedFlag, $flightInsertRow) = addNewFlight($flight);
				}

				// flight row didn't insert
				if(is_array($flightInsertRow)
					&& isset($flightInsertRow["error_code"])) {

					// non exit
					json_error($flightInsertRow["error_code"], "", $flightInsertRow["error_message_log"] . " - Call from TripIt fetch", 3, 1);
					continue;
					// return $flightInsertRow;
				}

				// Add TripIt Trip
				$tripName = isset($trip["display_name"]) ? $trip["display_name"] : "";
				list($tripAddedFlag, $userTrip) = addNewTripForTripIt($flight, $trip["id"], $tripName);

				// If not a new flight,
				// Or if trip name has changed, then update triptimestamps if needed
				if($tripAddedFlag == false
					|| (!empty($tripName) && strcasecmp($userTrip->get('tripName'), $tripName)!=0)) {

					updateUserTripTimestamps($userTrip, $flight, $tripName);
				}

				// Verify if a new row in FlightTrips required
				$flightTrips = parseExecuteQuery(["flight" => $flightInsertRow, "userTrip" => $userTrip, "isActive" => true], "FlightTrips");

				if(count_like_php5($flightTrips) == 0) {

					addNewFlightTrip($flightInsertRow, $userTrip);
				}

				if($newFlightAddedFlag == true) {

					// Add to Worker Queue for new flight processing
					// This is done after user trips are added
					$response = addToWorkerQueueNewFlight($flightId);

					if(isset($response["error_code"])) {

						json_error($response["error_code"], "", $response["error_message_log"] . " - Call from TripIt fetch", 3, 1);
						continue;
						// return $response;
					}
				}
			}
		}

		$i++;
	}

	// Delete any flights that are still active but no longer in TripIt
	foreach($existingFlightTripsWithTripItFlights as $flightTrip) {

		$uniqueId = $flightTrip->get("flight")->get("uniqueId");
		$tripItId = $flightTrip->get("userTrip")->get("extTripItId");

		// If TripIt userTrip
		if(!empty($tripItId)) {

			// TripIt Flight no longer in TripIt
			// Delete it
			if(!in_array($uniqueId, array_keys($flightIdListMaster))) {

				$flightTrip->set("isActive", false);
				$flightTrip->save();
			}
			// If the flight was found, but check if it now belongs to another trip
			else {

				// TripIt Trip and Flight connection no longer valid
				if(!in_array($tripItId, $flightIdListMaster[$uniqueId])) {

					$flightTrip->set("isActive", false);
					$flightTrip->save();
				}
			}
		}
	}

	// Update or delete userTrip details
	// Find user's trips that have extTripItId, i.e. are TripIt trips
	$userTripsForTripIt = parseExecuteQuery(["user" => $GLOBALS['user'], "__E__extTripItId" => "", "isActive" => true], "UserTrips");

	foreach($userTripsForTripIt as $userTrip) {

		// Initialize
		$updateFlag = false;
		resetUserTripTimestamps($userTrip);

		$flightTrips = parseExecuteQuery(["userTrip" => $userTrip, "isActive" => true], "FlightTrips", "", "", ["flight"]);

		// If no active flights were found, mark this userTrip as inactive
		if(count_like_php5($flightTrips) == 0) {

			$updateFlag = true;
			$userTrip->set("isActive", false);
		}

		else {

			// Iterate through all flights and find the first and last trip timestamps
			foreach($flightTrips as $flightTrip) {

				// Skip past flights
				// TODO: If a very old flight was here, then we lose the its info in the userTrip row
				// i.e. a flight was added a while back, then after it had landed, we made changes to TripIt trip
				// Now, that we are updating the tripIt trip, and we can't find the flight class object, then we lose the ability to use it to update the userTrip details 
				// if($flightTrip->get("flight")->get("lastKnownArrivalTimestamp") < time()) {

				// 	continue;
				// }

				// Initialize
				$flightFound = true;

				$flightId = $flightTrip->get("flight")->get("uniqueId");

				// Get flight object from cache
				$flight = getFlightInfoCache($flightId);

				// If no flight was found in the cache
				if(empty($flight)) {

					if(!empty($flightTrip->get("flight")->get("flightCacheObject"))) {

						try {

							$flight = unserialize(json_decode($flightTrip->get("flight")->get("flightCacheObject")));
						}
						catch (Exception $ex) {

							$flight = "";
						}
					}
					
					if(empty($flight)) {

						// Get/rebuild flight object
						$flight = updateStatusOfFlight($flightId, true, false, "", true, false);

						// We are unable to find details of this flight
						if(is_array($flight)) {

							$flightFound = false;
						}
					}
				}

				// If flight was found
				if($flightFound == true) {

					// This call doesn't save the userTrip, but simply updates the object
					updateUserTripTimestamps($userTrip, $flight, "", false);

					$updateFlag = true;
				}
			}
		}

		if($updateFlag == true) {

			// Save userTrip after all the changes
			$userTrip->save();
		}
	}
	////////////////////////////////////////////////////////////
}

function getTripItToken() {

	// Check if we already have the Access Token
	$tripItTokens = parseExecuteQuery(array("user" => $GLOBALS['user'], "isActive" => true), "TripItTokens");
	
	// If Access token found, then return authorization
	if(count_like_php5($tripItTokens) == 0) {

		return [];		
	}

	return ["oauthAccessToken" => $tripItTokens[0]->get('oauthAccessToken'), 
			"oauthAccessTokenSecret" => decryptStringInMotion($tripItTokens[0]->get('oauthAccessTokenSecretEncrypted'))];	
}

function connectToTripIt($tripItToken="") {

	if(empty($tripItToken)) {

		$tripItToken = getTripItToken();

		if(count_like_php5($tripItToken) == 0) {

			json_error("AS_102", "You must first authorize AtYourGate to allow access to your TripIt account.", "No Authorized User found! No TripIt tokens found for this user.");
		}
	}

	$oauthCredential = new OAuthConsumerCredential(
							$GLOBALS['env_TripITOAuthConsumerKey'], 
							$GLOBALS['env_TripITOAuthConsumerSecret'], 
							$tripItToken["oauthAccessToken"],
							$tripItToken["oauthAccessTokenSecret"]
						);

	return $oauthCredential;
}

function isFlightTimeInFuture($timestamp) {

	return ($timestamp > time() ? true : false);
}

function addToWorkerQueueNewFlight($flightId) {
	
	// Put new flight addition message on queue
	// Connect to Worker Queue
	try {

        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

		$workerQueue->sendMessage(
				array("action" => "flight_new_addition", 
					  "content" => 
					  	array(
					  		"flightId" => $flightId
			  			)
					)
				);
	}
	catch (Exception $ex) {

		return json_decode($ex->getMessage(), true);
	}

	/*
	$GLOBALS['sqs_client'] = getSQSClientObject();

	// Add to SQS to add to FlightPending Info
	$response = SQSSendMessage($GLOBALS['sqs_client'], $GLOBALS['env_workerQueueConsumerName'], 
			array("action" => "flight_new_addition", 
				  "content" => 
				  	array(
				  		"flightId" => $flightId
		  			)
				)
			);

	return $response;
	*/
}

function putStatusCheckMessageOnQueue($flightId, $processAfterTimestamp, $typeOfSchedule, $callbackId, $getWaitTimeForQueue) {

	try {

		// $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
		$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueFlightConsumerName']);
		$workerQueue->sendMessage(
				array("action" => "flight_status_check", 
					  "processAfter" => ["timestamp" => $processAfterTimestamp],
					  "content" => 
					  	array(
					  		"flightId" => $flightId,
					  		"typeOfSchedule" => $typeOfSchedule,
					  		"callbackId" => $callbackId,
					  	)
					),
					$getWaitTimeForQueue
				);
	}
	catch (Exception $ex) {

		// Json array formatted
		return json_error_return_array("AS_1069", "", "Flight status back on queue failed flightId = " . $flightId . " - " . $ex->getMessage(), 2);
		// return json_decode($ex->getMessage(), true);
	}

	return "";
}

function saveFlightNotifyToCacheFlightEvent($flightId, $lastKownArrivalTimestamp, $type, $typeOfNotify, $message, $callbackId="") {

	$callTimestamp = time();
	$flightNotifyTracker = getFlightNotifyTrackerCache($flightId);

	// if(empty($flightNotifyTracker)) {

	// 	$flightNotifyTracker = [];
	// }

	if(strcasecmp($type, 'notifications')==0) {

		$flightNotifyTracker[$type][$typeOfNotify][$callTimestamp] = $message;
	}
	else if(strcasecmp($type, 'validations')==0) {

		$flightNotifyTracker[$type][$typeOfNotify][$callTimestamp] = $message;
	}
	else if(strcasecmp($type, 'api-call')==0) {

		$flightNotifyTracker[$type][$typeOfNotify][$callTimestamp] = $message;
	}
	else if(strcasecmp($type, 'reminders')==0) {

		$flightNotifyTracker[$type][$typeOfNotify][$callTimestamp] = $message;
	}
	else if(strcasecmp($type, 'checkpoints')==0) {

		// type 			= checkpoints
		// typeOfNotify 	= threeHoursBefore, etc... landed - close out
		// message 			= [scheduled => processAfterTimestamp, delay => Queue delay, completed => when the message was pickedup]

		$flightNotifyTracker[$type][$typeOfNotify][$callTimestamp] = $message;

		// If landed, then store in the DB
		if(strcasecmp($typeOfNotify, 'landed')==0) {

			// Find flight record
			$flightObject = parseExecuteQuery(["uniqueId" => $flightId], "Flights", "", "", [], 1);

			if(count_like_php5($flightObject) > 0) {

				// Update tracker info
				// $flightUpdate = new ParseObject("Flights", $flightObject->getObjectId());
				$flightObject->set('notifyTracker', json_encode($flightNotifyTracker));
				$flightObject->save();
			}
			else {

				json_error("AS_3006", "", "Flight Tracker (" . $flightId . ") not stored in DB", 2, 1);
			}
		}
	}
	else if(strcasecmp($type, 'checkpoints_complete')==0) {

		// type 			= override type to => checkpoints
		// typeOfNotify 	= threeHoursBefore, etc... landed - close out
		// callbackId		= Original callTimestamp
		// message 			= timestamp of completion

		$flightNotifyTracker['checkpoints'][$typeOfNotify][$callbackId]['completed'] = $message;
	}

	setFlightNotifyTrackerCache($flightId, $flightNotifyTracker, ($lastKownArrivalTimestamp+(7*24*60*60)-time()));

	return $callTimestamp;
}

function getFlightInfoFromCacheOrAPI($flightId) {

	// Get flight's information cache
	$flight = getFlightInfoCache($flightId);
	// If cache is not found, fetch flight info again via uniqueId
	if(empty($flight)) {

		$flight = updateStatusOfFlight($flightId);

		if(is_array($flight)) {

			throw new Exception (json_encode(json_error_return_array($flight["error_code"], "", $flight["error_message_log"], $flight["error_severity"])));
		}

		if(empty($flight)) {

			throw new Exception (json_encode(json_error_return_array("AS_201", "", "Flight not found. FlightId = " . $flightId, 3)));
		}

		// Force status reevalution (this occurs when cache is cleared for an existing flight and if the flight is not longer in the message queue, e.g. departed or cancelled but still shows up in the same day list)
		updateFlightDetails($flight);
	}

	return $flight;
}

function formatFetchFlightObject($flightObjects) {

	$responseArray = [];
	foreach($flightObjects as $index => $flight) {

		$responseArray[$index]["info"] = $flight->get("info")->getAirlineInfo();
		$responseArray[$index]["info"]["uniqueId"] = $flight->get("info")->get("uniqueId");
		$responseArray[$index]["info"]["flightNum"] = $flight->get("info")->get("flightNum");
		$responseArray[$index]["info"]["statusDescription"] = $flight->get("info")->get("statusDescription");
		$responseArray[$index]["info"]["statusInterpreted"] = $flight->get("info")->get("statusInterpreted");

		// Departure
		$airport = $flight->get("departure")->getAirportInfo();
		$responseArray[$index]["departure"] = $airport;
		$responseArray[$index]["departure"]["flightTime"] = $flight->get("departure")->get("flightTime");
		$responseArray[$index]["departure"]["flightDate"] = $flight->get("departure")->get("flightDate");
		$responseArray[$index]["departure"]["delayMinutes"] = $flight->get("departure")->get("gateDelayMinutes");
		$responseArray[$index]["departure"]["earlyMinutes"] = $flight->get("departure")->get("gateEarlyMinutes");
		$responseArray[$index]["departure"]["lastKnownTimestamp"] = $flight->get("departure")->getLastKnownTimestamp();

		// Arrival
		$airport = $flight->get("arrival")->getAirportInfo();
		$responseArray[$index]["arrival"] = $airport;
		$responseArray[$index]["arrival"]["flightTime"] = $flight->get("arrival")->get("flightTime");
		$responseArray[$index]["arrival"]["flightDate"] = $flight->get("arrival")->get("flightDate");
		$responseArray[$index]["arrival"]["delayMinutes"] = $flight->get("arrival")->get("gateDelayMinutes");
		$responseArray[$index]["arrival"]["earlyMinutes"] = $flight->get("arrival")->get("gateEarlyMinutes");
		$responseArray[$index]["arrival"]["lastKnownTimestamp"] = $flight->get("departure")->getLastKnownTimestamp();

		// Check if this flight is not from past
		if(isFlightTimeInFuture($flight->get('departure')->getLastKnownTimestamp())) {

			$responseArray[$index]["info"]["hasAlreadyDeparted"] = false;
		}
		else {

			$responseArray[$index]["info"]["hasAlreadyDeparted"] = true;
		}
	}

	return $responseArray;	
}

function getFlightIdForRequestedFullfillmentTime($user, $requestedFullFillmentTimestamp, $deliveryAirportIataCode) {

    $currentTimeZone = date_default_timezone_get();
    $airportTimeZone = '';
    if(!empty($deliveryAirportIataCode)) {

        $airportTimeZone = fetchAirportTimeZone($deliveryAirportIataCode, $currentTimeZone);
        if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

            // Set Airport Timezone
            date_default_timezone_set($airportTimeZone);
        }
    }

    $date = date("Y-m-d", $requestedFullFillmentTimestamp);
    $midnightTimestampForTheDayOfOrderFullfillment = strtotime($date . " 11:59:59 PM");

    // If less than 6 hours to midnight
    if(($midnightTimestampForTheDayOfOrderFullfillment-$requestedFullFillmentTimestamp) < 6*60*60) {

    	// Then target next 6 hours to find a flight
    	$targetTimestampRange = ($midnightTimestampForTheDayOfOrderFullfillment-$requestedFullFillmentTimestamp) + 6*60*60;
    }
    else {

    	// Else use midnight
    	$targetTimestampRange = $midnightTimestampForTheDayOfOrderFullfillment;
    }

    if(!empty($airportIataCode) 
        && strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

        date_default_timezone_set($currentTimeZone);
    }

	$flightRefObjectDeparture = new ParseQuery("Flights");
	$flightsBeforeDeparture = parseSetupQueryParams(["__GTE__lastKnownDepartureTimestamp" => $requestedFullFillmentTimestamp, "__LTE__lastKnownDepartureTimestamp" => $targetTimestampRange, 'departureAirportIataCode' => $deliveryAirportIataCode], $flightRefObjectDeparture);

	// Find user's trips
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $user, "isActive" => true], $userTripsRefObject);

	$flightTrips = parseExecuteQuery(["__MATCHESQUERY__flight" => $flightsBeforeDeparture, "__MATCHESQUERY__userTrip" => $userTripsAssociation, "isActive" => true], "FlightTrips", "createdAt", "", ["userTrip", "flight"]);

	// If there more than 1 flight (not a real world possibility), 
	// then find the next flight by the earliest departure timestamp
	$earliestDepartureTimestamp = 0;
	$flightTrip = [];
	$nextFlightId = "";
	if(count_like_php5($flightTrips) > 0) {

		foreach($flightTrips as $flightTrip) {

			if($earliestDepartureTimestamp > $flightTrip->get('flight')->get('lastKnownDepartureTimestamp')
				|| $earliestDepartureTimestamp == 0) {

				$earliestDepartureTimestamp = $flightTrip->get('flight')->get('lastKnownDepartureTimestamp');
				$nextFlightId = $flightTrip->get('flight')->get('uniqueId');
			}
		}
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////	

	return [$flightTrip, $nextFlightId];
}

?>
