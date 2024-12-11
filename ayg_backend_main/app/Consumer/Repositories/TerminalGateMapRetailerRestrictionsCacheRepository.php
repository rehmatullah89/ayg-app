<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\TerminalGateMapRetailerRestriction;
use App\Consumer\Helpers\CacheExpirationHelper;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Services\CacheService;

class TerminalGateMapRetailerRestrictionsCacheRepository implements TerminalGateMapRetailerRestrictionsRepositoryInterface
{
    /**
     * @var TerminalGateMapRetailerRestrictionsRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService Redis client, already connected
     */
    private $cacheService;


    public function __construct(
        TerminalGateMapRetailerRestrictionsRepositoryInterface $terminalGateMapRetailerRestrictionsRepository,
        CacheService $cacheService
    ) {
        $this->decorator = $terminalGateMapRetailerRestrictionsRepository;
        $this->cacheService = $cacheService;
    }

    public function getDeliveryRestriction($retailerId, $locationId):?TerminalGateMapRetailerRestriction
    {
        $cacheKey = CacheKeyHelper::getDeliveryRestrictionKey($retailerId, $locationId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $lastOrderRating = $this->decorator->getDeliveryRestriction($retailerId, $locationId);

        $this->cacheService->setCache(
            $cacheKey,
            $lastOrderRating,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $lastOrderRating;
    }

    public function getPickupRestriction($retailerId, $locationId):?TerminalGateMapRetailerRestriction
    {
        $cacheKey = CacheKeyHelper::getPickupRestrictionKey($retailerId, $locationId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $lastOrderRating = $this->decorator->getPickupRestriction($retailerId, $locationId);

        $this->cacheService->setCache(
            $cacheKey,
            $lastOrderRating,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $lastOrderRating;
    }
}
