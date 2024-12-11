<?php
namespace App\Background\Entities;

use Ramsey\Uuid\Uuid;

/**
 * Class CheckInLog
 * @package App\Background\Entities
 */
class CheckInLog extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $objectId;

    /**
     * @var int
     */
    private $timestamp;

    /**
     * @var string
     */
    private $state;

    /**
     * @var string
     */
    private $country;

    /**
     * @var string
     */
    private $nearAirportIataCode;

    /**
     * @var string
     */
    private $timeTimezoneShort;

    /**
     * @var string
     */
    private $timeSecondOfHour;

    /**
     * @var string
     */
    private $timeMinuteOfHour;

    /**
     * @var string
     */
    private $timeHourOfDay;

    /**
     * @var string
     */
    private $timeDayOfWeekName;

    /**
     * @var string
     */
    private $timeDayOfWeek;

    /**
     * @var string
     */
    private $timeDayOfMonth;

    /**
     * @var string
     */
    private $timeMonthName;

    /**
     * @var string
     */
    private $timeMonth;

    /**
     * @var string
     */
    private $timeYear;

    /**
     * @var string
     */
    private $locationSource;


    /**
     * @return object
     */
    public function __construct($objectId, $timestamp, $state, $country, $nearAirportIataCode, $timeTimezoneShort, $timeSecondOfHour, $timeMinuteOfHour, $timeHourOfDay, $timeDayOfWeekName, $timeDayOfWeek, $timeDayOfMonth, $timeMonthName, $timeMonth, $timeYear, $locationSource)
    {
        $this->id=Uuid::uuid1();
        $this->objectId=$objectId;
        $this->timestamp=$timestamp;
        $this->state=$state;
        $this->country=$country;
        $this->nearAirportIataCode=$nearAirportIataCode;
        $this->timeTimezoneShort=$timeTimezoneShort;
        $this->timeSecondOfHour=$timeSecondOfHour;
        $this->timeMinuteOfHour=$timeMinuteOfHour;
        $this->timeHourOfDay=$timeHourOfDay;
        $this->timeDayOfWeekName=$timeDayOfWeekName;
        $this->timeDayOfWeek=$timeDayOfWeek;
        $this->timeDayOfMonth=$timeDayOfMonth;
        $this->timeMonthName=$timeMonthName;
        $this->timeMonth=$timeMonth;
        $this->timeYear=$timeYear;
        $this->locationSource=$locationSource;
    }

    /**
     * @return string
     */
    public function getObjectId(): string
    {
        return $this->objectId;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * @return string
     */
    public function getNearAirportIataCode(): string
    {
        return $this->nearAirportIataCode;
    }

    /**
     * @return string
     */
    public function getTimeTimezoneShort(): string
    {
        return $this->timeTimezoneShort;
    }

    /**
     * @return string
     */
    public function getTimeSecondOfHour(): string
    {
        return $this->timeSecondOfHour;
    }

    /**
     * @return string
     */
    public function getTimeMinuteOfHour(): string
    {
        return $this->timeMinuteOfHour;
    }

    /**
     * @return string
     */
    public function getTimeHourOfDay(): string
    {
        return $this->timeHourOfDay;
    }

    /**
     * @return string
     */
    public function getTimeDayOfWeekName(): string
    {
        return $this->timeDayOfWeekName;
    }

    /**
     * @return string
     */
    public function getTimeDayOfWeek(): string
    {
        return $this->timeDayOfWeek;
    }

    /**
     * @return string
     */
    public function getTimeDayOfMonth(): string
    {
        return $this->timeDayOfMonth;
    }

    /**
     * @return string
     */
    public function getTimeMonthName(): string
    {
        return $this->timeMonthName;
    }

    /**
     * @return string
     */
    public function getTimeMonth(): string
    {
        return $this->timeMonth;
    }

    /**
     * @return string
     */
    public function getTimeYear(): string
    {
        return $this->timeYear;
    }

    /**
     * @return string
     */
    public function getLocationSource(): string
    {
        return $this->locationSource;
    }

    /**
     * function called when encoded with json_encode
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}