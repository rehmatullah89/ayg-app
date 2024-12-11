<?php

class Flight {

	// private $sourceOfInfo;
	private $info;

	// Flight Side objects
	private $departure;
	private $arrival;

	function __construct() {

		foreach(array_keys(get_object_vars($this)) as $varName) {
			
			if(!is_array($this->{$varName})
				&& !isset($this->{$varName})) {

				$this->{$varName} = null;
			}
		}

		$this->info = new FlightInfo();

		$this->departure = new FlightSide();

		$this->arrival = new FlightSide();		
	}

	function set($key, $value) {

		$this->$key = replaceSpecialChars($value);
	}

	function get($key) {

		return $this->$key;
	}

	function setByRef($key, $array, $keyNameLevelArray, $defaultValue="") {

		$value = $defaultValue;

		$currentLevel = $array;

		$foundLastLevelFlag = true;

		// Run through each level and find the lowest level array index, which will be the value to get
		foreach($keyNameLevelArray as $index) {

			// If the key index is set
			if(isset($currentLevel[$index])) {

				$currentLevel = $currentLevel[$index];
			}

			// If index not found then break and not attempt to save
			else {

				$foundLastLevelFlag = false;
				break;
			}
		}

		if($foundLastLevelFlag) {

			$this->set($key, $currentLevel);
		}
	}

	function generateUniqueId() {

		if(!empty($this->get("arrival")->get("airportIataCode"))
			&& !empty($this->get("departure")->get("airportIataCode"))
			&& !empty($this->get("info")->get("airlineIataCode"))
			&& !empty($this->get("info")->get("flightNum"))) {

			$this->get("info")->set("uniqueId", 
				generateFlightUniqueId(
					$this->get("departure")->get("airportIataCode"), 
					$this->get("arrival")->get("airportIataCode"),
					$this->get("info")->get("airlineIataCode"),
					$this->get("info")->get("flightNum"),
					$this->get("info")->get("flightYear"), 
					$this->get("info")->get("flightMonth"), 
					$this->get("info")->get("flightDate")
				)				
			);
		}
		else {

			// throw exception
		}

		return $this->get("info")->get("uniqueId");
	}

	function getDateOrTimePerTimezone($timestamp, $timezone, $type='both', $fullTimeText="") {

		$currentTimezone = date_default_timezone_get();

		// Set Airport Timezone
		date_default_timezone_set($timezone);

		// If no timestamp is provided, but instead time text is provided
		if(empty($timestamp)
			&& !empty($fullTimeText)) {

			$timestamp = strtotime($fullTimeText);
		}

		if(strcasecmp($type, 'auto')==0) {

			// Same day, show time only
			if(strcasecmp(date("M-j-Y", $timestamp), date("M-j-Y"))==0) {

				$type = "time";
			}
			// Else show both
			else {

				$type = "bothformat";
			}
		}

		if(strcasecmp($type, 'date')==0) {

			$responseText = date("M-j-Y", $timestamp);
		}
		else if(strcasecmp($type, 'time')==0) {

			$responseText = date("g:i A", $timestamp);
		}
		else if(strcasecmp($type, 'bothformat')==0) {

			$responseText = date("M-j g:i A T", $timestamp);
		}
		// both

		else {

			$responseText = date("Y-m-d", $timestamp) . "T" . date("H:i:s", $timestamp) . ".000";
		}

		// Set Default Timezone
		date_default_timezone_set($currentTimezone);

		return $responseText;
	}

