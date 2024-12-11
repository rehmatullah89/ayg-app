<?php
$allowedOrigins = [
	"http://ayg-deb.test",
	"https://ayg.ssasoft.com",
	"http://ec2-18-116-237-65.us-east-2.compute.amazonaws.com",
    "http://ec2-18-190-155-186.us-east-2.compute.amazonaws.com", // test
    "https://order.atyourgate.com", // prod
];

if (isset($_SERVER["HTTP_REFERER"]) && in_array(trim($_SERVER["HTTP_REFERER"],'/'), $allowedOrigins)) {
	header("Access-Control-Allow-Origin: " . trim($_SERVER["HTTP_REFERER"],'/'));
}

require 'dirpath.php';
require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;


// Get Flight Schedule Details with to & from airport
$app->get('/flight/search/a/:apikey/e/:epoch/u/:sessionToken/airlineIataCode/:airlineIataCode/fromAirportIataCode/:fromAirportIataCode/toAirportIataCode/:toAirportIataCode/flightYear/:flightYear/flightMonth/:flightMonth/flightDate/:flightDate', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $airlineIataCode, $fromAirportIataCode, $toAirportIataCode, $flightYear, $flightMonth, $flightDate) {

	// Check if already have cache for this
	getRouteCache();

	$flightYear = intval($flightYear);
	$flightMonth = intval($flightMonth);
	$flightDate = intval($flightDate);
	$responseArray = [];
	$resultFromFlightAware = [];

	// Fetch Flight Aware cached results
	$flightAwareCacheKey = $airlineIataCode . '-' . $fromAirportIataCode . '-' . $toAirportIataCode . '-' . $flightYear . '-' . $flightMonth . '-' . $flightDate;
	$uniqueFlightsByDepartureTime = getFlightSearchFromFlightAware($flightAwareCacheKey);
	// $uniqueFlightsByDepartureTime = '';

	// If no cache was found, search in FlightAware
	if(empty($uniqueFlightsByDepartureTime)) {

		$resultFromFlightAware = '';

		/////////// Search for flights in FlightAware //////////////
		$client = new nusoap_client($GLOBALS['env_FlightAwareAPIURLPrefix_Status'] . "/wsdl",'wsdl');
		$client->setCredentials($GLOBALS['env_FlightAwareUsername'], $GLOBALS['env_FlightAwareAppKey']);

		if ($client->getError()) {

			json_error("AS_216", "", "Soap Constructor Error:" . $client->getError(), 1);
		}

		// set max size
		$params = array(
					"max_size" => 1000,
					);

		try {

			$resultFromFlightAware = $client->call("SetMaximumResultSize", $params);
		}
		catch (Exception $ex) {

			json_error("AS_216", "", "Soap Call Failed: " . $ex->getMessage(), 1);
		}

		// get ICAO code for the airline
		$airline = getAirlineByIataCode($airlineIataCode);

		// get ICAO codes for the airport
		$fromAirport = getAirportByIataCode($fromAirportIataCode);
		$toAirport = getAirportByIataCode($toAirportIataCode);

		if(!is_object($fromAirport)
			|| !is_object($toAirport)
			|| !is_object($airline)) {

			json_error("AS_216", "", "To or From airport's or Airline not found", 1);
		}

		$airlineIcaoCode = $airline->get('airlineIcaoCode');

		// check date format and if it is within next 12 months
		if(!checkdate($flightMonth, $flightDate, $flightYear)) {

			json_error("AS_203", "Departure date is not correct.", "Invalid Departure Date. Must be a valid YYYY MM DD. Parameters used: $flightMonth $flightDate $flightYear", 2);
		}

		$fromAirportIcaoCode = $fromAirport->get("airportIcaoCode");
		$fromAirportTimezone = $fromAirport->get("airportTimezone");
		$toAirportIcaoCode = $toAirport->get("airportIcaoCode");

		$currentTimezone = date_default_timezone_get();

		// set from airport timezone
		date_default_timezone_set($fromAirportTimezone);

		$flightTimeStamp = mktime(0, 0, 0, $flightMonth, $flightDate, $flightYear);
		$plus11MonthsTimeStamp = strtotime("+11 months midnight");
		
		// if date more than 1 year away
		if($flightTimeStamp > $plus11MonthsTimeStamp) {

			json_error("AS_203", "Departure date must be less 11 months from today.", "Departure date more than 11 months away. Parameters used: $flightMonth $flightDate $flightYear", 2);
		}

		// get the flight list by to and from airportto
		$params = array(
					"startDate" => mktime(0, 0, 0, $flightMonth, $flightDate, $flightYear),
					"endDate" => mktime(23, 59, 59, $flightMonth, $flightDate, $flightYear),
					// "endDate" => mktime(23, 59, 59, date('n', strtotime($flightYear . '-' . $flightMonth . '-'. $flightDate . '+1')), $flightDate, $flightYear),
					"origin" => $fromAirportIcaoCode,
					"destination" => $toAirportIcaoCode,
					"airline" => $airlineIataCode,
					"howMany" => 1000,
					"offset" => 0
					);

		// set default timezone
		date_default_timezone_set($currentTimezone);

		try {

			$resultFromFlightAware = $client->call("AirlineFlightSchedules", $params);
		}
		catch (Exception $ex) {

			json_error("AS_216", "", "Soap Call Failed: " . $ex->getMessage(), 1);
		}

		if($client->fault) {

			json_error("AS_216", "", "Soap Fault: ". $client->faultcode . "(" . $client->faultstring . ")", 1);
		}
		else if($client->getError()) {

			json_error("AS_216", "", "Soap Error: " . $client->getError(), 1);
		}
		////////////////////////////////////////////////////////////

		////////////////////////////////////////////////////////////
		$responseArray = [];
		if(isset($resultFromFlightAware["AirlineFlightSchedulesResult"]["data"])) {

			// De-duplicate the flight list (codeshares provide separate entries)
			$uniqueFlights = [];
			$uniqueFlightsByDepartureTime = [];

			// If only one flight was returned, refactor the array to create array inside ["data"]
			if(!isset($resultFromFlightAware["AirlineFlightSchedulesResult"]["data"][0])) {

				$temp = $resultFromFlightAware["AirlineFlightSchedulesResult"]["data"];
				unset($resultFromFlightAware["AirlineFlightSchedulesResult"]["data"]);
				$resultFromFlightAware["AirlineFlightSchedulesResult"]["data"][] = $temp;
			}

			foreach($resultFromFlightAware["AirlineFlightSchedulesResult"]["data"] as $flight) {

				if(is_array($flight["ident"])) {

					foreach($flight as $key => $array) {

						$flight[$key] = "";
						if(isset($array["!"])) {

							$flight[$key] = $array["!"];
						}
					}
				}

				if(!isset($flight["actual_ident"]) || empty($flight["actual_ident"])) {

					$flight["actual_ident"] = $flight["ident"];
				}

				if(!in_array($flight["actual_ident"], array_keys($uniqueFlights))) {

					$uniqueFlights[$flight["actual_ident"]] = true;

					$uniqueFlightsByDepartureTime[$flight["departuretime"]] = $flight;

					$uniqueId = generateFlightUniqueId($fromAirportIataCode,
																	$toAirportIataCode,
																	$airlineIataCode,
																	preg_replace("/[^0-9]/", "", str_replace($airlineIcaoCode, '', $flight["ident"])),
																	date("Y", $flight["departuretime"]),
																	date("n", $flight["departuretime"]),
																	date("j", $flight["departuretime"])
											);

					$uniqueFlightsByDepartureTime[$flight["departuretime"]]["uniqueId"] = $uniqueId;
				}
			}

			// sort by flight time
			ksort($uniqueFlightsByDepartureTime);
		
			setFlightSearchFromFlightAware($uniqueFlightsByDepartureTime, $flightAwareCacheKey);
		}
	}

	if(!empty($uniqueFlightsByDepartureTime)) {

		// Fetch flight objects for all flight ids identified
		$responseArray = [];
		foreach($uniqueFlightsByDepartureTime as $flightSearch) {

			$flight = updateStatusOfFlight($flightSearch["uniqueId"], false, true, "", false, true);

			// Flight not found
			if(!is_object($flight)) {

				// non-exiting error
				json_error("AS_217", "", "Flight Id lookup failed for: " . json_encode($flightSearch), 3, 1);
			}
			else {

				$responseArray = array_merge($responseArray, formatFetchFlightObject([$flight]));
			}
		}
	}

	if(count_like_php5($responseArray) == 0) {

		// Store error in array so it can be cached as route cache
		$error_array = json_error_return_array("AS_201", "No flights not found. Please check the entered details.", "Flight Aware response dump: " . json_encode($resultFromFlightAware), 3);

		// Print the error as non-exiting so it can be logged
		json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"], $error_array["error_severity"], 1);

		// prepare response array as an error response
		// This is manaully done for now because we want to cache the response when no flights are found to avoid duplicative runs and calls to Flight Aware
		$responseArray = ["error_code" => $error_array["error_code"], "error_description" => $error_array["error_message_user"]];
	}

	// JMD
	// Cache for 5 mins
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5*60
		])
	);
});

