<?php

// @todo - no file

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
	
	ini_set('auto_detect_line_endings',TRUE);
	$fileArray = array_map('str_getcsv', file('<path_to_files>\Retailer - RetailerItemCategories.csv'));

	array_walk_recursive($fileArray, 'utf8_encode_custom');

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"categoryName" => "N",
								"sequence" => "I",
							);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "RetailerItemCategories", $fileArray, $objectKeyIsArray, $objectKeys, "Y", array("categoryName")); // the last array lists the indexes to combine to make a lookupkey
	
	$cacheKeyList[]  = $GLOBALS['redis']->keys("*RetailerItemCategories*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItems*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemModifiers*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemModifierOptions*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemProperties*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("NRC__menu*");

	// TAG
	print_r(resetCache($cacheKeyList));

	@ob_end_clean();

?>
