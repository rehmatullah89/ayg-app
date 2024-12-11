<?php

namespace App\Background\Repositories;

use App\Background\Entities\RetailerList;
use App\Background\Services\CacheService;

class RetailerCacheRepository implements RetailerRepositoryInterface
{
    /**
     * @var RetailerPartnerRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(RetailerRepositoryInterface $retailerRepository, CacheService $cacheService)
    {
        $this->decorator = $retailerRepository;
        $this->cacheService = $cacheService;
    }

    public function getRetailersByUniqueIdArray(array $retailerUniqueIdArray): RetailerList
    {
        return $this->decorator->getRetailersByUniqueIdArray($retailerUniqueIdArray);
    }

    public function getAllActiveRetailers(): RetailerList
    {
        return $this->decorator->getAllActiveRetailers();
    }
}
