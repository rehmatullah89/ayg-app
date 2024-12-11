<?php
namespace App\Consumer\Mappers;

use App\Consumer\Entities\OrderRating;
use App\Consumer\Entities\User;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseOrderRatingIntoOrderRatingMapper
 * @package App\Consumer\Mappers
 */
class ParseOrderRatingIntoOrderRatingMapper
{
    /**
     * @param ParseObject $parseObject
     * @return OrderRating
     *
     * This method is called by OrderParseRepository
     *
     * This method will return OrderRating after fetching data from ParseObject
     */
    public static function map(ParseObject $parseObject)
    {
        return new OrderRating([
            'id' => $parseObject->getObjectId(),
            'order' => $parseObject->get('order'),
            'overAllRating' => $parseObject->get('overAllRating'),
            'user' => $parseObject->get('user'),
            'feedback' => $parseObject->get('feedback'),
            'createdAt' => $parseObject->getCreatedAt(),
        ]);
    }
}