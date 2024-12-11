<?php
namespace App\Background\Entities;

use Ramsey\Uuid\Uuid;

/**
 * Class QueueLog
 * @package App\Background\Entities
 */
class QueueLog extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $queueMessage;

    /**
     * @var string
     */
    private $actionIfSending;

    /**
     * @var string
     */
    private $typeOfOp;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @var string
     */
    private $endPoint;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var string
     */
    private $insertTimestamp;


    /**
     * @return object
     */
    public function __construct($queueMessage, $actionIfSending, $typeOfOp, $consumerTag, $endPoint, $queueName)
    {
        $this->id=Uuid::uuid1();
        $this->queueMessage=$queueMessage;
        $this->actionIfSending=$actionIfSending;
        $this->typeOfOp=$typeOfOp;
        $this->consumerTag=$consumerTag;
        $this->endPoint=$endPoint;
        $this->queueName=$queueName;
        $this->insertTimestamp=time();
    }

    /**
     * @return string
     */
    public function getQueueMessage(): string
    {
        return $this->queueMessage;
    }

    /**
     * @return string
     */
    public function getActionIfSending(): string
    {
        return $this->actionIfSending;
    }

    /**
     * @return string
     */
    public function getTypeOfOp(): string
    {
        return $this->typeOfOp;
    }

    /**
     * @return string
     */
    public function getConsumerTag(): string
    {
        return $this->consumerTag;
    }

    /**
     * @return string
     */
    public function getEndPoint(): string
    {
        return $this->endPoint;
    }

    /**
     * @return string
     */
    public function getQueueName(): string
    {
        return $this->queueName;
    }

    /**
     * @return string
     */
    public function getInsertTimestamp(): string
    {
        return $this->insertTimestamp;
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