<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemCustomizationList extends Entity implements \JsonSerializable
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerItemCustomization $retailerItemInventoryTitle)
    {
        $this->list[] = $retailerItemInventoryTitle;
    }

    public function findVerifiedByUniqueId(string $uniqueId):?RetailerItemCustomization
    {
        /** @var RetailerItemCustomization $item */
        foreach ($this->list as $item) {
            if ($item->getUniqueId() == $uniqueId && $item->getVerified()===true) {
                return $item;
            }
        }
        return null;
    }

    public static function createFromArray(array $array)
    {
        $list = new RetailerItemCustomizationList();

        foreach ($array as $item) {
            $list->addItem(RetailerItemCustomization::createFromArray($item));
        }
        return $list;
    }

    public function isThereUnverifiedItem()
    {
        /** @var RetailerItemCustomization $retailerItemCustomization */
        foreach ($this->list as $retailerItemCustomization) {
            if ($retailerItemCustomization->getVerified() === null) {
                return true;
            }
        }
        return false;
    }

    function jsonSerialize()
    {
        return $this->list;
    }
}
