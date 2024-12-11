<?php

// @todo - not needed now

	require 'dirpath.php';
	$fullPathToBackendLibraries = "";
	
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	
	// require 'parsedataload_functions.php';


	ob_start();
	while (ob_get_level() > 0)
    ob_end_flush();
	
	$fileArray = array_map('str_getcsv', file('<path_to_files>\DualPartnerConfig - prod.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"airportId" => "N",
								"partner" => "N",
							    "retailerId" => "N",
							    "uniqueId" => "N",
							    "uniqueId" => "N",
							    "extTaxCalculation" => "N",
							    "description" => "N",
							    "tabletIntegrated" => "N"
							);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "DualPartnerConfig", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("uniqueId"), [], []); // the second to last array lists the keys to combine to make a lookupkey

	$cacheKeyList[] = $GLOBALS['redis']->keys("*RetailerPOSConfig*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*DualPartnerConfig*");

	print_r(resetCache($cacheKeyList));

	@ob_end_clean();
	
?>