// Get Flight Schedule Details with Flight Info
$app->get('/flight/search/a/:apikey/e/:epoch/u/:sessionToken/airlineIataCode/:airlineIataCode/flightNum/:flightNum/flightYear/:flightYear/flightMonth/:flightMonth/flightDate/:flightDate', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $airlineIataCode, $flightNum, $flightYear, $flightMonth, $flightDate) {

	// Check if already have cache for this
	getRouteCache();

	$flightObjects = fetchFlights($airlineIataCode, $flightNum, $flightYear, $flightMonth, $flightDate);

	if(isset($flightObjects["error_code"])) {

		json_error($flightObjects["error_code"], $flightObjects["error_message_user"], $flightObjects["error_message_log"], $flightObjects["error_severity"]);
	}

	if(count_like_php5($flightObjects) == 0) {

		json_error("AS_201", "Flight was not found. Please check the entered information.", "Flight not found for = $airlineIataCode, $flightNum, $flightYear, $flightMonth, $flightDate", 3);
	}

	$responseArray = formatFetchFlightObject($flightObjects);

	// Cache for 5 mins
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5*60
		])
	);
});



// Get Flight Schedule Details with Flight ID
$app->get('/flight/add/a/:apikey/e/:epoch/u/:sessionToken/flightId/:flightId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $flightId) {

	$flight = getFlightInfoCache($flightId);

	if(empty($flight)) {

		// no cache found, json_error
		json_error("AS_206", "Your search session has expired, please search for your flight again.", "Flight id not found in cache during adding. FlightId = " . $flightId, 1);
	}

	// Check if this flight is not from past
	if(!isFlightTimeInFuture($flight->get('departure')->getLastKnownTimestamp())) {

		json_error("AS_211", "This flight has already departed.", "Flight already departed being added. FlightId = " . $flightId, 2);
	}

	// Evaluate flight info
	// Previously it was only evaluated if no row was found in db, e.g. $flightInsertRow count == 0
	updateFlightDetails($flight);

	$flightTripsInsertFlag = 0;
	$newFlightAddedFlag = false;

	// Check row doesn't exist
	$flightInsertRow = fetchFlightObject($flight->get('info')->get("uniqueId"));

	if(count_like_php5($flightInsertRow) == 0) {

		// Save updated object to cache - Expiry, 7 days after last known arrival
		setFlightInfoCache($flightId, $flight, ($flight->get('arrival')->getLastKnownTimestamp()-time())+(7*24*60*60));

		// Write row in Flights
		list($newFlightAddedFlag, $flightInsertRow) = addNewFlight($flight);

		if(is_array($flightInsertRow)
			&& isset($flightInsertRow["error_code"])) {

			json_error($flightInsertRow["error_code"], $flightInsertRow["error_message_user"], $flightInsertRow["error_message_log"], 1);
		}

		// Flight didn't exist in the DB, so doesn't exist in association in FlightTrips as well for ANYONE
		$flightTripsInsertFlag = 1;
	}

	else {

		// Find user's trips
		$userTripsRefObject = new ParseQuery("UserTrips");
		$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "__DNE__extTripItId" => "", "isActive" => true], $userTripsRefObject);

		// Check if the user has this flight already in their trips
		$flightTrips = parseExecuteQuery(["flight" => $flightInsertRow, "__MATCHESQUERY__userTrip" => $userTripsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);

		// Flight found in another user trip
		if(count_like_php5($flightTrips) > 0) {

			json_error("AS_207", "This flight is already added to your trips. Please view current list of Trips.", "Flight already in user's trip list. FlightId = " . $flightId . ", user = " . $GLOBALS['user']->getObjectId(), 1);
		}
	}

	/////////////////////////////////////////////////////////////////////////////////
	// Find Trips for Connections binding
	/////////////////////////////////////////////////////////////////////////////////

	// Existing arrival flights times are between Current flights Departure timestamp-5 hrs and Current flights Dep timestamp
	$flightRefObjectDeparture = new ParseQuery("Flights");
	$flightsBeforeDeparture = parseSetupQueryParams(["__GTE__lastKnownArrivalTimestamp" => $flightInsertRow->get('lastKnownDepartureTimestamp')-5*60*60, "__LTE__lastKnownArrivalTimestamp" => $flightInsertRow->get('lastKnownDepartureTimestamp'), 'arrivalAirportIataCode' => $flightInsertRow->get('departureAirportIataCode')], $flightRefObjectDeparture);

	// Existing departure flights times are between Current flights Arrival timestamp+5 hrs and Current flights Arrival timestamp
	$flightRefObjectArrival = new ParseQuery("Flights");
	$flightAfterArrival = parseSetupQueryParams(["__LTE__lastKnownDepartureTimestamp" => $flightInsertRow->get('lastKnownArrivalTimestamp')+5*60*60, "__GTE__lastKnownDepartureTimestamp" => $flightInsertRow->get('lastKnownArrivalTimestamp'), 'departureAirportIataCode' => $flightInsertRow->get('arrivalAirportIataCode')], $flightRefObjectArrival);

	// Find connecting flights
	$connectingFlights = ParseQuery::orQueries([$flightsBeforeDeparture, $flightAfterArrival]);

	// Find user's trips
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "__DNE__extTripItId" => "", "isActive" => true], $userTripsRefObject);

	$flightTrips = parseExecuteQuery(["__MATCHESQUERY__flight" => $connectingFlights, "__MATCHESQUERY__userTrip" => $userTripsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);

	// No Trips found
	if(count_like_php5($flightTrips) == 0) {

		// Insert a new UserTrips with Trip info
		// Create a new Trip for user
		$userTrip = addNewTrip($flight);

		$flightTripsInsertFlag = 1;
	}
	else {

		// Get the first FlightTrip record
		$flightTrip = $flightTrips[0];

		// Fetch user trip object
		$userTrip = $flightTrip->get('userTrip');

		// Update First Departure and/or Last Arrival timestamps for the UserTrips
		updateUserTripTimestamps($userTrip, $flight);

		/////////////////////////////////////////////////////////////////////////////////
		// Check if a new row in FlightTrips is required
		// Ensure it was marked for insertion before checking, in which case skip the check
		// Run through the FlightTrips, if this flight is found then skip
		/////////////////////////////////////////////////////////////////////////////////
		if($flightTripsInsertFlag != 1) {

			$flightFound = 0;
			for($i=0;$i<count_like_php5($flightTrips);$i++) {

				if(strcasecmp($flightTrips[$i]->get('flight')->get('uniqueId'), $flightInsertRow->get('uniqueId'))==0) {

					// Flight found
					$flightFound = 1;
					break;
				}
			}

			// Since we don't have this flight associated with this user
			// Set flag so a new row can be inserted in FlightTrips
			if($flightFound == 0) {

				$flightTripsInsertFlag = 1;
			}
		}
	}
	/////////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////////////////
	// Insert a new FlightTrips
	/////////////////////////////////////////////////////////////////////////////////
	if($flightTripsInsertFlag == 1) {

		// Associate Flight with the UserTrip (aka User)
		$flightTripsInsertRow = addNewFlightTrip($flightInsertRow, $userTrip);
	}
	/////////////////////////////////////////////////////////////////////////////////

	/////////////////////////////////////////////////////////////////////////////////
	// Insert a new FlightTrips
	/////////////////////////////////////////////////////////////////////////////////
	if($newFlightAddedFlag == true) {

		// Add to Worker Queue for new flight processing
		$response = addToWorkerQueueNewFlight($flightId);

		if(is_array($response)) {

			json_error($response["error_code"], "", $response["error_message_log"] . " Add flight pending info rows failed - " . $flightInsertRow->getObjectId(), 1);
		}
	}

    // Log user event
	// JMD
    if($GLOBALS['env_LogUserActions']) {

        try {

			//////////////////
			// actionForRetailerAirportIataCode
			$departureAirport = getAirportByIataCode($flightInsertRow->get('departureAirportIataCode'));
			$arrivalAirport = getAirportByIataCode($flightInsertRow->get('arrivalAirportIataCode'));

			$airportIataCode = "";
			$actionForRetailerAirportIataCode = "";
			if(!empty($departureAirport) && $departureAirport->get('isReady')==true) {

				$actionForRetailerAirportIataCode = $flightInsertRow->get('departureAirportIataCode');
				$airportIataCode = $flightInsertRow->get('departureAirportIataCode');
			}
			else if(!empty($arrivalAirport) && $arrivalAirport->get('isReady')==true) {

				$actionForRetailerAirportIataCode = $flightInsertRow->get('arrivalAirportIataCode');
			}
			//////////////////

            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

            $workerQueue->sendMessage(
                    array("action" => "log_user_action_add_flight",
                          "content" =>
                            array(
                                "objectId" => $GLOBALS['user']->getObjectId(),
                                "data" => json_encode(["flight" => $flightId, "actionForRetailerAirportIataCode" => $actionForRetailerAirportIataCode, "airportIataCode" => $airportIataCode]),
                                "timestamp" => time()
                            )
                        )
                    );
        }
        catch (Exception $ex) {

            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", "Log user action queue message failed " . $response["error_message_log"], 1, 1);
        }
    }

	$responseArray = ["tripId" => $userTrip->getObjectId()];

	json_echo(
		 json_encode($responseArray)
	);
});

