<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\RetailerItem;
use Parse\ParseObject;

/**
 * Class ParseRetailerItemIntoRetailerItemMapper
 * @package App\Tablet\Mappers
 */
class ParseRetailerItemIntoRetailerItemMapper
{
    /**
     * @param ParseObject $parseRetailerItem
     * @return RetailerItem
     */
    public static function map(ParseObject $parseRetailerItem)
    {
        return new RetailerItem([
            'id' => $parseRetailerItem->getObjectId(),
            'createdAt' => $parseRetailerItem->getCreatedAt(),
            'updatedAt' => $parseRetailerItem->getUpdatedAt(),
            'isActive' => $parseRetailerItem->get('isActive'),
            'itemCategoryName' => $parseRetailerItem->get('itemCategoryName'),
            'itemSecondCategoryName' => $parseRetailerItem->get('itemSecondCategoryName'),
            'itemThirdCategoryName' => $parseRetailerItem->get('itemThirdCategoryName'),
            'itemDisplayDescription' => $parseRetailerItem->get('itemDisplayDescription'),
            'itemDisplayName' => $parseRetailerItem->get('itemDisplayName'),
            'itemId' => $parseRetailerItem->get('itemId'),
            'itemPOSName' => $parseRetailerItem->get('itemPOSName'),
            'itemPrice' => $parseRetailerItem->get('itemPrice'),
            'priceLevelId' => $parseRetailerItem->get('priceLevelId'),
            'uniqueId' => $parseRetailerItem->get('uniqueId'),
            'uniqueRetailerId' => $parseRetailerItem->get('uniqueRetailerId'),
            'prepTimeCategory' => $parseRetailerItem->get('prepTimeCategory'),
            'taxCategory' => $parseRetailerItem->get('taxCategory'),
            'itemImageURL' => $parseRetailerItem->get('itemImageURL'),
        ]);
    }
}