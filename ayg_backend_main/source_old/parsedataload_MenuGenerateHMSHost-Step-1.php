<?php

// @todo - not needed now

	require 'dirpath.php';
	$fullPathToBackendLibraries = "";
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	require 'parsedataload_HMSHostMenuLoadConfig.php';

	ob_start();	
	while (ob_get_level() > 0)

    ob_end_flush();

	/////////////////////////////////////////////////////////////////
	// Retailer List by Airport
	$propertyId = 221; // 221 = CVG, 121 = LAX, 69 = MCO, 83 = BWI
	$hmshost = new HMSHost($propertyId, '', '', 'menu');
	$retailers = $hmshost->getListOfRetailersByAirport();

	foreach($retailers as $revenueCenterId => $retailerInfo) {

		// Test menu pull
		$hmshost = new HMSHost($propertyId, $revenueCenterId, '', 'menu');
		$hmshost->menu_pull();
		list($itemRows, $itemToBe86) = $hmshost->menu_extract($GLOBALS['__menuLoaderConfig'][$partner]['itemCategoriesNotAllowedThruSecurity'], $GLOBALS['__menuLoaderConfig'][$partner]['unallowedItems'], $GLOBALS['__menuLoaderConfig'][$partner]['unallowedItemsThruSecurityKeywords']);
		echo($retailerInfo["retailerName"] . ' - Rev Id: ' . $revenueCenterId . " - Items: " . count_like_php5($itemRows) . "<br />");
	}
	exit;
	/////////////////////////////////////////////////////////////////
	
?>
