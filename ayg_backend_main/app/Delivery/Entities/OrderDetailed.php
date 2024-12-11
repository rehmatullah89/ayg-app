<?php
namespace App\Delivery\Entities;

class OrderDetailed extends Entity implements \JsonSerializable
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
     * @var OrderDeliveryStatus
     */
    private $nextOrderDeliveryStatus;
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
    /**
     * @var UserContact|null
     */
    private $customerContact;
    /**
     * @var ItemList|null
     */
    private $itemList;
    /**
     * @var string
     */
    private $deliveryInstructions;
    /**
     * @var OrderCommentList|null
     */
    private $orderComments;

    public function __construct(
        $orderId,
        int $orderSequenceId,
        OrderDeliveryStatus $orderDeliveryStatus,
        OrderDeliveryStatus $nextOrderDeliveryStatus,
        \DateTime $pickupBy,
        \DateTime $deliveryBy,
        RetailerShortInfo $retailer,
        TerminalGateMapShortInfo $deliveryLocation,
        UserShortInfo $customer,
        ?UserContact $userContact,
        ?UserShortInfo $runner,
        ?ItemList $itemList,
        string $deliveryInstructions,
        ?OrderCommentList $orderComments
    ) {
        $this->orderId = $orderId;
        $this->orderSequenceId = $orderSequenceId;
        $this->orderDeliveryStatus = $orderDeliveryStatus;
        $this->nextOrderDeliveryStatus = $nextOrderDeliveryStatus;
        $this->pickupBy = $pickupBy;
        $this->deliveryBy = $deliveryBy;
        $this->retailer = $retailer;
        $this->deliveryLocation = $deliveryLocation;
        $this->customer = $customer;
        $this->customerContact = $userContact;
        $this->runner = $runner;
        $this->itemList = $itemList;
        $this->deliveryInstructions = $deliveryInstructions;
        $this->orderComments = $orderComments;
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

    /**
     * @return UserContact|null
     */
    public function getCustomerContact(): ?UserContact
    {
        return $this->customerContact;
    }

    /**
     * @param UserContact|null $customerContact
     */
    public function setCustomerContact(?UserContact $customerContact)
    {
        $this->customerContact = $customerContact;
    }

    /**
     * @return OrderDeliveryStatus
     */
    public function getNextOrderDeliveryStatus(): OrderDeliveryStatus
    {
        return $this->nextOrderDeliveryStatus;
    }

    /**
     * @return ItemList|null
     */
    public function getItemList()
    {
        return $this->itemList;
    }

    /**
     * @param ItemList|null $itemList
     */
    public function setItemList($itemList)
    {
        $this->itemList = $itemList;
    }

    /**
     * @return OrderCommentList|null
     */
    public function getOrderComments():?OrderCommentList
    {
        return $this->orderComments;
    }

    /**
     * @param OrderCommentList|null $orderComments
     */
    public function setOrderComments(?OrderCommentList $orderComments)
    {
        $this->orderComments = $orderComments;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
