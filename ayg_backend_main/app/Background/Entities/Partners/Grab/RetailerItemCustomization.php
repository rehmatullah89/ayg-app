<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemCustomization extends Entity implements \JsonSerializable
{

    /**
     * @var string
     */
    private $itemPOSName;
    /**
     * @var string
     */
    private $uniqueId;
    /**
     * @var bool
     */
    private $isActive;
    /**
     * @var int
     */
    private $itemDisplaySequence;
    /**
     * @var bool
     */
    private $allowedThruSecurity;
    /**
     * @var bool|null
     */
    private $verified;

    public function __construct(
        string $itemPOSName,
        string $uniqueId,
        bool $isActive,
        int $itemDisplaySequence,
        bool $allowedThruSecurity,
        ?bool $verified
    ) {
        $this->itemPOSName = $itemPOSName;
        $this->uniqueId = $uniqueId;
        $this->isActive = $isActive;
        $this->itemDisplaySequence = $itemDisplaySequence;
        $this->allowedThruSecurity = $allowedThruSecurity;
        $this->verified = $verified;
    }

    public static function createFromArray(array $arrayFromCsv)
    {
        $isActive = $arrayFromCsv[2] == 'Y' ? true : false;
        $allowedThruSecurity = $arrayFromCsv[4] == 'Y' ? true : false;
        $verified = $arrayFromCsv[5] == 'Y' ? true : ($arrayFromCsv[5] == 'N' ? false : null);

        return new RetailerItemCustomization(
            (string)$arrayFromCsv[0],
            (string)$arrayFromCsv[1],
            $isActive,
            (int)$arrayFromCsv[3],
            $allowedThruSecurity,
            $verified
        );
    }

    /**
     * @return string
     */
    public function getItemPOSName(): string
    {
        return $this->itemPOSName;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }


    /**
     * @return bool
     */
    public function isIsActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @return int
     */
    public function getItemDisplaySequence(): int
    {
        return $this->itemDisplaySequence;
    }

    /**
     * @return bool
     */
    public function isAllowedThruSecurity(): bool
    {
        return $this->allowedThruSecurity;
    }

    /**
     * @return bool|null
     */
    public function getVerified()
    {
        return $this->verified;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
