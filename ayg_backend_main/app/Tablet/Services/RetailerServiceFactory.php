<?php
namespace App\Tablet\Services;

use App\Tablet\Repositories\RetailerCacheRepository;
use App\Tablet\Repositories\RetailerParseRepository;
use App\Tablet\Repositories\RetailerPOSConfigCacheRepository;
use App\Tablet\Repositories\RetailerPOSConfigParseRepository;


/**
 * Class RetailerServiceFactory
 * @package App\Tablet\Services
 *
 * Creates instance of RetailerService
 */
class RetailerServiceFactory extends Service
{
    /**
     * @param CacheService $cacheService
     * @return RetailerService
     */
    public static function create(CacheService $cacheService)
    {
        return new RetailerService(
            new RetailerCacheRepository(
                new RetailerParseRepository(),
                $cacheService
            ),
            new RetailerPOSConfigCacheRepository(
                new RetailerPOSConfigParseRepository(),
                $cacheService
            )
        );
    }
}