<?php
// @todo - works fine

	require 'dirpath.php';
$fullPathToBackendLibraries = "../";
	
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	
	// require 'parsedataload_functions.php';


	ob_start();	
	while (ob_get_level() > 0)
    ob_end_flush();
	
	$fileArray = array_map('str_getcsv', file('./dummy/zDeliveryCoveragePeriod.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"dayOfWeek" => "N",
								"airportIataCode" => "BWI",
							    "coverageTime" => "X",
							);

	list($fileArray, $objectKeys, $objectKeyIsArray) = processDeliveryCoverageItemTimesData($fileArray, $objectKeys, $objectKeyIsArray);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "zDeliveryCoveragePeriod", $fileArray, $objectKeyIsArray, $objectKeys, "Y", ["dayOfWeek", "airportIataCode"], [], []); // the second to last array lists the keys to combine to make a lookupkey

	@ob_end_clean();
	
?>
