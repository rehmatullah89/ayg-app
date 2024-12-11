<?php
namespace App\Consumer\Services;

use App\Consumer\Repositories\HelloWorldCacheRepository;
use App\Consumer\Repositories\HelloWorldParseRepository;
use App\Consumer\Repositories\OrderCacheRepository;
use App\Consumer\Repositories\OrderParseRepository;
use App\Consumer\Repositories\UserCacheRepository;
use App\Consumer\Repositories\UserCreditCacheRepository;
use App\Consumer\Repositories\UserCreditParseRepository;
use App\Consumer\Repositories\UserParseRepository;
use Predis\Client;


/**
 * Class InfoServiceFactory
 * @package App\Consumer\Services
 */
class UserCreditServiceFactory extends Service
{
    /**
     * @param CacheService $cacheService
     * @return UserCreditService
     */
    public static function create(CacheService $cacheService)
    {
        return new UserCreditService(
            new UserCreditCacheRepository(
                new UserCreditParseRepository(),
                $cacheService
            ),
            new UserCacheRepository(
                new UserParseRepository(),
                $cacheService
            ),
            new OrderCacheRepository(
                new OrderParseRepository(),
                $cacheService
            )

        );
    }
}