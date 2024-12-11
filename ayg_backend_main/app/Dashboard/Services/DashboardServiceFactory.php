<?php
namespace App\Dashboard\Services;

use App\Dashboard\Repositories\DashboardCacheRepository;
use App\Dashboard\Services\QueueServiceFactory;

//use App\Dashboard\Repositories\DashboardParseRepository;

class DashboardServiceFactory extends Service
{
    public static function create(CacheService $cacheService)
    {
        return new DashboardService(
            new DashboardCacheRepository(
                //new DashboardParseRepository(),
                $cacheService
            ),
            QueueServiceFactory::create(),
        );
    }
}