// Get Flight Schedule Details with Flight Id
$app->get('/flight/a/:apikey/e/:epoch/u/:sessionToken/flightId/:flightId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $flightId) {

	// Check if this flight belongs to the user

	// Find user's flights for the provided trip
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "isActive" => true], $userTripsRefObject);

	// Find the flight record by Id
	$flightRefObject = new ParseQuery("Flights");
	$flightById = parseSetupQueryParams(["uniqueId" => $flightId], $flightRefObject);

	// Pull only one record back
	$flightTrip = parseExecuteQuery(["__MATCHESQUERY__userTrip" => $userTripsAssociation, "__MATCHESQUERY__flight" => $flightById, "isActive" => true], "FlightTrips", "", "", ["userTrip"], 1);

	if(count_like_php5($flightTrip) == 0
		|| count_like_php5($flightTrip) > 1) {

		json_error("AS_201", "Flight not found", "Flight not found. FlightId = " . $flightId . ", user = " . $GLOBALS['user']->getObjectId(), 3);
	}
	
	try {

		$flight = getFlightInfoFromCacheOrAPI($flightId);
	}
	catch(Exception $ex) {

		$error_array = json_decode($ex->getMessage(), true);
		json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Flight Status check failed - ", $error_array["error_severity"]);
	}

	/*
	$flight = getFlightInfoCache($flightId);

	// If cache is not found, fetch flight info again via uniqueId
	if(empty($flight)) {

		$flight = updateStatusOfFlight($flightId);

		if(is_array($flight["error_code"])) {

			json_error($flight["error_code"], "", $flight["error_message_log"] . " Add flight pending info rows failed - " . $flightId, 1);
		}

		if(empty($flight)) {

			json_error("AS_201", "Flight not found", "Flight not found. FlightId = " . $flightId . ", user = " . $GLOBALS['user']->getObjectId(), 3);
		}
	}
	*/

	// Prepare Status response array
	$responseArray = prepareFlightInfo($flight);

	// Cache for 60 seconds
	json_echo(
		json_encode($responseArray)
	);
});

