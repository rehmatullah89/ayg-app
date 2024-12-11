<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerCategory extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $categoryDescription;
    /**
     * @var int
     */
    private $categoryID;
    /**
     * @var string
     */
    private $categoryImageName;
    /**
     * @var string
     */
    private $categoryType;
    /**
     * @var bool
     */
    private $primaryCategory;

    public function __construct(
        string $categoryDescription,
        int $categoryID,
        string $categoryImageName,
        string $categoryType,
        bool $primaryCategory
    ) {
        $this->categoryDescription = $categoryDescription;
        $this->categoryID = $categoryID;
        $this->categoryImageName = $categoryImageName;
        $this->categoryType = $categoryType;
        $this->primaryCategory = $primaryCategory;
    }

    /**
     * @return string
     */
    public function getCategoryDescription(): string
    {
        return $this->categoryDescription;
    }

    /**
     * @return int
     */
    public function getCategoryID(): int
    {
        return $this->categoryID;
    }

    /**
     * @return string
     */
    public function getCategoryImageName(): string
    {
        return $this->categoryImageName;
    }

    /**
     * @return string
     */
    public function getCategoryType(): string
    {
        return $this->categoryType;
    }

    /**
     * @return bool
     */
    public function getPrimaryCategory(): bool
    {
        return $this->primaryCategory;
    }

    function jsonSerialize()
    {
        return get_object_vars($this);
    }
}
