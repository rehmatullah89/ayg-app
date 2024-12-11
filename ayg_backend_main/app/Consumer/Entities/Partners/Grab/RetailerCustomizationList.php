<?php
namespace App\Consumer\Entities\Partners\Grab;

use App\Consumer\Entities\Entity;

class RetailerCustomizationList extends Entity implements \JsonSerializable
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerCustomization $retailerItemInventoryTitle)
    {
        $this->list[] = $retailerItemInventoryTitle;
    }

    public function findVerifiedByUniqueRetailer(string $uniqueId):?RetailerCustomization
    {
        /** @var RetailerCustomization $item */
        foreach ($this->list as $item) {
            if ($item->getRetailerId() == $uniqueId && $item->getVerified()===true) {
                return $item;
            }
        }
        return null;
    }

    public static function createFromArray(array $array)
    {
        $list = new RetailerCustomizationList();

        foreach ($array as $item) {
            $list->addItem(RetailerCustomization::createFromArray($item));
        }
        return $list;
    }

    public function isThereUnverifiedItem()
    {
        /** @var RetailerCustomization $RetailerCustomization */
        foreach ($this->list as $RetailerCustomization) {
            if ($RetailerCustomization->getVerified() === null) {
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
