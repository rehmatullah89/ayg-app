<?php
namespace App\Consumer\Services;

use App\Consumer\Repositories\InfoRepositoryInterface;
use App\Consumer\Repositories\InfoCacheRepository;
use App\Consumer\Repositories\InfoParseRepository;
use App\Consumer\Services\InfoService;


class InfoServiceFactory extends Service
{
    public static function create(CacheService $cacheService)
    {
        return new InfoService(
            new InfoCacheRepository(
                new InfoParseRepository(),
                $cacheService
            )
        );
    }
}
