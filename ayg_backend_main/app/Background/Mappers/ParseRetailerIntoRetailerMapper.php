<?php
namespace App\Background\Mappers;

use App\Background\Entities\Retailer;
use App\Background\Helpers\ConfigHelper;
use App\Background\Helpers\S3Helper;
use Parse\ParseObject;

/**
 * Class ParseRetailerIntoRetailerMapper
 * @package App\Consumer\Mappers
 */
class ParseRetailerIntoRetailerMapper
{
    /**
     * @param ParseObject $parseRetailer
     * @return Retailer
     */
    public static function map(ParseObject $parseRetailer)
    {
        return new Retailer([
            'id' => $parseRetailer->getObjectId(),
            'retailerType' => $parseRetailer->get('retailerType'),
            'retailerPriceCategory' => $parseRetailer->get('retailerPriceCategory'),
            'locationId' => $parseRetailer->get('locationId'),
            'searchTags' => $parseRetailer->get('searchTags'),
            'imageLogo' => S3Helper::preparePublicS3URL(
                $parseRetailer->get('imageLogo'),
                S3Helper::getS3KeyPath_ImagesRetailerLogo($parseRetailer->get('airportIataCode')),
                ConfigHelper::get('env_S3Endpoint')
            ),
            'closeTimesSaturday' => $parseRetailer->get('closeTimesSaturday'),
            'closeTimesThursday' => $parseRetailer->get('closeTimesThursday'),
            'closeTimesWednesday' => $parseRetailer->get('closeTimesWednesday'),
            'imageBackground' => S3Helper::preparePublicS3URL(
                $parseRetailer->get('imageBackground'),
                S3Helper::getS3KeyPath_ImagesRetailerBackground($parseRetailer->get('airportIataCode')),
                ConfigHelper::get('env_S3Endpoint')
            ),
            'retailerFoodSeatingType' => $parseRetailer->get('retailerFoodSeatingType'),
            'openTimesSunday' => $parseRetailer->get('openTimesSunday'),
            'openTimesMonday' => $parseRetailer->get('openTimesMonday'),
            'closeTimesFriday' => $parseRetailer->get('closeTimesFriday'),
            'hasDelivery' => $parseRetailer->get('hasDelivery'),
            'retailerCategory' => $parseRetailer->get('retailerCategory'),
            'updatedAt' => $parseRetailer->get('updatedAt'),
            'isActive' => $parseRetailer->get('isActive'),
            'openTimesTuesday' => $parseRetailer->get('openTimesTuesday'),
            'openTimesSaturday' => $parseRetailer->get('openTimesSaturday'),
            'openTimesThursday' => $parseRetailer->get('openTimesThursday'),
            'uniqueId' => $parseRetailer->get('uniqueId'),
            'hasPickup' => $parseRetailer->get('hasPickup'),
            'isChain' => $parseRetailer->get('isChain'),
            'openTimesWednesday' => $parseRetailer->get('openTimesWednesday'),
            'createdAt' => $parseRetailer->get('createdAt'),
            'retailerName' => $parseRetailer->get('retailerName'),
            'openTimesFriday' => $parseRetailer->get('openTimesFriday'),
            'description' => $parseRetailer->get('description'),
            'airportIataCode' => $parseRetailer->get('airportIataCode'),
            'closeTimesMonday' => $parseRetailer->get('closeTimesMonday'),
            'closeTimesSunday' => $parseRetailer->get('closeTimesSunday'),
            'closeTimesTuesday' => $parseRetailer->get('closeTimesTuesday'),
        ]);
    }
}
