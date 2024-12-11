<?php
namespace App\Consumer\Services;

use App\Consumer\Repositories\RetailerRepositoryInterface;
use App\Consumer\Repositories\RetailerCacheRepository;
use App\Consumer\Repositories\RetailerParseRepository;
use App\Consumer\Services\RetailerService;


class RetailerServiceFactory extends Service
{
    public static function create(CacheService $cacheService)
    {
        return new RetailerService(
            new RetailerCacheRepository(
                new RetailerParseRepository(),
                $cacheService
            )
            //QueueServiceFactory::create(),
        );
    }
}
