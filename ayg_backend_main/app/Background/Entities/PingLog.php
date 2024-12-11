<?php
namespace App\Background\Entities;

use Ramsey\Uuid\Uuid;

/**
 * Class PingLog
 * @package App\Background\Entities
 */
class PingLog extends Entity implements \JsonSerializable
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
    private $objectType;

    /**
     * @var string
     */
    private $action;

    public function __construct($objectId, $timestamp, $objectType, $action)
    {
        $this->id=Uuid::uuid1();
        $this->objectId=$objectId;
        $this->timestamp=$timestamp;
        $this->objectType=$objectType;
        $this->action=$action;
    }

    /**
     * @return string
     */
    public function getRetailerUniqueId()
    {
        return $this->retailerUniqueId;
    }

    /**
     * @return int
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
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