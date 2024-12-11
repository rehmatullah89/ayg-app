<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\OrderDeliveryPlanList;

interface OrderDeliveryPlanRepositoryInterface
{
    public function getListByAirportIataCode(string $airportIataCode): OrderDeliveryPlanList;
}
