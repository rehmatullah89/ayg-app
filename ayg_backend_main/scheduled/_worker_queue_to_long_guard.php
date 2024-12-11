<?php
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

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


const LIMIT_IN_SECONDS = 55;

// basically check if how long does it take for the queue for

$queuesToCheck = [
    '_worker_queue_',
];

$closeCommands = [
    '_worker_queue_' => 'sudo supervisorctl restart ayg-queue',
];


foreach ($queuesToCheck as $queueName) {
    $queueStartTimestamp = getQueueWorkerStartTimestamp($queueName);
    if ($queueStartTimestamp === null) {
        continue;
    }

    $currentTimestamp = time();

    $timeSpendOnMessage = $currentTimestamp - $queueStartTimestamp;

    if ($timeSpendOnMessage > LIMIT_IN_SECONDS) {
        if (isset($closeCommands[$queueName])) {
            hSetCache('__QUEUE_WORKER_RESTART_LOGS__'.$queueName,$currentTimestamp,'restarted');
            exec($closeCommands[$queueName],$a,$o);
        }
    }

}
