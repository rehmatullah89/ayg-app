<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\RetailerItemModifierOption;
use Parse\ParseObject;

/**
 * Class ParseRetailerItemModifierOptionIntoRetailerItemModifierMapperOption
 * @package App\Tablet\Mappers
 */
class ParseRetailerItemModifierOptionIntoRetailerItemModifierMapperOption
{
    /**
     * @param ParseObject $parseRetailerItemModifierOption
     * @return RetailerItemModifierOption
     */
    public static function map(ParseObject $parseRetailerItemModifierOption)
    {
        return new RetailerItemModifierOption([
            'id' => $parseRetailerItemModifierOption->getObjectId(),
            'createdAt' => $parseRetailerItemModifierOption->getCreatedAt(),
            'updatedAt' => $parseRetailerItemModifierOption->getUpdatedAt(),
            'optionPOSName' => $parseRetailerItemModifierOption->get('optionPOSName'),
            'optionDisplayDescription' => $parseRetailerItemModifierOption->get('optionDisplayDescription'),
            'pricePerUnit' => $parseRetailerItemModifierOption->get('pricePerUnit'),
            'optionDisplayName' => $parseRetailerItemModifierOption->get('optionDisplayName'),
            'quantity' => $parseRetailerItemModifierOption->get('quantity'),
            'optionId' => $parseRetailerItemModifierOption->get('optionId'),
            'uniqueRetailerItemModifierId' => $parseRetailerItemModifierOption->get('uniqueRetailerItemModifierId'),
            'priceLevelId' => $parseRetailerItemModifierOption->get('priceLevelId'),
            'isActive' => $parseRetailerItemModifierOption->get('isActive'),
            'uniqueId' => $parseRetailerItemModifierOption->get('uniqueId'),
        ]);
    }
}