	function setDelaysAndInterpretStatus() {

		// Set default status
		$this->get("info")->set("statusInterpreted", $this->get("info")->get("status"));

		// Landed
		if(strcasecmp($this->get("info")->get("status"), "L")==0) {

			// Landed late - calculated delay
			// Landed and arrival scheduled is less than actual
			if($this->get("arrival")->get("scheduledGateTimestamp") != 0
				&& $this->get("arrival")->get("actualGateTimestamp") != 0
				&& $this->get("arrival")->get("scheduledGateTimestamp") < $this->get("arrival")->get("actualGateTimestamp")) {

				// Update delay times
				$this->get("arrival")->set("gateDelayMinutes", round(($this->get("arrival")->get("actualGateTimestamp") - $this->get("arrival")->get("scheduledGateTimestamp"))/60));

				// $this->get("info")->set("delayMinutes", $this->get("arrival")->get("gateDelayMinutes"));

				// Update Status interpreted
				$this->get("info")->set("statusInterpreted", "_L_DLY_");
			}

			// Landed late - posted delay
			// else if($this->get("arrival")->get("postedDelayMinutes") > 0) {

			// 	// Update Status interpreted
			// 	$this->get("info")->set("statusInterpreted", "_L_DLY_");
			// }

			// Landed early
			else if($this->get("arrival")->get("scheduledGateTimestamp") != 0
				&& $this->get("arrival")->get("actualGateTimestamp") != 0
				&& $this->get("arrival")->get("scheduledGateTimestamp") > $this->get("arrival")->get("actualGateTimestamp")) {

				// Update delay times
				$this->get("arrival")->set("gateEarlyMinutes", abs(round(($this->get("arrival")->get("scheduledGateTimestamp") - $this->get("arrival")->get("actualGateTimestamp"))/60)));

				// $this->get("info")->set("earlyMinutes", $this->get("arrival")->get("gateEarlyMinutes"));

				// Update Status interpreted
				$this->get("info")->set("statusInterpreted", "_L_ELY_");
			}
		}

		// Scheduled
		else if(strcasecmp($this->get("info")->get("status"), "S")==0) {

			// Delayed - calculated
			if($this->get("departure")->get("scheduledGateTimestamp") != 0
				&& $this->get("departure")->get("scheduledGateTimestamp") < $this->get("departure")->getLastKnownTimestamp()) {

				// Update delay times
				$this->get("departure")->set("gateDelayMinutes", round(($this->get("departure")->getLastKnownTimestamp() - $this->get("departure")->get("scheduledGateTimestamp"))/60));

				// $this->get("info")->set("delayMinutes", $this->get("departure")->get("gateDelayMinutes"));

				// Update Status interpreted
				$this->get("info")->set("statusInterpreted", "_S_DLY_");
			}

			// Delayed - calculated with Flight Plan data
			// Check Flight Plan timestamps only if scheduled timestamp is within 1 hour
			/*
			if($this->get("departure")->get("scheduledGateTimestamp") < (time()+60*60)
				&& $this->get("departure")->get("scheduledGateTimestamp") != 0
				&& $this->get("departure")->get("flightPlanPlannedGateTimestamp") != 0
				&& $this->get("departure")->get("scheduledGateTimestamp") < $this->get("departure")->get("flightPlanPlannedGateTimestamp")) {

				// Update delay times
				$this->get("departure")->set("gateDelayMinutes", round(($this->get("departure")->get("flightPlanPlannedGateTimestamp") - $this->get("departure")->get("scheduledGateTimestamp"))/60));

				// $this->get("info")->set("delayMinutes", $this->get("departure")->get("gateDelayMinutes"));

				// Update Status interpreted
				$this->get("info")->set("statusInterpreted", "_S_DLY_");
			}
			*/
		
			// Delayed - posted delay
			else if($this->get("departure")->get("postedDelayMinutes") > 0) {

				// Update Status interpreted
				$this->get("info")->set("statusInterpreted", "_S_DLY_");
			}

			// Same day departure, list as On time
			else if($this->getDateOrTimePerTimezone(time(), $this->get("departure")->getAirportTimezone(), 'date')
				== $this->get("departure")->get("flightDate")) {

				// Update Status interpreted
				$this->get("info")->set("statusInterpreted", "_S_TDY_");
			}
		}

		// In air
		else if(strcasecmp($this->get("info")->get("status"), "A")==0) {

			// Left early
			if($this->get("departure")->get("actualGateTimestamp") != 0
				&& $this->get("departure")->get("scheduledGateTimestamp") != 0
				&& $this->get("departure")->get("scheduledGateTimestamp") > $this->get("departure")->get("actualGateTimestamp")) {

				// Update times
				$this->get("departure")->set("gateEarlyMinutes", abs(round(($this->get("departure")->get("actualGateTimestamp") - $this->get("departure")->get("scheduledGateTimestamp"))/60)));
			}
		}

		// Unknown status check
		else if(!in_array($this->get("info")->get("status"), ["A", "C"])) {

			// Update Status interpreted
			$this->get("info")->set("statusInterpreted", "U");
		}
	}

	function getTripName() {

		return 'Trip to ' . $this->get('arrival')->getAirportInfo()["airportCity"];
	}
}

class FlightInfo extends Flight implements \JsonSerializable {

	protected $uniqueId;

	protected $airlineIataCode; 

	protected $flightNum;
	protected $flightYear;
	protected $flightMonth;
	protected $flightDate;

