<?php
namespace App\Background\Repositories;

use App\Background\Entities\RetailerPingLog;

/**
 * Class PingLogMysqlRepository
 * @package App\Background\Repositories
 */
class PingLogMysqlRepository extends MysqlRepository implements PingLogRepositoryInterface
{
    /**
     * @var \PDO
     */
    private $pdoConnection;

    /**
     * RetailerPingLogMysqlRepository constructor.
     * @param \PDO $pdoConnection
     */
    public function __construct(\PDO $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryPing($slackUsername, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO ping_logs SET 
            `object_id` = :slackUsername,
            `timestamp` = :currentTimestamp,
            `object_type` = 'delivery',
            `action` = 'p'
        ");

            $stmt->bindParam(':slackUsername', $slackUsername, \PDO::PARAM_STR);
            $stmt->bindParam(':currentTimestamp', $timestamp, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryActivated($slackUsername, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO ping_logs SET 
            `object_id` = :slackUsername,
            `timestamp` = :currentTimestamp,
            `object_type` = 'delivery',
            `action` = 'a'
        ");

            $stmt->bindParam(':slackUsername', $slackUsername, \PDO::PARAM_STR);
            $stmt->bindParam(':currentTimestamp', $timestamp, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryDeactivated($slackUsername, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO ping_logs SET 
            `object_id` = :slackUsername,
            `timestamp` = :currentTimestamp,
            `object_type` = 'delivery',
            `action` = 'd'
        ");

            $stmt->bindParam(':slackUsername', $slackUsername, \PDO::PARAM_STR);
            $stmt->bindParam(':currentTimestamp', $timestamp, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }


    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerLogin($retailerUniqueId, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO ping_logs SET 
            `object_id` = :retailerUniqueId,
            `timestamp` = :currentTimestamp,
            `object_type` = 'retailer',
            `action` = 'l'
        ");

            $stmt->bindParam(':retailerUniqueId', $retailerUniqueId, \PDO::PARAM_STR);
            $stmt->bindParam(':currentTimestamp', $timestamp, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }


    /**
     * @param $objectId
     * @param $action
     * @param $data
     * @param $location
     * @param $timestamp
     */
    public function logUserAction($objectId, $action, $data, $location, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO user_action_logs SET 
                  `objectId` = :objectId,
                  `action` = :action,
                  `data` = :data,
                  `timestamp` = :timestamp,
                  `timeTimezoneShort` = :timeTimezoneShort,
                  `timeSecondOfHour` = :timeSecondOfHour,
                  `timeMinuteOfHour` = :timeMinuteOfHour,
                  `timeHourOfDay` = :timeHourOfDay,
                  `timeDayOfWeekName` = :timeDayOfWeekName,
                  `timeDayOfWeek` = :timeDayOfWeek,
                  `timeDayOfMonth` = :timeDayOfMonth,
                  `timeMonthName` = :timeMonthName,
                  `timeMonth` = :timeMonth,
                  `timeYear` = :timeYear,
                  `state` = :state,
                  `country` = :country,
                  `locationSource` = :locationSource,
                  `nearAirportIataCode` = :nearAirportIataCode,
                  `actionForRetailerAirportIataCode` = :actionForRetailerAirportIataCode
        ");



            $state = $location["state"];
            $country = $location["country"];
            $locationSource = $location["locationSource"];
            $nearAirportIataCode = $location["nearAirportIataCode"];

            $currentTimeZone = date_default_timezone_get();
            $airportTimeZone = '';
            if(!empty($nearAirportIataCode)) {

                $airportTimeZone = fetchAirportTimeZone($nearAirportIataCode, $currentTimeZone);
                if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

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

            if(!empty($nearAirportIataCode) 
                && strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

                date_default_timezone_set($currentTimeZone);
            }

            $stmt->bindParam(':objectId', $objectId, \PDO::PARAM_STR);
            $stmt->bindParam(':action', $action, \PDO::PARAM_STR);
            $stmt->bindParam(':data', $data, \PDO::PARAM_STR);
            $stmt->bindParam(':timestamp', $timestamp, \PDO::PARAM_INT);

            $stmt->bindParam(':timeTimezoneShort', $timeTimezoneShort, \PDO::PARAM_STR);
            $stmt->bindParam(':timeSecondOfHour', $timeSecondOfHour, \PDO::PARAM_STR);
            $stmt->bindParam(':timeMinuteOfHour', $timeMinuteOfHour, \PDO::PARAM_STR);
            $stmt->bindParam(':timeHourOfDay', $timeHourOfDay, \PDO::PARAM_STR);
            $stmt->bindParam(':timeDayOfWeekName', $timeDayOfWeekName, \PDO::PARAM_STR);
            $stmt->bindParam(':timeDayOfWeek', $timeDayOfWeek, \PDO::PARAM_STR);
            $stmt->bindParam(':timeDayOfMonth', $timeDayOfMonth, \PDO::PARAM_STR);
            $stmt->bindParam(':timeMonthName', $timeMonthName, \PDO::PARAM_STR);
            $stmt->bindParam(':timeMonth', $timeMonth, \PDO::PARAM_STR);
            $stmt->bindParam(':timeYear', $timeYear, \PDO::PARAM_STR);

            $stmt->bindParam(':state', $state, \PDO::PARAM_STR);
            $stmt->bindParam(':country', $country, \PDO::PARAM_STR);
            $stmt->bindParam(':nearAirportIataCode', $nearAirportIataCode, \PDO::PARAM_STR);
            $stmt->bindParam(':locationSource', $locationSource, \PDO::PARAM_STR);

            $actionForRetailerAirportIataCode = '0';
            $dataArray = json_decode($data, true);
            if(isset($dataArray["actionForRetailerAirportIataCode"])) {

                $actionForRetailerAirportIataCode = $dataArray["actionForRetailerAirportIataCode"];
            }
            
            $stmt->bindParam(':actionForRetailerAirportIataCode', $actionForRetailerAirportIataCode, \PDO::PARAM_STR);

            $result = $stmt->execute();

            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerLogout($retailerUniqueId, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO ping_logs SET 
            `object_id` = :retailerUniqueId,
            `timestamp` = :currentTimestamp,
            `object_type` = 'retailer',
            `action` = 'o'
        ");

            $stmt->bindParam(':retailerUniqueId', $retailerUniqueId, \PDO::PARAM_STR);
            $stmt->bindParam(':currentTimestamp', $timestamp, \PDO::PARAM_INT);

            $result = $stmt->execute();

            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
        }
    }

    /**
     * @param $userId
     * @param $sessionObjectId
     */

    public function logUserCheckin($userId, $sessionObjectId)
    {

        $nearAirportIataCode = "";
        $locationCity = $locationState = $locationCountry = "";

        // Fetch Session Device
        $sessionDevice = parseExecuteQuery(array("objectId" => $sessionObjectId), "SessionDevices", "", "updatedAt", [], 1);

        list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationForSession($sessionDevice);

        try {

            $stmt = $this->pdoConnection->prepare("INSERT IGNORE INTO checkin_logs SET 
                `object_id` = :userId,
                `timestamp` = :timestamp,
                `state` = :state,
                `country` = :country,
                `nearAirportIataCode` = :nearAirportIataCode,
                `timeTimezoneShort` = :timeTimezoneShort,
                `timeSecondOfHour` = :timeSecondOfHour,
                `timeMinuteOfHour` = :timeMinuteOfHour,
                `timeHourOfDay` = :timeHourOfDay,
                `timeDayOfWeekName` = :timeDayOfWeekName,
                `timeDayOfWeek` = :timeDayOfWeek,
                `timeDayOfMonth` = :timeDayOfMonth,
                `timeMonthName` = :timeMonthName,
                `timeMonth` = :timeMonth,
                `timeYear` = :timeYear,
                `locationSource` = :locationSource
            ");
                // `city` = :city,

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

            if(!empty($nearAirportIataCode) 
                && strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

                date_default_timezone_set($currentTimeZone);
            }
            
            $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
            $stmt->bindParam(':timestamp', $checkinTimestamp, \PDO::PARAM_INT);
            // $stmt->bindParam(':city', $locationCity, \PDO::PARAM_STR);
            $stmt->bindParam(':state', $locationState, \PDO::PARAM_STR);
            $stmt->bindParam(':country', $locationCountry, \PDO::PARAM_STR);
            $stmt->bindParam(':nearAirportIataCode', $nearAirportIataCode, \PDO::PARAM_STR);
            $stmt->bindParam('timeTimezoneShort', $timeTimezoneShort, \PDO::PARAM_STR);
            $stmt->bindParam('timeSecondOfHour', $timeSecondOfHour, \PDO::PARAM_STR);
            $stmt->bindParam('timeMinuteOfHour', $timeMinuteOfHour, \PDO::PARAM_STR);
            $stmt->bindParam('timeHourOfDay', $timeHourOfDay, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfWeekName', $timeDayOfWeekName, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfWeek', $timeDayOfWeek, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfMonth', $timeDayOfMonth, \PDO::PARAM_STR);
            $stmt->bindParam('timeMonthName', $timeMonthName, \PDO::PARAM_STR);
            $stmt->bindParam('timeMonth', $timeMonth, \PDO::PARAM_STR);
            $stmt->bindParam('timeYear', $timeYear, \PDO::PARAM_STR);
            $stmt->bindParam('locationSource', $locationSource, \PDO::PARAM_STR);

            $result = $stmt->execute();
            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
            // echo $e->getMessage();
        }
    }

    /**
     * @param $objectId
     */
    public function logWebsiteDownload($objectId)
    {

        // Fetch Row
        $zWebsiteDownloads = parseExecuteQuery(array("objectId" => $objectId), "zWebsiteDownloads", "", "", [], 1);

        try {

            $stmt = $this->pdoConnection->prepare("INSERT IGNORE INTO downloadFromWebsite SET 
                `objectId` = :objectId,
                `timestamp` = :timestamp,
                `touchpointState` = :touchpointState,
                `touchpointCountry` = :touchpointCountry,
                `nearAirportIataCode` = :nearAirportIataCode,
                `referralCode` = :referralCode,
                `appPlatform` = :appPlatform,
                `timeTimezoneShort` = :timeTimezoneShort,
                `timeSecondOfHour` = :timeSecondOfHour,
                `timeMinuteOfHour` = :timeMinuteOfHour,
                `timeHourOfDay` = :timeHourOfDay,
                `timeDayOfWeekName` = :timeDayOfWeekName,
                `timeDayOfWeek` = :timeDayOfWeek,
                `timeDayOfMonth` = :timeDayOfMonth,
                `timeMonthName` = :timeMonthName,
                `timeMonth` = :timeMonth,
                `timeYear` = :timeYear,
                `locationSource` = :locationSource
            ");

            $nearAirportIataCode = $touchpointCity = $touchpointState = $touchpointCountry = "";
            $locationSource = "unknown";
            if(!empty($zWebsiteDownloads->get('IPAddr'))) {

                list($nearAirportIataCode, $touchpointCity, $touchpointState, $touchpointCountry, $locationSource) = getLocationByIP($zWebsiteDownloads->get('IPAddr'));
            }

            $timestamp = $zWebsiteDownloads->getCreatedAt()->getTimestamp();
            $appPlatform = $zWebsiteDownloads->get('appPlatform');
            $referralCode = $zWebsiteDownloads->get('referralCode');

            $currentTimeZone = date_default_timezone_get();

            $airportTimeZone = '';
            if(!empty($nearAirportIataCode)) {

                $airportTimeZone = fetchAirportTimeZone($nearAirportIataCode, $currentTimeZone);
                if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

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

            if(!empty($nearAirportIataCode) 
                && strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

                date_default_timezone_set($currentTimeZone);
            }
            
            $stmt->bindParam(':objectId', $objectId, \PDO::PARAM_STR);
            $stmt->bindParam(':timestamp', $timestamp, \PDO::PARAM_INT);
            // $stmt->bindParam(':city', $locationCity, \PDO::PARAM_STR);
            $stmt->bindParam(':touchpointState', $touchpointState, \PDO::PARAM_STR);
            $stmt->bindParam(':touchpointCountry', $touchpointCountry, \PDO::PARAM_STR);
            $stmt->bindParam(':nearAirportIataCode', $nearAirportIataCode, \PDO::PARAM_STR);
            $stmt->bindParam('timeTimezoneShort', $timeTimezoneShort, \PDO::PARAM_STR);
            $stmt->bindParam('timeSecondOfHour', $timeSecondOfHour, \PDO::PARAM_STR);
            $stmt->bindParam('timeMinuteOfHour', $timeMinuteOfHour, \PDO::PARAM_STR);
            $stmt->bindParam('timeHourOfDay', $timeHourOfDay, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfWeekName', $timeDayOfWeekName, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfWeek', $timeDayOfWeek, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfMonth', $timeDayOfMonth, \PDO::PARAM_STR);
            $stmt->bindParam('timeMonthName', $timeMonthName, \PDO::PARAM_STR);
            $stmt->bindParam('timeMonth', $timeMonth, \PDO::PARAM_STR);
            $stmt->bindParam('timeYear', $timeYear, \PDO::PARAM_STR);

            $stmt->bindParam('referralCode', $referralCode, \PDO::PARAM_STR);
            $stmt->bindParam('appPlatform', $appPlatform, \PDO::PARAM_STR);
            $stmt->bindParam('locationSource', $locationSource, \PDO::PARAM_STR);

            $result = $stmt->execute();
            if (!$result) {
                // print_r($result);exit;
                // log failed insert
                return false;
            }
        } catch (\Exception $e) {
            // echo($e->getMessage());exit;
            // log failed insert
            return false;
        }

        return "";
    }

    /**
     * @param $objectId
     */
    public function logWebsiteRatingClick($objectId)
    {

        // Fetch Row
        $zAppRatingRequestClicks = parseExecuteQuery(array("objectId" => $objectId), "zAppRatingRequestClicks", "", "", ["orderRatingRequest", "orderRatingRequest.userDevice"], 1);

        try {

            $stmt = $this->pdoConnection->prepare("INSERT IGNORE INTO ratingRequestClicks SET 
                `ratingRequestedId` = :ratingRequestedId,
                `timestamp` = :timestamp,
                `locationCity` = :locationCity,
                `locationState` = :locationState,
                `locationCountry` = :locationCountry,
                `nearAirportIataCode` = :nearAirportIataCode,
                `appPlatform` = :appPlatform,
                `clickSource` = :clickSource,
                `timeTimezoneShort` = :timeTimezoneShort,
                `timeSecondOfHour` = :timeSecondOfHour,
                `timeMinuteOfHour` = :timeMinuteOfHour,
                `timeHourOfDay` = :timeHourOfDay,
                `timeDayOfWeekName` = :timeDayOfWeekName,
                `timeDayOfWeek` = :timeDayOfWeek,
                `timeDayOfMonth` = :timeDayOfMonth,
                `timeMonthName` = :timeMonthName,
                `timeMonth` = :timeMonth,
                `timeYear` = :timeYear,
                `locationSource` = :locationSource
            ");

            $nearAirportIataCode = $locationCity = $locationState = $locationCountry = "";
            $locationSource = "unknown";
            if(!empty($zAppRatingRequestClicks->get('IPAddr'))) {

                list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationByIP($zAppRatingRequestClicks->get('IPAddr'));
            }

            $timestamp = $zAppRatingRequestClicks->getCreatedAt()->getTimestamp();
            $appPlatform = ($zAppRatingRequestClicks->get('orderRatingRequest')->get('userDevice')->get('isIos') == true ? 'iOS' : 'Android');
            $clickSource = $zAppRatingRequestClicks->get('clickSource');

            $currentTimeZone = date_default_timezone_get();

            $airportTimeZone = '';
            if(!empty($nearAirportIataCode)) {

                $airportTimeZone = fetchAirportTimeZone($nearAirportIataCode, $currentTimeZone);
                if(strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

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

            if(!empty($nearAirportIataCode) 
                && strcasecmp($airportTimeZone, $currentTimeZone)!=0) {

                date_default_timezone_set($currentTimeZone);
            }
            
            $stmt->bindParam(':ratingRequestedId', $objectId, \PDO::PARAM_STR);
            $stmt->bindParam(':timestamp', $timestamp, \PDO::PARAM_INT);
            $stmt->bindParam(':locationCity', $locationCity, \PDO::PARAM_STR);
            $stmt->bindParam(':locationState', $locationState, \PDO::PARAM_STR);
            $stmt->bindParam(':locationCountry', $locationCountry, \PDO::PARAM_STR);
            $stmt->bindParam(':nearAirportIataCode', $nearAirportIataCode, \PDO::PARAM_STR);
            $stmt->bindParam('timeTimezoneShort', $timeTimezoneShort, \PDO::PARAM_STR);
            $stmt->bindParam('timeSecondOfHour', $timeSecondOfHour, \PDO::PARAM_STR);
            $stmt->bindParam('timeMinuteOfHour', $timeMinuteOfHour, \PDO::PARAM_STR);
            $stmt->bindParam('timeHourOfDay', $timeHourOfDay, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfWeekName', $timeDayOfWeekName, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfWeek', $timeDayOfWeek, \PDO::PARAM_STR);
            $stmt->bindParam('timeDayOfMonth', $timeDayOfMonth, \PDO::PARAM_STR);
            $stmt->bindParam('timeMonthName', $timeMonthName, \PDO::PARAM_STR);
            $stmt->bindParam('timeMonth', $timeMonth, \PDO::PARAM_STR);
            $stmt->bindParam('timeYear', $timeYear, \PDO::PARAM_STR);

            $stmt->bindParam('clickSource', $clickSource, \PDO::PARAM_STR);
            $stmt->bindParam('appPlatform', $appPlatform, \PDO::PARAM_STR);
            $stmt->bindParam('locationSource', $locationSource, \PDO::PARAM_STR);

            $result = $stmt->execute();
            if (!$result) {
                // print_r($result);exit;
                // log failed insert
                return false;
            }
        } catch (\Exception $e) {
            // echo($e->getMessage());exit;
            // log failed insert
            return false;
        }

        return "";
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerPing($retailerUniqueId, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO ping_logs SET 
            `object_id` = :retailerUniqueId,
            `timestamp` = :currentTimestamp,
            `object_type` = 'retailer',
            `action` = 'p'
        ");

            $stmt->bindParam(':retailerUniqueId', $retailerUniqueId, \PDO::PARAM_STR);
            $stmt->bindParam(':currentTimestamp', $timestamp, \PDO::PARAM_INT);

            $result = $stmt->execute();
            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
            // echo $e->getMessage();
        }
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerConnectFailure($retailerUniqueId, $timestamp)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO ping_logs SET 
            `object_id` = :retailerUniqueId,
            `timestamp` = :currentTimestamp,
            `object_type` = 'retailer',
            `action` = 'f'
        ");

            $stmt->bindParam(':retailerUniqueId', $retailerUniqueId, \PDO::PARAM_STR);
            $stmt->bindParam(':currentTimestamp', $timestamp, \PDO::PARAM_INT);

            $result = $stmt->execute();
            if (!$result) {
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
            // echo $e->getMessage();
        }
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     * @return RetailerPingLog|null
     */
    public function getFirstRetailerPingAfterGivenTimestamp($retailerUniqueId, $timestamp)
    {
        $stmt = $this->pdoConnection->prepare("SELECT * FROM ping_logs WHERE 
            `object_id` = :retailerUniqueId AND
            `timestamp` >= :givenTimestamp AND
            `object_type` = 'retailer' AND 
            `action` = 'p'
            ORDER BY `timestamp` ASC
            LIMIT 1
        ");
        $stmt->bindParam(':retailerUniqueId', $retailerUniqueId, \PDO::PARAM_STR);
        $stmt->bindParam(':givenTimestamp', $timestamp, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return null;
        }

        return new RetailerPingLog([
            'retailerUniqueId' => $retailerUniqueId,
            'timestamp' => $result[0]['timestamp']
        ]);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     * @return RetailerPingLog|null
     */
    public function getLastRetailerPingBeforeGivenTimestamp($retailerUniqueId, $timestamp)
    {
        $stmt = $this->pdoConnection->prepare("SELECT * FROM ping_logs WHERE 
            `object_id` = :retailerUniqueId AND
            `timestamp` <= :givenTimestamp AND
            `object_type` = 'retailer' AND 
            `action` = 'p'
            ORDER BY `timestamp` DESC
            LIMIT 1
        ");
        $stmt->bindParam(':retailerUniqueId', $retailerUniqueId, \PDO::PARAM_STR);
        $stmt->bindParam(':givenTimestamp', $timestamp, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return null;
        }

        return new RetailerPingLog([
            'retailerUniqueId' => $retailerUniqueId,
            'timestamp' => $result[0]['timestamp']
        ]);
    }

    /**
     * @param $retailerUniqueId
     * @param $startTimestamp
     * @param $endTimestamp
     * @return int
     */
    public function countRetailerPingsBetweenTimestamps($retailerUniqueId, $startTimestamp, $endTimestamp)
    {
        $stmt = $this->pdoConnection->prepare("SELECT count(id) as c FROM ping_logs WHERE 
            `object_id` = :retailerUniqueId AND
            `timestamp` >= :givenStartTimestamp AND
            `timestamp` <= :givenEndTimestamp AND
            `object_type` = 'retailer' AND 
            `action` = 'p'
        ");
        $stmt->bindParam(':retailerUniqueId', $retailerUniqueId, \PDO::PARAM_STR);
        $stmt->bindParam(':givenStartTimestamp', $startTimestamp, \PDO::PARAM_INT);
        $stmt->bindParam(':givenEndTimestamp', $endTimestamp, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result[0]['c'];
    }

    /**
     * @param $deliveryId
     * @param $timestamp
     * @return Array|null
     */
    public function getDeliveryPingBetweenTimestamp($deliveryIds, $startTimestamp, $endTimestamp)
    {
        if(!empty($deliveryIds) && count_like_php5($deliveryIds) > 0) {

            $deliverIdsString = "'" . implode("','", $deliveryIds) . "'";
            $stmt = $this->pdoConnection->prepare("SELECT timestamp,object_id FROM ping_logs WHERE 
                `object_id` IN (" . $deliverIdsString . ") AND
                `timestamp` >= :startTimestamp AND
                `timestamp` < :endTimestamp AND
                `object_type` = 'delivery'
                ORDER BY `timestamp` ASC
            ");
            // $stmt->bindParam(':deliveryIds', $deliverIdsString, \PDO::PARAM_STR);
        }
        else {

            $stmt = $this->pdoConnection->prepare("SELECT timestamp,object_id FROM ping_logs WHERE 
                `timestamp` >= :startTimestamp AND
                `timestamp` < :endTimestamp AND
                `object_type` = 'delivery'
                ORDER BY `timestamp` ASC
            ");
        }

        $stmt->bindParam(':startTimestamp', $startTimestamp, \PDO::PARAM_INT);
        $stmt->bindParam(':endTimestamp', $endTimestamp, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * @param $uniqueId
     * @param $timestamp
     * @return Array|null
     */
    public function getRetailerPingBetweenTimestamp($uniqueId, $startTimestamp, $endTimestamp)
    {
        if(!empty($uniqueId)) {

            $stmt = $this->pdoConnection->prepare("SELECT timestamp,object_id FROM ping_logs WHERE 
                `object_id` = :uniqueId AND
                `timestamp` >= :startTimestamp AND
                `timestamp` < :endTimestamp AND
                `object_type` = 'retailer' AND
                `action` = 'p'
                ORDER BY timestamp
            ");
            $stmt->bindParam(':uniqueId', $uniqueId, \PDO::PARAM_STR);
        }
        else {

            $stmt = $this->pdoConnection->prepare("SELECT COUNT(DISTINCT timestamp) cnt FROM ping_logs WHERE 
                `timestamp` >= :startTimestamp AND
                `timestamp` < :endTimestamp AND
                `object_type` = 'retailer' AND
                `action` = 'p'
            ");
        }

        $stmt->bindParam(':startTimestamp', $startTimestamp, \PDO::PARAM_INT);
        $stmt->bindParam(':endTimestamp', $endTimestamp, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return null;
        }

        return $result;
    }

    /**
     * @param $queueMessage
     * @param $actionIfSending
     * @param $typeOfOp
     * @param $consumerTag
     * @param $endPoint
     * @param $queueName
     */
    public function logQueueMessageTracffic($queueMessage, $actionIfSending, $typeOfOp, $consumerTag, $endPoint, $queueName)
    {
        try {
            $stmt = $this->pdoConnection->prepare("INSERT INTO queue_logs SET 
            `queueMessage` = :queueMessage,
            `actionIfSending` = :actionIfSending,
            `typeOfOp` = :typeOfOp,
            `consumerTag` = :consumerTag,
            `endPoint` = :endPoint,
            `queueName` = :queueName,
            `insertTimestamp` = ".time()."
        ");

            $stmt->bindParam(':queueMessage', $queueMessage, \PDO::PARAM_STR);
            $stmt->bindParam(':actionIfSending', $actionIfSending, \PDO::PARAM_STR);
            $stmt->bindParam(':typeOfOp', $typeOfOp, \PDO::PARAM_STR);
            $stmt->bindParam(':consumerTag', $consumerTag, \PDO::PARAM_STR);
            $stmt->bindParam(':endPoint', $endPoint, \PDO::PARAM_STR);
            $stmt->bindParam(':queueName', $queueName, \PDO::PARAM_STR);

            $result = $stmt->execute();
            if (!$result) {
                json_error('AS_LOG_mysql_result_error', json_encode($stmt->errorInfo()), json_encode($stmt->errorInfo()) . " - " . $queueMessage,3,1);
                // log failed insert
            }
        } catch (\Exception $e) {
            // log failed insert
            json_error("AS_QLOG", "", "Failed to save for: " . $queueMessage . " -- " . $e->getMessage(), 2, 1);
        }
    }
}