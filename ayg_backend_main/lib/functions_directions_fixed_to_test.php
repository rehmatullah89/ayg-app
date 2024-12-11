<?php

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;

$oneStepInMiles = 0.001; // miles
$secondsPerStep = 0.75;
$factorMultiplier = 20;
$assetsLocation = $dirpath . "assets/maps";

function saveHighlightedPathImageToParse($markedImageFileName, $fromLocation, $toLocation)
{

    // try {
    // 	$fileParseObject->save();
    // } catch (ParseException $ex) { }

    // $markedImageURL = cleanURL($fileParseObject->getURL());

    $directionImagesParseObject = new ParseObject("DirectionImages");
    $directionImagesParseObject->set("fromLocation", $fromLocation);
    $directionImagesParseObject->set("toLocation", $toLocation);
    //$directionImagesParseObject->set("markedImage", $fileParseObject);
    $directionImagesParseObject->set("markedImageURL", $markedImageFileName);
    $directionImagesParseObject->save();

    //@unlink($localImageFile);

    return true;
}

function closeOutForDistanceMetrics(
    $responseArray,
    $segmentCount,
    $directionsStepsCounter,
    $terminal,
    $concourse,
    $closeOutLastGate,
    $airportIataCode
) {

    global $closeOutCurrentGate;

    $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["displayText"][] = "Continue walking towards " . getTerminalGateDisplayName($airportIataCode,
            $terminal, $concourse, $closeOutLastGate);
    $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["directionCue"] = "S";

    $responseArray = getPartDistanceMetrics($responseArray, $segmentCount, $directionsStepsCounter, $terminal,
        $concourse, $closeOutLastGate, $airportIataCode);

    $directionsStepsCounter++;
    $closeOutCurrentGate = $closeOutLastGate;

    return array($directionsStepsCounter, $responseArray);
}

function getPartDistanceMetrics(
    $responseArray,
    $segmentCount,
    $directionsStepsCounter,
    $terminal,
    $concourse,
    $closeOutLastGate,
    $airportIataCode
) {

    global $closeOutCurrentGate, $jsonGateMap;

    // Shortest path
    $graph = new Dijkstra($jsonGateMap[$airportIataCode][$terminal . $concourse]["directionWDistance"]);

    // @todo check if change from shortestPaths to breadthFirstSearch is always ok
    //var_dump($closeOutCurrentGate, $closeOutLastGate);
    //$pathToDestination = $graph->shortestPaths($closeOutCurrentGate, $closeOutLastGate);

    //var_dump($pathToDestination);
    // Fewest hops
    $graph = new Graph($jsonGateMap[$airportIataCode][$terminal . $concourse]["map"]);
    $pathToDestination = $graph->breadthFirstSearch($closeOutCurrentGate, $closeOutLastGate);
    //var_dump($pathToDestination);
    $tmpArray = calculateDistanceToDestination($pathToDestination,
        $jsonGateMap[$airportIataCode][$terminal . $concourse]["distance"]);

    // print_r($pathToDestination);
    // print_r($tmpArray);
    // exit;
    //$responseArray[$segmentCount]["directions"][$directionsStepsCounter]["pathToDestination"] = $tmpArray["pathToDestination"];
    $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["distanceSteps"] = $tmpArray["distanceStepsToGate"];
    $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["distanceMiles"] = $tmpArray["distanceMilesToGate"];
    $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["walkingTime"] = $tmpArray["walkingTimeToGate"];

    return $responseArray;
}

function array_sort_by_two_keys($array, $firstkey, $secondkey, $orderfirst = SORT_ASC, $ordersecond = SORT_ASC)
{

    $sort = array();
    foreach ($array as $k => $v) {

        $sort[$firstkey][$k] = $v[$firstkey];
        $sort[$secondkey][$k] = $v[$secondkey];
    }

    # sort by event_type desc and then title asc
    array_multisort($sort[$firstkey], $orderfirst, $sort[$secondkey], $ordersecond, $array);

    return $array;
}