// Trip list
$app->get('/list/a/:apikey/e/:epoch/u/:sessionToken/refresh/:refresh', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $refresh) {

	$refresh = intval($refresh);

	// if($refresh != 1) {

	// 	// Check if already have cache for this
	// 	getRouteCache();
	// }

	// If forced refresh, then call TripIt
	if($refresh == 1) {

		// Fetch TripIt trips and flights
		$tripItToken = getTripItToken();

		if(!empty($tripItToken)) {

			$oauthCredential = connectToTripIt($tripItToken);

			try {

				fetchTripItTrips($oauthCredential);
			}
			catch(Exception $ex) {

				$error_array = json_decode($ex->getMessage(), true);

				// No exit error
				json_error($error_array["error_code"], "", $error_array["error_message_log"] . " TripIt Error - ", 2, 1);
			}
		}
	}

	// Initalize
	$responseArray = [];
	
	// Find user's trips
	// Exclude flights that have departed beyond 1 hour
	$userTrips = parseExecuteQuery(["user" => $GLOBALS['user'], "__GTE__lastFlightArrivalTimestamp" => time()-60*60, "isActive" => true], "UserTrips", "firstFlightDepartureTimestamp");

	// Flight found in another user trip
	if(count_like_php5($userTrips) > 0) {

		for($i=0;$i<count_like_php5($userTrips);$i++) {

			$responseArray[$i]["tripId"] = $userTrips[$i]->getObjectId();
			$responseArray[$i]["tripName"] = $userTrips[$i]->get('tripName');
			$responseArray[$i]["firstFlightDepartureTimestamp"] = $userTrips[$i]->get('firstFlightDepartureTimestamp');
			$responseArray[$i]["firstFlightDepartureTimezone"] = $userTrips[$i]->get('firstFlightDepartureTimezone');
			$responseArray[$i]["firstFlightDepartureTimezoneShort"] = getTimezoneShort($userTrips[$i]->get('firstFlightDepartureTimezone'));
			$responseArray[$i]["firstFlightDepartureAirportIataCode"] = $userTrips[$i]->get('firstFlightDepartureAirportIataCode');
			$responseArray[$i]["lastFlightArrivalTimestamp"] = $userTrips[$i]->get('lastFlightArrivalTimestamp');
			$responseArray[$i]["lastFlightArrivalTimezone"] = $userTrips[$i]->get('lastFlightArrivalTimezone');
			$responseArray[$i]["lastFlightArrivalTimezoneShort"] = getTimezoneShort($userTrips[$i]->get('lastFlightArrivalTimezone'));
			$responseArray[$i]["lastFlightArrivalAirportIataCode"] = $userTrips[$i]->get('lastFlightArrivalAirportIataCode');
			$responseArray[$i]["isFromTripIt"] = empty($userTrips[$i]->get('extTripItId')) ? false : true;
		}
	}

	json_echo(
		 json_encode($responseArray)
	);

	// Cache for 1 hour
	// json_echo(
	// 	setRouteCache([
	// 			"jsonEncodedString" => json_encode($responseArray),
	// 			"expireInSeconds" => 60*60
	// 	])
	// );
});

