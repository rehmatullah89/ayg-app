<?php
// @todo uniqueId is not added automatically


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

	$fileArray = array_map('str_getcsv', file('./dummy/Airlines.csv'));

	$objectKeys = array_map('trim', array_shift($fileArray));

	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither
	$objectKeyIsArray = array(
								"airlineIataCode" => "N",
								"airlineIcaoCode" => "N",
								"airlineName" => "N",
								"airlineCallSign" => "N", 
								"airlineCountry" => "N"
							);
	
	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "Airlines", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("airlineIataCode"), []); // the last array lists the keys to combine to make a lookupkey
	
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*Airlines*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*airlines*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*AIRLINES*");

	print_r(resetCache($cacheKeyList));
	// setConfMetaUpdate();

	@ob_end_clean();
	
?>
