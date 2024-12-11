<?php
namespace App\Consumer\Services;

use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Repositories\FlightTripCacheRepository;
use App\Consumer\Repositories\FlightTripParseRepository;
use App\Consumer\Repositories\OrderCacheRepository;
use App\Consumer\Repositories\OrderParseRepository;
use App\Consumer\Repositories\UserCacheRepository;
use App\Consumer\Repositories\UserCustomIdentifierMysqlRepository;
use App\Consumer\Repositories\UserCustomSessionMysqlRepository;
use App\Consumer\Repositories\UserParseRepository;
use App\Consumer\Repositories\UserPhoneCacheRepository;
use App\Consumer\Repositories\UserPhoneParseRepository;

/**
 * Class UserServiceFactory
 * @package App\Consumer\Services
 */
class UserServiceFactory
{
    /**
     * @param CacheService $cacheService
     * @return UserService
     */
    public static function create(CacheService $cacheService)
    {
        $sessionsPdoConnection = new \PDO('mysql:host=' . $GLOBALS['env_mysqlSessionsDataBaseHost'] . ';port=' . $GLOBALS['env_mysqlSessionsDataBasePort'] . ';dbname=' . $GLOBALS['env_mysqlSessionsDataBaseName'],
            $GLOBALS['env_mysqlSessionsDataBaseUser'], $GLOBALS['env_mysqlSessionsDataBasePassword']);


        return new UserService(
            new FlightTripCacheRepository(
                new FlightTripParseRepository(),
                $cacheService
            ),
            new OrderCacheRepository(
                new OrderParseRepository(),
                $cacheService
            ),
            new UserCacheRepository(
                new UserParseRepository(),
                $cacheService
            ),
            new UserPhoneCacheRepository(
                new UserPhoneParseRepository(),
                $cacheService
            ),
            new UserCustomIdentifierMysqlRepository($sessionsPdoConnection),
            new UserCustomSessionMysqlRepository($sessionsPdoConnection),
            TwilioServiceFactory::create(),
            $cacheService
        );
    }

}
