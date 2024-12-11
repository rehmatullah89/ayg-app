<?php
namespace App\Tablet\Entities;

/**
 * Class OrderModifierShortInfo
 * @package App\Tablet\Entities
 */
class OrderModifierShortInfo extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $retailerItemName;
    /**
     * @var int
     */
    private $itemQuantity;
    /**
     * @var RetailerItemModifierOptionShortInfo[]
     */
    private $options;
    /**
     * @var string
     */
    private $itemComments;
    /**
     * @var string
     */
    private $itemCategoryName;
    /**
     * @var string
     */
    private $itemSecondCategoryName;
    /**
     * @var string
     */
    private $itemThirdCategoryName;


    /**
     * OrderModifierShortInfo constructor.
     * @param $retailerItemName
     * @param $itemQuantity
     * @param $options
     * @param $itemComments
     */
    public function __construct(
        $retailerItemName,
        $itemCategoryName,
        $itemSecondCategoryName,
        $itemThirdCategoryName,
        $itemQuantity,
        $options,
        $itemComments
    )
    {
        $this->retailerItemName = $retailerItemName;
        $this->itemCategoryName = $itemCategoryName;
        $this->itemSecondCategoryName = $itemSecondCategoryName;
        $this->itemThirdCategoryName = $itemThirdCategoryName;
        $this->itemQuantity = $itemQuantity;
        $this->options = $options;
        $this->itemComments = $itemComments;
    }

    /**
     * @return string
     */
    public function getRetailerItemName()
    {
        return $this->retailerItemName;
    }

    /**
     * @return string
     */
    public function getItemCategoryName()
    {
        return $this->itemCategoryName;
    }

    /**
     * @return string
     */
    public function getItemSecondCategoryName()
    {
        return $this->itemSecondCategoryName;
    }

    /**
     * @return string
     */
    public function getItemThirdCategoryName()
    {
        return $this->itemThirdCategoryName;
    }

    /**
     * @return int
     */
    public function getItemQuantity()
    {
        return $this->itemQuantity;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * @return string
     */
    public function getItemComments()
    {
        return $this->itemComments;
    }

    /**
     * @return array
     *
     * function called when encoded with json_encode
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}