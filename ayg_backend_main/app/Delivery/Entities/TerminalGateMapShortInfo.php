<?php
namespace App\Delivery\Entities;

class TerminalGateMapShortInfo extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $locationTerminalName;
    /**
     * @var null|string
     */
    private $locationConcourseName;
    /**
     * @var string
     */
    private $locationGateName;


    public function __construct(
        string $locationTerminalName,
        ?string $locationConcourseName,
        string $locationGateName
    ) {
        $this->locationTerminalName = $locationTerminalName;
        $this->locationConcourseName = $locationConcourseName;
        $this->locationGateName = $locationGateName;
    }

    /**
     * @return string
     */
    public function getLocationTerminalName(): string
    {
        return $this->locationTerminalName;
    }

    /**
     * @return null|string
     */
    public function getLocationConcourseName()
    {
        return $this->locationConcourseName;
    }

    /**
     * @return string
     */
    public function getLocationGateName(): string
    {
        return $this->locationGateName;
    }


    // function called when encoded with json_encode
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
