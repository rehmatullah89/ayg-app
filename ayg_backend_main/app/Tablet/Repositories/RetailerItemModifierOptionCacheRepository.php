<?php

namespace App\Tablet\Repositories;

use App\Tablet\Entities\RetailerItemModifierOption;
use App\Tablet\Helpers\CacheExpirationHelper;
use App\Tablet\Helpers\CacheKeyHelper;
use App\Tablet\Services\CacheService;

/**
 * Class RetailerItemModifierOptionCacheRepository
 * @package App\Tablet\Repositories
 */
class RetailerItemModifierOptionCacheRepository implements RetailerItemModifierOptionRepositoryInterface
{
    /**
     * @var RetailerItemModifierOptionRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * RetailerItemModifierOptionCacheRepository constructor.
     * @param RetailerItemModifierOptionRepositoryInterface $retailerItemModifierOptionRepository
     * @param CacheService $cacheService
     *
     * Gets Retailer Item Modifiers List by uniqueId
     */
    public function __construct(RetailerItemModifierOptionRepositoryInterface $retailerItemModifierOptionRepository, CacheService $cacheService)
    {
        $this->decorator = $retailerItemModifierOptionRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param array $uniqueIdList
     * @return RetailerItemModifierOption[]
     */
    public function getListByUniqueIdList(array $uniqueIdList)
    {
        $cacheKey = CacheKeyHelper::getRetailerItemModifierOptionListByUniqueIdListKey($uniqueIdList);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $retailerItemModifierOptionList = $this->decorator->getListByUniqueIdList($uniqueIdList);

        $this->cacheService->setCache(
            $cacheKey,
            $retailerItemModifierOptionList,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $retailerItemModifierOptionList;
    }

}