	protected $scheduledAirMinutes;
	protected $scheduledBlockMinutes;
		
	protected $status;
	protected $statusInterpreted;
	protected $statusDescription;

	// protected $delayMinutes = 0;
	// protected $earlyMinutes = 0;

	protected $extFlightId;
	protected $extAirlineName;

	protected $isKnownAirline;

	function __construct() {

		foreach(array_keys(get_object_vars($this)) as $varName) {
			
			if(!is_array($this->{$varName})
				&& !isset($this->{$varName})) {

				$this->{$varName} = null;
			}
		}
	}

	function setStatusDescription() {

		$this->set("statusDescription", $GLOBALS['flightStats_status'][$this->get("statusInterpreted")]["desc"]);
	}

	function getFlightDetails() {

		return $this->getAirlineInfo()["airlineIataCode"] . ' ' . $this->get('flightNum');
	}

	function getAirlineInfo() {

		$airlineInfo = getAirlineByIataCode($this->get("airlineIataCode"));

		if(count_like_php5($airlineInfo) == 0) {

			$this->set('isKnownAirline', false);

			return $this->getExtAirlineInfo();
		}
		else {

			$this->set('isKnownAirline', true);

			return [
				"airlineIataCode" => $airlineInfo->get("airlineIataCode"),
				"airlineName" => $airlineInfo->get("airlineName"),
				"airlineCountry" => $airlineInfo->get("airlineCountry"),
				"airlineCallSign" => $airlineInfo->get("airlineCallSign")
			];
		}
	}

	function getExtAirlineInfo() {

		return [
				"airlineIataCode" => $this->get("airlineIataCode"),
				"airlineName" => $this->get("extAirlineName"),
				"airlineCountry" => "",
				"airlineCallSign" => ""
			];
	}

	function getIsKnownAirline() {

		if(is_null($this->get('isKnownAirline'))) {

			$this->getAirlineInfo();
		}

		return $this->get('isKnownAirline');
	}

	function parseAndSetFlightDate($dateLocal) {

		$this->set("flightYear", substr($dateLocal, 0, 4));
		$this->set("flightMonth", intval(substr($dateLocal, 5, 2)));
		$this->set("flightDate", intval(substr($dateLocal, 8, 2)));
	}



    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}

class FlightSide extends Flight implements \JsonSerializable {

	protected $airportIataCode; 

	// Departure or Arrival date per the Airport timezone
	protected $flightDate;
	protected $flightTime;

	protected $scheduledGateTimestamp = 0;
	protected $scheduledGateLocal;

	protected $estimatedGateTimestamp = 0;
	protected $estimatedGateLocal;

	protected $flightPlanPlannedGateTimestamp = 0;
	protected $flightPlanPlannedGateLocal;

	protected $actualGateTimestamp = 0;
	protected $actualGateLocal;

	protected $postedDelayMinutes;

	protected $gateDelayMinutes = 0;
	protected $gateEarlyMinutes = 0;

	protected $gate;
	protected $terminal; // concourse embeded in here
	protected $terminalHistoric;
	protected $gateHistoric;
	protected $terminalGateMapLocationHistoric;
	protected $isGateInfoEstimated;

	protected $extAirportName; 
	protected $extAirportCity; 
	protected $extAirportCountry; 
	protected $extAirportTimezone;
	protected $extAirportTimezoneShort;

	protected $isKnownAirport;
	// protected $isKnownLocation;

	// Parse object
	// protected $lastKnownTerminalGateMapLocation;

	// Departure only
	// protected $boardingLocal;
	// JMD
	protected $boardingTimestamp;
	protected $deliveryAlertTimestamp;

	// Arrival only
	protected $baggage;

	function __construct() {

		foreach(array_keys(get_object_vars($this)) as $varName) {
			
			if(!is_array($this->{$varName})
				&& !isset($this->{$varName})) {

				$this->{$varName} = null;
			}
		}
	}



    public function jsonSerialize()
    {
        return get_object_vars($this);
    }


