<?php
namespace App\Tablet\Entities;

class RetailerItemModifierOptionShortInfo extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var string
     */
    private $categoryName;

    /**
     * RetailerItemModifierOptionShortInfo constructor.
     * @param $name
     * @param $quantity
     * @param $categoryName
     */
    public function __construct(
        $name,
        $quantity,
        $categoryName
    )
    {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->categoryName = $categoryName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }


}