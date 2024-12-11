<?php
namespace App\Tablet\Entities;

class OrderShortInfo extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $orderId;

    /**
     * @var int
     */
    private $orderSequenceId;

    /**
     * @var int
     */
    private $orderStatusCode;

    /**
     * @var string
     */
    private $orderStatusDisplay;

    /**
     * @var int
     */
    private $orderStatusCategoryCode;

    /**
     * @var string
     */
    private $orderType;

    /**
     * @var string
     */
    private $orderDateAndTime;

    /**
     * @var string
     */
    private $retailerId;

    /**
     * @var string
     */
    private $retailerName;

    /**
     * @var string
     */
    private $retailerLocation;

    /**
     * @var string
     */
    private $consumerName;

    /**
     * @var string
     */
    private $mustPickupBy;

    /**
     * @var int
     */
    private $numberOfItems;

    /**
     * @var OrderModifierShortInfo[]
     */
    private $items;

    /**
     * @var boolean
     */
    private $helpRequestPending;
    /**
     * @var string
     */

    /**
     * OrderShortInfo constructor.
     * @param $orderId
     * @param $orderSequenceId
     * @param $orderStatusCode
     * @param $orderStatusDisplay
     * @param $orderStatusCategoryCode
     * @param $orderType
     * @param $orderDateAndTime
     * @param $retailerId
     * @param $retailerName
     * @param $retailerLocation
     * @param $consumerName
     * @param $mustPickupBy
     * @param $discounts
     * @param $numberOfItems
     * @param $items
     * @param $helpRequestPending
     */
    public function __construct(
        $orderId,
        $orderSequenceId,
        $orderStatusCode,
        $orderStatusDisplay,
        $orderStatusCategoryCode,
        $orderType,
        $orderDateAndTime,
        $retailerId,
        $retailerName,
        $retailerLocation,
        $consumerName,
        $mustPickupBy,
        $discounts,
        $numberOfItems,
        $items,
        $helpRequestPending
    )
    {
        $this->orderId = $orderId;
        $this->orderSequenceId = $orderSequenceId;
        $this->orderStatusCode = $orderStatusCode;
        $this->orderStatusDisplay = $orderStatusDisplay;
        $this->orderStatusCategoryCode = $orderStatusCategoryCode;
        $this->orderType = $orderType;
        $this->orderDateAndTime = $orderDateAndTime;
        $this->retailerId = $retailerId;
        $this->retailerName = $retailerName;
        $this->retailerLocation = $retailerLocation;
        $this->consumerName = $consumerName;
        $this->mustPickupBy = $mustPickupBy;
        $this->discounts = $discounts;
        $this->numberOfItems = $numberOfItems;
        $this->items = $items;
        $this->helpRequestPending = $helpRequestPending;
    }

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getOrderSequenceId()
    {
        return $this->orderSequenceId;
    }

    /**
     * @return int
     */
    public function getOrderStatusCode()
    {
        return $this->orderStatusCode;
    }

    /**
     * @return string
     */
    public function getOrderStatusDisplay()
    {
        return $this->orderStatusDisplay;
    }

    /**
     * @return int
     */
    public function getOrderStatusCategoryCode()
    {
        return $this->orderStatusCategoryCode;
    }

    /**
     * @return string
     */
    public function getOrderType()
    {
        return $this->orderType;
    }

    /**
     * @return string
     */
    public function getOrderDateAndTime()
    {
        return $this->orderDateAndTime;
    }

    /**
     * @return string
     */
    public function getRetailerId()
    {
        return $this->retailerId;
    }

    /**
     * @return string
     */
    public function getRetailerName()
    {
        return $this->retailerName;
    }

    /**
     * @return string
     */
    public function getRetailerLocation()
    {
        return $this->retailerLocation;
    }

    /**
     * @return string
     */
    public function getConsumerName()
    {
        return $this->consumerName;
    }

    /**
     * @return string
     */
    public function getMustPickupBy()
    {
        return $this->mustPickupBy;
    }

    /**
     * @return int
     */
    public function getNumberOfItems()
    {
        return $this->numberOfItems;
    }

    /**
     * @return OrderModifierShortInfo[]
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return bool
     */
    public function isHelpRequestPending()
    {
        return $this->helpRequestPending;
    }



    /**
     * @param mixed $numberOfItems
     */
    public function setNumberOfItems($numberOfItems)
    {
        $this->numberOfItems = $numberOfItems;
    }

    /**
     * @param mixed $items
     */
    public function setItems($items)
    {
        $this->items = $items;
    }
    /**
     * @param bool $helpRequestPending
     */
    public function setHelpRequestPending($helpRequestPending)
    {
        $this->helpRequestPending = $helpRequestPending;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}