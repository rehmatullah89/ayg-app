<?php
namespace App\Delivery\Entities;

class Item extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $category;
    /**
     * @var bool
     */
    private $allowedThruSecurity;
    /**
     * @var int
     */
    private $quantity;
    /**
     * @var ItemModifierOptionList
     */
    private $itemModifierOptionList;

    public function __construct(
        string $name,
        string $category,
        bool $allowedThruSecurity,
        int $quantity,
        ItemModifierOptionList $itemModifierOptionList
    ) {
        $this->name = $name;
        $this->category = $category;
        $this->allowedThruSecurity = $allowedThruSecurity;
        $this->itemModifierOptionList = $itemModifierOptionList;
        $this->quantity = $quantity;
    }

    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