	function getGateDisplayName() {

		// If the Airport is Ready
		if($this->isReadyAirport() == true) {

			// If gate location of NEW flight is available
			if(is_object($this->getTerminalGateMapLocation(true))) {

				return $this->getTerminalGateMapLocation(true)->get('gateDisplayName');
			}
		}
		// Else show ext location
		else {

			$gateArray = $this->getExtTerminalGateMapLocation(true);

			if(!empty($gateArray["terminalConcourse"]) 
				&& !empty($gateArray["gate"])) {

				return 'Gate ' . $gateArray["gate"] . ' in ' . 'Terminal ' . $gateArray["terminalConcourse"];
			}
			else if(!empty($gateArray["terminalConcourse"]) 
				&& empty($gateArray["gate"])) {

				return 'Terminal ' . $gateArray["terminalConcourse"];
			}
			else if(empty($gateArray["terminalConcourse"]) 
				&& !empty($gateArray["gate"])) {

				return 'Gate ' . $gateArray["gate"];
			}
		}

		return "";
	}

	// Set after user adds flight
	function evaluateUTCAndTimestamps() {

		$currentTimezone = date_default_timezone_get();

		date_default_timezone_set($this->getAirportTimezone());

		$this->set("scheduledGateTimestamp", strtotime($this->get("scheduledGateLocal")));
		$this->set("estimatedGateTimestamp", strtotime($this->get("estimatedGateLocal")));
		$this->set("flightPlanPlannedGateTimestamp", strtotime($this->get("flightPlanPlannedGateLocal")));
		$this->set("actualGateTimestamp", strtotime($this->get("actualGateLocal")));

		// Set Default Timezone
		date_default_timezone_set($currentTimezone);

		// Scheduled
		// $this->set("scheduledGateUTC", convertToUTC($this->get("scheduledGateLocal"), $this->getAirportTimezone()));
		// $this->set("scheduledGateTimestamp", convertToTimestamp($this->get("scheduledGateLocal"), $this->getAirportTimezone()));
		
		// Estimated
		// $this->set("estimatedGateUTC", convertToUTC($this->get("estimatedGateLocal"), $this->getAirportTimezone()));
		// $this->set("estimatedGateTimestamp", convertToTimestamp($this->get("estimatedGateLocal"), $this->getAirportTimezone()));
		
		// Actual
		// $this->set("actualGateUTC", convertToUTC($this->get("actualGateLocal"), $this->getAirportTimezone()));
		// $this->set("actualGateTimestamp", convertToTimestamp($this->get("actualGateLocal"), $this->getAirportTimezone()));
	}

	function setBoardingTime($boardingWindowInMins = 30, $deliveryAlertWindowInMins = 15) {

		// $this->set("boardingTimestamp", $this->get("scheduledGateTimestamp")-($boardingWindowInMins * 60));
		$this->set("boardingTimestamp", $this->getLastKnownTimestamp()-($boardingWindowInMins * 60));
		$this->set("deliveryAlertTimestamp", $this->getLastKnownTimestamp()-($deliveryAlertWindowInMins * 60));

		// $this->set("boardingLocal", $this->getDateOrTimePerTimezone($this->get("boardingTimestamp"), $this->getAirportTimezone()));
	}

	function getBoardingTimestamp() {

		return $this->get("boardingTimestamp");
	}

	function getDeliveryAlertTimestamp() {

		return $this->get("deliveryAlertTimestamp");
	}

	function getAirportTimezone() {

		return $this->getAirportAttribute("timezone");
	}

	function setFlightDateTime() {

		// Use Scheduled time to set the flight date and time
		// $timestamp = convertToTimestamp($this->get("scheduledGateLocal"), $this->getAirportTimezone());
		//strtotime($this->get("scheduledGateLocal"));

		$this->set("flightDate", $this->getDateOrTimePerTimezone(0, $this->getAirportTimezone(), 'date', $this->get("scheduledGateLocal")));
		$this->set("flightTime", $this->getDateOrTimePerTimezone(0, $this->getAirportTimezone(), 'time', $this->get("scheduledGateLocal")));
	}

	function setExtAirportInfo($extAirportInfoArray) {

		$this->set("airportIataCode", $extAirportInfoArray["iata"]);
		$this->set("extAirportTimezone", $extAirportInfoArray["timeZoneRegionName"]);
		$this->set("extAirportTimezoneShort", getTimezoneShort($extAirportInfoArray["timeZoneRegionName"]));
		$this->set("extAirportName", $extAirportInfoArray["name"]);
		$this->set("extAirportCity", $extAirportInfoArray["city"]);
		$this->set("extAirportCountry", $extAirportInfoArray["countryName"]);
	}

