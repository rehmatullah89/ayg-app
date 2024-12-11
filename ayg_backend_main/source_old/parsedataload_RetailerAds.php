<?php

// @todo - not needed now

	require 'dirpath.php';
	$fullPathToBackendLibraries = "";
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	
	// require 'parsedataload_functions.php';

	ob_start();	
	
	while (ob_get_level() > 0)
    ob_end_flush();
	
	/////////////////////////////////////////////////////////////////////////////////////////////

	$airportIataCode = 'PIT'; // <<<<<<<<<<<<<<<<<<<< UPDATE;

	/////////////////////////////////////////////////////////////////////////////////////////////

	$fileArray = array_map('str_getcsv', file('<path_to_files>' . $airportIataCode . '\data\RetailerAds - test.csv'));

	$imagesIndexesWithPaths = array(
			"imageAd" => [
					"S3KeyPath" => getS3KeyPath_ImagesRetailerAds($airportIataCode),
					"useUniqueIdInName" => "Y",
					"useUniqueIdInNameColumn" => "uniqueRetailerId",
					"maxWidth" => '',
					"maxHeight" => '',
					"createThumbnail" => false,
					"imagePath" => 'D:\Cloud\Google Drive\Airport Sherpa\Airport Sherpa Operations (new)\11.1 - Data\Airports\\' . $airportIataCode .'\imageAd'],
	);

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"airportIataCode" => "N",
								"uniqueRetailerId" => "X",
								"displaySeconds" => "I",
								"imageAd" => "N",
								"isActive" => "N",
							);

	$referenceLookup = array(
			"retailer" => array(
								"className" => "Retailers",
								"isRequired" => true,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"uniqueId" => "uniqueRetailerId",
												)
							),
	);
	
	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "RetailerAds", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("retailer"), $imagesIndexesWithPaths, $referenceLookup); // the second to last array lists the keys to combine to make a lookupkey
	
	@ob_end_clean();
	
?>
