<?php

ini_set("memory_limit", "384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);
define("QUEUE_WORKER", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'lib/initiate.mysql_logs.php';

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
require_once $dirpath . 'lib/functions_order_ops.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

die();
$startsWith = 'msp_';

$itemsQuery = new ParseQuery("RetailerItems");
$itemsQuery->startsWith('uniqueId', $startsWith);
$itemsQuery->equalTo('isActive', true);
$itemsQuery->limit(10000000);
$items = $itemsQuery->find(true);

foreach ($items as $item) {
    echo $item->get('uniqueId').PHP_EOL;
    $item->set('isActive', false);
    $item->save(true);
}

$itemsQuery = new ParseQuery("RetailerItemModifiers");
$itemsQuery->startsWith('uniqueId', $startsWith);
$itemsQuery->equalTo('isActive', true);
$itemsQuery->limit(10000000);
$items = $itemsQuery->find(true);

foreach ($items as $item) {
    echo $item->get('uniqueId').PHP_EOL;
    $item->set('isActive', false);
    $item->save(true);
}

$itemsQuery = new ParseQuery("RetailerItemModifierOptions");
$itemsQuery->startsWith('uniqueId', $startsWith);
$itemsQuery->equalTo('isActive', true);
$itemsQuery->limit(10000000);
$items = $itemsQuery->find(true);

foreach ($items as $item) {
    echo $item->get('uniqueId').PHP_EOL;
    $item->set('isActive', false);
    $item->save(true);
}

$itemsQuery = new ParseQuery("RetailerItemProperties");
$itemsQuery->startsWith('uniqueRetailerItemId', $startsWith);
$itemsQuery->equalTo('isActive', true);
$itemsQuery->limit(10000000);
$items = $itemsQuery->find(true);

foreach ($items as $item) {
    echo $item->get('uniqueRetailerItemId').PHP_EOL;
    $item->set('isActive', false);
    $item->save(true);
}



