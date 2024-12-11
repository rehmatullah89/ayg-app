<?php
namespace App\Helpers;

class QueueStatusArbiter
{
    const CONDITION_GOOD = 'good';
    const CONDITION_STABLE = 'stable';
    const CONDITION_ERROR = 'error';

    public static function decideAboutCondition(
        $messageCount,
        $queueConditionGoodMaxMessages,
        $queueConditionStableMaxMessages,
        $messageMidPriorityCount,
        $queueMidPriorityConditionGoodMaxMessages,
        $queueMidPriorityConditionStableMaxMessages,
        $messageDeadLetterCount,
        $queueDeadLetterConditionGoodMaxMessages,
        $queueDeadLetterConditionStableMaxMessages,
        $parseDBConnectTime,
        $parseDBConditionGoodMaxConnectTime,
        $parseDBConditionStableMaxConnectTime
    ) {
        if (
            $messageCount <= $queueConditionGoodMaxMessages &&
            $messageMidPriorityCount <= $queueMidPriorityConditionGoodMaxMessages &&
            $messageDeadLetterCount <= $queueDeadLetterConditionGoodMaxMessages &&
            $parseDBConnectTime <= $parseDBConditionGoodMaxConnectTime
        ) {
            return self::CONDITION_GOOD;
        }

        if ($messageCount <= $queueConditionStableMaxMessages &&
            $messageMidPriorityCount <= $queueMidPriorityConditionStableMaxMessages &&
            $messageDeadLetterCount <= $queueDeadLetterConditionStableMaxMessages &&
            $parseDBConnectTime <= $parseDBConditionStableMaxConnectTime
        ) {
            return self::CONDITION_STABLE;
        }

        return self::CONDITION_ERROR;
    }
}
