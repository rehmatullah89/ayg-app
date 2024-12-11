<?php

namespace App\Tablet\Repositories;

use App\Tablet\Entities\RetailerItemModifier;
use App\Tablet\Helpers\CacheExpirationHelper;
use App\Tablet\Helpers\CacheKeyHelper;
use App\Tablet\Services\CacheService;

/**
 * Class RetailerItemModifierCacheRepository
 * @package App\Tablet\Repositories
 */
class RetailerItemModifierCacheRepository implements RetailerItemModifierRepositoryInterface
{
    /**
     * @var RetailerItemModifierRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * RetailerItemModifierCacheRepository constructor.
     * @param RetailerItemModifierRepositoryInterface $retailerItemModifierRepository
     * @param CacheService $cacheService
     */
    public function __construct(RetailerItemModifierRepositoryInterface $retailerItemModifierRepository, CacheService $cacheService)
    {
        $this->decorator = $retailerItemModifierRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param array $uniqueIdList
     * @return RetailerItemModifier[]
     *
     * Get Retailer Item Modifiers List by uniqueId List
     */
    public function getListByUniqueIdList(array $uniqueIdList)
    {
        $cacheKey = CacheKeyHelper::getRetailerItemModifierListByUniqueIdListKey($uniqueIdList);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $retailerItemModifierList =  $this->decorator->getListByUniqueIdList($uniqueIdList);

        $this->cacheService->setCache(
            $cacheKey,
            $retailerItemModifierList,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $retailerItemModifierList;
    }
}