<?php

namespace App\Tablet\Repositories;

use App\Tablet\Entities\CacheKey;
use App\Tablet\Entities\Retailer;
use App\Tablet\Helpers\CacheKeyHelper;
use App\Tablet\Services\CacheService;

/**
 * Class RetailerPOSConfigCacheRepository
 * @package App\Tablet\Repositories
 */
class RetailerPOSConfigCacheRepository implements RetailerPOSConfigRepositoryInterface
{
    /**
     * @var RetailerPOSConfigRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * RetailerPOSConfigCacheRepository constructor.
     * @param RetailerPOSConfigRepositoryInterface $retailerPOSConfigRepository
     * @param CacheService $cacheService
     *
     * saves last successful ping timestamp
     */
    public function __construct(RetailerPOSConfigRepositoryInterface $retailerPOSConfigRepository, CacheService $cacheService)
    {
        $this->decorator = $retailerPOSConfigRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param Retailer[] $retailers
     * @param int $timestamp
     * @return void
     */
    public function setLastSuccessfulPingTimestampByRetailers($retailers, $timestamp)
    {
        $this->decorator->setLastSuccessfulPingTimestampByRetailers($retailers, $timestamp);
    }

    /**
     * @param array $retailerUniqueIds
     * @param int $timestamp
     * @return void
     */
    public static function setLastSuccessfulPingTimestampByRetailersStatic($retailerUniqueIds, $timestamp)
    {
        $this->decorator->setLastSuccessfulPingTimestampByRetailersStatic($retailerUniqueIds, $timestamp);
    }

}