<?php
use App\Background\Repositories\DeliveryUserParseRepository;
use App\Background\Repositories\UserParseRepository;
use App\Background\Services\DeliveryService;

$_SERVER['REQUEST_METHOD'] = '';
$_SERVER['REMOTE_ADDR'] = '';
$_SERVER['REQUEST_URI'] = '';
$_SERVER['SERVER_NAME'] = '';

require 'dirpath.php';
$fullPathToBackendLibraries = "../";

require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
require_once $fullPathToBackendLibraries . 'admin/functions_retailers.php';

// require 'parsedataload_functions.php';

$x = explode('airport_specific/', __DIR__);
$airportIataCode = substr($x[1], 0, 3);
ob_start();
while (ob_get_level() > 0) {
    ob_end_flush();
}

$fileArray = array_map('str_getcsv',
    file('./airport_specific/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-DeliveryUser.csv'));

// Skip the Header row and create key arrays
$objectKeys = array_map('trim', array_shift($fileArray));

$total = count_like_php5($fileArray);

$deliveryService = new DeliveryService(
    new UserParseRepository(),
    new DeliveryUserParseRepository()
);

foreach ($fileArray as $i => $line) {
    $stats = $deliveryService->addOrUpdateDeliveryUserData(
        $line[0],
        $line[1],
        $line[2],
        $line[3],
        $line[4],
        $airportIataCode
    );
    echo($stats->toString());
    echo PHP_EOL;
}

?>
