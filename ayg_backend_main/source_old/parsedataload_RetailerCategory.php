<?php

// @todo retailer Type should be filled before

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
	
	$fileArray = array_map('str_getcsv', file('./dummy/RetailerCategory.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"retailerCategory" => "N",
								"retailerType" => "N",
								"displayOrder" => "I",
								"iconCode" => "N",
							);

	$referenceLookup = array(
			"retailerType" => array(
								"className" => "RetailerType",
								"isRequired" => true,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"retailerType" => "retailerType",
												)
							),
	);

	verifyNewValues($fileArray, "RetailerType", "retailerType", "retailerType", array_search("retailerType", $objectKeys));

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "RetailerCategory", $fileArray, $objectKeyIsArray, $objectKeys, "Y", array("retailerCategory"), array(), $referenceLookup); // the last array lists the indexes to combine to make a lookupkey
	
	@ob_end_clean();
	
?>
