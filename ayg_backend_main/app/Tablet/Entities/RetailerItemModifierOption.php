<?php
namespace App\Tablet\Entities;

class RetailerItemModifierOption implements \JsonSerializable
{
    private $id;
    private $optionPOSName;
    private $optionDisplayDescription;
    private $pricePerUnit;
    private $optionDisplayName;
    private $quantity;
    private $uniqueRetailerItemModifierId;
    private $optionId;
    private $priceLevelId;
    private $isActive;
    private $uniqueId;
    private $updatedAt;
    private $createdAt;

    public function __construct($data)
    {
        $this->id = $data['id'];
        $this->createdAt = $data['id'];
        $this->updatedAt = $data['id'];
        $this->optionPOSName = $data['optionPOSName'];
        $this->optionDisplayDescription = $data['optionDisplayDescription'];
        $this->pricePerUnit = $data['pricePerUnit'];
        $this->optionDisplayName = $data['optionDisplayName'];
        $this->quantity = $data['quantity'];
        $this->optionId = $data['optionId'];
        $this->uniqueRetailerItemModifierId = $data['uniqueRetailerItemModifierId'];
        $this->uniqueId = $data['uniqueId'];
        $this->priceLevelId = $data['priceLevelId'];
        $this->isActive = $data['isActive'];
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
    public function getOptionId()
    {
        return $this->optionId;
    }

    /**
     * @return mixed
     */
    public function getPriceLevelId()
    {
        return $this->priceLevelId;
    }

    /**
     * @return mixed
     */
    public function getUniqueRetailerItemModifierId()
    {
        return $this->uniqueRetailerItemModifierId;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getOptionPOSName()
    {
        return $this->optionPOSName;
    }

    /**
     * @return mixed
     */
    public function getOptionDisplayDescription()
    {
        return $this->optionDisplayDescription;
    }

    /**
     * @return mixed
     */
    public function getPricePerUnit()
    {
        return $this->pricePerUnit;
    }

    /**
     * @return mixed
     */
    public function getOptionDisplayName()
    {
        return $this->optionDisplayName;
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return mixed
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public function toString()
    {
        return json_encode(get_object_vars($this));
    }

    /**
     * @return mixed
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

}