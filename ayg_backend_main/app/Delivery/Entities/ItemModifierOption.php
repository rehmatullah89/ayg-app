<?php
namespace App\Delivery\Entities;

class ItemModifierOption extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $optionName;
    /**
     * @var string
     */
    private $modifierName;
    /**
     * @var int
     */
    private $quantity;

    public function __construct(
        string $optionName,
        string $modifierName,
        int $quantity
    )
    {
        $this->optionName = $optionName;
        $this->modifierName = $modifierName;
        $this->quantity = $quantity;
    }
    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
