<?php
namespace App\Tablet\Helpers;

use App\Tablet\Entities\RetailerItemModifierOption;

/**
 * Class RetailerItemModifierOptionHelper
 * @package App\Tablet\Helpers
 */
class RetailerItemModifierOptionHelper
{
    /**
     * @param RetailerItemModifierOption[] $retailerItemModifierOptionList
     * @return string[]
     */
    public static function getUniqueRetailerItemModifierIdListFromRetailerItemModifierOptionList(array $retailerItemModifierOptionList)
    {
        $return = [];
        foreach ($retailerItemModifierOptionList as $retailerItemModifierOption) {
            $return[] = $retailerItemModifierOption->getUniqueRetailerItemModifierId();
        }
        return $return;
    }
}