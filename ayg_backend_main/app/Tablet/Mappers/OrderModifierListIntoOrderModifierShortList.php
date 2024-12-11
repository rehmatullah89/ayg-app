<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\OrderModifier;
use App\Tablet\Entities\OrderModifierShortInfo;


/**
 * Class OrderModifierListIntoOrderModifierShortList
 * @package App\Tablet\Mappers
 */
class OrderModifierListIntoOrderModifierShortList
{

    /**
     * @param OrderModifier[] $orderModifiers
     * @return OrderModifierShortInfo[]
     */
    public static function map(array $orderModifiers)
    {
        $return = [];
        foreach ($orderModifiers as $orderModifier) {
            $return[] = new OrderModifierShortInfo(
                !empty($orderModifier->getRetailerItem()->getItemDisplayName()) ? $orderModifier->getRetailerItem()->getItemDisplayName() : $orderModifier->getRetailerItem()->getItemPOSName(),
                $orderModifier->getItemQuantity(),
                $orderModifier->getModifierOptions(),
                $orderModifier->getItemComment()
            );
        }
        return $return;
    }
}