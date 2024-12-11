<?php

// @todo retailer Type should be filled before

error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'dirpath.php';
$fullPathToBackendLibraries = "../";

require_once $fullPathToBackendLibraries . 'vendor/autoload.php';

require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
// require 'parsedataload_functions.php';

$x = explode('airport_specific/', __DIR__);
$airportIataCode = substr($x[1], 0, 3);

$fileArray = array_map('str_getcsv',
    file('airport_specific/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-TerminalGateMapRetailerRestrictions.csv'));

// Skip the Header row and create key arrays
$objectKeys = array_map('trim', array_shift($fileArray));

// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
$objectKeyIsArray = array(
    "retailerUniqueId" => "N",
    "locationUniqueId" => "N",
    "isDeliveryLocationNotAvailable" => "N",
    "isPickupLocationNotAvailable" => "N",
);

$referenceLookup = array(
    "retailer" => array(
        "className" => "Retailers",
        "isRequired" => true,
        "lookupCols" => array(
            // Column in ClassName => Column in File
            "uniqueId" => "retailerUniqueId",
        )
    ),
    "deliveryLocation" => array(
        "className" => "TerminalGateMap",
        "isRequired" => true,
        "lookupCols" => array(
            // Column in ClassName => Column in File
            "uniqueId" => "locationUniqueId",
        )
    ),
);

//verifyNewValues($fileArray, "TerminalGateMap", "uniqueId", "deliveryLocation", array_search("locationUniqueId", $objectKeys));
//verifyNewValues($fileArray, "Retailers", "uniqueId", "retailer", array_search("retailerUniqueId", $objectKeys));


prepareAndPostToParse(
    $env_ParseApplicationId,
    $env_ParseRestAPIKey,
    "TerminalGateMapRetailerRestrictions",
    $fileArray,
    $objectKeyIsArray,
    $objectKeys,
    "Y",
    array("retailer","deliveryLocation","retailerUniqueId","locationUniqueId"),
    array(),
    $referenceLookup,
    false,
    [],
    true
); // the last array lists the indexes to combine to make a lookupkey


// update uniqueId


?>
