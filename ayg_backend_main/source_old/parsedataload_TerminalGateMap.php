<?php

require 'dirpath.php';
$fullPathToBackendLibraries = "../";
require $fullPathToBackendLibraries . 'lib/initiate.inc.php';

// require 'parsedataload_functions.php';

ob_start();

while (ob_get_level() > 0) {
    ob_end_flush();
}

/////////////////////////////////////////////////////////////////////////////////////////////

$airportIataCode = 'WMI'; // <<<<<<<<<<<<<<<<<<<< UPDATE;

/////////////////////////////////////////////////////////////////////////////////////////////

ini_set('auto_detect_line_endings', true);
$fileArray = array_map('str_getcsv', file('./gatemap_dummy/wmi/TerminalGateMap1A.csv'));

// Skip the Header row and create key arrays
$objectKeys = array_map('trim', array_shift($fileArray));

// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
$objectKeyIsArray = array(
    "airportIataCode" => "N",
    "terminal" => "N",
    "concourse" => "N",
    "gate" => "N",
    "locationDisplayName" => "N",
    "gateDisplayName" => "N",
    "displaySequence" => "I",
    "geoPointLocation" => "G",
    "isDefaultLocation" => "N",
    "includeInGateMap" => "N",
    "gpsRangeInMeters" => "I",
    "isPresecurityLocation" => "N",
    "terminalDisplayName" => "N",
    "concourseDisplayName" => "N",
    "requiresDeliveryInstructions" => "N",
    "deliveryLimitedToPerHourOffset" => "I",
);

$imagesIndexesWithPaths = array();

$referenceLookup = array();

// Verify no new airport codes were added
verifyNewValues($fileArray, "Airports", "airportIataCode", "airportIataCode",
    array_search("airportIataCode", $objectKeys));

prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "TerminalGateMap", $fileArray, $objectKeyIsArray,
    $objectKeys, "Y", array("airportIataCode", "terminal", "concourse", "gate"));

$cacheKeyList[] = $GLOBALS['redis']->keys("*__TERMINALGATEMAP_*");
$cacheKeyList[] = $GLOBALS['redis']->keys("*__DIRECTIONS_*");
$cacheKeyList[] = $GLOBALS['redis']->keys("RR*gate*");
$cacheKeyList[] = $GLOBALS['redis']->keys("*TerminalGateMap*");
$cacheKeyList[] = $GLOBALS['redis']->keys("*fullfillmentInfo*");

print_r(resetCache($cacheKeyList));

setConfMetaUpdate();
echo("<br /><br />");
echo("<a href='convertExcelToArray_GateSequence.php'>Convert GateMap Sequence Array (needs code checkin)</a>");

echo("<br /><br />");
echo("<a href='generate_DistanceMetricsCache.php'>Generate Distance Metrics Cache</a>");

@ob_end_clean();

?>
