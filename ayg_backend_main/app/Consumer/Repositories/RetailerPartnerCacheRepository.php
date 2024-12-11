<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\RetailerPartner;
use App\Consumer\Helpers\CacheExpirationHelper;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Services\CacheService;

class RetailerPartnerCacheRepository implements RetailerPartnerRepositoryInterface
{
    /**
     * @var RetailerPartnerRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(
        RetailerPartnerRepositoryInterface $decorator,
        CacheService $cacheService
    ) {
        $this->decorator = $decorator;
        $this->cacheService = $cacheService;
    }

    public function getPartnerNameByRetailerId($retailerId):?RetailerPartner
    {
        $cacheKey = CacheKeyHelper::getRetailerPartnerByRetailerIdKey($retailerId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $retailerItemModifierList = $this->decorator->getPartnerNameByRetailerId($retailerId);

        $this->cacheService->setCache(
            $cacheKey,
            $retailerItemModifierList,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $retailerItemModifierList;
    }

    public function getRetailerPartnerByRetailerUniqueId($retailerUniqueId):?RetailerPartner
    {
        $cacheKey = CacheKeyHelper::getRetailerPartnerByRetailerUniqueIdKey($retailerUniqueId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }
        $retailerItemModifierList = $this->decorator->getRetailerPartnerByRetailerUniqueId($retailerUniqueId);

        $this->cacheService->setCache(
            $cacheKey,
            $retailerItemModifierList,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $retailerItemModifierList;
    }
}
