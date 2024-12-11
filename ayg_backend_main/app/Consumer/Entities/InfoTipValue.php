<?php
namespace App\Consumer\Entities;

class InfoTipValue extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $display;
    /**
     * @var string
     */
    private $type;
    /**
     * @var string
     */
    private $value;
    /**
     * @var bool
     */
    private $isDefault;

    public function __construct(
        string $display,
        string $type,
        string $value,
        bool $isDefault
    ) {
        $this->display = $display;
        $this->type = $type;
        $this->value = $value;
        $this->isDefault = $isDefault;
    }

    /**
     * @return string
     */
    public function getDisplay(): string
    {
        return $this->display;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return bool
     */
    public function isIsDefault(): bool
    {
        return $this->isDefault;
    }



    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
