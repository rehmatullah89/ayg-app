<?php
namespace App\Delivery\Entities;

class OrderShortInfo extends Entity implements \JsonSerializable
{


    /**
     * @var
     */
    private $orderId;
    /**
     * @var int
     */
    private $orderSequenceId;
    /**
     * @var OrderDeliveryStatus
     */
    private $orderDeliveryStatus;
    /**
     * @var \DateTime
     */
    private $pickupBy;
    /**
     * @var \DateTime
     */
    private $deliveryBy;
    /**
     * @var RetailerShortInfo
     */
    private $retailer;
    /**
     * @var TerminalGateMapShortInfo
     */
    private $deliveryLocation;
    /**
     * @var UserShortInfo
     */
    private $customer;
    /**
     * @var UserShortInfo|null
     */
    private $runner;

    public function __construct(
        $orderId,
        int $orderSequenceId,
        OrderDeliveryStatus $orderDeliveryStatus,
        \DateTime $pickupBy,
        \DateTime $deliveryBy,
        RetailerShortInfo $retailer,
        TerminalGateMapShortInfo $deliveryLocation,
        UserShortInfo $customer,
        ?UserShortInfo $runner
    ) {
        $this->orderId = $orderId;
        $this->orderSequenceId = $orderSequenceId;
        $this->orderDeliveryStatus = $orderDeliveryStatus;
        $this->pickupBy = $pickupBy;
        $this->deliveryBy = $deliveryBy;
        $this->retailer = $retailer;
        $this->deliveryLocation = $deliveryLocation;
        $this->customer = $customer;
        $this->runner = $runner;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getOrderSequenceId(): int
    {
        return $this->orderSequenceId;
    }

    /**
     * @return OrderDeliveryStatus
     */
    public function getOrderDeliveryStatus(): OrderDeliveryStatus
    {
        return $this->orderDeliveryStatus;
    }

    /**
     * @return \DateTime
     */
    public function getPickupBy(): \DateTime
    {
        return $this->pickupBy;
    }

    /**
     * @return \DateTime
     */
    public function getDeliveryBy(): \DateTime
    {
        return $this->deliveryBy;
    }

    /**
     * @return RetailerShortInfo
     */
    public function getRetailer(): RetailerShortInfo
    {
        return $this->retailer;
    }

    /**
     * @return TerminalGateMapShortInfo
     */
    public function getDeliveryLocation(): TerminalGateMapShortInfo
    {
        return $this->deliveryLocation;
    }

    /**
     * @return UserShortInfo
     */
    public function getCustomer(): UserShortInfo
    {
        return $this->customer;
    }

    /**
     * @return UserShortInfo|null
     */
    public function getRunner()
    {
        return $this->runner;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
