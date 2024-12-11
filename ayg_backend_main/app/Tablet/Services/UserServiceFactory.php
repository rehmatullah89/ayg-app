<?php
namespace App\Tablet\Services;
use App\Tablet\Repositories\OrderCacheRepository;
use App\Tablet\Repositories\OrderParseRepository;
use App\Tablet\Repositories\RetailerCacheRepository;
use App\Tablet\Repositories\RetailerParseRepository;

/**
 * Class UserServiceFactory
 * @package App\Tablet\Services
 *
 * Create instance of UserService
 */
class UserServiceFactory extends Service
{
    /**
     * @param CacheService $cacheService
     * @return UserService
     */
    public static function create(CacheService $cacheService)
    {
        return new UserService(
            new RetailerCacheRepository(
                new RetailerParseRepository(),
                $cacheService
            ),
            new OrderCacheRepository(
                new OrderParseRepository(),
                $cacheService
            ),
            QueueServiceFactory::create()
        );
    }
}