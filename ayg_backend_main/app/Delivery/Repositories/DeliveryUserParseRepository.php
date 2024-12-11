<?php

namespace App\Delivery\Repositories;

use App\Delivery\Exceptions\Exception;
use Parse\ParseQuery;

class DeliveryUserParseRepository implements DeliveryUserRepositoryInterface
{
    public function getDeliveryUserAirportIataCode($deliveryUserId): string
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo('objectId', $deliveryUserId);

        $parseDeliveryUsersQuery = new ParseQuery('DeliveryUsers');
        $parseDeliveryUsersQuery->matchesQuery('user', $userInnerQuery);
        $parseDeliveryUsers = $parseDeliveryUsersQuery->find();

        if (count($parseDeliveryUsers) !== 1) {
            throw new Exception('User ' . $deliveryUserId . ' not found');
        }

        $parseDeliveryUser = $parseDeliveryUsers[0];

        return $parseDeliveryUser->airportIataCode;
    }
}