function array_sort($array, $on, $order = SORT_ASC)
{

    $new_array = array();
    $sortable_array = array();

    if (count_like_php5($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function isGateCorrect($airportIataCode, $terminal, $concourse, $gate)
{

    global $jsonGateMap;

    // Hard-coded for MVP
    // if($airportIataCode != "BWI") {

    // 	json_error("AS_1001", "", "Invalid Airport Code!");
    // }

    // Check if Terminal and Gate values are valid
    $gate = (int)$gate;
    if (!isset($jsonGateMap[$airportIataCode][$terminal . $concourse]) || !isset($jsonGateMap[$airportIataCode][$terminal . $concourse]["map"][$gate])) {

        json_error("AS_1002", "", "Invalid Terminal or Gate!");
    }
}

function getAirportsArray()
{

    if (empty($GLOBALS['airportsArray'])) {

        $GLOBALS['airportsArray'] = getAirportsCache();
    }

    // If no cache found, rebuild it
    if (!is_array($GLOBALS['airportsArray'])
        || count_like_php5($GLOBALS['airportsArray']) == 0
    ) {

        $objAirports = parseExecuteQuery(array(), "Airports");

        $GLOBALS['airportsArray'] = [];
        foreach ($objAirports as $airport) {

            // shouldn't be used since this is cached and won't reflect daylight savings changes
            // $airport->set("airportTimezoneShort", getTimezoneShort($airport->get("airportTimezone")));

            $GLOBALS['airportsArray']["byAirportIataCode"][$airport->get('airportIataCode')] = $airport;
        }

        // Set cache
        setAirportsCache($GLOBALS['airportsArray']);
    }

    return $GLOBALS['airportsArray'];
}

function getAirportList($isReady = false)
{

    $responseArray = array();
    // $objParseQueryAirports = parseExecuteQuery(array(), "Airports");
    $objParseQueryAirports = getAirportsArray();

    $i = 0;
    foreach ($objParseQueryAirports["byAirportIataCode"] as $airport) {

        $responseArrayTemp = $airport->getAllKeys();

        if (($responseArrayTemp["isReady"] == true && $isReady == true)
            || $isReady == false
        ) {

            $responseArray[$i] = $responseArrayTemp;
            $responseArray[$i]["objectId"] = $airport->getObjectId();
            $responseArray[$i]["imageBackground"] = preparePublicS3URL($responseArray[$i]["imageBackground"],
                getS3KeyPath_ImagesAirportBackground(), $GLOBALS['env_S3Endpoint']);
            $responseArray[$i]["geoPointLocation"] = array(
                "longitude" => $airport->get('geoPointLocation')->getLongitude(),
                "latitude" => $airport->get('geoPointLocation')->getLatitude()
            );

            $i++;
        }
    }

    return $responseArray;
}

function getLocationIdsAndNamesByTerminalConcoursePairing($airportIataCode)
{

    $locationsByTerminalConcourse = [];
    $namesByTerminalConcourse = [];
    $terminalGateMap = getAirportTerminalGateMap($airportIataCode);
    foreach ($terminalGateMap as $gateMap) {

        $key = $gateMap["terminal"] . "-" . $gateMap["concourse"];
        $locationsByTerminalConcourse[$key][] = $gateMap["objectId"];

        if (!isset($namesByTerminalConcourse[$key])) {

            $namesByTerminalConcourse[$key] = $gateMap["terminalDisplayName"] . '-' . $gateMap["concourseDisplayName"];
        }
    }

    return [$locationsByTerminalConcourse, $namesByTerminalConcourse];
}

function getAirportTerminalGateMap($airportIataCode)
{

    $responseArray = array();
    $objParseQueryTGM = parseExecuteQuery(array("airportIataCode" => $airportIataCode, "includeInGateMap" => true),
        "TerminalGateMap");

    $i = 0;
    foreach ($objParseQueryTGM as $tgm) {

        $responseArray[$i] = $tgm->getAllKeys();
        $responseArray[$i]["objectId"] = $tgm->getObjectId();
        unset($responseArray[$i]["includeInGateMap"]);

        if (!empty($tgm->get('geoPointLocation'))) {

            $responseArray[$i]["geoPointLocation"] = array(
                "longitude" => $tgm->get('geoPointLocation')->getLongitude(),
                "latitude" => $tgm->get('geoPointLocation')->getLatitude()
            );
        } else {

            $responseArray[$i]["geoPointLocation"] = array("longitude" => 0, "latitude" => 0);
        }

        $i++;
    }

    // JMD
    if (strcasecmp($airportIataCode, "LAX") == 0) {

        /*
        foreach ($responseArray as $key => $location) {

            $terminal = $location["terminal"];
            $terminalDisplayName = $location["terminalDisplayName"];

            $responseArray[$key]["terminal"] = $location["concourse"];
            $responseArray[$key]["terminalDisplayName"] = $location["concourseDisplayName"];

            $responseArray[$key]["concourse"] = $terminal;
            $responseArray[$key]["concourseDisplayName"] = $terminalDisplayName;
        }
        */
    }

    return $responseArray;
}

function getAirportByIataCode($airportIataCode)
{

    $airportsArray = getAirportsArray();

    if (!isset($airportsArray["byAirportIataCode"][$airportIataCode])) {

        return [];
    }

    return $airportsArray["byAirportIataCode"][$airportIataCode];
}

function getAirlinesArray()
{

    if (empty($GLOBALS['airlinesArray'])) {

        $airlinesArray = getAirlinesCache();
    }

    // If no cache found, rebuild it
    if (!is_array($GLOBALS['airlinesArray'])
        || count_like_php5($GLOBALS['airlinesArray']) == 0
    ) {

        $objAirlines = parseExecuteQuery(array(), "Airlines");

        $GLOBALS['airlinesArray'] = [];
        foreach ($objAirlines as $airline) {

            $GLOBALS['airlinesArray']["byAirlineIataCode"][$airline->get('airlineIataCode')] = $airline;
        }

        // Set cache
        setAirlinesCache($GLOBALS['airlinesArray']);
    }

    return $GLOBALS['airlinesArray'];
}

function getAirlineByIataCode($airlineIataCode)
{

    $airlinesArray = getairlinesArray();

    if (!isset($airlinesArray["byAirlineIataCode"][$airlineIataCode])) {

        return [];
    }

    return $airlinesArray["byAirlineIataCode"][$airlineIataCode];
}

function getTerminalGateMapArray($airportIataCode, $byType = 'byLocationId')
{

    if (empty($GLOBALS['teminalGateMapArray'][$airportIataCode][$byType])) {

        $array = getTerminalGateMapCache($airportIataCode, $byType);

        // JMD
        if (is_array($array) && count_like_php5($array) > 0) {

            $GLOBALS['teminalGateMapArray'][$airportIataCode][$byType] = $array;
        }
    }

    // If no cache found, rebuild it
    if (!isset($GLOBALS['teminalGateMapArray'][$airportIataCode])
        || !is_array($GLOBALS['teminalGateMapArray'][$airportIataCode])
        || count_like_php5($GLOBALS['teminalGateMapArray'][$airportIataCode]) == 0
    ) {

        $objTerminalGateMap = parseExecuteQuery(array("airportIataCode" => $airportIataCode), "TerminalGateMap");

        $GLOBALS['teminalGateMapArray'][$airportIataCode] = [];
        foreach ($objTerminalGateMap as $location) {

            $GLOBALS['teminalGateMapArray'][$airportIataCode]["byLocationId"][$location->getObjectId()] = $location;
            $GLOBALS['teminalGateMapArray'][$airportIataCode]["byLocationRef"][$location->get('terminal') . $location->get('concourse') . $location->get('gate')] = $location;

            // Is default location
            if ($location->get('isDefaultLocation') == true) {

                $GLOBALS['teminalGateMapArray'][$airportIataCode]["defaultLocation"] = $location;
            }
        }

        // Set cache if TerminalGateMap info for this airport was found
        if (count_like_php5($GLOBALS['teminalGateMapArray'][$airportIataCode]) > 0) {

            setTerminalGateMapCache($airportIataCode, $GLOBALS['teminalGateMapArray'][$airportIataCode]);
        }
    }

    return $GLOBALS['teminalGateMapArray'][$airportIataCode];
}

function getTerminalGateMapDefaultLocation($airportIataCode)
{

    $teminalGateMapArray = getTerminalGateMapArray($airportIataCode, 'defaultLocation');

    if (!isset($teminalGateMapArray["defaultLocation"])) {

        return [];
    }

    return $teminalGateMapArray["defaultLocation"];
}

function getTerminalGateMapByLocationId($airportIataCode, $locationId)
{

    $teminalGateMapArray = getTerminalGateMapArray($airportIataCode, 'byLocationId');

    if (!isset($teminalGateMapArray["byLocationId"][$locationId])) {

        return [];
    }

    return $teminalGateMapArray["byLocationId"][$locationId];
}

function getTerminalGateMapByLocationRef($airportIataCode, $terminal, $concourse, $gate)
{
    $teminalGateMapArray = getTerminalGateMapArray($airportIataCode, 'byLocationRef');

    // JMD
    if (!isset($teminalGateMapArray["byLocationRef"][$terminal . $concourse . $gate])) {

        return [];
    }

    return $teminalGateMapArray["byLocationRef"][$terminal . $concourse . $gate];
}

function getTerminalGateDisplayName($airportIataCode, $terminal, $concourse, $gate)
{

    if (empty(getTerminalGateMapByLocationRef($airportIataCode, $terminal, $concourse, $gate))) {

        // var_dump(debug_backtrace());
        // echo("$airportIataCode, $terminal, $concourse, $gate");exit;
    }

    return getTerminalGateMapByLocationRef($airportIataCode, $terminal, $concourse, $gate)->get('gateDisplayName');
}

function getGateLocationDetails($airportIataCode, $locationId)
{

    $objTerminalGateMap = getTerminalGateMapByLocationId($airportIataCode, $locationId);

    if (!empty($objTerminalGateMap)
        && count_like_php5($objTerminalGateMap) > 0
    ) {

        return array(
            $objTerminalGateMap->get("airportIataCode"),
            $objTerminalGateMap->get("terminal"),
            $objTerminalGateMap->get("concourse"),
            $objTerminalGateMap->get("gate"),
            $objTerminalGateMap->get("isPresecurityLocation")
        );
    }

    return array("", "", "", "", false);
}

/*
function getRetailerGateLocation($retailerId) {
	
	// Cachable query
	$GLOBALS['cacheParseQuery'] = true;
	$objParseQueryRetailersResults = parseExecuteQuery(array("uniqueId" => $retailerId, "isActive" => true), "Retailers", "", "", array("location", "retailerType", "retailerPriceCategory", "retailerPriceCategory", "retailerFoodSeatingType"));
	
	// try {

	// 	$objParseQueryRetailers->equalTo();
	// 	$objParseQueryRetailersResults = $objParseQueryRetailers->find();

	// } catch (ParseException $ex) {

	// 	json_error("AS_1003", "", "Invalid Retailer reference");
	// }
	
	// If no retailer is found
	if(count_like_php5($objParseQueryRetailersResults) == 0) {
		
		json_error("AS_1004", "", "Invalid Retailer reference");
	}
	
	$concourse = "";
	if(!empty(trim($objParseQueryRetailersResults[0]->get("location")->get("concourse")))) {
		
		$concourse = $objParseQueryRetailersResults[0]->get("location")->get("concourse");
	}
	
	return array($objParseQueryRetailersResults[0]->get("location")->get("airportIataCode"), $objParseQueryRetailersResults[0]->get("location")->get("terminal"), $concourse, $objParseQueryRetailersResults[0]->get("location")->get("gate"), $objParseQueryRetailersResults[0]->get("retailerName"));
}
*/

function getDistanceMetrics(
    $toTerminal,
    $toConcourse,
    $toGate,
    $fromTerminal,
    $fromConcourse,
    $fromGate,
    $returnTotaled = true,
    $airportIataCode,
    $cacheGen = false
) {


    // @todo change it $cacheGen
    $cacheGen = true;
    if (!$cacheGen) {

        $distanceMetricsCache = getDistanceMetricsCache($toTerminal, $toConcourse, $toGate, $fromTerminal,
            $fromConcourse, $fromGate, $returnTotaled, $airportIataCode);

        if (!empty($distanceMetricsCache)) {

            return $distanceMetricsCache;
        }

        json_error("AS_INFO", "",
            "Distance metrics manually calculated - $toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate, $airportIataCode",
            3, 1);
    }


    global $jsonGateMap, $jsonT2TMap, $oneStepInMiles, $secondsPerStep, $factorMultiplier;

    $tempArray = array();

    // echo("----$toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate <br />");

    // Terminal Change required
    if (strcasecmp(($toTerminal . $toConcourse), ($fromTerminal . $fromConcourse)) != 0) {

        $finalResponseArray = array();
        $responseArray = array();

        // From Terminal's current location to last gate
        $fromTerminalLastGate = $jsonT2TMap[$airportIataCode][$fromTerminal . $fromConcourse . '-' . $toTerminal . $toConcourse]['fromTerminalLastGate'];
        if ($fromTerminalLastGate != $fromGate) {

            $tempArray = getDistanceMetrics($fromTerminal, $fromConcourse, $fromTerminalLastGate, $fromTerminal,
                $fromConcourse, $fromGate, true, $airportIataCode, $cacheGen, $cacheGen);
        } else {

            $tempArray = array(
                "pathToDestination" => $fromTerminalLastGate . '>' . $fromGate,
                "distanceStepsToGate" => 0,
                "distanceMilesToGate" => 0,
                "walkingTimeToGate" => 0
            );
        }

        // Check if this segment path was valid
        if ($tempArray["pathToDestination"] == "NOP") {

            return noPathResponseArray();
        } else {

            $responseArray[] = $tempArray;
        }
        //////

        // Terminal to Terminal
        $totalSteps = $jsonT2TMap[$airportIataCode][$fromTerminal . $fromConcourse . '-' . $toTerminal . $toConcourse]['nextTerminalDistanceUnits'] * $factorMultiplier;
        $totalMiles = $jsonT2TMap[$airportIataCode][$fromTerminal . $fromConcourse . '-' . $toTerminal . $toConcourse]['nextTerminalDistanceUnits'] * $factorMultiplier * $oneStepInMiles;
        $totalWalkingTime = round(($jsonT2TMap[$airportIataCode][$fromTerminal . $fromConcourse . '-' . $toTerminal . $toConcourse]['nextTerminalDistanceUnits'] * $factorMultiplier * $secondsPerStep) / 60);

        // If rounded time is 0, but steps are more than 0, set time to 1
        if ($totalWalkingTime == 0 && $totalSteps != 0) {

            $totalWalkingTime = 1;
        }

        $responseArray[] = array(
            "pathToDestination" => $fromTerminal . $fromConcourse . '>' . $toTerminal . $toConcourse,
            "distanceStepsToGate" => $totalSteps,
            "distanceMilesToGate" => round($totalMiles, 2),
            "walkingTimeToGate" => $totalWalkingTime
        );

        // To Terminal's current location to last gate
        $toTerminalFirstGate = $jsonT2TMap[$airportIataCode][$fromTerminal . $fromConcourse . '-' . $toTerminal . $toConcourse]['toTerminalFirstGate'];

        if ($toTerminalFirstGate != $toGate) {

            $tempArray = getDistanceMetrics($toTerminal, $toConcourse, $toGate, $toTerminal, $toConcourse,
                $toTerminalFirstGate, true, $airportIataCode, $cacheGen);
        } else {

            $tempArray = array(
                "pathToDestination" => $toGate . '>' . $toTerminalFirstGate,
                "distanceStepsToGate" => 0,
                "distanceMilesToGate" => 0,
                "walkingTimeToGate" => 0
            );
        }

        // Check if this segment path was valid
        if ($tempArray["pathToDestination"] == "NOP") {

            return noPathResponseArray();
        } else {

            $responseArray[] = $tempArray;
        }
        //////

        if ($returnTotaled) {

            $finalResponseArray = array(
                "distanceStepsToGate" => 0,
                "distanceMilesToGate" => 0,
                "walkingTimeToGate" => 0
            );

            // As we need to send Total Distance, sum them up
            foreach ($responseArray as $levelMetric) {

                $finalResponseArray["distanceStepsToGate"] += $levelMetric["distanceStepsToGate"];
                $finalResponseArray["distanceMilesToGate"] += $levelMetric["distanceMilesToGate"];
                $finalResponseArray["walkingTimeToGate"] += $levelMetric["walkingTimeToGate"];
            }

            // If there were more than one level, meaning there is a Terminal change, add text for that
            $finalResponseArray["differentTerminalFlag"] = "Y";
        } else {

            $finalResponseArray = $responseArray;
        }

        return $finalResponseArray;
    } else {

        // Initialize Graph
        // Shortest path
        //var_dump($jsonGateMap);
        //var_dump($jsonGateMap[$airportIataCode][$toTerminal . $toConcourse]["directionWDistance"]);
        //$graph = new Dijkstra($jsonGateMap[$airportIataCode][$toTerminal . $toConcourse]["directionWDistance"]);
        //var_dump($graph);

        //$pathToDestination = $graph->shortestPaths($fromGate, $toGate);

        // Initialize Graph
        // Fewest hops
        $graph = new Graph($jsonGateMap[$airportIataCode][$toTerminal . $toConcourse]["map"]);
        $pathToDestination = $graph->breadthFirstSearch($fromGate, $toGate);
        //echo("$fromTerminal . $fromGate, $toTerminal . $toGate -- " .$pathToDestination . "<br />");
        //print_r(calculateDistanceToDestination($pathToDestination, $jsonGateMap[$airportIataCode][$toTerminal]["distance"]));exit;

        $responseArray = calculateDistanceToDestination($pathToDestination,
            $jsonGateMap[$airportIataCode][$toTerminal . $toConcourse]["distance"]);

        //exit;

        // If total is requested then return one array
        if ($returnTotaled) {

            return $responseArray;
        } // Else return an metrics in an one index array
        else {

            return array($responseArray);
        }
    }
}

function noPathResponseArray()
{

    return array(
        "pathToDestination" => "NOP",
        "distanceStepsToGate" => 0,
        "distanceMilesToGate" => 0,
        "walkingTimeToGate" => 0
    );
}

function calculateDistanceToDestination($pathToDestination, $distanceMap)
{

    if ($pathToDestination == "NOP") {

        //json_error("AS_500", "Path not found!");
        return noPathResponseArray();
    }

    $gateArray = explode('>', $pathToDestination);
    $totalSteps = 0;
    $totalMiles = 0;
    $totalWalkingTime = 0;

    for ($i = 0; $i < count_like_php5($gateArray); $i++) {

        $next = $i + 1;

        if (isset($gateArray[$next])) {

            $distanceKey = $gateArray[$i] . '-' . $gateArray[$next];

            $totalSteps += computeTotalSteps($distanceMap[$distanceKey]);
            $totalMiles += computeTotalMiles($distanceMap[$distanceKey]);
            $totalWalkingTime += computeTotalWalkingTime($distanceMap[$distanceKey]);
        }
    }

    return array(
        "pathToDestination" => $pathToDestination,
        "distanceStepsToGate" => $totalSteps,
        "distanceMilesToGate" => roundTotalMiles($totalMiles),
        "walkingTimeToGate" => roundWalkingTime($totalWalkingTime, $totalSteps)
    );
    //return array("distanceStepsToGate" => $totalSteps, "distanceMilesToGate" => round($totalMiles,2), "walkingTimeToGate" => $totalWalkingTime);
}

function computeTotalSteps($distance)
{

    global $factorMultiplier;

    return $distance * $factorMultiplier;
}

function computeTotalMiles($distance)
{

    global $factorMultiplier, $oneStepInMiles;

    return $distance * $factorMultiplier * $oneStepInMiles;
}

function computeTotalWalkingTime($distance)
{

    global $factorMultiplier, $secondsPerStep;

    return $distance * $factorMultiplier * $secondsPerStep;
}

function roundWalkingTime($totalWalkingTime, $totalSteps)
{

    $totalWalkingTime = round($totalWalkingTime / 60);

    // If rounded time is 0, but steps are more than 0, set time to 1
    if ($totalWalkingTime == 0 && $totalSteps != 0) {

        $totalWalkingTime = 1;
    }

    return $totalWalkingTime;
}

function roundTotalMiles($totalMiles)
{

    return round($totalMiles, 2);
}

class Dijkstra
{
    /** @var integer[][] The graph, where $graph[node1][node2]=cost */
    protected $graph;
    /** @var integer[] Distances from the source node to each other node */
    protected $distance;
    /** @var string[][] The previous node(s) in the path to the current node */
    protected $previous;
    /** @var integer[] Nodes which have yet to be processed */
    protected $queue;

    /**
     * @param integer[][] $graph
     */
    public function __construct($graph)
    {
        $this->graph = $graph;
    }

    /**
     * Process the next (i.e. closest) entry in the queue
     *
     * @param string[] $exclude A list of nodes to exclude - for calculating next-shortest paths.
     *
     * @return void
     */
    protected function processNextNodeInQueue(array $exclude)
    {
        // Process the closest vertex
        $closest = array_search(min($this->queue), $this->queue);
        if (!empty($this->graph[$closest]) && !in_array($closest, $exclude)) {
            foreach ($this->graph[$closest] as $neighbor => $cost) {
                if (isset($this->distance[$neighbor])) {
                    if ($this->distance[$closest] + $cost < $this->distance[$neighbor]) {
                        // A shorter path was found
                        $this->distance[$neighbor] = $this->distance[$closest] + $cost;
                        $this->previous[$neighbor] = array($closest);
                        $this->queue[$neighbor] = $this->distance[$neighbor];
                    } elseif ($this->distance[$closest] + $cost === $this->distance[$neighbor]) {
                        // An equally short path was found
                        $this->previous[$neighbor][] = $closest;
                        $this->queue[$neighbor] = $this->distance[$neighbor];
                    }
                }
            }
        }
        unset($this->queue[$closest]);
    }

    /**
     * Extract all the paths from $source to $target as arrays of nodes.
     *
     * @param string $target The starting node (working backwards)
     *
     * @return string[][] One or more shortest paths, each represented by a list of nodes
     */
    protected function extractPaths($target)
    {
        $paths = array(array($target));


        // @todo check if this works perfectly fine
        //while (list($key, $path) = each($paths)) {

        foreach ($paths as $key => $path) {

            if ($this->previous[$path[0]]) {
                foreach ($this->previous[$path[0]] as $previous) {
                    $copy = $path;
                    array_unshift($copy, $previous);
                    $paths[] = $copy;
                }
                unset($paths[$key]);
            }
        }

        // Initial path variable
        $pathToDestination = '';
        foreach ($paths as $firstIndex) {

            foreach ($firstIndex as $hop) {

                $pathToDestination .= $hop . '>';
            }

            // Remove the last > sign
            $pathToDestination = substr($pathToDestination, 0, -1);

            // Only the first index is used
            break;
        }

        // If no path was found
        if (empty($pathToDestination)) {

            return "NOP";
        }

        return $pathToDestination;
    }

    /**
     * Calculate the shortest path through a a graph, from $source to $target.
     *
     * @param string $source The starting node
     * @param string $target The ending node
     * @param string[] $exclude A list of nodes to exclude - for calculating next-shortest paths.
     *
     * @return string[][] Zero or more shortest paths, each represented by a list of nodes
     */
    public function shortestPaths($source, $target, array $exclude = array())
    {

        // echo("$source to $target <br />");

        // The shortest distance to all nodes starts with infinity...
        $this->distance = array_fill_keys(array_keys($this->graph), INF);
        // ...except the start node
        $this->distance[$source] = 0;
        // The previously visited nodes
        $this->previous = array_fill_keys(array_keys($this->graph), array());
        // Process all nodes in order
        $this->queue = array($source => 0);
        while (!empty($this->queue)) {
            $this->processNextNodeInQueue($exclude);
        }
        if ($source === $target) {
            // A null path
            return $source;
        } elseif (empty($this->previous[$target])) {
            // No path between $source and $target
            return "NOP";
        } else {
            // One or more paths were found between $source and $target
            return $this->extractPaths($target);
        }
    }
}

class Graph
{
    protected $graph;
    protected $visited = array();

    public function __construct($graph)
    {
        $this->graph = $graph;
    }

    // find least number of hops (edges) between 2 nodes
    // (vertices)
    public function breadthFirstSearch($origin, $destination)
    {
        // mark all nodes as unvisited

        $response = "";

        foreach ($this->graph as $vertex => $adj) {
            $this->visited[$vertex] = false;
        }

        // create an empty queue
        $q = new SplQueue();

        // enqueue the origin vertex and mark as visited
        $q->enqueue($origin);
        $this->visited[$origin] = true;

        // this is used to track the path back from each node
        $path = array();
        $path[$origin] = new SplDoublyLinkedList();
        $path[$origin]->setIteratorMode(
            SplDoublyLinkedList::IT_MODE_FIFO | SplDoublyLinkedList::IT_MODE_KEEP
        );

        $path[$origin]->push($origin);

        $found = false;
        // while queue is not empty and destination not found
        while (!$q->isEmpty()) {
            $t = $q->dequeue();

            if (!empty($this->graph[$t])) {
                // for each adjacent neighbour
                foreach ($this->graph[$t] as $vertex) {
                    if (!$this->visited[$vertex]) {
                        // if not yet visited, enqueue vertex and mark
                        // as visited
                        $q->enqueue($vertex);
                        $this->visited[$vertex] = true;
                        // add vertex to current path
                        $path[$vertex] = clone $path[$t];
                        $path[$vertex]->push($vertex);
                    }
                }
            }
        }

        // print_r($path);exit;

        if (isset($path[$destination])) {
            // echo "$origin to $destination in ",
            // count_like_php5($path[$destination]) - 1,
            // " hopsn";
            $sep = '';
            foreach ($path[$destination] as $vertex) {
                //echo $sep, $vertex;
                $response .= $sep . $vertex;
                $sep = '>';
            }
            //echo "n";
        } else {
            //echo "-1";
            $response = "NOP";
        }

        return $response;
    }
}

/*
function highlightPath($mapImage, $terminal, $concourse, $startGate, $endGate, $airportIataCode) {

	global $jsonGateCords, $assetsLocation;

	// $mapImage = imagecreatefrompng( $assetsLocation . '\BWI_Terminal_' . $terminal . '.png');

	// echo($startGate . " to " . $endGate . "<br />");
	
	//print_r($jsonGateCords);exit;
	
	if($startGate == "0") {
		
		return $mapImage;
	}
	
	// Add line with pre-defined blue color
	$lineColor = imagecolorallocate($mapImage, 63, 145, 255);
	imagelinethick( $mapImage, $jsonGateCords[$airportIataCode][$terminal . $concourse . $startGate]['x'], $jsonGateCords[$airportIataCode][$terminal . $concourse . $startGate]['y'], $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['x'], $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['y'], $lineColor, 5);
	
	return $mapImage;
}
*/

function highlightPath($mapImage, $terminal, $concourse, $startGate, $endGate, $airportIataCode)
{

    global $jsonGateCords, $assetsLocation;

    // $mapImage = imagecreatefrompng( $assetsLocation . '\BWI_Terminal_' . $terminal . '.png');
    // echo($startGate . " to " . $endGate . "<br />");
    //print_r($jsonGateCords);exit;
    // if($startGate == "0") {

    // 	return $mapImage;
    // }

    // Add line with pre-defined blue color
    $lineColor = imagecolorallocate($mapImage, 63, 145, 255);
    imagelinethick($mapImage,
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $startGate]['x'],
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $startGate]['y'],
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['x'],
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['y'],
        $lineColor,
        10);

    return $mapImage;
}

function addPinAndSave($mapImage, $terminal, $concourse, $endGate, $airportIataCode)
{

    global $jsonGateCords, $assetsLocation;

    $pinImage = imagecreatefrompng($assetsLocation . '/pin.png');

    // Add Pin
    $pinCords = array(
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['x'],
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['y']
    );

    imagecopy($mapImage, $pinImage,
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['x'] - imagesx($pinImage) / 2,
        $jsonGateCords[$airportIataCode][$terminal . $concourse . $endGate]['y'] - imagesy($pinImage) / 2, 0, 0,
        imagesx($pinImage), imagesy($pinImage));

    // Create Image and echo it; collect contents to save
    ob_start();
    imagepng($mapImage);
    $mapImageBinary = ob_get_contents();
    ob_end_clean();

    imagedestroy($mapImage);
    imagedestroy($pinImage);

    return $mapImageBinary;
}

function imagelinethick($image, $x1, $y1, $x2, $y2, $color, $thick = 1)
{

    if ($thick == 1) {
        return imageline($image, $x1, $y1, $x2, $y2, $color);
    }
    $t = $thick / 2 - 0.5;
    if ($x1 == $x2 || $y1 == $y2) {
        return imagefilledrectangle($image, round(min($x1, $x2) - $t), round(min($y1, $y2) - $t),
            round(max($x1, $x2) + $t), round(max($y1, $y2) + $t), $color);
    }
    $k = ($y2 - $y1) / ($x2 - $x1); //y = kx + q
    $a = $t / sqrt(1 + pow($k, 2));
    $points = array(
        round($x1 - (1 + $k) * $a),
        round($y1 + (1 - $k) * $a),
        round($x1 - (1 - $k) * $a),
        round($y1 - (1 + $k) * $a),
        round($x2 + (1 + $k) * $a),
        round($y2 - (1 - $k) * $a),
        round($x2 + (1 - $k) * $a),
        round($y2 + (1 + $k) * $a),
    );
    imagefilledpolygon($image, $points, 4, $color);
    return imagepolygon($image, $points, 4, $color);
}

function fixIndexesForArray($arrayToFix)
{

    $returnArray = array();

    foreach ($arrayToFix as $key => $valueArray) {

        $returnArray[] = $valueArray;
    }

    return $returnArray;
}

function getDirections(
    $airportIataCode,
    $fromLocationId,
    $toRetailerLocationId,
    $referenceRetailerId = "",
    $setCache = true
) {

    global $env_FileTempLocation, $jsonGateMap, $jsonT2TMap, $closeOutCurrentGate, $assetsLocation;

    $airportIataCodeInput = $airportIataCode;

    // Find in cache
    $directionsFromCache = getDirectionsCache($airportIataCode, $fromLocationId, $toRetailerLocationId,
        $referenceRetailerId);
    if (!empty($directionsFromCache) && !$setCache) {

        return $directionsFromCache;
    }

    json_error("AS_INFO", "", "$airportIataCode, $fromLocationId, $toRetailerLocationId, $referenceRetailerId", 3, 1);

    // Verify Airport Iata Code
    if (count_like_php5(getAirportByIataCode($airportIataCode)) == 0) {

        json_error("AS_511", "", "Invalid Airport Code provided for Airport - " . $airportIataCode);
    }

    // Get To Terminal and To Gate of the Retailer from Parse
    list($airportIataCode, $toTerminal, $toConcourse, $toGate, $toGateIsPresecurityLocation) = getGateLocationDetails($airportIataCodeInput,
        $toRetailerLocationId);


    // Get Destinate gate's TerminalGateMap object
    $destinationLocation = getTerminalGateMapByLocationId($airportIataCode, $toRetailerLocationId);

    // Check if From Terminal and From Gate values are valid
    list($airportIataCode, $fromTerminal, $fromConcourse, $fromGate, $fromGateIsPresecurityLocation) = getGateLocationDetails($airportIataCodeInput,
        $fromLocationId);

    if (empty($airportIataCode)
        || empty($toTerminal)
        || empty_zero_allowed($toGate)
        || empty($fromTerminal)
        || empty_zero_allowed($fromGate)
    ) {

        json_error("AS_514",
            " $airportIataCode, $toTerminal, $toConcourse, $toGate -- $fromTerminal, $fromConcourse, $fromGate -- $fromLocationId, $toRetailerLocationId",
            "Provided location Ids are invalid");
    }

    // Find reference retailer name
    $retailerName = "";
    if (!empty($referenceRetailerId)) {

        $objParseRetailerResults = parseExecuteQuery(array("uniqueId" => $referenceRetailerId, "isActive" => true),
            "Retailers");

        if (count_like_php5($objParseRetailerResults) > 0) {

            $retailerName = $objParseRetailerResults[0]->get("retailerName");
        }
    }
    //////////////////////////
    // Check if the From Gate is a Pre-security Gate
    $fromGateIsPreSecurity = 0;

    // Stores flags for Segments with special edge cases
    $exceptionCases = array();

    // Flag that indicates if security reentry is required
    $flagToRenterSecurity = "N";

    // Check if the From Gate is Security gate location
    if ($fromGate == 0) {

        // If going from one terminal to another, we will just remove the first segment as we don't need it, e.g. A0 to last gate of A
        if ($fromTerminal . $fromConcourse != $toTerminal . $toConcourse) {

            $exceptionCases[0] = 1;
        } // We are going within the same terminal, e.g. from Pre-security to some gate
        else {

            // Set from Gate to the gate connected to 0 gate of the terminal
            // Remove the terminal as well
            $fromGate = $jsonGateMap[$airportIataCode][$fromTerminal . $fromConcourse]["map"][$fromGate][0];

            // Set Flag to display can be changed and an additional direction step can be added
            $fromGateIsPreSecurity = $fromGate;
        }
    }
    //////////////////////////

    // echo("$toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate <br />\n");

    // Get Path to destination
    $xarray = $directionsArray = getDistanceMetrics($toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse,
        $fromGate, false, $airportIataCode);


    // print_r($directionsArray);

    // Pull out non T2T segment Paths
    $pathsPerSegment = array();

    $pathsPerSegment[] = isset($directionsArray[0]["pathToDestination"]) ? $directionsArray[0]["pathToDestination"] : $directionsArray["pathToDestination"];

    if (count_like_php5($directionsArray) > 1 && isset($directionsArray[0])) {

        $pathsPerSegment[] = $directionsArray[1]["pathToDestination"];
        $pathsPerSegment[] = $directionsArray[2]["pathToDestination"];
    }

    // Unset the raw path as it is not to be responded
    for ($i = 0; $i < count_like_php5($directionsArray); $i++) {

        unset($directionsArray[$i]["pathToDestination"]);
    }

    $directionsArray = array();

    // Identify Special Direction Text
    $responseArray = array();
    foreach ($pathsPerSegment as $segmentCount => $segmentPath) {

        $gates = array();

        $fromConcourseDisplay = $toConcourseDisplay = "";

        // Connection between terminal or concourse
        if ($fromTerminal != $toTerminal) {

            $connectionT2TText = 'Terminal';
            $toTerminalDisplay = $toTerminal;
            $fromTerminalDisplay = $fromTerminal;
        } else {

            $connectionT2TText = 'Concourse';
            $toTerminalDisplay = '';
            $fromTerminalDisplay = '';
            $toConcourseDisplay = $toConcourse;
            $fromConcourseDisplay = $fromConcourse;
        }

        // T2T Directions
        if ($segmentCount == 1) {

            //print_r($jsonT2TMap);exit;

            $unitsForT2T = $jsonT2TMap[$airportIataCode][$fromTerminal . $fromConcourse . '-' . $toTerminal . $toConcourse];

            $responseArray[$segmentCount]["pathImage"] = '';

            // // Connection between terminal or concourse
            // if($fromTerminal != $toTerminal) {

            // 	$connectionT2TText = 'Terminal';
            // 	$toTerminalDisplay = $toTerminal;
            // 	$fromTerminalDisplay = $fromTerminal;
            // }
            // else {

            // 	$connectionT2TText = 'Concourse';
            // 	$toTerminalDisplay = '';
            // 	$fromTerminalDisplay = '';
            // 	$toConcourseDisplay = $toConcourse;
            // 	$fromConcourseDisplay = $fromConcourse;
            // }

            $responseArray[$segmentCount]["segmentPathText"] = $connectionT2TText . " " . $fromTerminalDisplay . $fromConcourseDisplay . " to " . $toTerminalDisplay . $toConcourseDisplay;

            // Add basic line explaining Terminal connection
            if (empty($toConcourse)) {

                $responseArray[$segmentCount]["directions"][0]["displayText"][] = "Walk towards " . getTerminalGateDisplayName($airportIataCode,
                        $toTerminal, $toConcourse,
                        $unitsForT2T["toTerminalFirstGate"]) . " in " . $connectionT2TText . " " . $toTerminalDisplay;
                //$responseArray[$segmentCount]["directions"][0]["displayText"][] = "Walk towards Gate " . $toTerminal . $unitsForT2T["toTerminalFirstGate"] . " in Terminal " . $toTerminal;
            } else {

                $responseArray[$segmentCount]["directions"][0]["displayText"][] = "Walk towards " . getTerminalGateDisplayName($airportIataCode,
                        $toTerminal, $toConcourse,
                        $unitsForT2T["toTerminalFirstGate"]) . " in " . $connectionT2TText . " " . $toTerminalDisplay . $toConcourseDisplay;
                //$responseArray[$segmentCount]["directions"][0]["displayText"][] = "Walk towards Gate " . $toTerminal . "-" . $toConcourse . $unitsForT2T["toTerminalFirstGate"] . " in Terminal " . $toTerminal;
            }

            // Set flag for Security Reentry if required
            // However, skip it if the destination is a pre-security location
            $arrayReEnterSecurity = array();
            $securityClearanceTimeInMins = 0;
            if (isset($unitsForT2T["flagToRenterSecurity"]) && $unitsForT2T["flagToRenterSecurity"] == "Y"
                && $destinationLocation->get("isPresecurityLocation") == false
            ) {

                $flagToRenterSecurity = "Y";
                $arrayReEnterSecurity = array("(Requires security re-entry)");

                // Add time for Security clearance
                // TODO: Utilize dynamic security clearance time between terminals
                $securityClearanceTimeInMins = 15;
            }
            $responseArray[$segmentCount]["directions"][0]["directionCue"] = "S";
            $responseArray[$segmentCount]["directions"][0]["distanceSteps"] = computeTotalSteps($unitsForT2T["nextTerminalDistanceUnits"]);
            $responseArray[$segmentCount]["directions"][0]["distanceMiles"] = roundTotalMiles(computeTotalMiles($unitsForT2T["nextTerminalDistanceUnits"]));
            $responseArray[$segmentCount]["directions"][0]["walkingTime"] = $securityClearanceTimeInMins + roundWalkingTime(computeTotalWalkingTime($unitsForT2T["nextTerminalDistanceUnits"]),
                    $responseArray[$segmentCount]["directions"][0]["distanceSteps"]);

            $responseArray[$segmentCount]["distanceSteps"] = computeTotalSteps($unitsForT2T["nextTerminalDistanceUnits"]);
            $responseArray[$segmentCount]["distanceMiles"] = roundTotalMiles(computeTotalMiles($unitsForT2T["nextTerminalDistanceUnits"]));
            $responseArray[$segmentCount]["walkingTime"] = roundWalkingTime(computeTotalWalkingTime($unitsForT2T["nextTerminalDistanceUnits"]),
                $responseArray[$segmentCount]["distanceSteps"]);

            // If Security change text is available
            $startingS = 1;
            if (count_like_php5($arrayReEnterSecurity) > 0) {

                // Move the current directions to index 1
                $tempArray = $responseArray[$segmentCount]["directions"][0];

                // Reset
                unset($responseArray[$segmentCount]["directions"][0]);

                $responseArray[$segmentCount]["directions"][0]["displayText"][] = $arrayReEnterSecurity[0];
                $responseArray[$segmentCount]["directions"][0]["directionCue"] = "S";
                $responseArray[$segmentCount]["directions"][0]["distanceSteps"] = 0;
                $responseArray[$segmentCount]["directions"][0]["distanceMiles"] = 0;
                $responseArray[$segmentCount]["directions"][0]["walkingTime"] = 0;

                // Put original 0 index on 1
                $responseArray[$segmentCount]["directions"][1] = $tempArray;

                $startingS = 2;
            }

            // Add specific Terminal connection, when available
            if (!empty($unitsForT2T["connectionText"])) {

                $connectSpecialText = explode(". ", $unitsForT2T["connectionText"]);
                for ($s = $startingS; $s < count_like_php5($connectSpecialText) + $startingS; $s++) {

                    $responseArray[$segmentCount]["directions"][$s]["displayText"][] = trim($connectSpecialText[$s - $startingS]);
                    $responseArray[$segmentCount]["directions"][$s]["directionCue"] = "S";
                    $responseArray[$segmentCount]["directions"][$s]["distanceSteps"] = 0;
                    $responseArray[$segmentCount]["directions"][$s]["distanceMiles"] = 0;
                    $responseArray[$segmentCount]["directions"][$s]["walkingTime"] = 0;
                }
            }

            continue;
        }

        // Check if there is a valid path
        if (strcasecmp($segmentPath, "NOP") == 0) {

            $descriptiveError = "Segment #" . $segmentCount . " resulted in no defined path. Overall call was made on " . $fromTerminal . '-' . $fromConcourse . '-' . $fromGate . " to " . $toTerminal . '-' . $toConcourse . '-' . $toGate;
            json_error("AS_501", "Path not found!" . $descriptiveError, "", 1);
            // json_error("AS_501", "", "Path not found!" . $descriptiveError, 2);

        } else {


            $gates = explode(">", $segmentPath);
            array_walk($gates, "convertToInt");

            // From segment
            if ($segmentCount == 0) {

                $stepTerminal = $fromTerminal;
                $stepConcourse = $fromConcourse;
            } // To segment
            else {

                $stepTerminal = $toTerminal;
                $stepConcourse = $toConcourse;
            }

            $directionsTextArray = $jsonGateMap[$airportIataCode][$stepTerminal . $stepConcourse]["directions"]["text"];
            $directionsCueArray = $jsonGateMap[$airportIataCode][$stepTerminal . $stepConcourse]["directions"]["cue"];

            /////////////////////////////
            // Check if the Path image already exists in Parse
            $fromLocation = getTerminalGateMapByLocationRef($airportIataCode, $stepTerminal, $stepConcourse,
                strval($gates[0]));

            $toLocation = getTerminalGateMapByLocationRef($airportIataCode, $stepTerminal, $stepConcourse,
                strval($gates[count_like_php5($gates) - 1]));


            /*
            Image path creation
                        $objParseQueryDirectionImagesResults = parseExecuteQuery(array(
                                                                                    "fromLocation" => $fromLocation,
                                                                                    "toLocation" => $toLocation
                                                                                    ), "DirectionImages");

                        // If Image is found, save URL and not regenerate
                        if(count_like_php5($objParseQueryDirectionImagesResults) > 0) {

                            $responseArray[$segmentCount]["pathImage"] = preparePublicS3URL($objParseQueryDirectionImagesResults[0]->get("markedImageURL"), getS3KeyPath_ImagesDirection($airportIataCode), $GLOBALS['env_S3Endpoint']);
                        }
                        // Create Path Image
                        else {

                            // S3 file name
                            $mapFileName = $fromLocation->get('airportIataCode') . '_'
                                . $fromLocation->get('terminal') . $fromLocation->get('concourse') . $fromLocation->get('gate') . '_'
                                . $toLocation->get('terminal') . $toLocation->get('concourse') . $toLocation->get('gate')
                                . '.png';

                            // Fetch the map
                            $mapImage = imagecreatefrompng($assetsLocation . "/" . $airportIataCode . '_' . $stepTerminal . $stepConcourse . '.png');
            */

            /*
            OLD process
            // Find Breakpoints and highlight path
            // Set the first breakpoint to the first gate
            $lastBreakPointGate = $gates[0];

            for($i=0; $i<count_like_php5($gates); $i++) {

                $currentGate = $gates[$i];
                $nextGate = isset($gates[$i+1]) ? $gates[$i+1] : $gates[$i];

                // If Last Gate in the Path
                if($i == (count_like_php5($gates)-1)) {

                    // Last Break Point to Next Gate
                    $mapImage = highlightPath($mapImage, $stepTerminal, $stepConcourse, $lastBreakPointGate, $nextGate, $airportIataCode);
                    continue;
                }

                if(!empty($directionsCueArray[$currentGate . '-' . $nextGate])) {

                    // Last Break Point to Current Gate
                    $mapImage = highlightPath($mapImage, $stepTerminal, $stepConcourse, $lastBreakPointGate, $currentGate, $airportIataCode);

                    // Current Gate to Next Gate
                    $mapImage = highlightPath($mapImage, $stepTerminal, $stepConcourse, $currentGate, $nextGate, $airportIataCode);

                    // Save Net Gate as Last Breatk Point
                    $lastBreakPointGate = $nextGate;
                }
            }
            */
            /*
            Image path creation
                            for($i=0; $i<count_like_php5($gates); $i++) {

                                $currentGate = $gates[$i];
                                $nextGate = isset($gates[$i+1]) ? $gates[$i+1] : '';

                                // If not the last gate
                                if(!empty($nextGate)) {

                                    // Draw a path between gates
                                    $mapImage = highlightPath($mapImage, $stepTerminal, $stepConcourse, $currentGate, $nextGate, $airportIataCode);
                                }
                            }

                            // Add Pin and Save to Local Drive
                            // $gates[count_like_php5($gates)-1] = Last Gate in the Path
                            $mapImageBinary = addPinAndSave($mapImage, $stepTerminal, $stepConcourse, $gates[count_like_php5($gates)-1], $airportIataCode);

                            // S3 Upload Directions Image
                            $s3_client = getS3ClientObject();
                            $keyWithFolderPath = getS3KeyPath_ImagesDirection($airportIataCode) . '/' . $mapFileName;
                            $markedImageURL = S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath, $mapImageBinary, true);

                            if(is_array($markedImageURL)) {

                                json_error($markedImageURL["error_code"], "", $markedImageURL["error_message_log"] . " Directions Image save failed", 1, 1);
                            }

                            // Save to Parse and get URL
                            // $responseArray[$segmentCount]["pathImage"] = saveHighlightedPathImageToParse(ParseFile::createFromData($mapImageBinary, "map.png"), new ParseObject("DirectionImages"), $fromLocation, $toLocation);

                            $markedImageFileName = extractFilenameFromS3URL($markedImageURL);
                            saveHighlightedPathImageToParse($markedImageFileName, $fromLocation, $toLocation);

                            $responseArray[$segmentCount]["pathImage"] = $markedImageURL;
                        }
                        /////////////////////////////
             */

            // If the To Gate is the final destination then, show the Retailer name in the To text
            // If the count of segments returned were 1, meaning there is only one
            // Or if the segmentCount index = 2 (we unset index 1 to plug in Terminal to Terminal)

            $firstGateInSegment = getTerminalGateDisplayName($airportIataCode, $stepTerminal, $stepConcourse,
                $gates[0]);

            // if($gates[count_like_php5($gates)-1] == 18) {
            // 	print_r($gates);
            // 	exit;
            // }

            $lastGateInSegment = getTerminalGateDisplayName($airportIataCode, $stepTerminal, $stepConcourse,
                $gates[count_like_php5($gates) - 1]);

            if ((count_like_php5($pathsPerSegment) == 1 || $segmentCount == 2) && !empty($retailerName)) {

                $toSegmentPathText = $retailerName; // . " (Gate " . $gates[count_like_php5($gates)-1] . ")"; // Commented out the Gate location
            } // Else show the Gate number
            else {

                $toSegmentPathText = getTerminalGateDisplayName($airportIataCode, $stepTerminal, $stepConcourse,
                    $gates[count_like_php5($gates) - 1]);
            }

            // Flag exception cases
            // If we have already set before (e.g. for Pre-security) this then don't change
            if (!isset($exceptionCases[$segmentCount])) {

                // From Gate = Last gate in the terminal
                if ($gates[0] == $gates[count_like_php5($gates) - 1]) {

                    $exceptionCases[$segmentCount] = 1;
                } else {

                    $exceptionCases[$segmentCount] = 0;
                }
            }

            /*
            // Override text for Pre-security for 0th segment
            if($fromGateIsPreSecurity != 0
                && $segmentCount == 0) {

                $fromSegmentPathText = "Pre-security";
            }
            else {

                $fromSegmentPathText = "Gate " . $gates[0];
            }
            */

            $fromSegmentPathText = getTerminalGateDisplayName($airportIataCode, $stepTerminal, $stepConcourse,
                $gates[0]);

            // If from and to gate locations are the same, and segmentCounter is not the first one
            if (strcasecmp($fromSegmentPathText, $toSegmentPathText) == 0
                && $segmentCount > 0
            ) {

                $responseArray[$segmentCount]["segmentPathText"] = "Arrive at " . $toSegmentPathText;
            } else {

                $responseArray[$segmentCount]["segmentPathText"] = $fromSegmentPathText . " to " . $toSegmentPathText;
            }

            /////////////////////////////
            // Direction Text
            // Start looking at pairs of gates
            $groupedGatesCounter = -2;

            $directionsStepsCounter = 0;
            $lastGate = "";
            $closeOutCurrentGate = $gates[0];

            for ($i = 0; $i < count_like_php5($gates); $i++) {

                $currentGate = $gates[$i];
                $nextGate = isset($gates[$i + 1]) ? $gates[$i + 1] : $gates[$i];

                // If second gate, so set the direction
                if ($i == 0) {

                    // If linked to Pre-security
                    $walkTowardsText = getTerminalGateDisplayName($airportIataCode, $stepTerminal, $stepConcourse,
                        $nextGate);

                    /*
                    $walkTowardsText = "Gate $nextGate";
                    if($nextGate == "0") {

                        $walkTowardsText = "Security area of the terminal";
                    }
                    */

                    $responseArray[$segmentCount]["directions"][$directionsStepsCounter] = array(

                        "displayText" => array("Walk towards " . $walkTowardsText),
                        "directionCue" => "S",
                        "distanceSteps" => 0,
                        "distanceMiles" => 0,
                        "walkingTime" => 0
                    );

                    $directionsStepsCounter++;
                } // If Last Gate in the Path
                else {
                    if ($i == (count_like_php5($gates) - 1)) {

                        // Close out last step for distance metrics
                        list($directionsStepsCounter, $responseArray) = closeOutForDistanceMetrics($responseArray,
                            $segmentCount, $directionsStepsCounter, $stepTerminal, $stepConcourse, $currentGate,
                            $airportIataCode);

                        continue;
                    }
                }

                // Check if there is special text between currentGate and nextGate
                if (isset($directionsTextArray[$currentGate . '-' . $nextGate])
                    && !is_numeric($directionsTextArray[$currentGate . '-' . $nextGate])
                    && !empty($directionsTextArray[$currentGate . '-' . $nextGate])
                ) {

                    // Close out last step for distance metrics
                    list($directionsStepsCounter, $responseArray) = closeOutForDistanceMetrics($responseArray,
                        $segmentCount, $directionsStepsCounter, $stepTerminal, $stepConcourse, $currentGate,
                        $airportIataCode);

                    $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["displayText"][] = $directionsTextArray[$currentGate . '-' . $nextGate];

                    // Check if there is a direction Cue
                    $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["directionCue"] = "S";
                    if (!empty($directionsCueArray[$currentGate . '-' . $nextGate])) {

                        // Just add cue
                        $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["directionCue"] = $directionsCueArray[$currentGate . '-' . $nextGate];
                    }

                    // Add Distance metrics for this unique case
                    $responseArray = getPartDistanceMetrics($responseArray, $segmentCount, $directionsStepsCounter,
                        $stepTerminal, $stepConcourse, $nextGate, $airportIataCode);
                    // Change Close Out Gate so next metrics doesn't count this in
                    $closeOutCurrentGate = $nextGate;

                    $groupedGatesCounter = 0;

                    // Update so step after this can be calculated
                    $directionsStepsCounter++;
                } // Check if there is a direction Cue
                else {
                    if (!empty($directionsCueArray[$currentGate . '-' . $nextGate])) {

                        // Close out last step for distance metrics
                        list($directionsStepsCounter, $responseArray) = closeOutForDistanceMetrics($responseArray,
                            $segmentCount, $directionsStepsCounter, $stepTerminal, $stepConcourse, $currentGate,
                            $airportIataCode);

                        // Add Cue Step
                        $cueText = $directionsCueArray[$currentGate . '-' . $nextGate] == "L" ? "Left" : "Right";
                        $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["displayText"][] = "Turn $cueText towards " . getTerminalGateDisplayName($airportIataCode,
                                $stepTerminal, $stepConcourse, $nextGate);
                        $responseArray[$segmentCount]["directions"][$directionsStepsCounter]["directionCue"] = $directionsCueArray[$currentGate . '-' . $nextGate];

                        // Add Distance metrics for this unique case
                        $responseArray = getPartDistanceMetrics($responseArray, $segmentCount, $directionsStepsCounter,
                            $stepTerminal, $stepConcourse, $nextGate, $airportIataCode);

                        // Change Close Out Gate so next metrics doesn't count this in
                        $closeOutCurrentGate = $nextGate;

                        $groupedGatesCounter = 0;

                        // Update so step after this can be calculated
                        //$directionsStepsCounter++;
                    } else {

                        // Count how many gate didn't have special text
                        $groupedGatesCounter++;

                        if ($groupedGatesCounter == 3) {

                            // Close out last step for distance metrics
                            list($directionsStepsCounter, $responseArray) = closeOutForDistanceMetrics($responseArray,
                                $segmentCount, $directionsStepsCounter, $stepTerminal, $stepConcourse, $currentGate,
                                $airportIataCode);

                            $groupedGatesCounter = 0;

                            // Update so step after this can be calculated
                            //$directionsStepsCounter++;
                        }
                    }
                }

                $lastGate = $currentGate;
            }

            // Add segment totals
            $distanceStepsSegmentTotal = 0;
            $distanceMilesSegmentTotal = 0;
            $walkingTimeSegmentTotal = 0;

            // Total up direction metrics as Segment metrics
            for ($j = 0; $j < count_like_php5($responseArray[$segmentCount]["directions"]); $j++) {

                $distanceStepsSegmentTotal += $responseArray[$segmentCount]["directions"][$j]["distanceSteps"];
                $distanceMilesSegmentTotal += $responseArray[$segmentCount]["directions"][$j]["distanceMiles"];
                $walkingTimeSegmentTotal += $responseArray[$segmentCount]["directions"][$j]["walkingTime"];
            }

            $responseArray[$segmentCount]["distanceSteps"] = $distanceStepsSegmentTotal;
            $responseArray[$segmentCount]["distanceMiles"] = $distanceMilesSegmentTotal;
            $responseArray[$segmentCount]["walkingTime"] = $walkingTimeSegmentTotal;
        }
    }


    // Add final step of Arriving at the Retailer
    $gateHint = "";
    if ($firstGateInSegment != $lastGateInSegment) {

        $gateHint = " (" . $lastGateInSegment . ")";
    }

    // Set a default name if retailer name is not found
    if (empty($retailerName)) {

        $retailerName = "Destination";
    }

    $responseArray[$segmentCount]["directions"][$directionsStepsCounter] = array(

        "displayText" => array("Arrive at " . $retailerName . $gateHint),
        "directionCue" => "S",
        "distanceSteps" => 0,
        "distanceMiles" => 0,
        "walkingTime" => 0
    );

    // Get total Trip distance metrics
    $distanceStepsTripTotal = 0;
    $distanceMilesTripTotal = 0;
    $walkingTimeTripTotal = 0;

    for ($segmentCount = 0; $segmentCount < count_like_php5($responseArray); $segmentCount++) {

        $distanceStepsTripTotal += $responseArray[$segmentCount]["distanceSteps"];
        $distanceMilesTripTotal += $responseArray[$segmentCount]["distanceMiles"];
        $walkingTimeTripTotal += $responseArray[$segmentCount]["walkingTime"];
    }
    //////////////////////////////////

    // Check of Exception / edge cases
    $flagFixMainArray = 0;
    foreach ($exceptionCases as $segmentCount => $segmentFlag) {

        if ($segmentFlag == 1) {

            // If the From Gate is the last gate of the Terminal, then remove that segment
            // Don't unset if this is the only segment we have, e.g. in cases where we go from A1 to A1
            if ($segmentCount == 0) {

                if (count_like_php5($responseArray) > 1) {

                    // Reset just the directions section, as we will update this later in exceptionCases section
                    // $responseArray[0]["directions"] = [];
                    unset($responseArray[0]);
                    $flagFixMainArray = 1;
                }
            } // If the Retailer Gate is the First gate in the terminal
            else {

                // Unset all direction points except the last one
                for ($i = 0; $i < count_like_php5($responseArray[2]["directions"]); $i++) {

                    unset($responseArray[2]["directions"][$i]);
                }

                $responseArray[2]["directions"] = fixIndexesForArray($responseArray[2]["directions"]);
            }
        }
    }

    // Add a Pre-security step if the flag is set (only when going same terminal Pre-security to its another Gate)
    if ($fromGateIsPreSecurity != 0
        && isset($responseArray[0])
    ) {

        $distances = getDistanceMetrics($fromTerminal, $fromConcourse, $fromGateIsPreSecurity, $fromTerminal,
            $fromConcourse, 0, false, $airportIataCode);

        array_unshift(
            $responseArray[0]["directions"],
            array(
                "displayText" => array("Walk towards $connectionT2TText $fromTerminalDisplay$fromConcourseDisplay gates"),
                "directionCue" => "S",
                "distanceSteps" => $distances[0]["distanceStepsToGate"],
                "distanceMiles" => $distances[0]["distanceMilesToGate"],
                "walkingTime" => $distances[0]["walkingTimeToGate"],
            )
        );

        $distanceStepsTripTotal += $distances[0]["distanceStepsToGate"];
        $distanceMilesTripTotal += $distances[0]["distanceMilesToGate"];
        $walkingTimeTripTotal += $distances[0]["walkingTimeToGate"];

        $distanceStepsToGate = 0;
        $distanceMilesToGate = 0;
        $walkingTimeToGate = 0;

        foreach ($responseArray[0]["directions"] as $directionArea) {

            $distanceStepsToGate += $directionArea["distanceSteps"];
            $distanceMilesToGate += $directionArea["distanceMiles"];
            $walkingTimeToGate += $directionArea["walkingTime"];
        }

        $responseArray[0]["distanceSteps"] = $distanceStepsToGate;
        $responseArray[0]["distanceMiles"] = $distanceMilesToGate;
        $responseArray[0]["walkingTime"] = $walkingTimeToGate;
    }

    if ($flagFixMainArray == 1) {

        $responseArray = fixIndexesForArray($responseArray);
    }
    //////////////////////////////////

    $responseArrayFinal = array();

    $responseArrayFinal["directionsBySegments"] = $responseArray;

    // EDIT: to only save total value
    $responseArrayFinal = [];

    // Special Exception for starting location Pre-security but destination post security
    // If flagToRenterSecurity = N
    if (strcasecmp($flagToRenterSecurity, "N") == 0
        && $fromGateIsPresecurityLocation == true
        && $toGateIsPresecurityLocation == false
    ) {

        $flagToRenterSecurity = "Y";
    }

    $responseArrayFinal["totalDistanceMetricsForTrip"] = array(

        "distanceSteps" => $distanceStepsTripTotal,
        "distanceMiles" => $distanceMilesTripTotal,
        "walkingTime" => $walkingTimeTripTotal,
        "reEnterSecurityFlag" => $flagToRenterSecurity
    );

    if ($setCache) {

        setDirectionsCache($responseArrayFinal, $airportIataCode, $fromLocationId, $toRetailerLocationId,
            $referenceRetailerId);
    }

    return $responseArrayFinal;
}

function getLocationForSession($sessionDevice)
{

    $nearAirportIataCode = "";
    $locationCity = $locationState = $locationCountry = "";

    // If Geo Coordinates are available
    if (!empty($sessionDevice->get('geoLocation')->getLatitude())
        && $sessionDevice->get('geoLocation')->getLatitude() != 0
    ) {

        $locationSource = 'chords';
        $googleAddress = getpage("https://maps.googleapis.com/maps/api/geocode/json?latlng=" . $sessionDevice->get('geoLocation')->getLatitude() . "," . $sessionDevice->get('geoLocation')->getLongitude() . "&key=" . $GLOBALS['env_GoogleMapsKey']);

        try {

            $googleAddressArray = json_decode($googleAddress, true);

            if (isset($googleAddressArray["results"][0]["address_components"])) {
                foreach ($googleAddressArray["results"][0]["address_components"] as $address_component) {

                    // if(in_array("locality", $address_component["types"])) {

                    //     $locationCity = $address_component["long_name"];
                    // }
                    if (in_array("administrative_area_level_1", $address_component["types"])) {

                        $locationState = $address_component["long_name"];
                    } else {
                        if (in_array("country", $address_component["types"])) {

                            $locationCountry = $address_component["long_name"];
                        }
                    }
                }
            }
        } catch (Exception $ex) {

            $locationCity = $locationState = $locationCountry = "";
        }

        // Find if this location is near an airport
        $airports = getAirportsArray();
        foreach ($airports["byAirportIataCode"] as $aiport) {

            $isAtAirport = isAtAirport(
                [
                    "lat" => $sessionDevice->get('geoLocation')->getLatitude(),
                    "lng" => $sessionDevice->get('geoLocation')->getLongitude()
                ],
                [
                    "lat" => $aiport->get("geoPointLocation")->getLatitude(),
                    "lng" => $aiport->get("geoPointLocation")->getLongitude()
                ],
                5);

            if (strcasecmp($isAtAirport, "Y") == 0) {

                $nearAirportIataCode = $aiport->get("airportIataCode");
                break;
            }
        }
    } // Else use the IP lookup
    else {
        if (!empty($sessionDevice->get('IPAddress'))) {

            list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationByIP($sessionDevice->get('IPAddress'));
        }
    }

    return [$nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource];
}

function getLocationByIP($ipAddressInput)
{

    $nearAirportIataCode = $locationCity = $locationState = $locationCountry = "";
    $longitude = $latitude = 0;

    // Sleep for 0.5 second
    usleep(1000000 / 2);

    try {

        $ipAddresses = explode(" ~ ", $ipAddressInput);
        if (!empty($ipAddresses[0])) {

            $locationInfo = getIPLocationFromCache($ipAddresses[0]);
            if (empty($locationInfo)) {

                $locationInfoJson = getpage("http://api.ipstack.com/" . $ipAddresses[0] . '?access_key=' . $GLOBALS['env_IPStackKey']);
                $locationInfo = json_decode($locationInfoJson, true);

                setIPLocationToCache($ipAddresses[0], $locationInfo);
            }

            $locationCountry = isset($locationInfo["country_name"]) && !is_null($locationInfo["country_name"]) ? $locationInfo["country_name"] : "";
            $locationState = isset($locationInfo["region_name"]) && !is_null($locationInfo["region_name"]) ? $locationInfo["region_name"] : "";
            // $locationCity = $locationInfo["city"];
            $latitude = isset($locationInfo["latitude"]) && !is_null($locationInfo["latitude"]) ? $locationInfo["latitude"] : 0;
            $longitude = isset($locationInfo["longitude"]) && !is_null($locationInfo["latitude"]) ? $locationInfo["longitude"] : 0;
        } else {

            throw new Exception("No IP address found");
        }
    } catch (Exception $ex) {

        $locationCity = $locationState = $locationCountry = "";
    }

    // Find if this location is near an airport
    $airports = getAirportsArray();
    foreach ($airports["byAirportIataCode"] as $aiport) {

        $isAtAirport = isAtAirport(
            [
                "lat" => $latitude,
                "lng" => $longitude
            ],
            [
                "lat" => $aiport->get("geoPointLocation")->getLatitude(),
                "lng" => $aiport->get("geoPointLocation")->getLongitude()
            ],
            5);

        if (strcasecmp($isAtAirport, "Y") == 0) {

            $nearAirportIataCode = $aiport->get("airportIataCode");
            break;
        }
    }

    return [$nearAirportIataCode, $locationCity, $locationState, $locationCountry, 'ip'];
}

?>
