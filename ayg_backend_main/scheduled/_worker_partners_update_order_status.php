<?php

$_SERVER['REQUEST_METHOD'] = '';
$_SERVER['REMOTE_ADDR'] = '';
$_SERVER['REQUEST_URI'] = '';
$_SERVER['SERVER_NAME'] = '';

ini_set("memory_limit", "384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);
define("WORKER_MENU_LOADER", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


$integrationService = \App\Background\Services\PartnerIntegrationServiceFactory::create();

$lastInteractionTimestamp = 0;

while (1) {
    if (\App\Background\Helpers\LooperHelper::hasLooperLastRunTimeLimitPassed(
        $lastInteractionTimestamp,
        \App\Background\Helpers\ConfigHelper::get('env_PingPartnerOrdersIntervalInSecs_Grab') / 60)
    ) {
        $startTime = new DateTime('now', new DateTimeZone('UTC'));
        echo 'grab orders updater started ' . $startTime->format('c') . PHP_EOL;

        $integrationService->emulateRetailerAcceptance();
        $integrationService->handleCanceledOrders();

        $endTime = new DateTime('now', new DateTimeZone('UTC'));
        echo 'grab orders updater ended ' . $endTime->format('c') . PHP_EOL;
        $lastInteractionTimestamp = $endTime->getTimestamp();
    }
    sleep(5);
}
