<?php

namespace App\Delivery\Entities;


class UserPhone implements \JsonSerializable
{

    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $userId;
    /**
     * @var string
     */
    private $phoneNumberFormatted;
    /**
     * @var string
     */
    private $phoneNumber;
    /**
     * @var string
     */
    private $phoneCountryCode;
    /**
     * @var bool
     */
    private $phoneVerified;
    /**
     * @var string
     */
    private $phoneCarrier;
    /**
     * @var bool
     */
    private $SMSNotificationsEnabled;
    /**
     * @var \DateTime
     */
    private $startTimestamp;
    /**
     * @var \DateTime
     */
    private $endTimestamp;
    /**
     * @var bool
     */
    private $isActive;
    /**
     * @var \DateTime
     */
    private $createdAt;
    /**
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * UserPhone constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data["id"];
        $this->createdAt = $data["createdAt"];
        $this->updatedAt = $data["updatedAt"];
        $this->userId = $data["userId"];
        $this->phoneNumberFormatted = $data["phoneNumberFormatted"];
        $this->phoneNumber = $data["phoneNumber"];
        $this->phoneCountryCode = $data["phoneCountryCode"];
        $this->phoneVerified = $data["phoneVerified"];
        $this->phoneCarrier = $data["phoneCarrier"];
        $this->SMSNotificationsEnabled = $data["SMSNotificationsEnabled"];
        $this->startTimestamp = $data["startTimestamp"];
        $this->endTimestamp = $data["endTimestamp"];
        $this->isActive = $data["isActive"];
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string
     */
    public function getPhoneNumberFormatted()
    {
        return $this->phoneNumberFormatted;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @return string
     */
    public function getPhoneCountryCode()
    {
        return $this->phoneCountryCode;
    }

    /**
     * @return bool
     */
    public function isPhoneVerified()
    {
        return $this->phoneVerified;
    }

    /**
     * @return self
     */
    public function setPhoneAsVerified(): self
    {
        $this->phoneVerified = true;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhoneCarrier()
    {
        return $this->phoneCarrier;
    }

    /**
     * @return bool
     */
    public function isSMSNotificationsEnabled()
    {
        return $this->SMSNotificationsEnabled;
    }

    /**
     * @return \DateTime
     */
    public function getStartTimestamp()
    {
        return $this->startTimestamp;
    }

    /**
     * @return \DateTime
     */
    public function getEndTimestamp()
    {
        return $this->endTimestamp;
    }

    /**
     * @return bool
     */
    public function isIsActive()
    {
        return $this->isActive;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
