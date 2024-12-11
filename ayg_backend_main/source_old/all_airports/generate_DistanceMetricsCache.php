E<?php

	require 'dirpath.php';
    $fullPathToBackendLibraries = "../../";
	
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	
	// require 'parsedataload_functions.php';


	use Parse\ParseQuery;

	ob_start();	
	while (ob_get_level() > 0)
    ob_end_flush();

	rebuildDistanceAndDirectionsCache();

function rebuildDistanceAndDirectionsCache() {

	// $airports = parseExecuteQuery(["__CONTAINEDIN__airportIataCode" => ["BWI", "MCO"]], "Airports", "airportIataCode");
	$airports = parseExecuteQuery(["isReady" => true], "Airports", "airportIataCode");
	foreach($airports as $airport) {

		// Rebuild terminal gate map cache
		getTerminalGateMapArray($airport->get('airportIataCode'));

		$airportIataCode = $airport->get('airportIataCode');
		$result = [];
		$resultDirections = [];
		$nop = 0;
		$lastAirportIataCode = "";

		$terminalGateMap = parseExecuteQuery(["airportIataCode" => $airportIataCode, "includeInGateMap" => true], "TerminalGateMap", "airportIataCode");

		echo(" -- " . $airportIataCode . "<br />");flush();@ob_flush();

		// TAG
		foreach($terminalGateMap as $j => $fromGateLocation) {

			echo(" - " . ($j+1) . " of " . count_like_php5($terminalGateMap) . " - " . $fromGateLocation->getObjectId() . "<br />");flush();@ob_flush();
			for($i=0;$i<count($terminalGateMap);$i++) {

				$toTerminal = $terminalGateMap[$i]->get("terminal");
				$toConcourse = $terminalGateMap[$i]->get("concourse");
				$toGate = $terminalGateMap[$i]->get("gate");
				$toLocationId = $terminalGateMap[$i]->getObjectId();

				$fromTerminal = $fromGateLocation->get("terminal");
				$fromConcourse = $fromGateLocation->get("concourse");
				$fromGate = $fromGateLocation->get("gate");
				$fromLocationId = $fromGateLocation->getObjectId();

				$returnTotaled = true;
				$cacheIndexName = getDistanceMetricsCacheIndexName($toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate, $returnTotaled);

				if(strcasecmp($fromLocationId, $toLocationId)!=0) {
	
					$resultDirections[$fromLocationId . '-' . $toLocationId] = getDirections($airportIataCode, $fromLocationId, $toLocationId, "", false);
				}

				$result[$cacheIndexName] = getDistanceMetrics($toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate, $returnTotaled, $airportIataCode, true);

				if(isset($result[$cacheIndexName]["pathToDestination"])
					&& $result[$cacheIndexName]["pathToDestination"] == "NOP") {

					$GLOBALS['testmode'] = 1;
					print_r($result);exit;
					echo($cacheIndexName . "\r\n");

					$nop++;
				}

				$returnTotaled = false;
				$cacheIndexName = getDistanceMetricsCacheIndexName($toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate, $returnTotaled);

				$result[$cacheIndexName] = getDistanceMetrics($toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate, $returnTotaled, $airportIataCode, true);

				if(isset($result[$cacheIndexName]["pathToDestination"])
					&& $result[$cacheIndexName]["pathToDestination"] == "NOP") {

					$nop++;
				}
			}
		}

		// setCache("__DISTANCEMETRICS__" . $airportIataCode, $result, 1);
		// TAG
		echo("Setting Distance Metrics" . "<br />");flush();@ob_flush();
		foreach($result as $cacheKey => $valueArray) {

			hSetCache("__HASH__DISTANCEMETRICS__" . $airportIataCode, $cacheKey, $valueArray, 1, -1);
		}

		// setCache("__DIRECTIONS__" . $airportIataCode, $resultDirections, 1);
		echo("Setting Directions" . "<br />");flush();@ob_flush();
		foreach($resultDirections as $cacheKey => $valueArray) {

			hSetCache("__HASH__DIRECTIONS__" . $airportIataCode, $cacheKey, $valueArray, 1, -1);
		}

		// TAG
		echo($airportIataCode . " - " . count_like_php5($result) . " - NOP: " . $nop . "\r\n" . "<br />");
		flush();@ob_flush();

		$result = [];
		$resultDirections = [];
	}
}

	@ob_end_clean();
		
?>
