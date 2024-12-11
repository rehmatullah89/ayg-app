<?php
$_SERVER['REQUEST_METHOD']='';
$_SERVER['REMOTE_ADDR']='';
$_SERVER['REQUEST_URI']='';
$_SERVER['SERVER_NAME']='';

	require 'dirpath.php';
$fullPathToBackendLibraries = "../";
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	// require 'parsedataload_functions.php';

$x = explode('airport_specific/',__DIR__);
$airportIataCode=substr($x[1],0,3);

	$retailersToProcess = [
        ["directory" => 'McDonalds-3', "uniqueId" => 'd85ad4c530731e05e4c5b924b376f96f'],
        ["directory" => 'DCM', "uniqueId" => '87cfcb12e0adc902ef8bcd73c98bf286'],
        ["directory" => 'Einstein', "uniqueId" => 'f90cb4bf1cc2b1d6bb41369def6fc3af'],
        ["directory" => 'Garbanzos', "uniqueId" => '6b65590c2a68b03c699abbf4b8d8e45d'],
        ["directory" => 'RootDown', "uniqueId" => '55332fb06a8e00a439e8785aac528da7'],
        ["directory" => 'Timberline', "uniqueId" => 'ed297b1ddf43a9b1986688fb3e4c450f'],
        ["directory" => 'VinoVolo', "uniqueId" => '20cf766845ccdca412a5e603991a7d3c'],
	];




