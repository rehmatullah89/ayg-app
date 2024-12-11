<?php

namespace App\Consumer\Repositories;


use App\Consumer\Entities\TerminalGateMapRetailerRestriction;

interface TerminalGateMapRetailerRestrictionsRepositoryInterface
{
    public function getDeliveryRestriction($retailerId, $locationId):?TerminalGateMapRetailerRestriction;


    public function getPickupRestriction($retailerId, $locationId):?TerminalGateMapRetailerRestriction;
}
