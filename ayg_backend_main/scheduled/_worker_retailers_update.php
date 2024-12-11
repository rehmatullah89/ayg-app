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


///////////////////////////////////////////////////////////////////////////////
// Check if 9001 request came in
///////////////////////////////////////////////////////////////////////////////

$s3Service = new \App\Background\Services\S3Service(
    new S3Client([
        'version' => 'latest',
        'region' => $GLOBALS['env_MenuUploadAWSS3Region'],
        'credentials' => new Credentials($GLOBALS['env_MenuUploadAWSS3AccessId'], $GLOBALS['env_MenuUploadAWSS3Secret'])
    ]),
    $GLOBALS['env_MenuUploadAWSS3Bucket']
);

$slackService = new \App\Background\Services\SlackService($GLOBALS['env_retailer_edit_notification_slack_webhook_url']);
$retailerUpdateService = new \App\Background\Services\RetailersUpdateService(
    $s3Service,
    new \App\Background\Services\CacheService(),
    $slackService
);


$runningTime = 0;
unset($workerQueue);

while (1 > 0) {

    $mainApi9001Status = getCacheAPI9001Status();
    $worker9001Status = getCacheAPI9001RetailersUpdate();
    if (!empty($mainApi9001Status)) {
        //shutdownProcess($workerQueue);
        if (empty($worker9001Status)) {
            setCacheAPI9001RetailersUpdate();
        }
        echo '9001 found, waiting...' . PHP_EOL;
        sleep(10);
        continue;
    }

    if (empty($mainApi9001Status) && !empty($worker9001Status)) {
        delCacheAPI9001RetailersUpdate();
        echo 'api 9001 empty, cleaned worker 9001 as well' . PHP_EOL;
    }


    if (!isset($workerQueue)) {
        $workerQueue = new WorkerQueue($GLOBALS['env_RabbitMQConsumerRetailersEdit'],
            $GLOBALS['env_workerQueueConsumerLPInSecs']);
    }

    try {
        echo 'receive message started ' . PHP_EOL;
        $messages = $workerQueue->receiveMessage(1);
    } catch (Exception $exception) {
        $exceptionMsg = $exception->getMessage();

        $exceptionMsg = json_decode($exceptionMsg, true);
        if (isset($exceptionMsg['error_code']) && $exceptionMsg['error_code'] == 'AS_1077') {
            $workerQueue = new WorkerQueue($GLOBALS['env_RabbitMQConsumerRetailersEdit'],
                $GLOBALS['env_workerQueueConsumerLPInSecs']);

            $messages = $workerQueue->receiveMessage(1);
        } else {
            die();
        }
    }


    if (isset($messages[0])) {
        $processMessage = $messages[0];
        $message = json_decode($messages[0]->body, true);


        if ($message['action'] == 'shutdown_request_9001') {
            echo 'shut down process triggered' . PHP_EOL;
            $workerQueue->deleteMessage($workerQueue->getMessageId($processMessage), $processMessage);
            $workerQueue->disconnect();
            unset($workerQueue);
        }

        if ($message['action'] == 'retailers_update' && isset($message['content']['airportIataCode'])) {
            try {
                if (isset($message['content']['skipUniqueIdGeneration']) && $message['content']['skipUniqueIdGeneration'] == true) {
                    $skipUniqueIdGeneration = true;
                } else {
                    $skipUniqueIdGeneration = false;
                }
                if (isset($message['content']['silentMode']) && $message['content']['silentMode'] == true) {
                    $silentMode = true;
                } else {
                    $silentMode = false;
                }


                $retailerUpdateService->updateByAirportIataCode($message['content']['airportIataCode'], $skipUniqueIdGeneration, $silentMode);
                $workerQueue->deleteMessage($workerQueue->getMessageId($processMessage), $processMessage);
            } catch (Exception $exception) {
                $slackService->sendMessage(':bangbang: ' . $exception->getMessage());
                $slackService->sendMessage(':bangbang: please fix the problem and trigger update again');
                $workerQueue->deleteMessage($workerQueue->getMessageId($processMessage), $processMessage);
                // todo think about deadletter
            }
        } else {
            echo 'not data update or no airport code' . PHP_EOL;
            // todo think about deadletter
        }
    } else {
        echo 'no messages' . PHP_EOL;
        // todo think about deadletter
    }
    echo 'waiting for new instructions' . PHP_EOL;
    sleep(10);
}




