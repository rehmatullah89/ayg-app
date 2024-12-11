<?php

namespace App\Consumer\Services;

use App\Consumer\Entities\RetailerItemTimeRestrictions;
use App\Consumer\Repositories\RetailerItemPropertiesRepositoryInterface;

class RetailerItemTimeRestrictionService extends Service
{
    /**
     * @var RetailerItemPropertiesRepositoryInterface
     */
    private $retailerItemPropertiesRepository;

    public function __construct(
        RetailerItemPropertiesRepositoryInterface $retailerItemPropertiesRepository
    ) {
        $this->retailerItemPropertiesRepository = $retailerItemPropertiesRepository;
    }

    public function getTimeRestrictionByRetailerItemUniqueIdAndDay(
        string $uniqueRetailerItemId,
        int $dayOfWeekAtAirport
    ): RetailerItemTimeRestrictions {
        $retailerItemPropertyList = $this->retailerItemPropertiesRepository->getActiveByUniqueRetailerItemIdAndDayOfWeek(
            $uniqueRetailerItemId,
            $dayOfWeekAtAirport
        );

        return new RetailerItemTimeRestrictions($retailerItemPropertyList);
    }

}
