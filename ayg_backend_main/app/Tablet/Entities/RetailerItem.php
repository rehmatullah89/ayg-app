<?php
namespace App\Tablet\Entities;

class RetailerItem extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;
    private $createdAt;
    private $updatedAt;
    private $isActive;

    private $itemCategoryName;
    private $itemSecondCategoryName;
    private $itemThirdCategoryName;
    private $itemDisplayDescription;
    private $itemDisplayName;
    private $itemId;
    private $itemPOSName;
    private $itemPrice;
    private $priceLevelId;
    private $uniqueId;
    private $uniqueRetailerId;
    private $prepTimeCategory;
    private $taxCategory;
    private $itemImageURL;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->isActive = $data['isActive'];
        $this->itemCategoryName = $data['itemCategoryName'];
        $this->itemSecondCategoryName = $data['itemSecondCategoryName'];
        $this->itemThirdCategoryName = $data['itemThirdCategoryName'];
        $this->itemDisplayDescription = $data['itemDisplayDescription'];
        $this->itemDisplayName = $data['itemDisplayName'];
        $this->itemId = $data['itemId'];
        $this->itemPOSName = $data['itemPOSName'];
        $this->itemPrice = $data['itemPrice'];
        $this->priceLevelId = $data['priceLevelId'];
        $this->uniqueId = $data['uniqueId'];
        $this->uniqueRetailerId = $data['uniqueRetailerId'];
        $this->prepTimeCategory = $data['prepTimeCategory'];
        $this->taxCategory = $data['taxCategory'];
        $this->itemImageURL = $data['itemImageURL'];
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
    public function getCreatedAt()
    {
        return $this->createdAt;
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
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @return mixed
     */
    public function getItemCategoryName()
    {
        return $this->itemCategoryName;
    }

    /**
     * @return mixed
     */
    public function getItemSecondCategoryName()
    {
        return $this->itemSecondCategoryName;
    }

    /**
     * @return mixed
     */
    public function getItemThirdCategoryName()
    {
        return $this->itemThirdCategoryName;
    }

    /**
     * @return mixed
     */
    public function getItemDisplayDescription()
    {
        return $this->itemDisplayDescription;
    }

    /**
     * @return mixed
     */
    public function getItemDisplayName()
    {
        return $this->itemDisplayName;
    }

    /**
     * @return mixed
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return mixed
     */
    public function getItemPOSName()
    {
        return $this->itemPOSName;
    }

    /**
     * @return mixed
     */
    public function getItemPrice()
    {
        return $this->itemPrice;
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
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @return mixed
     */
    public function getUniqueRetailerId()
    {
        return $this->uniqueRetailerId;
    }

    /**
     * @return RetailerItemPrepTimeCategory
     */
    public function getPrepTimeCategory()
    {
        return $this->prepTimeCategory;
    }

    /**
     * @param RetailerItemPrepTimeCategory|null $prepTimeCategory
     * @return $this
     */
    public function setPrepTimeCategory($prepTimeCategory)
    {
        $this->prepTimeCategory = $prepTimeCategory;
        return $this;
    }

    /**
     * @return RetailerItemTaxCategory|null
     */
    public function getTaxCategory()
    {
        return $this->taxCategory;
    }

    /**
     * @param RetailerItemTaxCategory|null $taxCategory
     * @return $this
     */
    public function setTaxCategory($taxCategory)
    {
        $this->taxCategory = $taxCategory;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getItemImageURL()
    {
        return $this->itemImageURL;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}