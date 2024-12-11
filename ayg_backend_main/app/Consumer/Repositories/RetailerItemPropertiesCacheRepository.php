<?php

namespace App\Consumer\Repositories;

use App\Consumer\Helpers\CacheExpirationHelper;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Services\CacheService;
use App\Consumer\Entities\RetailerItemPropertyList;


class RetailerItemPropertiesCacheRepository implements RetailerItemPropertiesRepositoryInterface
{
    private $decorator;

    private $cacheService;


    public function __construct(RetailerItemPropertiesRepositoryInterface $userCouponRepository, CacheService $cacheService)
    {
        $this->decorator = $userCouponRepository;
        $this->cacheService = $cacheService;
    }

    public function getActiveByUniqueRetailerItemIdAndDayOfWeek(string $uniqueRetailerItemId, int $dayOfWeekAtAirport): RetailerItemPropertyList
    {
        $cacheKey = CacheKeyHelper::getActiveByUniqueRetailerItemIdAndDayOfWeekKey($uniqueRetailerItemId, $dayOfWeekAtAirport);
        //$cacheValue = $this->cacheService->getCache($cacheKey);
        $cacheValue = null;
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $activeByUniqueRetailerItemIdAndDayOfWeek = $this->decorator->getActiveByUniqueRetailerItemIdAndDayOfWeek($uniqueRetailerItemId, $dayOfWeekAtAirport);

        $this->cacheService->setCache(
            $cacheKey,
            $activeByUniqueRetailerItemIdAndDayOfWeek,
            0
        );

        return $activeByUniqueRetailerItemIdAndDayOfWeek;
    }
}
