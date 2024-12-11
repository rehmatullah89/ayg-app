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

	// First Menu generate so to be loaded to Test
	// Update the Config file first
	$hmshost = new HMSHost($propertyId, $revenueCenterId, $retailerUniqueId, 'menu');
   	$hmshost->menu_modifiers_pull();
	$hmshost->menu_pull();
	list($newMenu, $itemsSkipped) = $hmshost->menu_extract($GLOBALS['__menuLoaderConfig'][$partner]['itemCategoriesNotAllowedThruSecurity'], $GLOBALS['__menuLoaderConfig'][$partner]['unallowedItems'], $GLOBALS['__menuLoaderConfig'][$partner]['unallowedItemsThruSecurityKeywords']);
	$hmshost->menu_initial_load_to_file("", $newMenu, false, '<path_to_files>' . $airportIataCode . '\data\Menus\\'  . $menuFileNameNewPrefix . ' - ');
	echo($retailerUniqueId . " - generated with " . count_like_php5($newMenu) . " items.");
	exit;
	
?>
