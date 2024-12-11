<?php
namespace App\Background\Helpers;


class LooperHelper
{
    public static function hasLooperLastRunTimeLimitPassed(int $lastRunTimestamp, float $gapNeededInMinutes)
    {
        return hasLooperLastRunTimeLimitPassed($lastRunTimestamp, $gapNeededInMinutes);
    }
}
