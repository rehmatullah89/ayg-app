<?php
namespace App\Tablet\Entities;

class RetailerItemModifier extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;
    /**
     * @var \DateTime
     */
    private $createdAt;
    /**
     * @var \DateTime
     */
    private $updatedAt;
    /**
     * @var string
     */
    private $modifierDisplayName;
    /**
     * @var bool
     */
    private $isRequired;
    /**
     * @var string
     */
    private $modifierDisplayDescription;
    /**
     * @var string
     */
    private $uniqueRetailerItemId;
    /**
     * @var bool
     */
    private $isActive;
    /**
     * @var string
     */
    private $uniqueId;
    /**
     * @var string
     */
    private $modifierPOSName;
    /**
     * @var string
     */
    private $modifierId;
    /**
     * @var int
     */
    private $maxQuantity;
    /**
     * @var int
     */
    private $minQuantity;

    /**
     * RetailerItemModifier constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->modifierDisplayName = $data['modifierDisplayName'];
        $this->isRequired = $data['isRequired'];
        $this->modifierDisplayDescription = $data['modifierDisplayDescription'];
        $this->uniqueRetailerItemId = $data['uniqueRetailerItemId'];
        $this->isActive = $data['isActive'];
        $this->uniqueId = $data['uniqueId'];
        $this->modifierPOSName = $data['modifierPOSName'];
        $this->modifierId = $data['modifierId'];
        $this->maxQuantity = $data['maxQuantity'];
        $this->minQuantity = $data['minQuantity'];
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @return string
     */
    public function getModifierDisplayName()
    {
        return $this->modifierDisplayName;
    }

    /**
     * @return bool
     */
    public function isIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * @return string
     */
    public function getModifierDisplayDescription()
    {
        return $this->modifierDisplayDescription;
    }

    /**
     * @return string
     */
    public function getUniqueRetailerItemId()
    {
        return $this->uniqueRetailerItemId;
    }

    /**
     * @return bool
     */
    public function isIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return string
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @return string
     */
    public function getModifierPOSName()
    {
        return $this->modifierPOSName;
    }

    /**
     * @return string
     */
    public function getModifierId()
    {
        return $this->modifierId;
    }

    /**
     * @return int
     */
    public function getMaxQuantity()
    {
        return $this->maxQuantity;
    }

    /**
     * @return int
     */
    public function getMinQuantity()
    {
        return $this->minQuantity;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}