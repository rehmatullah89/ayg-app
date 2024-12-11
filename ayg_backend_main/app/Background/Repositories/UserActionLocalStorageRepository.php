<?php
namespace App\Background\Repositories;

use App\Background\Entities\UserActionLog;

/**
 * Class UserActionLogS3Repository
 * @package App\Background\Repositories
 */
class UserActionLocalStorageRepository extends LocalStorageRepository implements UserActionLogRepositoryInterface
{
    const USER_ACTION_LOG_DIRECTORY = 'user_action_logs';

    /**
     * @param $objectId
     * @param $action
     * @param $data
     * @param $location
     * @param $timestamp
     */
    public function logUserAction(string $objectId, string $action, string $data, array $location, int $timestamp): void
    {
        $state = $location["state"];
        $country = $location["country"];
        $locationSource = $location["locationSource"];
        $nearAirportIataCode = $location["nearAirportIataCode"];

        $currentTimeZone = date_default_timezone_get();
        $airportTimeZone = '';
        if (!empty($nearAirportIataCode)) {
            $airportTimeZone = fetchAirportTimeZone($nearAirportIataCode, $currentTimeZone);
            if (strcasecmp($airportTimeZone, $currentTimeZone) != 0) {

                // Set Airport Timezone
                date_default_timezone_set($airportTimeZone);
            }
        }

        $timeTimezoneShort = date("T", $timestamp);
        $timeSecondOfHour = date("s", $timestamp);
        $timeMinuteOfHour = date("i", $timestamp);
        $timeHourOfDay = date("G", $timestamp);
        $timeDayOfWeekName = date("D", $timestamp);
        $timeDayOfWeek = date("N", $timestamp);
        $timeDayOfMonth = date("d", $timestamp);
        $timeMonthName = date("M", $timestamp);
        $timeMonth = date("m", $timestamp);
        $timeYear = date("Y", $timestamp);

        if (!empty($nearAirportIataCode) && strcasecmp($airportTimeZone, $currentTimeZone) != 0) {
            date_default_timezone_set($currentTimeZone);
        }

        $actionForRetailerAirportIataCode = '0';
        $dataArray = json_decode($data, true);
        if (isset($dataArray["actionForRetailerAirportIataCode"])) {
            $actionForRetailerAirportIataCode = $dataArray["actionForRetailerAirportIataCode"];
        }

        $userActionLog = new UserActionLog($objectId, $action, $data, $timestamp, $timeTimezoneShort, $timeSecondOfHour,
            $timeMinuteOfHour, $timeHourOfDay, $timeDayOfWeekName, $timeDayOfWeek, $timeDayOfMonth, $timeMonthName,
            $timeMonth, $timeYear, $state, $country, $nearAirportIataCode, $locationSource);
        $this->store(self::USER_ACTION_LOG_DIRECTORY, $userActionLog);
    }
}
