<?php
namespace App\Background\Repositories;

use App\Background\Entities\User;
use App\Background\Exceptions\DeliveryUserNotFoundException;
use Parse\ParseObject;
use Parse\ParseQuery;

class DeliveryUserParseRepository
{

    public function updateDeliveryUser(User $user, $comments, $airportIataCode)
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo("objectId", $user->getId());

        $query = new ParseQuery('DeliveryUsers');
        $query->matchesQuery('user', $userInnerQuery);
        $result = $query->find(true, true);

        if (empty($result)) {
            throw new DeliveryUserNotFoundException('delivery user for ' . json_encode($user) . ' not found');
        }

        $deliveryUser = $result[0];
        $deliveryUser->set('comments', $comments);
        $deliveryUser->set('airportIataCode', $airportIataCode);
        $deliveryUser->save(true);
    }

    public function addDeliveryUser(User $user, $comments, $airportIataCode)
    {
        $query = new ParseQuery('_User');
        $query->equalTo('email', $user->getEmail());
        $result = $query->find(true, true);
        $user = $result[0];

        $deliveryUser = new ParseObject('DeliveryUsers');
        $deliveryUser->set('user', $user);
        $deliveryUser->set('comments', $comments);
        $deliveryUser->set('airportIataCode', $airportIataCode);
        $deliveryUser->save();
    }
}
