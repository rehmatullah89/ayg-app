<?php
use App\Background\Services\QueueServiceFactory;
use App\Background\Helpers\QueueMessageHelper;

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
        \App\Background\Helpers\ConfigHelper::get('env_PingPartnerRetailerUpdateIntervalInSecs_Grab') / 60)
    ) {
        // grab section
        $startTime = new DateTime('now', new DateTimeZone('UTC'));
        echo 'grab retailers and items updater started ' . $startTime->format('c') . PHP_EOL;


// grab section
        $grabAirports = ['BWI'];
        //$grabAirports = ['MDW'];
        $integrationService->updateRetailersAndItems('grab', $grabAirports);


        $endTime = new DateTime('now', new DateTimeZone('UTC'));
        echo 'grab retailers and items updater ended ' . $endTime->format('c') . PHP_EOL;
        $lastInteractionTimestamp = $endTime->getTimestamp();
    }
    sleep(5);
}