// Flights within Trip for a given Trip Id
$app->get('/a/:apikey/e/:epoch/u/:sessionToken/tripId/:tripId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $tripId) {
	
	// Find user's trips
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "objectId" => $tripId, "isActive" => true], $userTripsRefObject);

	// Find flights's that haven't landed or landed within 1 hour
	$flightsRefObject = new ParseQuery("Flights");
	$flightsAssociation = parseSetupQueryParams(["__GTE__lastKnownArrivalTimestamp" => time()-60*60], $flightsRefObject);

	// Check if the user has this flight already in their trips
	$flightTrips = parseExecuteQuery(["__MATCHESQUERY__userTrip" => $userTripsAssociation, "__MATCHESQUERY__flight" => $flightsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);

	// Flight found in another user trip
	if(count_like_php5($flightTrips) == 0) {

		json_error("AS_208", "Trip not found", "Trip not found (list pull failed). TripId = " . $tripId . ", user = " . $GLOBALS['user']->getObjectId(), 1);
	}

	$responseArray = [];
	$responseArrayInterim = [];
	$flightIdsByDepartureTimestamps = [];

	foreach($flightTrips as $flightFromTrip) {

		$flightId = $flightFromTrip->get('flight')->get('uniqueId');

		try {

			$flight = getFlightInfoFromCacheOrAPI($flightId);
		}
		catch(Exception $ex) {

			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Flight Status check failed - ", $error_array["error_severity"]);
		}


		// Store prepared information in an interim array
		$responseArrayInterim[$flightId] = prepareFlightInfo($flight);

		// Store departure timestamps by flight ids for sorting
		$flightIdsByDepartureTimestamps[$flightId] = floatval($responseArrayInterim[$flightId]["departure"]["lastKnownTimestamp"]);
	}

	// Sort according to the departureTimestamps
	asort($flightIdsByDepartureTimestamps);

	// Store final response array according to the departure timestamps
	foreach(array_keys($flightIdsByDepartureTimestamps) as $flightId) {

		$responseArray[] = $responseArrayInterim[$flightId];
	}

	json_echo(
		 json_encode($responseArray)
	);
});

