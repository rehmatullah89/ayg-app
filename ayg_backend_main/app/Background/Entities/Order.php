<?php

namespace App\Background\Entities;

class Order extends Entity implements \JsonSerializable
{
    const STATUS_NOT_ORDERED = 1;
    const STATUS_NOT_ABANDONED = 100;
    const STATUS_ORDERED = 2;
    const STATUS_PAYMENT_ACCEPTED = 3;
    const STATUS_PUSHED_TO_RETAILER = 4;
    const STATUS_ACCEPTED_BY_RETAILER = 5;

    const STATUS_CANCELED_BY_SYSTEM = 6;
    const COMMENT_CART_DELETED_DUE_TO_VERIFICATION_BY_OTHER_USER = 'Canceled: User Signed by Phone Number';
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
    const STATUS_DELIVERY_CANCELED_BY_DELIVERY_PERSON = 6;
    const STATUS_DELIVERY_BEING_REASIGNED = 7;
    const STATUS_DELIVERY_DELIVERED = 10;

    const STATUS_DELIVERY_LIST_FOR_INFORM_USER = [
        self::STATUS_DELIVERY_PICKED_UP,
        self::STATUS_DELIVERY_DELIVERED,
    ];

    const STATUSES_LIST_FOR_INTERNAL = [
        self::STATUS_NOT_ABANDONED,
    ];

    const STATUSES_LIST_CART = [
        self::STATUS_NOT_ORDERED,
    ];

    const STATUSES_LIST_SUCCESS_COMPLETED = [
        self::STATUS_COMPLETED,
    ];

    const STATUSES_LIST_CANCELED = [
        self::STATUS_CANCELED_BY_SYSTEM,
        self::STATUS_CANCELED_BY_USER,
    ];

    const STATUSES_LIST_NON_INTERNAL_NON_CART = [
        self::STATUS_ORDERED,
        self::STATUS_PAYMENT_ACCEPTED,
        self::STATUS_PUSHED_TO_RETAILER,
        self::STATUS_ACCEPTED_BY_RETAILER,
        self::STATUS_ACCEPTED_ON_TABLET,
        self::STATUS_CANCELED_BY_SYSTEM,
        self::STATUS_CANCELED_BY_USER,
        self::STATUS_SCHEDULED,
        self::STATUS_NEEDS_REVIEW,
        self::STATUS_COMPLETED,
    ];

    // note that we should add completed in one hour as active
    const STATUSES_LIST_ACTIVE = [
        self::STATUS_ORDERED,
        self::STATUS_PAYMENT_ACCEPTED,
        self::STATUS_PUSHED_TO_RETAILER,
        self::STATUS_ACCEPTED_BY_RETAILER,
        self::STATUS_ACCEPTED_ON_TABLET,
        self::STATUS_SCHEDULED,
        self::STATUS_NEEDS_REVIEW,
    ];

    const STATUSES_LIST_PENDING_IN_PROGRESS = [
        self::STATUS_DELIVERY_ASSIGNED,
        self::STATUS_PAYMENT_ACCEPTED,
        self::STATUS_PUSHED_TO_RETAILER,
        self::STATUS_ACCEPTED_BY_RETAILER,
        self::STATUS_ACCEPTED_ON_TABLET,
        self::STATUS_SCHEDULED,
        self::STATUS_NEEDS_REVIEW,
    ];

    const STATUS_LIST_INFORM_USER = [
        self::STATUS_DELIVERY_ASSIGNED,
        self::STATUS_ACCEPTED_BY_RETAILER,
        self::STATUS_ACCEPTED_ON_TABLET,
        self::STATUS_CANCELED_BY_SYSTEM,
        self::STATUS_CANCELED_BY_USER,
        self::STATUS_SCHEDULED,
        self::STATUS_NEEDS_REVIEW,
        self::STATUS_COMPLETED,
    ];

