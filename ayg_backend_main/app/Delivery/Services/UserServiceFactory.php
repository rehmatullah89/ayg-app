<?php
namespace App\Delivery\Services;


use App\Delivery\Repositories\DeliveryUserCacheRepository;
use App\Delivery\Repositories\DeliveryUserParseRepository;

class UserServiceFactory extends Service
{
    public static function create(CacheService $cacheService): UserService
    {
        return new UserService(
            new DeliveryUserCacheRepository(
                new DeliveryUserParseRepository(),
                $cacheService
            )
        );
    }
}