// Get Trip and Flight Ids of the next flight
$app->get('/next/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {
	
	$responseArray = [];

	// Find if there is an active flight has not yet arrived
	$flightRefObjectDeparture = new ParseQuery("Flights");
	$flightsActive = parseSetupQueryParams(["__GTE__lastKnownDepartureTimestamp" => time(), "__LTE__lastKnownDepartureTimestamp" => time()], $flightRefObjectDeparture);

	// Find user's trips
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "isActive" => true], $userTripsRefObject);

	$flightTrips = parseExecuteQuery(["__MATCHESQUERY__flight" => $flightsActive, "__MATCHESQUERY__userTrip" => $userTripsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);

	if(count_like_php5($flightTrips) > 0) {

		$earliestDepartureTimestamp = 0;

		// Find the next flight and trip ids by the earliest departure timestamp
		foreach($flightTrips as $flightTrip) {

			if($earliestDepartureTimestamp > $flightTrip->get('flight')->get('lastKnownDepartureTimestamp')
				|| $earliestDepartureTimestamp == 0) {

				$earliestDepartureTimestamp = $flightTrip->get('flight')->get('lastKnownDepartureTimestamp');

				$responseArray["nextFlightId"] = $flightTrip->get('flight')->get('uniqueId');
				$responseArray["nextTripId"] = $flightTrip->get('userTrip')->getObjectId();
			}
		}
	}
	else {

		// Find all flights that have departure timestamp greater than current time
		$flightRefObjectDeparture = new ParseQuery("Flights");
		$flightsAfterCurrentTime = parseSetupQueryParams(["__GTE__lastKnownDepartureTimestamp" => time()], $flightRefObjectDeparture);

		// Find user's trips
		$userTripsRefObject = new ParseQuery("UserTrips");
		$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "isActive" => true], $userTripsRefObject);

		$flightTrips = parseExecuteQuery(["__MATCHESQUERY__flight" => $flightsAfterCurrentTime, "__MATCHESQUERY__userTrip" => $userTripsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip", "flight"]);

		if(count_like_php5($flightTrips) > 0) {

			$earliestDepartureTimestamp = 0;

			// Find the next flight and trip ids by the earliest departure timestamp
			foreach($flightTrips as $flightTrip) {

				if($earliestDepartureTimestamp > $flightTrip->get('flight')->get('lastKnownDepartureTimestamp')
					|| $earliestDepartureTimestamp == 0) {

					$earliestDepartureTimestamp = $flightTrip->get('flight')->get('lastKnownDepartureTimestamp');
					$responseArray["nextFlightId"] = $flightTrip->get('flight')->get('uniqueId');
					$responseArray["nextTripId"] = $flightTrip->get('userTrip')->getObjectId();
				}
			}
		}
	}

	json_echo(
		 json_encode($responseArray)
	);
});

