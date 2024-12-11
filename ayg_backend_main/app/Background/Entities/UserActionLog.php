<?php
namespace App\Background\Entities;

use Ramsey\Uuid\Uuid;

/**
 * Class UserActionLog
 * @package App\Background\Entities
 */
class UserActionLog extends Entity implements \JsonSerializable
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
    private  $action;

    /**
     * @var string
     */
    private  $data;

    /**
     * @var int
     */
    private  $timestamp;

    /**
     * @var string
     */
    private $timeTimezoneShort;

    /**
     * @var int
     */
    private $timeSecondOfHour;

    /**
     * @var int
     */
    private $timeMinuteOfHour;

    /**
     * @var int
     */
    private $timeHourOfDay;

    /**
     * @var int
     */
    private $timeDayOfWeekName;

    /**
     * @var int
     */
    private $timeDayOfWeek;

    /**
     * @var int
     */
    private $timeDayOfMonth;

    /**
     * @var string
     */
    private $timeMonthName;

    /**
     * @var int
     */
    private $timeMonth;

    /**
     * @var int
     */
    private $timeYear;

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
    private $locationSource;

    /**
     * @return object
     */
    public function __construct($objectId, $action, $data, $timestamp, $timeTimezoneShort, $timeSecondOfHour, $timeMinuteOfHour, $timeHourOfDay, $timeDayOfWeekName, $timeDayOfWeek, $timeDayOfMonth, $timeMonthName, $timeMonth, $timeYear, $state,$country,$nearAirportIataCode,$locationSource)
    {
        $this->id=Uuid::uuid1();
        $this->objectId=$objectId;
        $this->action=$action;
        $this->data=$data;
        $this->timestamp=$timestamp;
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
        $this->state=$state;
        $this->country=$country;
        $this->nearAirportIataCode=$nearAirportIataCode;
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
    public function getAction(): int
    {
        return $this->action;
    }

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
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
    public function getTimeTimezoneShort(): string
    {
        return $this->timeTimezoneShort;
    }

    /**
     * @return int
     */
    public function getTimeSecondOfHour(): int
    {
        return $this->timeSecondOfHour;
    }

    /**
     * @return int
     */
    public function getTimeMinuteOfHour(): int
    {
        return $this->timeMinuteOfHour;
    }

    /**
     * @return int
     */
    public function getTimeHourOfDay(): int
    {
        return $this->timeHourOfDay;
    }

    /**
     * @return int
     */
    public function getTimeDayOfWeekName(): int
    {
        return $this->timeDayOfWeekName;
    }

    /**
     * @return int
     */
    public function getTimeDayOfWeek(): int
    {
        return $this->timeDayOfWeek;
    }

    /**
     * @return int
     */
    public function getTimeDayOfMonth(): int
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
     * @return int
     */
    public function getTimeMonth(): int
    {
        return $this->timeMonth;
    }

    /**
     * @return int
     */
    public function getTimeYear(): int
    {
        return $this->timeYear;
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