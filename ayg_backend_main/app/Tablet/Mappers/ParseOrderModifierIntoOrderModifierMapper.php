<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\OrderModifier;
use Parse\ParseObject;

/**
 * Class ParseOrderModifierIntoOrderModifierMapper
 * @package App\Tablet\Mappers
 */
class ParseOrderModifierIntoOrderModifierMapper
{
    /**
     * @param ParseObject $parseOrderModifier
     * @return OrderModifier
     */
    public static function map(ParseObject $parseOrderModifier)
    {
        return new OrderModifier([
            'id' => $parseOrderModifier->getObjectId(),
            'createdAt' => $parseOrderModifier->getCreatedAt(),
            'updatedAt' => $parseOrderModifier->getUpdatedAt(),
            'order' => $parseOrderModifier->get('order'),
            'retailerItem' => $parseOrderModifier->get('retailerItem'),
            'itemQuantity' => $parseOrderModifier->get('itemQuantity'),
            'itemComment' => $parseOrderModifier->get('itemComment'),
            'modifierOptions' => $parseOrderModifier->get('modifierOptions'),
        ]);
    }
}