<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;

class RetailerCategoryList extends Entity implements \JsonSerializable
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerCategory $retailerCategory)
    {
        $this->list[] = $retailerCategory;
    }

    public static function createFromArray(array $array): RetailerCategoryList
    {
        $list = new RetailerCategoryList();
        foreach ($array as $item) {
            $list->addItem(new RetailerCategory(
                $item['categoryDescription'],
                $item['categoryID'],
                $item['categoryImageName'],
                $item['categoryType'],
                (bool)$item['primaryCategory']
            ));
        }
        return $list;
    }

    public function getPrimaryCategory(): ?RetailerCategory
    {
        /** @var RetailerCategory $retailer */
        foreach ($this->list as $retailerCategory) {
            if ($retailerCategory->getPrimaryCategory() == true) {
                return $retailerCategory;
            }
        }
        return null;
    }


    function jsonSerialize()
    {
        return $this->data;
    }
}
