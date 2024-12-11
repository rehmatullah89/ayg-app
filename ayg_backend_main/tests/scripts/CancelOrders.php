<?php


use Parse\ParseQuery;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../putenv.php';

date_default_timezone_set('America/New_York');

$env_ParseServerURL = getenv('env_ParseServerURL');
$env_ParseApplicationId = getenv('env_ParseApplicationId');
$env_ParseRestAPIKey = getenv('env_ParseRestAPIKey');
$env_ParseMasterKey = getenv('env_ParseMasterKey');
$env_ParseMount = getenv('env_ParseMount');
/*

require_once __DIR__ . '/../../lib/initiate.parse.php';
require_once __DIR__ . '/../../scheduled/_queue_functions.php';


$ordersNotFinished = new ParseQuery('Order');
$ordersNotFinished->notContainedIn('status', [6, 10]);
$c = $ordersNotFinished->count();
var_dump($c);D

$ordersNotFinished = new ParseQuery('Order');
$ordersNotFinished->notContainedIn('status', [6, 10]);
$all = $ordersNotFinished->find();

foreach ($all as $order) {
    $order->set('status', 6);
    $order->save();
}
*/