foreach ($retailersToProcess as $retailersToProcessKey => $retailersToProcessValue){
    $retailerDirectoryName = $retailersToProcessValue['directory'];
    $retailerUniqueId = $retailersToProcessValue['uniqueId'];



    $filePath = './airport_specific/'.$airportIataCode.'/'.$airportIataCode.'-data/'.$airportIataCode.'-Menus/'.$airportIataCode.'-'.$retailerDirectoryName.'/'.$airportIataCode.'-'.$retailerDirectoryName.'-';
    $imagesPath = './airport_specific/'.$airportIataCode.'/'.$airportIataCode.'-data/'.$airportIataCode.'-Menus/'.$airportIataCode.'-'.$retailerDirectoryName.'/itemImages';

	// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither
	// TAG
	$objectKeyIsArrayCustom = array(
							"0" => array(
										"itemCategoryName" => "N", 
										"itemSecondCategoryName" => "N", 
										"itemThirdCategoryName" => "N", 
										"itemPOSName" => "N", 
										"itemDisplayName" => "N", 
										"itemDisplayDescription" => "N", 
										"itemId" => "N", 
										"itemPrice" => "I", 
										"priceLevelId" => "N", 
										"isActive" => "N", 
										"uniqueRetailerId" => "N", 
										"uniqueId" => "N", 
										"itemImageURL" => "N",
										"itemDisplaySequence" => "I",
										"itemTags" => "N",
										"taxCategory" => "N",
										"allowedThruSecurity" => "N"
									),
							"1" => array(
										"modifierPOSName" => "N", 
										"modifierDisplayName" => "N", 
										"modifierDisplayDescription" => "N", 
										"modifierDisplaySequence" => "I",
										"modifierId" => "N", 
										"maxQuantity" => "I", 
										"minQuantity" => "I", 
										"isRequired" => "N", 
										"isActive" => "N", 
										"uniqueRetailerItemId" => "N", 
										"uniqueId" => "N"
									),
							"2" => array(
										"optionPOSName" => "N", 
										"optionDisplayName" => "N", 
										"optionDisplayDescription" => "N", 
										"optionDisplaySequence" => "I",
										"optionId" => "N", 
										"pricePerUnit" => "I", 
										"priceLevelId" => "N", 
										"isActive" => "N", 
										"uniqueRetailerItemModifierId" => "N", 
										"uniqueId" => "N"
									),
							"3" => array(
										"uniqueRetailerItemId" => "N", 
										"dayOfWeek" => "I", 
										"restrictOrderTimes" => "X", 
										"prepRestrictTimesGroup1" => "X", 
										"prepTimeCategoryIdGroup1" => "X", 
										"prepRestrictTimesGroup2" => "X", 
										"prepTimeCategoryIdGroup2" => "X", 
										"prepRestrictTimesGroup3" => "X", 
										"prepTimeCategoryIdGroup3" => "X", 
										"isActive" => "N"
									),
						);
						
	$className = array(
					"0" => "RetailerItems",
					"1" => "RetailerItemModifiers",
					"2" => "RetailerItemModifierOptions",
					"3" => "RetailerItemProperties",
				);
	
	$duplicateKeyName = array(
					"0" => array("uniqueId"),
					"1" => array("uniqueId"),
					"2" => array("uniqueId"),
					"3" => array("uniqueRetailerItemId", "dayOfWeek")
				);
	
	$uniqueIdKeyGeneration = array(
					"0" => "N",
					"1" => "N",
					"2" => "N",
					"3" => "Y"
				);

	$referenceLookup = array(
			"0" => array(
					"taxCategory" => array(
										"className" => "RetailerItemTaxCategory",
										"isRequired" => false,
										"lookupCols" => array(
															// Column in ClassName => Column in File
															"categoryId" => "taxCategory",
														),
									),
				),
			"1" => array(),
			"2" => array(),
			"3" => array(
				"prepTimeCategoryGroup1" => array(
									"className" => "RetailerItemPrepTimeCategory",
									"isRequired" => false,
									"lookupCols" => array(
														// Column in ClassName => Column in File
														"categoryId" => "prepTimeCategoryIdGroup1",
													)
								),
				"prepTimeCategoryGroup2" => array(
									"className" => "RetailerItemPrepTimeCategory",
									"isRequired" => false,
									"lookupCols" => array(
														// Column in ClassName => Column in File
														"categoryId" => "prepTimeCategoryIdGroup1",
													)
								),
				"prepTimeCategoryGroup3" => array(
									"className" => "RetailerItemPrepTimeCategory",
									"isRequired" => false,
									"lookupCols" => array(
														// Column in ClassName => Column in File
														"categoryId" => "prepTimeCategoryIdGroup1",
													)
								),
				)
	);

	//foreach($retailersToProcess as $currentRetailer) {


		echo("<br />" . '-------------------------------------------------------------------------------------' . "<br />");
		echo("<b>" . $retailerDirectoryName . "</b><br />");
		echo('-------------------------------------------------------------------------------------' . "<br /><br />");

		$fileList = array(
						"0" => $filePath . 'items.csv',
						"1" => $filePath . 'modifiers.csv',
						"2" => $filePath . 'modifierOptions.csv',
						"3" => $filePath . 'itemTimes.csv',
					);
		
		$imagesIndexesWithPaths = array(
				"0" => array(
								"itemImageURL" => [
									"S3KeyPath" => getS3KeyPath_ImagesRetailerItem($airportIataCode),
									"useUniqueIdInName" => "Y",
									"maxWidth" => 1080,
									"maxHeight" => 1920,
									"createThumbnail" => true,
									"imagePath" => $imagesPath
									]
							),
				"1" => array(),
				"2" => array(),
				"3" => array()
		);

		// Process RetailerItemTimes file

		// Process files
		foreach($fileList as $index => $fileName) {

            var_dump($fileName);

			if(!file_exists($fileName)) {

				echo("<b><i>" . $fileName . " - Skipping..." . "</i></b><br /><br />");
				continue;
			}

			ini_set('auto_detect_line_endings',TRUE);
			$fileArray = array_map('str_getcsv', array_map(function($item) use ($retailerUniqueId){
                return str_replace(['UNIQUE_RETAILER_ID','unique_retailer_id'],$retailerUniqueId, $item);
			},file($fileName)));


			// UTF8 encoding
			array_walk_recursive($fileArray, 'utf8_encode_custom');

			// Skip the Header row and create key arrays
			$objectKeys = array_map('trim', array_shift($fileArray));
			echo("<br />" . $className[$index]);
			echo("<br />----------------------------------------------<br />");

			// RetailerItems
			if($index == 0) {

				verifyNewValues($fileArray, "RetailerItemTaxCategory", "categoryId", "taxCategory", array_search("taxCategory", $objectKeys));
			}
			// RetailerItemProperties
			else if($index == 3) {

				verifyNewValues($fileArray, "RetailerItemPrepTimeCategory", "categoryId", "prepTimeCategoryIdGroup1", array_search("prepTimeCategoryIdGroup1", $objectKeys));
				echo("<br />");
				verifyNewValues($fileArray, "RetailerItemPrepTimeCategory", "categoryId", "prepTimeCategoryIdGroup2", array_search("prepTimeCategoryIdGroup1", $objectKeys));
				echo("<br />");
				verifyNewValues($fileArray, "RetailerItemPrepTimeCategory", "categoryId", "prepTimeCategoryIdGroup3", array_search("prepTimeCategoryIdGroup1", $objectKeys));
				echo("<br />");
				verifyNewValues($fileArray, "RetailerItems", "uniqueId", "uniqueRetailerItemId", array_search("uniqueRetailerItemId", $objectKeys), true);

				list($fileArray, $objectKeys, $updatedObjectKeyIsArrayCustom) = processRetailerItemTimesData($fileArray, $objectKeys, $objectKeyIsArrayCustom[$index]);

				$objectKeyIsArrayCustom[$index] = $updatedObjectKeyIsArrayCustom;
			}

			prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, $className[$index], $fileArray, $objectKeyIsArrayCustom[$index], $objectKeys, $uniqueIdKeyGeneration[$index], $duplicateKeyName[$index], $imagesIndexesWithPaths[$index], $referenceLookup[$index]);
		}
	//}



}



s3logMenuLoader(printLogTime() . "---- completed" . "\r\n", true);
$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItems*");
$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemModifiers*");
$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemModifierOptions*");
$cacheKeyList[]  = $GLOBALS['redis']->keys("PQ__RetailerItemProperties*");
$cacheKeyList[]  = $GLOBALS['redis']->keys("NRC__menu*");

// TAG
print_r(resetCache($cacheKeyList));
// setConfMetaUpdate();
	
?>
