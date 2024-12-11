<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerCustomization extends Entity implements \JsonSerializable
{


    /**
     * @var string
     */
    private $retailerName;
    /**
     * @var string
     */
    private $retailerId;
    /**
     * @var string
     */
    private $terminal;
    /**
     * @var string
     */
    private $concourse;
    /**
     * @var string
     */
    private $gate;
    /**
     * @var bool
     */
    private $hasDelivery;
    /**
     * @var bool
     */
    private $hasPickup;
    /**
     * @var bool
     */
    private $isActive;
    /**
     * @var bool|null
     */
    private $verified;

    public function __construct(
        string $retailerName,
        string $retailerId,
        string $terminal,
        string $concourse,
        string $gate,
        bool $hasDelivery,
        bool $hasPickup,
        bool $isActive,
        ?bool $verified
    ) {
        $this->retailerName = $retailerName;
        $this->retailerId = $retailerId;
        $this->terminal = $terminal;
        $this->concourse = $concourse;
        $this->gate = $gate;
        $this->hasDelivery = $hasDelivery;
        $this->hasPickup = $hasPickup;
        $this->isActive = $isActive;
        $this->verified = $verified;
    }

    /**
     * @return string
     */
    public function getRetailerName(): string
    {
        return $this->retailerName;
    }

    /**
     * @return string
     */
    public function getRetailerId(): string
    {
        return $this->retailerId;
    }

    /**
     * @return string
     */
    public function getTerminal(): string
    {
        return $this->terminal;
    }

    /**
     * @return string
     */
    public function getConcourse(): string
    {
        return $this->concourse;
    }

    /**
     * @return string
     */
    public function getGate(): string
    {
        return $this->gate;
    }

    /**
     * @return bool
     */
    public function isHasDelivery(): bool
    {
        return $this->hasDelivery;
    }

    /**
     * @return bool
     */
    public function isHasPickup(): bool
    {
        return $this->hasPickup;
    }

    /**
     * @return bool
     */
    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return bool|null
     */
    public function getVerified()
    {
        return $this->verified;
    }




    public static function createFromArray(array $arrayFromCsv)
    {
        $hasDelivery = $arrayFromCsv[5] == 'Y' ? true : false;
        $hasPickup = $arrayFromCsv[6] == 'Y' ? true : false;
        $isActive = $arrayFromCsv[7] == 'Y' ? true : false;

        $verified = $arrayFromCsv[8] == 'Y' ? true : ($arrayFromCsv[8] == 'N' ? false : null);

        return new RetailerCustomization(
            (string)$arrayFromCsv[0],           // $retailerName
            (string)$arrayFromCsv[1],           // $retailerId
            (string)$arrayFromCsv[2],           // $terminal
            (string)$arrayFromCsv[3],           // $concourse
            (string)$arrayFromCsv[4],           // $gate
            (bool)$hasDelivery,           // $hasDelivery
            (bool)$hasPickup,           // $hasPickup
            (bool)$isActive,           // $isActive
            $verified           // $verified
        );
    }



    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