// Trip delete
$app->get('/delete/a/:apikey/e/:epoch/u/:sessionToken/tripId/:tripId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $tripId) {

	// Find user's flights for the provided trip
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "objectId" => $tripId, "isActive" => true], $userTripsRefObject);

	$flightTrip = parseExecuteQuery(["__MATCHESQUERY__userTrip" => $userTripsAssociation, "isActive" => true], "FlightTrips", "", "", ["userTrip"], 1);

	if(count_like_php5($flightTrip) == 0) {

		json_error("AS_208", "Trip not found", "Trip not found (could not be deleted). TripId = " . $tripId . ", user = " . $GLOBALS['user']->getObjectId(), 1);
	}
	else {

		// Check this is not a TripIt trip
		if(!empty($flightTrip->get('userTrip')->get('extTripItId'))) {

			json_error("AS_209", "To delete a TripIt trip, please delete from TripIt and sync.", "TripIt trip requested to be deleted. TripId = " . $tripId, 1);
		}

		// Delete FlightTrips and UserTrips
		deleteTrip($flightTrip);

		/*
		// Mark UserTrip record inactive
		$flightTrips[0]->get('userTrip')->set("isActive", false);
		$flightTrips[0]->get('userTrip')->save();

		// Mark each FlightTrip record as inactive
		foreach($flightTrips as $flightTrip) {

			$flightTrip->set("isActive", false);
			$flightTrip->save();
		}
		*/
	
		$responseArray = ["deleted" => true];
	}

	json_echo(
		 json_encode($responseArray)
	);
});

