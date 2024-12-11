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

	$fileArray = array_map('str_getcsv', file('<path_to_files>\Retailer - RetailerPOSConfig - test.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArray = array(
								"retailerUniqueId" => "X",
								"areTipsAllowed" => "N",
							    "comments" => "N",
							    "continousPingCheck" => "N",
							    "employeeId" => "N",
							    // "lastSuccessfulPingTimestamp" => "I",
							    "locationId" => "N",
							    "orderTypeId" => "N",
							    "placeHolderItemId" => "N",
							    "printerId" => "N",
							    "pushOrdersToPOS" => "N",
							    "revenueCenterId" => "N",
							    "taxRate" => "N",
							    "tenderTypeId" => "N",
							    "avgPrepTimeInSeconds" => "I",
							    "placeHolderItemPriceLevelId" => "I",
							    "tabletSlackURL" => "N",
							    "tabletId" => "N",
							    "tabletMobilockId" => "N",
							    "dualPartnerConfigUniqueId" => "X",
							    "disallowEmpDiscount" => "N",
							    "disallowMilitaryDiscount" => "N",
							    "automatedMenuPull" => "N",
							    // "tabletUsername" => "X",
							);

	$referenceLookup = array(
			"retailer" => array(
								"className" => "Retailers",
								"isRequired" => true,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"uniqueId" => "retailerUniqueId",
													// "__LKPVAL__isActive" => true
												)
							),
			"dualPartnerConfig" => array(
								"className" => "DualPartnerConfig",
								"isRequired" => false,
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"uniqueId" => "dualPartnerConfigUniqueId",
													// "__LKPVAL__isActive" => true
												)
							),
	);

	verifyNewValues($fileArray, "Retailers", "uniqueId", "retailerUniqueId", array_search("retailerUniqueId", $objectKeys));

	verifyNewValues($fileArray, "DualPartnerConfig", "uniqueId", "dualPartnerConfigUniqueId", array_search("dualPartnerConfigUniqueId", $objectKeys));

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "RetailerPOSConfig", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("retailer"), [], $referenceLookup); // the second to last array lists the keys to combine to make a lookupkey


	$cacheKeyList[] = $GLOBALS['redis']->keys("*RetailerPOSConfig*");
	print_r(resetCache($cacheKeyList));

	@ob_end_clean();
	
?>
