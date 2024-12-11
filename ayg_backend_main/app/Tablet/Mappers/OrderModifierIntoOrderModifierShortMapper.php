<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\OrderModifier;
use App\Tablet\Entities\OrderModifierShortInfo;


/**
 * Class OrderModifierIntoOrderModifierShortMapper
 * @package App\Tablet\Mappers
 */
class OrderModifierIntoOrderModifierShortMapper
{
    /**
     * @param OrderModifier $orderModifier
     * @return OrderModifierShortInfo
     */
    public static function map($orderModifier)
    {
        return new OrderModifierShortInfo(
            !empty($orderModifier->getRetailerItem()->getItemDisplayName()) ? $orderModifier->getRetailerItem()->getItemDisplayName() : $orderModifier->getRetailerItem()->getItemPOSName(),
            $orderModifier->getRetailerItem()->getItemCategoryName(),
            $orderModifier->getRetailerItem()->getItemSecondCategoryName(),
            $orderModifier->getRetailerItem()->getItemThirdCategoryName(),
            $orderModifier->getItemQuantity(),
            $orderModifier->getModifierOptions(),
            $orderModifier->getItemComment()
        );
    }
}