// Flight delete
$app->get('/flight/delete/a/:apikey/e/:epoch/u/:sessionToken/flightId/:flightId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $flightId) {

	// Find user's flights for the provided trip
	$userTripsRefObject = new ParseQuery("UserTrips");
	$userTripsAssociation = parseSetupQueryParams(["user" => $GLOBALS['user'], "isActive" => true], $userTripsRefObject);

	// Find the flight record by Id
	$flightRefObject = new ParseQuery("Flights");
	$flightById = parseSetupQueryParams(["uniqueId" => $flightId], $flightRefObject);

	// Pull only one record back
	$flightTrip = parseExecuteQuery(["__MATCHESQUERY__userTrip" => $userTripsAssociation, "__MATCHESQUERY__flight" => $flightById, "isActive" => true], "FlightTrips", "", "", ["userTrip"], 1);

	if(count_like_php5($flightTrip) == 0
		|| count_like_php5($flightTrip) > 1) {

		json_error("AS_201", "Flight not found", "Flight not found (could not be deleted). FlightId = " . $flightId . ", user = " . $GLOBALS['user']->getObjectId(), 3);
	}
	else {

		// Check this is not a TripIt trip
		if(!empty($flightTrip->get('userTrip')->get('extTripItId'))) {

			json_error("AS_210", "To delete a TripIt flight, please delete from TripIt and sync.", "TripIt flight requested to be deleted. FlightId = " . $flightId, 1);
		}

		// Mark FlightTrip record as inactive
		$flightTrip->set("isActive", false);
		$flightTrip->save();

		// Check if this was the only flight in the Trip
		$remainingFlightTrips = parseExecuteQuery(["userTrip" => $flightTrip->get('userTrip'), "isActive" => true], "FlightTrips");

		if(count_like_php5($remainingFlightTrips) == 0) {

			// Mark UserTrip record inactive
			$flightTrip->get('userTrip')->set("isActive", false);
			$flightTrip->get('userTrip')->save();
		}

		$responseArray = ["deleted" => true];
	}

	json_echo(
		 json_encode($responseArray)
	);
});

$app->notFound(function () {
	
	json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

?>
