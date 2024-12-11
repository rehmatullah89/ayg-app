<?php
namespace App\Delivery\Entities;

class RetailerShortInfo extends Entity implements \JsonSerializable
{

    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $imageLogoUrl;

    /**
     * @var TerminalGateMapShortInfo
     */
    private $location;

    public function __construct(
        string $name,
        string $imageLogoUrl,
        TerminalGateMapShortInfo $terminalGateMapShort
    ) {
        $this->name = $name;
        $this->imageLogoUrl = $imageLogoUrl;
        $this->location = $terminalGateMapShort;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return TerminalGateMapShortInfo
     */
    public function getLocation(): TerminalGateMapShortInfo
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getImageLogoUrl(): string
    {
        return $this->imageLogoUrl;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
