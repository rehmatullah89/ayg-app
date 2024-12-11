<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\RetailerItemModifier;
use App\Tablet\Entities\RetailerItemModifierOption;
use App\Tablet\Entities\RetailerItemModifierOptionShortInfo;

/**
 * Class RetailerItemModifierOptionListWithQuantitiesIntoRetailerItemModifierOptionShortInfoListMapper
 * @package App\Tablet\Mappers
 */
class RetailerItemModifierOptionListWithQuantitiesIntoRetailerItemModifierOptionShortInfoListMapper
{
    /**
     * @param RetailerItemModifierOption[] $retailerItemModifierOptionList
     * @param RetailerItemModifier[] $retailerItemModifierList
     * @param $orderModifierOptions
     * @return RetailerItemModifierOptionShortInfo[]
     */
    public static function map($retailerItemModifierOptionList, $retailerItemModifierList, $orderModifierOptions)
    {
        $return = [];

        // iterate by options in order
        foreach ($orderModifierOptions as $k => $v) {
            $uniqueId = $v->id;
            $quantity = $v->quantity;
            $optionFound = false;

            // get RetailerModifierOptionId and look for it in the Retailer Item Modifier Option
            foreach ($retailerItemModifierOptionList as $retailerItemModifierOption) {
                if ($retailerItemModifierOption->getUniqueId() == $uniqueId) {
                    $optionFound = $retailerItemModifierOption;
                    break;
                }
            }
            if ($optionFound === false) {
                continue;
            }

            // if found look for Retailer Item Modifier name (option category)
            $modifierName = '';
            foreach ($retailerItemModifierList as $retailerItemModifier) {
                if ($retailerItemModifier->getUniqueId() == $optionFound->getUniqueRetailerItemModifierId()) {
                    $modifierName = !empty($retailerItemModifier->getModifierDisplayName()) ? $retailerItemModifier->getModifierDisplayName() : $retailerItemModifier->getModifierPOSName();
                    break;
                }
            }

            $return[] = new RetailerItemModifierOptionShortInfo(
                !empty($optionFound->getOptionDisplayName()) ? $optionFound->getOptionDisplayName() : $optionFound->getOptionPOSName(),
                $quantity,
                $modifierName
            );
        }
        return $return;
    }
}