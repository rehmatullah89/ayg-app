<?php

namespace App\Consumer\Repositories;

use Parse\ParseQuery;

class FlightTripParseRepository extends ParseRepository implements FlightTripRepositoryInterface
{
    public function switchFlightTripOwner(string $fromUserId, string $toUserUserId): void
    {
        $userFromInnerQuery = new ParseQuery('_User');
        $userFromInnerQuery->equalTo("objectId", $fromUserId);
        $userToInnerQuery = new ParseQuery('_User');

        $userToInnerQuery->equalTo("objectId", $toUserUserId);
        $userToInnerQueryResult = $userToInnerQuery->find();
        if (empty($userToInnerQueryResult) || $userToInnerQueryResult === false || !is_array($userToInnerQueryResult)) {
            throw new \Exception('User not Found');
        }
        $userToInnerQueryResult = $userToInnerQueryResult[0];

        $query = new ParseQuery('FlightTrips');
        $query->matchesQuery("user", $userFromInnerQuery);
        $query->find();
        $records = $query->find();



        foreach ($records as $orderObject) {
            // check if trip is not already on the list
            $flightDuplicateQuery = new ParseQuery('FlightTrips');
            $flightDuplicateQuery->equalTo('flight', $orderObject->get('flight'));
            $flightDuplicateQuery->equalTo('user', $userToInnerQueryResult);
            $flightDuplicateCount = $flightDuplicateQuery->count();

            // if there is already such fight trip, then we just delete the on from guest account
            if ($flightDuplicateCount > 0) {
                $userTrip = $orderObject->get('userTrip');
                $userTrip->destroy(true);
                $orderObject->destroy(true);
                continue;
            }

            // otherwise we switch user on userTrip and FlightTrip
            $userTrip = $orderObject->get('userTrip');
            $userTrip->set('user', $userToInnerQueryResult);
            $userTrip->save();
            $orderObject->set('user', $userToInnerQueryResult);
            $orderObject->save();
        }
    }
}
