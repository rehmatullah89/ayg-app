<?php

use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Services\CacheService;
use Parse\ParseQuery;
use Predis\Client;

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


$parseUser = new ParseQuery("_User");
$parseUser->equalTo('objectId','qh9LIwUbfu');
$parseUser=$parseUser->find();
$parseUser=$parseUser[0];


// Create row in UserDevices
$userDeviceQuery = new ParseQuery("UserDevices");
$userDeviceQuery->equalTo('user', $parseUser);
$userDeviceQuery->addDescending('createdAt');
$userDeviceQuery->limit(1);
$userDevices = $userDeviceQuery->find();

if (count($userDevices) != 1) {
    throw new \Exception('User Device not found');
}

$objUserDevice = $userDevices[0];


try {
    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
    $workerQueue->sendMessage(
        array(
            "action" => "email_verify_on_signup",
            "content" =>
                array(
                    "objectId" => $parseUser->getObjectId(),
                    "app" => $objUserDevice->get('isIos') == true ? 'iOS' : 'Android'
                )
        ),
        0
    );

} catch (Exception $ex) {

    $response = json_decode($ex->getMessage(), true);
    json_error($response["error_code"], "",
        "User signup failed! " . $response["error_message_log"], 1);
}


die();

$cacheService = new CacheService(new Client([
    'host' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_HOST),
    'port' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PORT),
    'password' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PASS),
]));
$service = \App\Consumer\Services\UserServiceFactory::create($cacheService);


$fromUserId = 'sEm0x7SIbt';
$toUserId = 'x39eLLdfRq';
$service->switchFlightTripOwner($fromUserId,$toUserId);


?>
