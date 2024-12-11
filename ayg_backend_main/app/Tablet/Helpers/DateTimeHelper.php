<?php
namespace App\Tablet\Helpers;

use DateInterval;
use DateTime;

/**
 * Class DateTimeHelper
 * @package App\Tablet\Helpers
 */
class DateTimeHelper
{
    /**
     * @param int $minutes
     * @return int
     *
     * gets timestamp for time after $minutes minutes
     */
    public static function getXMinutesTimestamp($minutes)
    {
        $minutes=intval($minutes);
        $dateTime = new DateTime('now');
        $dateTime->add(new DateInterval('P0Y0M0DT0H'.$minutes.'M0S'));
        return $dateTime->getTimestamp();
    }

    /**
     * @return int
     *
     * gets timestamp of the end of current day (11:59:59PM)
     * with respect to currently set default timezone
     */
    public static function getEndOfCurrentDayTimestamp()
    {
        $dateTime = new DateTime('tomorrow midnight');
        return $dateTime->getTimestamp() - 1;
    }

    /**
     * @return int
     *
     * gets timestamp of the end of current week (saturday 11:59:59PM)
     * with respect to currently set default timezone
     */
    public static function getEndOfCurrentWeekTimestamp()
    {
        $dateTime = new DateTime('next monday midnight');
        return $dateTime->getTimestamp() - 1;
    }

    /**
     * @return int
     *
     * gets timestamp of the end of current month (last day of the month 11:59:59PM)
     * with respect to currently set default timezone
     */
    public static function getEndOfCurrentMonthTimestamp()
    {
        $dateTime = new DateTime('first day of next month midnight');
        return $dateTime->getTimestamp() - 1;
    }

}