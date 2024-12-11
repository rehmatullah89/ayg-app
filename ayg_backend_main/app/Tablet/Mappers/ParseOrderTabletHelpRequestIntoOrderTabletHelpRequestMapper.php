<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\OrderTabletHelpRequest;
use App\Tablet\Entities\User;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseOrderTabletHelpRequestIntoOrderTabletHelpRequestMapper
 * @package App\Tablet\Mappers
 */
class ParseOrderTabletHelpRequestIntoOrderTabletHelpRequestMapper
{
    /**
     * @param ParseObject $parseUser
     * @return OrderTabletHelpRequest
     */
    public static function map(ParseObject $parseUser)
    {
        return new OrderTabletHelpRequest([
            'id' => $parseUser->getObjectId(),
            'order' => $parseUser->get('order'),
            'content' => $parseUser->get('content')
        ]);
    }
}