	function getAirportInfo() {

		$airportInfo = getAirportByIataCode($this->get("airportIataCode"));

		if(count_like_php5($airportInfo) == 0) {

			$this->set('isKnownAirport', false);

			return $this->getExtAirportInfo();
		}
		else {

			$this->set('isKnownAirport', true);

			return [
				"airportIataCode" => $airportInfo->get("airportIataCode"),
				"airportTimezone" => $airportInfo->get("airportTimezone"),
				"airportTimezoneShort" => getTimezoneShort($airportInfo->get("airportTimezone")),
				"airportName" => $airportInfo->get("airportName"),
				"airportCity" => $airportInfo->get("airportCity"),
				"airportCountry" => $airportInfo->get("airportCountry"),
				"airportIsReady" => $airportInfo->get("isReady")
			];
		}
	}

	function getExtAirportInfo() {

		return [
				"airportIataCode" => $this->get("airportIataCode"),
				"airportTimezone" => $this->get("extAirportTimezone"),
				"airportTimezoneShort" => getTimezoneShort($this->get("extAirportTimezone")),
				"airportName" => $this->get("extAirportName"),
				"airportCity" => $this->get("extAirportCity"),
				"airportCountry" => $this->get("extAirportCountry"),
				"airportIsReady" => false
			];
	}

	function getLastKnownTimestamp() {

		if(!empty($this->get("actualGateTimestamp"))) {

			return $this->get("actualGateTimestamp");
		}

		/* Turned this off on 7/14 as it was giving erratic results */

		// Use Planned timestamp only if its within 1 hour of the scheduled timestamp
		// else if(!empty($this->get("flightPlanPlannedGateTimestamp"))
		// 	&& $this->get("scheduledGateTimestamp") < (time()+60*60)) {

		// 	return $this->get("flightPlanPlannedGateTimestamp");
		// }
		else if(!empty($this->get("estimatedGateTimestamp"))) {

			return $this->get("estimatedGateTimestamp");
		}
		else {

			return $this->get("scheduledGateTimestamp");
		}
	}

	function getLastKnownTimestampFormatted() {

		return $this->getDateOrTimePerTimezone($this->getLastKnownTimestamp(), $this->getAirportTimezone(), 'auto');
	}

	function getBoardingTimestampFormatted() {

		return $this->getDateOrTimePerTimezone($this->getBoardingTimestamp(), $this->getAirportTimezone(), 'time');
	}

	function getAirportCacheObject() {

		// Look for airport info in cache
		return getAirportByIataCode($this->get("airportIataCode"));
	}

	function getAirportAttribute($attribute) {

		$objAirport = $this->getAirportCacheObject();

		// If known airport
		if(count_like_php5($objAirport) > 0) {

			return $objAirport->get('airport' . ucwords($attribute));
		}
		// Else use externally provided (from flight stats API)
		else {
			
			$attributeName = "extAirport" . ucwords($attribute);
			return $this->$attributeName;
		}
	}

	function getExtTerminalGateMapLocation($historicAllowed = false) {

		if($this->isReadyAirport() == false) {

			// External gate info available
			if(!empty($this->get("terminal"))
				&& !empty($this->get("gate"))) {

				$this->set('isGateInfoEstimated', false);
				return ["terminalConcourse" => strval($this->get("terminal")), "gate" => strval($this->get("gate"))];
			}

			// Historic gate info available
			else if(!empty($this->get("terminalHistoric"))
				&& $historicAllowed == true) {

				$this->set('isGateInfoEstimated', true);
				return ["terminalConcourse" => strval($this->get("terminalHistoric")), "gate" => strval($this->get("gateHistoric"))];
			}

			else if(!empty($this->get("terminal"))) {

				$this->set('isGateInfoEstimated', true);
				return ["terminalConcourse" => strval($this->get("terminalHistoric")), "gate" => ""];
			}

			else if(!empty($this->get("gate"))) {

				$this->set('isGateInfoEstimated', true);
				return ["terminalConcourse" => "", "gate" => strval($this->get("gate"))];
			}
		}

		return ["terminalConcourse" => "", "gate" => ""];
		// return [];
	}

	function getTerminal() {

		if($this->isReadyAirport() == true) {

			return $this->getTerminalForReadyAirport();
		}
		else {

			return $this->getTerminalForNotReadyAirport();
		}	
	}

	function getTerminalForReadyAirport() {

		$location = $this->getTerminalGateMapLocation(true);

		if(!empty($location)) {

			return $location->get('terminal');
		}

		return null;
	}

	function getTerminalForNotReadyAirport() {

		$gateArray = $this->getExtTerminalGateMapLocation(true);

		return $gateArray["terminalConcourse"];
	}

