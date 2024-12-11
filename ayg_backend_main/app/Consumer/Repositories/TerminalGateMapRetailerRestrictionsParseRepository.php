<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\TerminalGateMapRetailerRestriction;
use Parse\ParseQuery;

class TerminalGateMapRetailerRestrictionsParseRepository implements TerminalGateMapRetailerRestrictionsRepositoryInterface
{
    public function getDeliveryRestriction($retailerId, $locationId):?TerminalGateMapRetailerRestriction
    {
        // list sessions for an active user
        $retailerInnerQuery = new ParseQuery('Retailers');
        $retailerInnerQuery->equalTo('objectId', $retailerId);

        $locationInnerQuery = new ParseQuery('TerminalGateMap');
        $locationInnerQuery->equalTo('objectId', $locationId);

        $query = new ParseQuery('TerminalGateMapRetailerRestrictions');
        $query->matchesQuery('retailer', $retailerInnerQuery);
        $query->matchesQuery('deliveryLocation', $locationInnerQuery);
        $query->equalTo('isDeliveryLocationNotAvailable', true);
        $query->limit(1);
        $terminalGateMapRetailerRestrictions = $query->find();

        if (empty($terminalGateMapRetailerRestrictions)) {
            return null;
        }

        return new TerminalGateMapRetailerRestriction(
            $terminalGateMapRetailerRestrictions[0]->getObjectId(),
            $terminalGateMapRetailerRestrictions[0]->get('uniqueId'),
            $terminalGateMapRetailerRestrictions[0]->get('isDeliveryLocationNotAvailable'),
            $terminalGateMapRetailerRestrictions[0]->getObjectId('isPickupLocationNotAvailable')
        );
    }

    public function getPickupRestriction($retailerId, $locationId):?TerminalGateMapRetailerRestriction
    {
        // list sessions for an active user
        $retailerInnerQuery = new ParseQuery('Retailers');
        $retailerInnerQuery->equalTo('objectId', $retailerId);

        $locationInnerQuery = new ParseQuery('TerminalGateMap');
        $locationInnerQuery->equalTo('objectId', $locationId);

        $query = new ParseQuery('TerminalGateMapRetailerRestrictions');
        $query->matchesQuery('retailer', $retailerInnerQuery);
        $query->matchesQuery('deliveryLocation', $locationInnerQuery);
        $query->equalTo('isPickupLocationNotAvailable', true);
        $query->limit(1);
        $terminalGateMapRetailerRestrictions = $query->find();

        if (empty($terminalGateMapRetailerRestrictions)) {
            return null;
        }

        return new TerminalGateMapRetailerRestriction(
            $terminalGateMapRetailerRestrictions[0]->getObjectId(),
            $terminalGateMapRetailerRestrictions[0]->get('uniqueId'),
            $terminalGateMapRetailerRestrictions[0]->get('isDeliveryLocationNotAvailable'),
            $terminalGateMapRetailerRestrictions[0]->getObjectId('isPickupLocationNotAvailable')
        );
    }
}
