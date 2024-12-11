<?php

use App\Helpers\MessageFormatter;
use App\Helpers\OverallStatusArbiter;
use App\Helpers\QueueStatusArbiter;

require_once 'vendor/autoload.php';

$config = require_once 'app/Config/app.php';

$app = new \App\App();
$app->initiate($config);

$websiteUpStatus = $app->getWebsiteUpService()->check();
$webResponseStatus = $app->getWebsiteResponseService()->getWebsiteResponse();

$parseStatus = true;
$queueStatus = true;
$queueMidPriorityStatus = true;
$queueDeadLetterStatus = true;

$parseDBConnectTime = $app->getParseService()->getParseConnectTime();
if ($parseDBConnectTime === null) {
    $parseStatus = false;
}
$queueMessageCount = $app->getQueueService()->getMessageCount();
if ($queueMessageCount === null) {
    $queueStatus = false;
}
$queueMidPriorityMessageCount = $app->getQueueMidPriorityService()->getMessageCount();
if ($queueMidPriorityMessageCount === null) {
    $queueMidPriorityStatus = false;
}
$queueDeadLetterMessageCount = $app->getQueueDeadLetterService()->getMessageCount();
if ($queueDeadLetterMessageCount === null) {
    $queueDeadLetterStatus = false;
}

$queueMessageCondition = QueueStatusArbiter::decideAboutCondition(
    $queueMessageCount,
    $config['queueConditionGoodMaxMessages'],
    $config['queueConditionStableMaxMessages'],
    $queueMidPriorityMessageCount,
    $config['queueMidPriorityConditionGoodMaxMessages'],
    $config['queueMidPriorityConditionStableMaxMessages'],
    $queueDeadLetterMessageCount,
    $config['queueDeadLetterConditionGoodMaxMessages'],
    $config['queueDeadLetterConditionStableMaxMessages'],
    $parseDBConnectTime,
    $config['parseConditionGoodMaxConnectTime'],
    $config['parseConditionStableMaxConnectTime']
);

$overAllStatus = OverallStatusArbiter::decide(
    $webResponseStatus,
    $websiteUpStatus,
    $queueStatus,
    $queueMidPriorityStatus,
    $queueDeadLetterStatus,
    $parseStatus,
    $queueMessageCondition
);

$slackMessageFormatted = MessageFormatter::formatSlackNotificationMessage(
    new DateTime('now', new DateTimeZone('America/New_York')),
    $webResponseStatus,
    $websiteUpStatus,
    $queueStatus,
    $queueMessageCount,
    $queueMidPriorityStatus,
    $queueMidPriorityMessageCount,
    $queueDeadLetterStatus,
    $queueDeadLetterMessageCount,
    $parseStatus,
    $parseDBConnectTime,
    $queueMessageCondition,
    $config['appEnvironment'],
    $overAllStatus
);

if ($overAllStatus) {
    $app->getNotificationService()->informAboutStatus($slackMessageFormatted);
} else {
    $app->getNotificationService()->informAboutError($slackMessageFormatted);
}
