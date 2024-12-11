<?php

ini_set("memory_limit", "384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'scheduled/_process_orders.php';
require_once $dirpath . 'scheduled/_confirm_print_orders.php';

require_once $dirpath . 'scheduled/_confirm_pos_orders.php';
require_once $dirpath . 'scheduled/_confirm_tablet_orders.php';
require_once $dirpath . 'scheduled/_send_order_receipt.php';
require_once $dirpath . 'scheduled/_process_delivery.php';
require_once $dirpath . 'scheduled/_send_email.php';
require_once $dirpath . 'scheduled/_create_onesignal_device.php';
require_once $dirpath . 'scheduled/_queue_functions.php';
require_once $dirpath . 'scheduled/_ping_retailers.php';
require_once $dirpath . 'scheduled/_ping_slack_delivery.php';
require_once $dirpath . 'scheduled/_process_delivery_slack.php';
require_once $dirpath . 'scheduled/_worker_functions.php';
require_once $dirpath . 'scheduled/_send_user_communication.php';
require_once $dirpath . 'scheduled/_process_flight.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

///////////////////////////////////////////////////////////////////////////////
// Check if 9001 request came in
///////////////////////////////////////////////////////////////////////////////
/*
if(!empty(getCacheAPI9001Status())) {

	$nullValue = '';
	shutdownProcess($nullValue);
}
*/


$possibleSalts = [
    getenv("env_AppRestAPIKeySalt"),
    getenv("env_AppRestAPIKeySaltIos"),
    getenv("env_AppRestAPIKeySaltAndroid"),
    getenv("env_AppRestAPIKeySaltWebsite"),
];


function generateAPIToken($epoch, $salt)
{
    return md5($epoch . $salt);
}

foreach ($possibleSalts as $possibleSalt){
    //var_dump(generateAPIToken(1634557952102,$possibleSalt));

    var_dump(generateAPIToken(1635886937724,$possibleSalt));


    //var_dump(generateAPIToken(1635887707354,$possibleSalt));


}


//
die();

$airports = ['SEA', 'DFW', 'BWI', 'MDW', 'PHL', 'SLC', 'TPA'];


foreach ($airports as $airport) {
    echo $airport;
    echo PHP_EOL;


    $pq = new ParseQuery('Retailers');
    $pq->includeKey('location');
    $pq->equalTo('airportIataCode', $airport);

    $pq->ascending('retailerName');
    $list = $pq->find(true);


    foreach ($list as $item) {
        echo $item->get('retailerName');
        echo ', ';
        echo $item->get('location')->get('terminal');
        echo ', ';
        echo $item->get('location')->get('concourse');
        echo ', ';
        echo $item->get('location')->get('gate');

        echo PHP_EOL;
    }


    echo PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL;
    echo PHP_EOL;
}

