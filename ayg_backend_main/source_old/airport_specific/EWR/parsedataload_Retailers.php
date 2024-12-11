<?php

	require 'dirpath.php';
    $fullPathToBackendLibraries = "../../../";
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	
	// require 'parsedataload_functions.php';

	ob_start();
	
	while (ob_get_level() > 0)
    ob_end_flush();
	
	/////////////////////////////////////////////////////////////////////////////////////////////

	$airportIataCode = 'EWR'; // <<<<<<<<<<<<<<<<<<<< UPDATE;

	/////////////////////////////////////////////////////////////////////////////////////////////

	$fileArray = array_map('str_getcsv', file('./data/EWR-Retailers.csv'));

	$imagesIndexesWithPaths = array(
			"imageLogo" => [
					"S3KeyPath" => getS3KeyPath_ImagesRetailerLogo($airportIataCode),
					"useUniqueIdInName" => "Y",
					"maxWidth" => '',
					"maxHeight" => '',
					"createThumbnail" => false,
					"imagePath" => './imageLogo'],
			"imageBackground" => [
					"S3KeyPath" => getS3KeyPath_ImagesRetailerBackground($airportIataCode),
					"useUniqueIdInName" => "Y",
					"maxWidth" => '',
					"maxHeight" => '',
					"createThumbnail" => false,
					"imagePath" => './imageBackground',]
	);


	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"airportIataCode" => "N",
								"retailerName" => "N",
								"terminal" => "X", // remove after location value is looked up
								"concourse" => "X", // remove after location value is looked up
								"gate" => "X", // remove after location value is looked up
								"retailerType" => "N",
								"retailerCategory" => "Y", // will be an array
								"retailerPriceCategory" => "I",
								"retailerFoodSeatingType" => "Y", // will be an array
								"isChain" => "N",
								"hasPickup" => "N",
								"hasDelivery" => "N",
								"searchTags" => "Y",
								"imageLogo" => "N",
								"imageBackground" => "N",
								"description" => "N",
								"openTimesMonday" => "N",
								"closeTimesMonday" => "N",
								"openTimesTuesday" => "N",
								"closeTimesTuesday" => "N",
								"openTimesWednesday" => "N",
								"closeTimesWednesday" => "N",
								"openTimesThursday" => "N",
								"closeTimesThursday" => "N",
								"openTimesFriday" => "N",
								"closeTimesFriday" => "N",
								"openTimesSaturday" => "N",
								"closeTimesSaturday" => "N",
								"openTimesSunday" => "N",
								"closeTimesSunday" => "N",
								"isActive" => "N",
							);

	$referenceLookup = array(
			"location" => array(
								"className" => "TerminalGateMap",
								"isRequired" => true,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"airportIataCode" => "airportIataCode",
													"terminal" => "terminal",
													"concourse" => "concourse",
													"gate" => "gate",
												),
								// "lookupColsType" => array(
								// 					// Column in ClassName => Column in File
								// 					"airportIataCode" => "Y", // An array
								// 				)
							),
			"retailerType" => array(
								"className" => "RetailerType",
								"isRequired" => true,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"retailerType" => "retailerType",
												)
							),
			"retailerCategory" => array(
								"className" => "RetailerCategory",
								"isRequired" => true,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"retailerCategory" => "retailerCategory",
												)
							),
			"retailerPriceCategory" => array(
								"className" => "RetailerPriceCategory",
								"isRequired" => false,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"retailerPriceCategory" => "retailerPriceCategory",
												)
							),
			"retailerFoodSeatingType" => array(
								"className" => "RetailerFoodSeatingType",
								"isRequired" => false,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"retailerFoodSeatingType" => "retailerFoodSeatingType",
												)
							)
	);

	verifyNewValues($fileArray, "RetailerCategory", "retailerCategory", "retailerCategory", array_search("retailerCategory", $objectKeys));
	verifyNewValues($fileArray, "RetailerPriceCategory", "retailerPriceCategory", "retailerPriceCategory", array_search("retailerPriceCategory", $objectKeys));
	verifyNewValues($fileArray, "RetailerFoodSeatingType", "retailerFoodSeatingType", "retailerFoodSeatingType", array_search("retailerFoodSeatingType", $objectKeys));
	verifyNewValues($fileArray, "RetailerType", "retailerType", "retailerType", array_search("retailerType", $objectKeys));



	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "Retailers", $fileArray, $objectKeyIsArray, $objectKeys, "Y", array("airportIataCode", "retailerName", "terminal", "concourse", "gate"), $imagesIndexesWithPaths, $referenceLookup); // the second to last array lists the keys to combine to make a lookupkey
	s3logMenuLoader(printLogTime() . "---- completed" . "\r\n", true);


	$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__Retailers*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__Retailer*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("RR__retailer*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("RR__retailer__info*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("RR__retailer__list*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("RR__retailer__bydistance*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("RR__retailer__fullfillmentinfo*");
	$cacheKeyList[]  = $GLOBALS['redis']->keys("__RETAILERINFO_*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*curated*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*fullfillmentinfo*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*FULLFILLMENTINFO*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*RetailerPOSConfig*");

	print_r(resetCache($cacheKeyList));
	setConfMetaUpdate();

	@ob_end_clean();

?>
