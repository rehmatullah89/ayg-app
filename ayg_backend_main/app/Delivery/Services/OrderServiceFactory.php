<?php
namespace App\Delivery\Services;

use App\Background\Repositories\OrderCommentCacheRepository;
use App\Background\Repositories\OrderCommentMysqlRepository;
use App\Delivery\Repositories\DeliveryUserCacheRepository;
use App\Delivery\Repositories\DeliveryUserParseRepository;
use App\Delivery\Repositories\OrderCacheRepository;
use App\Delivery\Repositories\OrderDeliveryStatusCacheRepository;
use App\Delivery\Repositories\OrderDeliveryStatusParseRepository;
use App\Delivery\Repositories\OrderParseRepository;
use App\Delivery\Repositories\UserPhoneCacheRepository;
use App\Delivery\Repositories\UserPhoneParseRepository;

class OrderServiceFactory extends Service
{
    public static function create(CacheService $cacheService)
    {
        $dataPdoConnection = new \PDO('mysql:host=' . $GLOBALS['env_mysqlDataDataBaseHost'] . ';port=' . $GLOBALS['env_mysqlDataDataBasePort'] . ';dbname=' . $GLOBALS['env_mysqlDataDataBaseName'],
            $GLOBALS['env_mysqlDataDataBaseUser'], $GLOBALS['env_mysqlDataDataBasePassword']);

        return new OrderService(
            new OrderCacheRepository(
                new OrderParseRepository(),
                $cacheService
            ),
            new DeliveryUserCacheRepository(
                new DeliveryUserParseRepository(),
                $cacheService
            ),
            new UserPhoneCacheRepository(
                new UserPhoneParseRepository(),
                $cacheService
            ),
            new OrderDeliveryStatusCacheRepository(
                new OrderDeliveryStatusParseRepository(),
                $cacheService
            ),
            new OrderCommentCacheRepository(
                new OrderCommentMysqlRepository($dataPdoConnection),
                $cacheService
            )
        );
    }
}
