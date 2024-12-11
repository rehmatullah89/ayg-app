<?php
$_SERVER['REQUEST_METHOD'] = '';
$_SERVER['REMOTE_ADDR'] = '';
$_SERVER['REQUEST_URI'] = '';
$_SERVER['SERVER_NAME'] = '';

require 'dirpath.php';
$fullPathToBackendLibraries = "./../";
require $fullPathToBackendLibraries . 'lib/initiate.inc.php';

// require 'parsedataload_functions.php';

ob_start();

while (ob_get_level() > 0) {
    ob_end_flush();
}

/////////////////////////////////////////////////////////////////////////////////////////////


$x = explode('airport_specific/', __DIR__);
$airportIataCode = substr($x[1], 0, 3);


/////////////////////////////////////////////////////////////////////////////////////////////

$resultArray = [];
$resultArray[0][]='retailerUniqueId';
$resultArray[0][]='locationUniqueId';
$resultArray[0][]='isDeliveryLocationNotAvailable';
$resultArray[0][]='isPickupLocationNotAvailable';


$query = new \Parse\ParseQuery("Retailers");
$query->equalTo("airportIataCode", $airportIataCode);
$query->includeKey("location");
$results = $query->find();
echo "Successfully retrieved " . count($results) . " scores." . PHP_EOL;
// Do something with the returned ParseObject values
for ($i = 0; $i < count($results); $i++) {
    $object = $results[$i];
    echo $object->getObjectId() . ' - ' . $object->get('retailerName') . ' - ' . $object->get('uniqueId') . PHP_EOL;
    echo $object->get('location')->get('locationDisplayName') . ' - ' . $object->get('location')->get('terminal') . PHP_EOL;

    $retailerUniqueId = $object->get('uniqueId');
    $terminal = $object->get('location')->get('terminal');

    $restrictedLocationQuery = new \Parse\ParseQuery("TerminalGateMap");
    $restrictedLocationQuery->equalTo("airportIataCode", $airportIataCode);
    $restrictedLocationQuery->notEqualTo("terminal", $terminal);
    $restrictedLocationResult = $restrictedLocationQuery->find();
    for ($ii = 0; $ii < count($restrictedLocationResult); $ii++) {

        $resultEntry[0] = $retailerUniqueId;
        $resultEntry[1] = $restrictedLocationResult[$ii]->get('uniqueId');
        $resultEntry[2] = 'Y';
        $resultEntry[3] = 'Y';
        $resultArray[] = $resultEntry;
    }


    echo '-------------' . PHP_EOL;


}

unlink('airport_specific/'.$airportIataCode.'/'.$airportIataCode.'-data/'.$airportIataCode.'-TerminalGateMapRetailerRestrictions.csv');
// Open a file in write mode ('w')
$fp = fopen('airport_specific/'.$airportIataCode.'/'.$airportIataCode.'-data/'.$airportIataCode.'-TerminalGateMapRetailerRestrictions.csv', 'w');

// Loop through file pointer and a line
foreach ($resultArray as $fields) {
    fputcsv($fp, $fields);
}

fclose($fp);

?>
