<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\RetailerItemProperty;
use App\Consumer\Entities\RetailerItemPropertyList;
use Parse\ParseQuery;

class RetailerItemPropertiesParseRepository extends ParseRepository implements RetailerItemPropertiesRepositoryInterface
{
    public function getActiveByUniqueRetailerItemIdAndDayOfWeek(
        string $uniqueRetailerItemId,
        int $dayOfWeekAtAirport
    ): RetailerItemPropertyList {
        $itemPropertiesQuery = new ParseQuery('RetailerItemProperties');
        $itemPropertiesQuery->equalTo("uniqueRetailerItemId", $uniqueRetailerItemId);
        $itemPropertiesQuery->equalTo("dayOfWeek", $dayOfWeekAtAirport);
        $itemPropertiesQuery->equalTo("isActive", true);
        $itemProperties = $itemPropertiesQuery->find();

        $returnList = new RetailerItemPropertyList();

        foreach ($itemProperties as $itemProperty) {
            $returnList->addItem(new RetailerItemProperty(
                $itemProperty->get('uniqueRetailerItemId'),
                $itemProperty->get('dayOfWeek'),
                $itemProperty->get('restrictOrderTimeInSecsStart'),
                $itemProperty->get('restrictOrderTimeInSecsEnd')
            ));
        }

        return $returnList;
    }
}
