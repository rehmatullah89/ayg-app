<?php

namespace App\Consumer\Services;

use App\Consumer\Repositories\LogInvalidSignupCouponsCacheRepository;
use App\Consumer\Repositories\LogInvalidSignupCouponsParseRepository;
use App\Consumer\Repositories\UserCouponCacheRepository;
use App\Consumer\Repositories\UserCouponParseRepository;
use App\Consumer\Repositories\UserCreditCacheRepository;
use App\Consumer\Repositories\UserCreditParseRepository;


/**
 * Class UserCouponServiceFactory
 * @package App\Consumer\Services
 */
class UserCouponServiceFactory extends Service
{
    /**
     * @param CacheService $cacheService
     * @return UserCouponService
     */
    public static function create(CacheService $cacheService)
    {
        return new UserCouponService(
            new UserCouponCacheRepository(
                new UserCouponParseRepository(),
                $cacheService
            ),
            new UserCreditCacheRepository(
                new UserCreditParseRepository(),
                $cacheService
            ),
            new LogInvalidSignupCouponsCacheRepository(
                new LogInvalidSignupCouponsParseRepository(),
                $cacheService
            )
        );
    }
}