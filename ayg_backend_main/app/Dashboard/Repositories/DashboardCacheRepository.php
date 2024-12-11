<?php

namespace App\Dashboard\Repositories;

use App\Dashboard\Services\CacheService;

/**
 * Class DashboardCacheRepository
 * @package App\Dashboard\Repositories
 */
class DashboardCacheRepository implements DashboardRepositoryInterface
{
    const _86ITEM_LIST = "__86ITEM__LIST";

    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * OrderCacheRepository constructor.
     * @param CacheService $cacheService
     */
    public function __construct(CacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    public function getAllCachedMenuItems(): array
    {
        return hGetAllCache(self::_86ITEM_LIST);
    }

}
