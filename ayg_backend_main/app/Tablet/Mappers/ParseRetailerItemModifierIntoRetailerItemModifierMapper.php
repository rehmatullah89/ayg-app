<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\RetailerItemModifier;
use Parse\ParseObject;

/**
 * Class ParseRetailerItemModifierIntoRetailerItemModifierMapper
 * @package App\Consumer\Mappers
 */
class ParseRetailerItemModifierIntoRetailerItemModifierMapper
{
    /**
     * @param ParseObject $parseRetailerItemModifier
     * @return RetailerItemModifier
     */
    public static function map(ParseObject $parseRetailerItemModifier)
    {
        return new RetailerItemModifier([
            'id' => $parseRetailerItemModifier->getObjectId(),
            'createdAt' => $parseRetailerItemModifier->getCreatedAt(),
            'updatedAt' => $parseRetailerItemModifier->getUpdatedAt(),
            'modifierDisplayName' => $parseRetailerItemModifier->get('modifierDisplayName'),
            'isRequired' => $parseRetailerItemModifier->get('isRequired'),
            'modifierDisplayDescription' => $parseRetailerItemModifier->get('modifierDisplayDescription'),
            'uniqueRetailerItemId' => $parseRetailerItemModifier->get('uniqueRetailerItemId'),
            'isActive' => $parseRetailerItemModifier->get('isActive'),
            'uniqueId' => $parseRetailerItemModifier->get('uniqueId'),
            'modifierPOSName' => $parseRetailerItemModifier->get('modifierPOSName'),
            'modifierId' => $parseRetailerItemModifier->get('modifierId'),
            'maxQuantity' => $parseRetailerItemModifier->get('maxQuantity'),
            'minQuantity' => $parseRetailerItemModifier->get('minQuantity'),
        ]);
    }
}