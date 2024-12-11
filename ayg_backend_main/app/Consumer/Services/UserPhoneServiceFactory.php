<?php
namespace App\Consumer\Services;


use App\Consumer\Repositories\UserPhoneCacheRepository;
use App\Consumer\Repositories\UserPhoneParseRepository;

class UserPhoneServiceFactory extends Service
{
    public static function create(CacheService $cacheService)
    {
        return new UserPhoneService(
            new UserPhoneCacheRepository(
                new UserPhoneParseRepository(),
                $cacheService
            ),
            TwilioServiceFactory::create()
        );
    }
}
