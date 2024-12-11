<?php

namespace App\Tablet\Repositories;

use App\Tablet\Helpers\CacheExpirationHelper;
use App\Tablet\Helpers\CacheKeyHelper;
use App\Tablet\Services\CacheService;

/**
 * Class RetailerCacheRepository
 * @package App\Tablet\Repositories
 */
class RetailerCacheRepository implements RetailerRepositoryInterface
{
    /**
     * @var RetailerRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * HelloWorldCacheRepository constructor.
     * @param RetailerRepositoryInterface $retailerRepository
     * @param CacheService $cacheService
     */
    public function __construct(RetailerRepositoryInterface $retailerRepository, CacheService $cacheService)
    {
        $this->decorator = $retailerRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $userId
     * @return \App\Tablet\Entities\Retailer[]
     *
     *  gets Retailer Entity List by Id
     */
    public function getByTabletUserId($userId)
    {
        $cacheKey = CacheKeyHelper::getRetailerListByTabletUserKey($userId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $retailerList = $this->decorator->getByTabletUserId($userId);
        $this->cacheService->setCache(
            $cacheKey,
            $retailerList,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $retailerList;
    }
}