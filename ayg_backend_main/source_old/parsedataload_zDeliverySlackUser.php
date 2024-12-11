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
	
	$fileArray = array_map('str_getcsv', file('./dummy/zDeliverySlackUser.csv'));


	// change true/false strings into bools

	var_dump($fileArray);

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
var_dump($objectKeys);
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"airportIataCode" => "N",
								"slackUserId" => "N",
							    "deliveryName" => "N",
							    "slackChannelName" => "N",
							    "slackURL" => "N",
							    "slackUsername" => "N",
							    "gender" => "N",
							    "SMSPhoneNumber" => "N",
							    "isActive" => "N",
							    "isDeleted" => "N"
							);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "zDeliverySlackUser", $fileArray, $objectKeyIsArray, $objectKeys, "N", ["slackUsername"], [], []); // the second to last array lists the keys to combine to make a lookupkey

	$cacheKeyList[]  = $GLOBALS['redis']->keys("*zDeliverySlackUser*");

	print_r(resetCache($cacheKeyList));
	setConfMetaUpdate();

	@ob_end_clean();
	
?>
