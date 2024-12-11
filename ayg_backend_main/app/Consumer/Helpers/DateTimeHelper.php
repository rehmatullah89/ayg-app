<?php
namespace App\Consumer\Helpers;

use App\Consumer\Entities\CacheKey;
use DateInterval;
use DateTime;
use Slim\Route;

/**
 * Class DateTimeHelper
 * @package App\Consumer\Helpers
 */
class DateTimeHelper
{
    /**
     * @param int $seconds
     * @return int
     *
     * gets timestamp for time after $seconds seconds
     */
    public static function getXSecondsTimestamp($seconds)
    {
        $seconds = intval($seconds);
        $dateTime = new DateTime('now');
        $dateTime->add(new DateInterval('P0Y0M0DT0H0M' . $seconds . 'S'));
        return $dateTime->getTimestamp();
    }

    /**
     * @param int $minutes
     * @return int
     *
     * gets timestamp for time after $minutes minutes
     */
    public static function getXMinutesTimestamp($minutes)
    {
        $minutes = intval($minutes);
        $dateTime = new DateTime('now');
        $dateTime->add(new DateInterval('P0Y0M0DT0H' . $minutes . 'M0S'));
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

    public static function setHourAndMinuteBasedOnRetailerHours(DateTime $dateTime, string $retailerHours)
    {
        $openingDay = clone $dateTime;
        $retailerHours = explode(' ', $retailerHours);
        $retailerHours[0] = explode(':', $retailerHours[0]);
        $retailerHour = $retailerHours[0][0];
        $retailerMinute = $retailerHours[0][1];
        $retailerAmPm = $retailerHours[1];
        if ($retailerAmPm == 'PM') {
            $retailerHour = $retailerHour + 12;
        }

        if ($retailerHour == 12 && $retailerAmPm == 'AM') {
            $retailerHour = 0;
        }

        $openingDay->setTime($retailerHour, $retailerMinute, 0);

        return $openingDay;
    }

    public static function getDayOfWeekByTimestamp(int $timestamp, string $timezone): int
    {
        $dateTime = new DateTime('now', new \DateTimeZone($timezone));
        $dateTime->setTimestamp($timestamp);
        return $dateTime->format('N');
    }

    public static function getAmountOfSecondsSinceMidnightByTimestamp(int $timestamp, string $timezone): int
    {
        $dateTime = new DateTime('now', new \DateTimeZone($timezone));
        $dateTime->setTimestamp($timestamp);

        $midnight = new DateTime('now', new \DateTimeZone($timezone));
        $midnight->setTimestamp($timestamp);
        $midnight->setTime(0, 0, 0);

        return $dateTime->getTimestamp()-$midnight->getTimestamp();
    }


    public static function getOrderFullfillmentTimeRangeEstimateDisplay(
        int $fullfillmentTimeInSecs,
        int $rangeLowInSecs,
        int $rangeHighInSecs,
        string $timeZone
    ) {
        if (empty($fullfillmentTimeInSecs)) {
            return ["", 0, 0];
        }

        $locale = 'en_US';
        $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);

        $now = new DateTime('now', new \DateTimeZone($timeZone));
        $futureLow = (clone($now))->setTimestamp($now->getTimestamp() + $fullfillmentTimeInSecs - $rangeLowInSecs);
        $futureHigh = (clone($now))->setTimestamp($now->getTimestamp() + $fullfillmentTimeInSecs + $rangeHighInSecs);

        $isEstimationInPast = false;
        if ($now->getTimestamp() > $futureLow->getTimestamp()){
            $isEstimationInPast = true;
        }

        $fullfillmentTimeLowInSecs = $fullfillmentTimeInSecs - $rangeLowInSecs;
        $fullfillmentTimeHighInSecs = $fullfillmentTimeInSecs + $rangeHighInSecs;
        if ($fullfillmentTimeInSecs > 60 * 60 || $isEstimationInPast) {
            // same day
            $dayPart = '';
            if ($now->format('Y-m-d') != $futureLow->format('Y-m-d')) {
                $interval = $futureLow->diff($now);
                if ($interval->format('%a') == 1) {
                    $dayPart = 'Tomorrow';
                } else {
                    $dayPart = $futureLow->format('l, F ') . $numberFormatter->format((int)$futureLow->format('j'));
                }
                $dayPart = $dayPart.', ';
            }

            $displayText = $dayPart . '' . $futureLow->format('h:ia') . ' - ' . $futureHigh->format('h:ia');


        } else {
            $displayText = floor($fullfillmentTimeLowInSecs / 60) . " - " . floor($fullfillmentTimeHighInSecs / 60) . " mins";
        }

        return [$displayText, $fullfillmentTimeLowInSecs, $fullfillmentTimeHighInSecs];
    }


    public static function getOrderFullfillmentTimeRangeEstimateDisplay2(
        int $fullfillmentTimeInSecs,
        int $rangeLowInSecs,
        int $rangeHighInSecs,
        string $timeZone
    ) {
        if (empty($fullfillmentTimeInSecs)) {
            return ["", 0, 0];
        }

        $locale = 'en_US';
        $numberFormatter = new \NumberFormatter($locale, \NumberFormatter::ORDINAL);

        $now = new DateTime('now', new \DateTimeZone($timeZone));
        $futureLow = (clone($now))->setTimestamp($now->getTimestamp() + $fullfillmentTimeInSecs - $rangeLowInSecs);
        $futureHigh = (clone($now))->setTimestamp($now->getTimestamp() + $fullfillmentTimeInSecs + $rangeHighInSecs);


        $fullfillmentTimeLowInSecs = $fullfillmentTimeInSecs - $rangeLowInSecs;
        $fullfillmentTimeHighInSecs = $fullfillmentTimeInSecs + $rangeHighInSecs;
        if ($fullfillmentTimeInSecs > 60 * 60) {
            // same day
            $dayPart = '';
            if ($now->format('Y-m-d') != $futureLow->format('Y-m-d')) {
                $interval = $futureLow->diff($now);
                if ($interval->format('%a') == 1) {
                    $dayPart = 'Tomorrow';
                } else {
                    $dayPart = $futureLow->format('l, F ') . $numberFormatter->format((int)$futureLow->format('j'));
                }
            }

            $displayText = $dayPart . ' ' . $futureLow->format('h:ia') . ' - ' . $futureHigh->format('h:ia');


        } else {
            $displayText = floor($fullfillmentTimeLowInSecs / 60) . " - " . floor($fullfillmentTimeHighInSecs / 60) . " mins";
        }

        return [$displayText, $fullfillmentTimeLowInSecs, $fullfillmentTimeHighInSecs];
    }
}
