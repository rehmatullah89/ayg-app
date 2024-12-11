<?php

namespace App\Background\Repositories;

use App\Background\Entities\RetailerList;
use App\Background\Services\CacheService;


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

    public function __construct(
        RetailerPOSConfigRepositoryInterface $retailerPOSConfigRepository,
        CacheService $cacheService
    ) {
        $this->decorator = $retailerPOSConfigRepository;
        $this->cacheService = $cacheService;
    }

    public function addNotExistingRetailerPOSConfigByRetailerList(
        RetailerList $retailerList
    ) {
        return $this->decorator->addNotExistingRetailerPOSConfigByRetailerList($retailerList);
    }

}
