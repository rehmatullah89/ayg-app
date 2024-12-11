<?php
namespace App\Tablet\Entities;

class Order extends Entity implements \JsonSerializable
{
    const STATUS_NOT_ORDERED = 1;
    const STATUS_NOT_ABANDONED = 100;
    const STATUS_ORDERED = 2;
    const STATUS_PAYMENT_ACCEPTED = 3;
    const STATUS_PUSHED_TO_RETAILER = 4;
    const STATUS_ACCEPTED_BY_RETAILER = 5;
    const STATUS_CANCELED_BY_SYSTEM = 6;

    const STATUS_CANCELED_BY_USER = 7;
    const STATUS_SCHEDULED = 8;
    const STATUS_NEEDS_REVIEW = 9;
    const STATUS_COMPLETED = 10;
    const STATUS_ACCEPTED_ON_TABLET = 12;

    const STATUS_DELIVERY_NOT_PROCESSED = 0;
    const STATUS_DELIVERY_BEING_ASSIGNED = 1;
    const STATUS_DELIVERY_ASSIGNED = 2;
    const STATUS_DELIVERY_ARRIVED_AT_RETAILER = 4;
    const STATUS_DELIVERY_PICKED_UP = 5;
    const STATUS_DELIVERY_ARRIVED_AT_CUSTOMER_PLACE = 6;
    const STATUS_DELIVERY_DELIVERED = 10;

    /**
     * @see functions_orders.php $statusNames and $statusDeliveryNames
     */
    const ORDER_STATUS_CATEGORY_RETAILER_PENDING = 100;
    const ORDER_STATUS_CATEGORY_RETAILER_ACCEPTED = 200;
    const ORDER_STATUS_CATEGORY_RETAILER_COMPLETED = 400;
    const ORDER_STATUS_CATEGORY_CANCELED = 600;
    const ORDER_STATUS_CATEGORY_OTHER = 900;

    const ORDER_STATUS_READY_FOR_DELIVERY_DISPLAY = 'Ready for Delivery';

    private $id;

    private $user;
    private $retailer;
    private $deliveryLocation;

    private $interimOrderStatus;
    private $paymentType;
    private $paymentId;
    private $submissionAttempt;
    private $orderPOSId;
    private $totalsWithFees;
    private $etaTimestamp;
    private $coupon;
    private $statusDelivery;
    private $tipPct;
    private $cancelReason;
    private $quotedFullfillmentFeeTimestamp;
    private $fullfillmentType;
    private $invoicePDFURL;
    private $orderSequenceId;
    private $totalsForRetailer;
    private $paymentTypeName;
    private $fullfillmentProcessTimeInSeconds;
    private $updatedAt;
    private $quotedFullfillmentPickupFee;
    private $status;
    private $fullfillmentFee;
    private $requestedFullFillmentTimestamp;
    private $orderPrintJobId;
    private $deliveryInstructions;
    private $quotedFullfillmentDeliveryFee;
    private $createdAt;
    private $totalsFromPOS;
    private $paymentTypeId;
    private $submitTimestamp;
    private $comment;
    private $sessionDevice;

