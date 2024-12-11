<?php
namespace App\Background\Repositories;

use App\Background\Entities\CheckInLog;

/**
 * Class PingLogMysqlRepository
 * @package App\Background\Repositories
 */
class CheckInS3Repository extends S3Repository implements CheckInRepositoryInterface
{
    const CHECKINLOG_DIRECTORY = 'checkin_logs';

    /**
     * @param $userId
     * @param $sessionObjectId
     */
    public function logUserCheckin($userId, $sessionObjectId): void
    {
            $nearAirportIataCode = "";
            $locationCity = $locationState = $locationCountry = "";

            // Fetch Session Device
            $sessionDevice = parseExecuteQuery(array("objectId" => $sessionObjectId), "SessionDevices", "", "updatedAt", [], 1);

            list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationForSession($sessionDevice);

            $checkinTimestamp = $sessionDevice->get('checkinTimestamp');
            $currentTimeZone = date_default_timezone_get();

            $airportTimeZone = '';
            if(!empty($nearAirportIataCode)) {

                $airportTimeZone = fetchAirportTimeZone($nearAirportIataCode, $currentTimeZone);
                if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {
                    // Set Airport Timezone
                    date_default_timezone_set($airportTimeZone);
                }
            }

            $timeTimezoneShort = date("T", $checkinTimestamp);
            $timeSecondOfHour = date("s", $checkinTimestamp);
            $timeMinuteOfHour = date("i", $checkinTimestamp);
            $timeHourOfDay = date("G", $checkinTimestamp);
            $timeDayOfWeekName = date("D", $checkinTimestamp);
            $timeDayOfWeek = date("N", $checkinTimestamp);
            $timeDayOfMonth = date("d", $checkinTimestamp);
            $timeMonthName = date("M", $checkinTimestamp);
            $timeMonth = date("m", $checkinTimestamp);
            $timeYear = date("Y", $checkinTimestamp);

            if(!empty($nearAirportIataCode) && strcasecmp($airportTimeZone, $currentTimeZone)!=0) {
                date_default_timezone_set($currentTimeZone);
            }

            $checkInLog = new CheckInLog($userId, $checkinTimestamp, $locationState, $locationCountry, $nearAirportIataCode, $timeTimezoneShort, $timeSecondOfHour, $timeMinuteOfHour, $timeHourOfDay, $timeDayOfWeekName, $timeDayOfWeek, $timeDayOfMonth, $timeMonthName, $timeMonth, $timeYear, $locationSource);
            $this->store(self::CHECKINLOG_DIRECTORY, $checkInLog);
    }
}
