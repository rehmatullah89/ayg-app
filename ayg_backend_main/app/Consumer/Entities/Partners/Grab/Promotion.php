<?php

namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;
use App\Consumer\Exceptions\Partners\PromotionIsNotValidException;
use JsonSerializable;

class Promotion extends Entity implements JsonSerializable
{
    const PERCENTAGE_TYPE_CODE = 'PERCENTAGE';
    /**
     * @var
     */
    private $firstOrderOnly;
    /**
     * @var
     */
    private $fundingType;
    /**
     * @var
     */
    private $isEmployeeDiscountPromotion;
    /**
     * @var
     */
    private $maxPromotionValue;
    /**
     * @var
     */
    private $partnerCode;
    /**
     * @var
     */
    private $promotionCode;
    /**
     * @var
     */
    private $promotionConfirmMsg;
    /**
     * @var
     */
    private $promotionConfirmMsgImage;
    /**
     * @var
     */
    private $promotionConfirmMsgTitle;
    /**
     * @var
     */
    private $promotionDescription;
    /**
     * @var
     */
    private $promotionEmail;
    /**
     * @var
     */
    private $promotionEndDate;
    /**
     * @var
     */
    private $promotionID;
    /**
     * @var
     */
    private $promotionMaxValue;
    /**
     * @var
     */
    private $promotionOwner;
    /**
     * @var
     */
    private $promotionStartDate;
    /**
     * @var
     */
    private $promotionTypeCode;
    /**
     * @var
     */
    private $promotionTypeDescription;
    /**
     * @var
     */
    private $promotionValue;
    /**
     * @var
     */
    private $registrationDate;
    /**
     * @var
     */
    private $storeWaypointID;

    public function __construct(
        $firstOrderOnly,
        $fundingType,
        $isEmployeeDiscountPromotion,
        $maxPromotionValue,
        $partnerCode,
        $promotionCode,
        $promotionConfirmMsg,
        $promotionConfirmMsgImage,
        $promotionConfirmMsgTitle,
        $promotionDescription,
        $promotionEmail,
        $promotionEndDate,
        $promotionID,
        $promotionMaxValue,
        $promotionOwner,
        $promotionStartDate,
        $promotionTypeCode,
        $promotionTypeDescription,
        $promotionValue,
        $registrationDate,
        $storeWaypointID
    ) {
        $this->firstOrderOnly = $firstOrderOnly;
        $this->fundingType = $fundingType;
        $this->isEmployeeDiscountPromotion = $isEmployeeDiscountPromotion;
        $this->maxPromotionValue = $maxPromotionValue;
        $this->partnerCode = $partnerCode;
        $this->promotionCode = $promotionCode;
        $this->promotionConfirmMsg = $promotionConfirmMsg;
        $this->promotionConfirmMsgImage = $promotionConfirmMsgImage;
        $this->promotionConfirmMsgTitle = $promotionConfirmMsgTitle;
        $this->promotionDescription = $promotionDescription;
        $this->promotionEmail = $promotionEmail;
        $this->promotionEndDate = $promotionEndDate;
        $this->promotionID = $promotionID;
        $this->promotionMaxValue = $promotionMaxValue;
        $this->promotionOwner = $promotionOwner;
        $this->promotionStartDate = $promotionStartDate;
        $this->promotionTypeCode = $promotionTypeCode;
        $this->promotionTypeDescription = $promotionTypeDescription;
        $this->promotionValue = $promotionValue;
        $this->registrationDate = $registrationDate;
        $this->storeWaypointID = $storeWaypointID;
    }

    public static function createFromValidatePromotionJson(string $json)
    {
        $json = json_decode($json, true);

        if (isset($json['exception']) && $json['exception']!='') {
            throw new PromotionIsNotValidException(
                'Grab promotion is not valid',
                0,
                null,
                json_encode($json));
        }

        $json = $json['promotions'][0];

        return new Promotion(
            $json['firstOrderOnly'],
            $json['fundingType'],
            $json['isEmployeeDiscountPromotion'],
            $json['maxPromotionValue'],
            $json['partnerCode'],
            $json['promotionCode'],
            $json['promotionConfirmMsg'],
            $json['promotionConfirmMsgImage'],
            $json['promotionConfirmMsgTitle'],
            $json['promotionDescription'],
            $json['promotionEmail'],
            $json['promotionEndDate'],
            $json['promotionID'],
            $json['promotionMaxValue'],
            $json['promotionOwner'],
            $json['promotionStartDate'],
            $json['promotionTypeCode'],
            $json['promotionTypeDescription'],
            $json['promotionValue'],
            $json['registrationDate'],
            $json['storeWaypointID']
        );
    }

    /**
     * @return mixed
     */
    public function getFirstOrderOnly()
    {
        return $this->firstOrderOnly;
    }

    /**
     * @return mixed
     */
    public function getFundingType()
    {
        return $this->fundingType;
    }

    /**
     * @return mixed
     */
    public function getIsEmployeeDiscountPromotion()
    {
        return $this->isEmployeeDiscountPromotion;
    }

    /**
     * @return mixed
     */
    public function getMaxPromotionValue()
    {
        return $this->maxPromotionValue;
    }

    /**
     * @return mixed
     */
    public function getPartnerCode()
    {
        return $this->partnerCode;
    }

    /**
     * @return mixed
     */
    public function getPromotionCode()
    {
        return $this->promotionCode;
    }

    /**
     * @return mixed
     */
    public function getPromotionConfirmMsg()
    {
        return $this->promotionConfirmMsg;
    }

    /**
     * @return mixed
     */
    public function getPromotionConfirmMsgImage()
    {
        return $this->promotionConfirmMsgImage;
    }

    /**
     * @return mixed
     */
    public function getPromotionConfirmMsgTitle()
    {
        return $this->promotionConfirmMsgTitle;
    }

    /**
     * @return mixed
     */
    public function getPromotionDescription()
    {
        return $this->promotionDescription;
    }

    /**
     * @return mixed
     */
    public function getPromotionEmail()
    {
        return $this->promotionEmail;
    }

    /**
     * @return mixed
     */
    public function getPromotionEndDate()
    {
        return $this->promotionEndDate;
    }

    /**
     * @return mixed
     */
    public function getPromotionID()
    {
        return $this->promotionID;
    }

    /**
     * @return mixed
     */
    public function getPromotionMaxValue()
    {
        return $this->promotionMaxValue;
    }

    /**
     * @return mixed
     */
    public function getPromotionOwner()
    {
        return $this->promotionOwner;
    }

    /**
     * @return mixed
     */
    public function getPromotionStartDate()
    {
        return $this->promotionStartDate;
    }

    /**
     * @return mixed
     */
    public function getPromotionTypeCode()
    {
        return $this->promotionTypeCode;
    }

    /**
     * @return mixed
     */
    public function getPromotionTypeDescription()
    {
        return $this->promotionTypeDescription;
    }

    /**
     * @return mixed
     */
    public function getPromotionValue()
    {
        return $this->promotionValue;
    }

    /**
     * @return mixed
     */
    public function getRegistrationDate()
    {
        return $this->registrationDate;
    }

    /**
     * @return mixed
     */
    public function getStoreWaypointID()
    {
        return $this->storeWaypointID;
    }

    public function isPercentage(): bool
    {
        if ($this->getPromotionTypeCode() == self::PERCENTAGE_TYPE_CODE) {
            return true;
        }
        return false;
    }

    public function getPercentage(): ?int
    {
        if (!$this->isPercentage()) {
            return null;
        }
        return $this->getPromotionValue()*100;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
