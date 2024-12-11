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

	$fileArray = array_map('str_getcsv', file('./all_airports/data/Airports.csv'));

	// Skip the Header row and create key arrays
	// $objectKeys = array_map('trim', explode(",", array_shift($fileArray)));
	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));

	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither
	$objectKeyIsArray = array(
								"airportName" => "N",
								"airportCity" => "N",
								"airportCountry" => "N",
								"airportIataCode" => "N", 
								"airportIcaoCode" => "N",
								"isReady" => "N", 
								"isDeliveryReady" => "N", 
								"isPickupReady" => "N", 
								"imageBackground" => "N", 
								"geoPointLocation" => "G",
								"airportTimezone" => "N", 
								"employeeDiscountPCT" => "F",
								"militaryDiscountPCT" => "F",
        						"deliveryFeeInCents" => "I",
        						"employeeDeliveryFeeInCents" => "I",
								"pickupFeeInCents" => "I",
								"serviceFeePCT" => "F",
							);
	
	$imagesIndexesWithPaths = array(
			"imageBackground" => [
					"S3KeyPath" => getS3KeyPath_ImagesAirportBackground(),
					"useUniqueIdInName" => "N",
					"maxWidth" => '',
					"maxHeight" => '',
					"createThumbnail" => false,
					"imagePath" => 'all_airports']
	);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "Airports", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("airportIataCode"), $imagesIndexesWithPaths); // the last array lists the keys to combine to make a lookupkey
	
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*AIRPORT*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*Airport*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*airport*");

	print_r(resetCache($cacheKeyList));
	setConfMetaUpdate();

	@ob_end_clean();
?>
