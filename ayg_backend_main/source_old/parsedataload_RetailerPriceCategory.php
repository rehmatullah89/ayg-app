<?php

// @todo - no file

	require 'dirpath.php';
	require $dirpath . '__sys_specific_vars.php';
	
	require $fullPathToBackendLibraries . 'vendor/autoload.php';
	require $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require $fullPathToBackendLibraries . 'lib/functions.php';
	
	// require 'parsedataload_functions.php';

	ob_start();	
	
	while (ob_get_level() > 0)
    ob_end_flush();
	
	$fileArray = array_map('str_getcsv', file('<path_to_files>\Retailer - RetailerPriceCategory.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"retailerPriceCategory" => "I",
								"retailerPriceCategorySign" => "N",
								"displayOrder" => "I",
								"iconCode" => "N",
							);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "RetailerPriceCategory", $fileArray, $objectKeyIsArray, $objectKeys, "Y", array("retailerPriceCategory")); // the last array lists the indexes to combine to make a lookupkey
	
	@ob_end_clean();

?>
