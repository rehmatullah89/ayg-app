<?php
namespace App\Helpers;

class OverallStatusArbiter
{
    public static function decide(
        $webResponseStatus,
        $websiteUpStatus,
        $queueStatus,
        $queueMidPriorityStatus,
        $queueDeadLetterStatus,
        $parseStatus,
        $queueMessageCondition

    ) {
        if ($webResponseStatus && $websiteUpStatus && $queueStatus && $queueMidPriorityStatus && $queueDeadLetterStatus && $parseStatus && $queueMessageCondition == QueueStatusArbiter::CONDITION_GOOD) {
            return true;
        }
        return false;
    }
}
