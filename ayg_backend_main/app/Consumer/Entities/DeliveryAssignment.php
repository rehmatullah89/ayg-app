<?php
namespace App\Consumer\Entities;

/**
 * Class DeliveryAssignment
 * @package App\Consumer\Entities
 */
class DeliveryAssignment extends Entity implements \JsonSerializable
{
    const STATUS_COMPLETED = 10;

    /**
     * @var string
     */
    private $id;

    /**
     * @var Order|null
     */
    private $order;

    /**
     * @var int
     */
    private $statusCode;

    /**
     * @var int
     */
    private $earnings;

    /**
     * @var int
     */
    private $assignmentTimestamp;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var User
     */
    private $delivery;


    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->order = $data['order'];
        $this->statusCode = $data['statusCode'];
        $this->earnings = $data['earnings'];
        $this->assignmentTimestamp = $data['assignmentTimestamp'];
        $this->isActive = $data['isActive'];
        $this->delivery = $data['delivery'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Order|null
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return int
     */
    public function getEarnings()
    {
        return $this->earnings;
    }

    /**
     * @return int
     */
    public function getAssignmentTimestamp()
    {
        return $this->assignmentTimestamp;
    }

    /**
     * @return bool
     */
    public function isIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return User
     */
    public function getDelivery()
    {
        return $this->delivery;
    }

    /**
     * @param User $delivery
     */
    public function setDelivery($delivery)
    {
        $this->delivery = $delivery;
    }

    /**
     * @param Order|null $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }




    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}