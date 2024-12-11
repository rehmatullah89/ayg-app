<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\OrderDeliveryPlan;
use App\Consumer\Entities\OrderDeliveryPlanList;
use App\Consumer\Services\CacheService;
use Parse\ParseQuery;

class OrderDeliveryPlanParseRepository implements OrderDeliveryPlanRepositoryInterface
{

    public function getListByAirportIataCode(string $airportIataCode): OrderDeliveryPlanList
    {
        $query = new ParseQuery('OrderDeliveryPlan');
        $query->equalTo("airportIataCode", $airportIataCode);
        $records = $query->find();

        $list = new OrderDeliveryPlanList();

        foreach ($records as $record) {
            $list->addItem(new OrderDeliveryPlan(
                $record->get('weekDay'),
                $record->get('startingTime'),
                $record->get('endingTime'),
                $record->get('airportIataCode')
            ));
        }

        return $list;
    }
}
