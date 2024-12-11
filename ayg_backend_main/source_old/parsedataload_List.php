<?php

// @todo - not needed now

	require 'dirpath.php';
	$fullPathToBackendLibraries = "";
	
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	
	// require 'parsedataload_functions.php';



	use Parse\ParseClient;
	use Parse\ParseQuery;
	use Parse\ParseObject;
	use Parse\ParseUser;
	use Parse\ParseFile;
	use Parse\ParseGeoPoint;

	require $fullPathToBackendLibraries . 'lib/initiate.parse.php';

	ob_start();	
	while (ob_get_level() > 0)
    ob_end_flush();

	////////////////////////////////////////////////////////////////////////////

	$fileArrayList = array_map('str_getcsv', file('<path_to_files>\List - prod.csv'));

	// Skip the Header row and create key arrays
	$objectKeysList = array_map('trim', array_shift($fileArrayList));
	
	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
	$objectKeyIsArrayList = array(
								"airportIataCode" => "N",
								"isActive" => "N",
								"displaySequence" => "I",
								"name" => "N",
								"description" => "N",
								"type" => "N",
								"cardType" => "N",
								"uniqueId" => "N",
								"restrictListTimeInSecsStart" => "I",
								"restrictListTimeInSecsEnd" => "I"
							);

	$referenceLookupList = array();

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "List", $fileArrayList, $objectKeyIsArrayList, $objectKeysList, "N", array("uniqueId"), [], $referenceLookupList); // the second to last array lists the keys to combine to make a lookupkey

	////////////////////////////////////////////////////////////////////////////

	$fileArrayListDetails = array_map('str_getcsv', file('<path_to_files>\ListDetails - prod.csv'));

	// Skip the Header row and create key arrays
	$objectKeysListDetails = array_map('trim', array_shift($fileArrayListDetails));
	
	$objectKeyIsArrayListDetails = array(
								"listUniqueId" => "X",
								"retailerUniqueId" => "X",
								"retailerItemUniqueId" => "X",
								"displaySequence" => "I",
								"imageType" => "N",
								"imageName" => "N",
								"spotlight" => "N",
								"description" => "N",
								"spotlightIcon" => "N",
								"isActive" => "N"
							);

	$referenceLookupListDetails = array(
			"list" => array(
								"className" => "List",
								"isRequired" => true,
								"whenColumnValuePresentIsRequired" => "listUniqueId", // Name of the column to check, if it is preset then isRequired is assumed to be true
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"uniqueId" => "listUniqueId",
													// "__LKPVAL__isActive" => true
												),
								// "lookupColsType" => array(
								// 					"email" => "Y", // An array
								// 				)
							),
			"retailer" => array(
								"className" => "Retailers",
								"isRequired" => true,
								"whenColumnValuePresentIsRequired" => "retailerUniqueId", // Name of the column to check, if it is preset then isRequired is assumed to be true
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"uniqueId" => "retailerUniqueId",
													// "__LKPVAL__isActive" => true
												),
								// "lookupColsType" => array(
								// 					"email" => "Y", // An array
								// 				)
							),
			"retailerItem" => array(
								"className" => "RetailerItems",
								"isRequired" => false,
								"whenColumnValuePresentIsRequired" => "retailerItemUniqueId", // Name of the column to check, if it is preset then isRequired is assumed to be true
								"lookupCols" => array(
													// Column in ClassName => Column in File
													"uniqueId" => "retailerItemUniqueId",
													// "__LKPVAL__isActive" => true
												),
								// "lookupColsType" => array(
								// 					"email" => "Y", // An array
								// 				)
							),
	);

	verifyNewValues($fileArrayListDetails, "List", "uniqueId", "listUniqueId", array_search("listUniqueId", $objectKeysListDetails), true);

	verifyNewValues($fileArrayListDetails, "Retailers", "uniqueId", "retailerUniqueId", array_search("retailerUniqueId", $objectKeysListDetails), true);

	verifyNewValues($fileArrayListDetails, "RetailerItems", "uniqueId", "retailerItemUniqueId", array_search("retailerItemUniqueId", $objectKeysListDetails), true);

	prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "ListDetails", $fileArrayListDetails, $objectKeyIsArrayListDetails, $objectKeysListDetails, "Y", array("listUniqueId", "retailerUniqueId", "retailerItemUniqueId"), [], $referenceLookupListDetails); // the second to last array lists the keys to combine to make a lookupkey

	////////////////////////////////////////////////////////////////////////////

	$cacheKeyList[] = $GLOBALS['redis']->keys("*List*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*curated*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*fullfillmentinfo*");
	$cacheKeyList[] = $GLOBALS['redis']->keys("*FULLFILLMENTINFO*");
	echo("Resetting cache..." . "\r\n" . "<br />");

	print_r(resetCache($cacheKeyList));

	@ob_end_clean();
	
?>
