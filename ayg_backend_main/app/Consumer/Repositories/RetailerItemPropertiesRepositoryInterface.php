<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\RetailerItemPropertyList;

interface RetailerItemPropertiesRepositoryInterface
{
    public function getActiveByUniqueRetailerItemIdAndDayOfWeek(string $uniqueRetailerItemId, int $dayOfWeekAtAirport): RetailerItemPropertyList;
}
