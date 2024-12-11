<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemInventoryTitle extends Entity implements \JsonSerializable
{

    /**
     * @var string
     */
    private $detailsDescription;
    /**
     * @var string
     */
    private $endTime;
    /**
     * @var string
     */
    private $imageNameLong;
    /**
     * @var string
     */
    private $imageNameWide;
    /**
     * @var string
     */
    private $inventorySubTitles;
    /**
     * @var string
     */
    private $inventoryTitleDescription;
    /**
     * @var int
     */
    private $inventoryTitleID;
    /**
     * @var int
     */
    private $inventoryTitleOrder;
    /**
     * @var string
     */
    private $startTime;

    public function __construct(
        string $detailsDescription,
        string $endTime,
        string $imageNameLong,
        string $imageNameWide,
        string $inventorySubTitles,
        string $inventoryTitleDescription,
        int $inventoryTitleID,
        int $inventoryTitleOrder,
        string $startTime
    ) {
        $this->detailsDescription = $detailsDescription;
        $this->endTime = $endTime;
        $this->imageNameLong = $imageNameLong;
        $this->imageNameWide = $imageNameWide;
        $this->inventorySubTitles = $inventorySubTitles;
        $this->inventoryTitleDescription = $inventoryTitleDescription;
        $this->inventoryTitleID = $inventoryTitleID;
        $this->inventoryTitleOrder = $inventoryTitleOrder;
        $this->startTime = $startTime;
    }

    /**
     * @return string
     */
    public function getDetailsDescription(): string
    {
        return $this->detailsDescription;
    }

    /**
     * @return string
     */
    public function getEndTime(): string
    {
        return $this->endTime;
    }

    /**
     * @return string
     */
    public function getImageNameLong(): string
    {
        return $this->imageNameLong;
    }

    /**
     * @return string
     */
    public function getImageNameWide(): string
    {
        return $this->imageNameWide;
    }

    /**
     * @return string
     */
    public function getInventorySubTitles(): string
    {
        return $this->inventorySubTitles;
    }

    /**
     * @return string
     */
    public function getInventoryTitleDescription(bool $replaceNewLines = false): string
    {
        if ($replaceNewLines) {
            return str_replace([
                "\n",
                '
'
            ], ' ', $this->inventoryTitleDescription);
        }
        return $this->inventoryTitleDescription;
    }

    /**
     * @return int
     */
    public function getInventoryTitleID(): int
    {
        return $this->inventoryTitleID;
    }

    /**
     * @return int
     */
    public function getInventoryTitleOrder(): int
    {
        return $this->inventoryTitleOrder;
    }

    /**
     * @return string
     */
    public function getStartTime(): string
    {
        return $this->startTime;
    }


    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