    public function __construct(array $data)
    {
        $this->id = $data['id'];

        // relations
        if (isset($data['user'])) {
            $this->user = $data['user'];
        }
        if (isset($data['sessionDevice'])) {
            $this->sessionDevice = $data['sessionDevice'];
        }
        if (isset($data['retailer'])) {
            $this->retailer = $data['retailer'];
        }
        if (isset($data['deliveryLocation'])) {
            $this->deliveryLocation = $data['deliveryLocation'];
        }


        $this->interimOrderStatus = $data['interimOrderStatus'];
        $this->paymentType = $data['paymentType'];
        $this->paymentId = $data['paymentId'];
        $this->submissionAttempt = $data['submissionAttempt'];
        $this->orderPOSId = $data['orderPOSId'];
        $this->totalsWithFees = $data['totalsWithFees'];
        $this->etaTimestamp = $data['etaTimestamp'];
        $this->coupon = $data['coupon'];
        $this->statusDelivery = $data['statusDelivery'];
        $this->tipPct = $data['tipPct'];
        $this->cancelReason = $data['cancelReason'];
        $this->quotedFullfillmentFeeTimestamp = $data['quotedFullfillmentFeeTimestamp'];
        $this->fullfillmentType = $data['fullfillmentType'];
        $this->invoicePDFURL = $data['invoicePDFURL'];
        $this->orderSequenceId = $data['orderSequenceId'];
        $this->totalsForRetailer = $data['totalsForRetailer'];
        $this->paymentTypeName = $data['paymentTypeName'];
        $this->fullfillmentProcessTimeInSeconds = $data['fullfillmentProcessTimeInSeconds'];
        $this->updatedAt = $data['updatedAt'];
        $this->quotedFullfillmentPickupFee = $data['quotedFullfillmentPickupFee'];
        $this->status = $data['status'];
        $this->fullfillmentFee = $data['fullfillmentFee'];
        $this->requestedFullFillmentTimestamp = $data['requestedFullFillmentTimestamp'];
        $this->orderPrintJobId = $data['orderPrintJobId'];
        $this->deliveryInstructions = $data['deliveryInstructions'];
        $this->quotedFullfillmentDeliveryFee = $data['quotedFullfillmentDeliveryFee'];
        $this->createdAt = $data['createdAt'];
        $this->totalsFromPOS = $data['totalsFromPOS'];
        $this->paymentTypeId = $data['paymentTypeId'];
        $this->submitTimestamp = $data['submitTimestamp'];
        $this->comment = $data['comment'];
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSessionDevice()
    {
        return $this->sessionDevice;
    }

    /**
     * @return TerminalGateMap|null
     */
    public function getDeliveryLocation()
    {
        return $this->deliveryLocation;
    }

    /**
     * @param TerminalGateMap|null $terminalGateMap
     * @return $this
     */
    public function setDeliveryLocation($terminalGateMap)
    {
        $this->deliveryLocation = $terminalGateMap;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @return mixed
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @return mixed
     */
    public function getSubmissionAttempt()
    {
        return $this->submissionAttempt;
    }

    /**
     * @param $submissionAttempt
     * @return mixed
     */
    public function setSubmissionAttempt($submissionAttempt)
    {
        $this->submissionAttempt = $submissionAttempt;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOrderPOSId()
    {
        return $this->orderPOSId;
    }

    /**
     * @return mixed
     */
    public function getTotalsWithFees()
    {
        return $this->totalsWithFees;
    }

    /**
     * @return mixed
     */
    public function getCoupon()
    {
        return $this->coupon;
    }

    /**
     * @return mixed
     */
    public function getTipPct()
    {
        return $this->tipPct;
    }

    /**
     * @param $tipPct
     * @return Order
     */
    public function setTipPct($tipPct)
    {
        $this->tipPct = $tipPct;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCancelReason()
    {
        return $this->cancelReason;
    }

    /**
     * @return mixed
     */
    public function getQuotedFullfillmentFeeTimestamp()
    {
        return $this->quotedFullfillmentFeeTimestamp;
    }

    /**
     * @return mixed
     */
    public function getInvoicePDFURL()
    {
        return $this->invoicePDFURL;
    }

    /**
     * @return mixed
     */
    public function getOrderSequenceId()
    {
        return $this->orderSequenceId;
    }

    /**
     * @return mixed
     */
    public function getTotalsForRetailer()
    {
        return $this->totalsForRetailer;
    }

    /**
     * @return mixed
     */
    public function getPaymentTypeName()
    {
        return $this->paymentTypeName;
    }

    /**
     * @return mixed
     */
    public function getFullfillmentProcessTimeInSeconds()
    {
        return $this->fullfillmentProcessTimeInSeconds;
    }

    /**
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return mixed
     */
    public function getQuotedFullfillmentPickupFee()
    {
        return $this->quotedFullfillmentPickupFee;
    }

    /**
     * @return mixed
     */
    public function getFullfillmentFee()
    {
        return $this->fullfillmentFee;
    }

    /**
     * @return mixed
     */
    public function getRequestedFullFillmentTimestamp()
    {
        return $this->requestedFullFillmentTimestamp;
    }

    /**
     * @return mixed
     */
    public function getOrderPrintJobId()
    {
        return $this->orderPrintJobId;
    }

    /**
     * @return mixed
     */
    public function getDeliveryInstructions()
    {
        return $this->deliveryInstructions;
    }

    /**
     * @return mixed
     */
    public function getQuotedFullfillmentDeliveryFee()
    {
        return $this->quotedFullfillmentDeliveryFee;
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
    public function getTotalsFromPOS()
    {
        return $this->totalsFromPOS;
    }

    /**
     * @return mixed
     */
    public function getPaymentTypeId()
    {
        return $this->paymentTypeId;
    }

    /**
     * @return mixed
     */
    public function getSubmitTimestamp()
    {
        return $this->submitTimestamp;
    }

    /**
     * @param mixed $submitTimestamp
     * @return $this
     */
    public function setSubmitTimestamp($submitTimestamp)
    {
        $this->submitTimestamp = $submitTimestamp;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getInterimOrderStatus()
    {
        return $this->interimOrderStatus;
    }

    /**
     * @param mixed $interimOrderStatus
     * @return $this
     */
    public function setInterimOrderStatus($interimOrderStatus)
    {
        $this->interimOrderStatus = $interimOrderStatus;
        return $this;
    }

    /**
     * @return Retailer
     */
    public function getRetailer()
    {
        return $this->retailer;
    }

    /**
     * @param Retailer|null $retailer
     * @return $this
     */
    public function setRetailer($retailer)
    {
        $this->retailer = $retailer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function getHasAirportEmployeeDiscount()
    {
        if(isset(json_decode($this->getTotalsWithFees(), true)["AirEmployeeDiscount"])
            && json_decode($this->getTotalsWithFees(), true)["AirEmployeeDiscount"] > 0) {

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getAirportEmployeeDiscountPercentage()
    {
        if(isset(json_decode($this->getTotalsWithFees(), true)["AirEmployeeDiscountPercentageDisplay"])) {

            return json_decode($this->getTotalsWithFees(), true)["AirEmployeeDiscountPercentageDisplay"];
        }

        return "";
    }

    /**
     * @return bool
     */
    public function getHasMilitaryDiscount()
    {
        if(isset(json_decode($this->getTotalsWithFees(), true)["MilitaryDiscount"])
            && json_decode($this->getTotalsWithFees(), true)["MilitaryDiscount"] > 0) {

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getMilitaryDiscountPercentage()
    {
        if(isset(json_decode($this->getTotalsWithFees(), true)["MilitaryDiscountPercentageDisplay"])) {

            return json_decode($this->getTotalsWithFees(), true)["MilitaryDiscountPercentageDisplay"];
        }

        return "";
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return mixed
     */
    public function getEtaTimestamp()
    {
        return $this->etaTimestamp;
    }

    /**
     * @return mixed
     */
    public function getStatusDelivery()
    {
        return $this->statusDelivery;
    }

    /**
     * @return mixed
     */
    public function getFullfillmentType()
    {
        return $this->fullfillmentType;
    }

    /**
     * @param mixed $fullfillmentType
     * @return $this
     */
    public function setFullfillmentType($fullfillmentType)
    {
        $this->fullfillmentType = $fullfillmentType;
        return $this;
    }

    /**
     * @param mixed $coupon
     * @return Order
     */
    public function setCoupon($coupon)
    {
        $this->coupon = $coupon;
        return $this;
    }

    /**
     * @param mixed $fullfillmentProcessTimeInSeconds
     * @return $this
     */
    public function setFullfillmentProcessTimeInSeconds($fullfillmentProcessTimeInSeconds)
    {
        $this->fullfillmentProcessTimeInSeconds = $fullfillmentProcessTimeInSeconds;
        return $this;
    }

    /**
     * @param mixed $fullfillmentFee
     * @return $this
     */
    public function setFullfillmentFee($fullfillmentFee)
    {
        $this->fullfillmentFee = $fullfillmentFee;
        return $this;
    }

    /**
     * @param mixed $deliveryInstructions
     * @return $this
     */
    public function setDeliveryInstructions($deliveryInstructions)
    {
        $this->deliveryInstructions = $deliveryInstructions;
        return $this;
    }

    /**
     * @param mixed $statusDelivery
     */
    public function setStatusDelivery($statusDelivery)
    {
        $this->statusDelivery = $statusDelivery;
    }

    /**
     * @param mixed $requestedFullFillmentTimestamp
     */
    public function setRequestedFullFillmentTimestamp($requestedFullFillmentTimestamp)
    {
        $this->requestedFullFillmentTimestamp = $requestedFullFillmentTimestamp;
    }

    /**
     * @param mixed $sessionDevice
     */
    public function setSessionDevice($sessionDevice)
    {
        $this->sessionDevice = $sessionDevice;
    }

    /**
     * @param mixed $totalsWithFees
     * @return $this
     */
    public function setTotalsWithFees($totalsWithFees)
    {
        $this->totalsWithFees = $totalsWithFees;
        return $this;
    }

    /**
     * @param mixed $totalsForRetailer
     * @return $this
     */
    public function setTotalsForRetailer($totalsForRetailer)
    {
        $this->totalsForRetailer = $totalsForRetailer;
        return $this;
    }

    /**
     * @param mixed $paymentType
     * @return $this
     */
    public function setPaymentType($paymentType)
    {
        $this->paymentType = $paymentType;
        return $this;
    }

    /**
     * @param mixed $paymentId
     * @return $this
     */
    public function setPaymentId($paymentId)
    {
        $this->paymentId = $paymentId;
        return $this;
    }

    /**
     * @param mixed $paymentTypeName
     * @return $this
     */
    public function setPaymentTypeName($paymentTypeName)
    {
        $this->paymentTypeName = $paymentTypeName;
        return $this;
    }

    /**
     * @param mixed $paymentTypeId
     * @return $this
     */
    public function setPaymentTypeId($paymentTypeId)
    {
        $this->paymentTypeId = $paymentTypeId;
        return $this;
    }

    /**
     * @param mixed $etaTimestamp
     * @return $this
     */
    public function setEtaTimestamp($etaTimestamp)
    {
        $this->etaTimestamp = $etaTimestamp;
        return $this;
    }


}