<?php
namespace App\Tablet\Entities;

class OrderModifier extends Entity implements \JsonSerializable
{

    private $id;
    private $createdAt;
    private $updatedAt;
    private $order;
    private $retailerItem;
    private $itemQuantity;
    private $itemComment;
    private $itemCategoryName;
    private $itemSecondCategoryName;
    private $itemThirdCategoryName;
    private $modifierOptions;

    /**
     * OrderModifier constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->createdAt = $data['createdAt'];
        $this->updatedAt = $data['updatedAt'];
        $this->order = $data['order'];
        $this->retailerItem = $data['retailerItem'];
        $this->itemQuantity = $data['itemQuantity'];
        $this->itemComment = $data['itemComment'];
        $this->modifierOptions = $data['modifierOptions'];
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
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * @param mixed $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    /**
     * @return RetailerItem|null
     */
    public function getRetailerItem()
    {
        return $this->retailerItem;
    }

    /**
     * @param RetailerItem|null $retailerItem
     * @return $this
     */
    public function setRetailerItem($retailerItem)
    {
        $this->retailerItem = $retailerItem;
        return $this;
    }

    /**
     * @return int
     */
    public function getItemQuantity()
    {
        return $this->itemQuantity;
    }

    /**
     * @return mixed
     */
    public function getItemComment()
    {
        return $this->itemComment;
    }

    /**
     * @return mixed
     */
    public function getModifierOptions()
    {
        return $this->modifierOptions;
    }



    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}