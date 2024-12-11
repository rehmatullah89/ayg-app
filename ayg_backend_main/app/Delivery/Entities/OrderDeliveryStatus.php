<?php
namespace App\Delivery\Entities;

class OrderDeliveryStatus extends Entity implements \JsonSerializable
{

    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $displayName;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $color;
    /**
     * @var int|null
     */
    private $nextStatusId;

    public function __construct(
        int $id,
        string $displayName,
        string $name,
        string $color,
        ?int $nextStatusId
    ) {

        $this->id = $id;
        $this->displayName = $displayName;
        $this->name = $name;
        $this->color = $color;
        $this->nextStatusId = $nextStatusId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @return int|null
     */
    public function getNextStatusId()
    {
        return $this->nextStatusId;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        $array = get_object_vars($this);
        unset($array['nextStatusId']);
        return $array;
    }
}