    const STATUS_LIST_CONSOLIDATE_MULTIPLE_STATUS_REPORTS = [
        self::STATUS_DELIVERY_DELIVERED
    ];

    const STATUS_LIST_NOT_FULLFILLED = [
        self::STATUS_CANCELED_BY_SYSTEM,
        self::STATUS_DELIVERY_BEING_REASIGNED,
    ];

    const STATUS_CATEGORY_CODE = [
        self::STATUS_ORDERED => 100,
        self::STATUS_PAYMENT_ACCEPTED => 100,
        self::STATUS_PUSHED_TO_RETAILER => 200,
        self::STATUS_ACCEPTED_BY_RETAILER => 200,
        self::STATUS_ACCEPTED_ON_TABLET => 200,
        self::STATUS_CANCELED_BY_SYSTEM => 600,
        self::STATUS_CANCELED_BY_USER => 600,
        self::STATUS_SCHEDULED => 700,
        self::STATUS_NEEDS_REVIEW => 500,
        self::STATUS_COMPLETED => 400,
        self::STATUS_NOT_ORDERED => 0,
        self::STATUS_NOT_ABANDONED => 600,
    ];

    const STATUS_DELIVERY_CATEGORY_CODE = [
        self::STATUS_DELIVERY_NOT_PROCESSED => null,
        self::STATUS_DELIVERY_BEING_ASSIGNED => 200,
        self::STATUS_DELIVERY_ASSIGNED => 200,
        self::STATUS_DELIVERY_ARRIVED_AT_RETAILER => 200,
        self::STATUS_DELIVERY_PICKED_UP => 300,
        self::STATUS_DELIVERY_CANCELED_BY_DELIVERY_PERSON => 200,
        self::STATUS_DELIVERY_BEING_REASIGNED => 200,
        self::STATUS_DELIVERY_DELIVERED => 400,
    ];

    const TIP_APPLIED_AS_DEFAULT = 'default';
    const TIP_APPLIED_AS_PERCENTAGE = 'percentage';
    const TIP_APPLIED_AS_FIXED_VALUE = 'fixed_value';

    const TIP_APPLIED_AS_OPTIONS = [
        self::TIP_APPLIED_AS_DEFAULT,
        self::TIP_APPLIED_AS_PERCENTAGE,
        self::TIP_APPLIED_AS_FIXED_VALUE,
    ];

    const SCHEDULED_ORDER_POSSIBLE_DAYS_AMOUNT = 7;
    const SCHEDULED_ORDER_TIME_RANGE_IN_MINUTES = 15;
    const SCHEDULED_ORDER_TODAY_STRING = 'Today';
    const SCHEDULED_ORDER_TOMORROW_STRING = 'Tomorrow';


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
    private $tipCents;
    private $tipAppliedAs;
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
    private $partnerName;
    private $partnerOrderId;

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
        $this->tipCents = $data['tipCents'];
        $this->tipAppliedAs = $data['tipAppliedAs'];
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
        $this->partnerName = $data['partnerName'];
        $this->partnerOrderId = $data['partnerOrderId'];
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
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
     * @return mixed
     */
    public function getTipCents()
    {
        return $this->tipCents;
    }

    /**
     * @return mixed
     */
    public function getTipAppliedAs()
    {
        return $this->tipAppliedAs;
    }

    public function setTipAsPercentage(int $tipPct): self
    {
        $this->tipPct = $tipPct;
        $this->tipAppliedAs = Order::TIP_APPLIED_AS_PERCENTAGE;
        return $this;
    }

    public function setTipAsFixedValue(int $tipInCents): self
    {
        $this->tipCents = $tipInCents;
        $this->tipAppliedAs = Order::TIP_APPLIED_AS_FIXED_VALUE;
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
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
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

    /**
     * @return mixed
     */
    public function getPartnerName()
    {
        return $this->partnerName;
    }

    /**
     * @return mixed
     */
    public function getPartnerOrderId()
    {
        return $this->partnerOrderId;
    }


}
