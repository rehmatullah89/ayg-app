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
	
	$fileArray = array_map('str_getcsv', file('./dummy/RetailerItemPrepTimeCategory.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"categoryName" => "N",
								"categoryId" => "N",
								"prepTimeInSeconds" => "I"
							);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "RetailerItemPrepTimeCategory", $fileArray, $objectKeyIsArray, $objectKeys, "Y", array("categoryName", "categoryId")); // the last array lists the indexes to combine to make a lookupkey
	
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*RetailerItemPrepTimeCategory*");
	print_r(resetCache($cacheKeyList));

	@ob_end_clean();

?>
