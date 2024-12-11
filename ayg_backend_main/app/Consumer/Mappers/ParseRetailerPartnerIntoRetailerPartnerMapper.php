<?php
namespace App\Consumer\Mappers;


use App\Consumer\Entities\RetailerPartner;
use Parse\ParseObject;

class ParseRetailerPartnerIntoRetailerPartnerMapper
{
    public static function map(
        ParseObject $parseObject
    ) {
        return new RetailerPartner(
            $parseObject->getObjectId(),
            (string)$parseObject->get('partner'),
            (int)$parseObject->get('partnerId'),
            (string)$parseObject->get('airportIataCode'),
            (bool)$parseObject->get('isActive'),
            (string)$parseObject->get('itemsDirectoryName'),
            (string)$parseObject->get('retailerUniqueId')
        );
    }
}