	function getConcourse() {

		if($this->isReadyAirport() == true) {

			return $this->getConcourseForReadyAirport();
		}
		else {

			return $this->getConcourseForNotReadyAirport();
		}	
	}

	function getConcourseForReadyAirport() {

		$location = $this->getTerminalGateMapLocation(true);

		if(!empty($location)) {

			return $location->get('concourse');
		}

		return null;
	}

	function getConcourseForNotReadyAirport() {

		$gateArray = $this->getExtTerminalGateMapLocation(true);

		return $gateArray["terminalConcourse"];
	}

	function getGate() {

		if($this->isReadyAirport() == true) {

			return $this->getGateForReadyAirport();
		}
		else {

			return $this->getGateForNotReadyAirport();
		}	
	}

	function getGateForReadyAirport() {

		$location = $this->getTerminalGateMapLocation(true);

		if(!empty($location)) {

			return $location->get('gate');
		}

		return null;
	}

	function getGateForNotReadyAirport() {

		$gateArray = $this->getExtTerminalGateMapLocation(true);

		return $gateArray["gate"];
	}

	function getTerminalGateMapLocation($historicAllowed = false) {

		$location = null;

		// If airpor is ready
		if($this->isReadyAirport() == true) {

			// If Terminal and Gate info is available, lookup location object
			if(!empty($this->get("terminal"))
				&& !empty($this->get("gate"))) {

				$this->set('isGateInfoEstimated', false);
				$location = getTerminalGateMapByLocationRef($this->get("airportIataCode"), $this->get("terminal"), "", $this->get("gate"));

				if(empty($location)) {

					$location = null;
				}
			}

			// Historic
			else if(!empty($this->get("terminalGateMapLocationHistoric"))
				&& $historicAllowed == true) {

				$this->set('isGateInfoEstimated', true);
				$location = getTerminalGateMapByLocationId($this->getAirportInfo()['airportIataCode'], $this->get("terminalGateMapLocationHistoric"));

				if(empty($location)) {

					$location = null;
				}
			}
		}

		return $location;
	}

	/*
	function setTerminalGateMapLocation() {

		$locationFound = false;

		if($this->isReadyAirport() == true) {

			$terminalGateMapObj = $this->getTerminalGateMapLocation();

			// If TerminalGateMap object is found
			if(!is_null($terminalGateMapObj)
				&& count_like_php5($terminalGateMapObj) > 0) {

				// $this->set('isKnownLocation', true);
				// $this->set("lastKnownTerminalGateMapLocation", $terminalGateMapObj);
				$locationFound = true;
			}
		}

		if(!$locationFound) {

			// $this->set('isKnownLocation', false);
		}
	}
	*/

	function setTerminalGateHistoricLocation($terminalGateMapLocationHistoric) {

		if(!empty($terminalGateMapLocationHistoric)) {

			// $this->set('isKnownLocation', true);
			// $this->set("lastKnownTerminalGateMapLocation", $terminalGateMapLocationHistoric);
			$this->set('terminalGateMapLocationHistoric', $terminalGateMapLocationHistoric->getObjectId());
		}
	}

	function setTerminalGateHistoricExternal($extLocationInfo) {

		if(!empty($extLocationInfo)) {

			$historicArray = json_decode($extLocationInfo, true);

			$this->set('terminalHistoric', $historicArray["terminalConcourse"]);
			$this->set('gateHistoric', $historicArray["gate"]);
		}
	}

	function getIsKnownAirport() {

		if(is_null($this->get('isKnownAirport'))) {

			$this->getAirportInfo();
		}

		return $this->get('isKnownAirport');
	}

	function isReadyAirport() {

		return $this->getAirportInfo()['airportIsReady'];
	}

	/*
	function getIsKnownLocation() {

		if(is_null($this->get('isKnownLocation'))) {

			$this->applyTerminalGateRules();
			$this->setTerminalGateMapLocation();
		}

		return $this->get('isKnownLocation');
	}
	*/
	/////////////////////////////////////////////////
	// MANUAL EDITS REQUIRED
	/////////////////////////////////////////////////
	function applyTerminalGateRules() {

		// If Terminal is empty
		if(empty($this->get("terminal"))
			&& !empty($this->get("gate"))) {

			// MDW, BWI, CHS
			// if(in_array($this->get("airportIataCode"), ["MDW", "BWI", "CHS"])) {

				$this->set("terminal", 1);				
			// }
		}
	}
	/////////////////////////////////////////////////
}
