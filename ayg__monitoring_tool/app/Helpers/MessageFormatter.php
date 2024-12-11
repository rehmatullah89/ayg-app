<?php
namespace App\Helpers;

class MessageFormatter
{
    public static function formatSlackNotificationMessage(
        \DateTime $dateTime,
        $webResponseStatus,
        $websiteUpStatus,
        $queueMessageStatus,
        $queueMessageCount,
        $queueMidPriorityStatus,
        $queueMidPriorityMessageCount,
        $queueDeadLetterStatus,
        $queueDeadLetterMessageCount,
        $parseStatus,
        $parseDBConnectTime,
        $queueMessageCondition,
        $appEnvironment,
        $overAllStatus
    ) {
        return
            self::getIntro($dateTime, $overAllStatus) .
            self::getWebResponseStatusFormatted($webResponseStatus) . "\n" .
            self::getWebsiteUpStatusFormatted($websiteUpStatus) . "\n" .
            self::getQueueStatusFormatted(
                $queueMessageStatus,
                $queueMessageCount,
                $queueMidPriorityStatus,
                $queueMidPriorityMessageCount,
                $queueDeadLetterStatus,
                $queueDeadLetterMessageCount,
                $parseStatus,
                $parseDBConnectTime,
                $appEnvironment,
                $queueMessageCondition) . "\n";
    }

    private static function getIntro($dateTime, $overAllStatus)
    {
        $currentDateTimeFormatted = $dateTime->format('Y-m-d H:i:s') . '  (New York)';
        if ($overAllStatus) {
            return ':white_check_mark: _' . $currentDateTimeFormatted . '_: ' . "\n";
        }
        return ':no_entry: _' . $currentDateTimeFormatted . '_: ' . "\n";
    }

    private static function getWebsiteUpStatusFormatted($websiteUpStatus)
    {
        if ($websiteUpStatus) {
            return ':+1: Backend *Up and running*';
        }
        return 'Backend *IS DOWN!*';
    }

    private static function getWebResponseStatusFormatted($websiteUpStatus)
    {
        if ($websiteUpStatus) {
            return ':+1: Website *Up and running*';
        }
        return 'Website *IS DOWN!*';
    }

    private static function getQueueStatusFormatted(
        $queueMessageStatus,
        $queueMessageCount,
        $queueMidPriorityStatus,
        $queueMidPriorityMessageCount,
        $queueDeadLetterStatus,
        $queueDeadLetterMessageCount,
        $parseStatus,
        $parseDBConnectTime,
        $appEnvironment,
        $queueMessageCondition
    )
    {
        if (!$parseStatus) {
            return ':bangbang: Parse DB *IS DOWN!*';
        }
        if (!$queueMessageStatus) {
            return ':bangbang: Queue *IS DOWN!*';
        }
        if (!$queueMidPriorityStatus) {
            return ':bangbang: Queue mid priority *IS DOWN!*';
        }
        if (!$queueDeadLetterStatus) {
            return ':bangbang: Queue dead letter *IS DOWN!*';
        }

        if ($queueMessageCondition == QueueStatusArbiter::CONDITION_GOOD) {
            return
                ':+1: Environment: ' . $appEnvironment . '*' . "\n" .
                ':+1: Queue *Message count: ' . $queueMessageCount . '*' . "\n" .
                ':+1: Queue *Message mid priority count: ' . $queueMidPriorityMessageCount . '*'. "\n" .
                ':+1: Queue *Message dead letter count: ' . $queueDeadLetterMessageCount . '*'. "\n" .
                ':+1: Parse DB *Connection Time in Seconds: ' . $parseDBConnectTime . '*';
        }

        if ($queueMessageCondition == QueueStatusArbiter::CONDITION_STABLE) {
            return
                ':fearful: Environment: ' . $appEnvironment . '*' . "\n" .
                ':fearful: Queue *Message count: ' . $queueMessageCount . '*' . "\n" .
                ':fearful: Queue mid priority *Message count: ' . $queueMidPriorityMessageCount . '*'. "\n" .
                ':fearful: Queue dead letter *Message count: ' . $queueDeadLetterMessageCount . '*'. "\n" .
                ':fearful: Parse DB *Connection Time in Seconds: ' . $parseDBConnectTime . '*';
        }

        if ($queueMessageCondition == QueueStatusArbiter::CONDITION_ERROR) {
            return
                ':bangbang: Environment: ' . $appEnvironment . '*' . "\n" .
                ':bangbang: Queue *Message count: ' . $queueMessageCount . '*' . "\n" .
                ':bangbang: Queue mid priority *Message count: ' . $queueMidPriorityMessageCount . '*'. "\n" .
                ':bangbang: Queue dead letter *Message count: ' . $queueDeadLetterMessageCount . '*'. "\n" .
                ':bangbang: Parse DB *Connection Time in Seconds: ' . $parseDBConnectTime . '*';
        }
    }

    public static function transformSlackMessageIntoTextMessage($slackMessage)
    {
        return str_replace([
            ':bangbang:',
            ':fearful:',
            ':+1:',
            ':no_entry:',
            ':white_check_mark:',
        ], '', $slackMessage);
    }
}
