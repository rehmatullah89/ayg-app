<?php
namespace App\Consumer\Services;


use App\Consumer\Repositories\PaymentCacheRepository;
use App\Consumer\Repositories\PaymentParseRepository;
use App\Consumer\Repositories\UserCreditCacheRepository;
use App\Consumer\Repositories\UserCreditParseRepository;
use App\Consumer\Repositories\VouchersCacheRepository;
use App\Consumer\Repositories\VouchersParseRepository;

class PaymentServiceFactory extends Service
{
    public static function create(CacheService $cacheService)
    {
        return new PaymentService(
            new VouchersCacheRepository(
                new VouchersParseRepository(),
                $cacheService
            ),
            new UserCreditCacheRepository(
                new UserCreditParseRepository(),
                $cacheService
            ),
            new PaymentCacheRepository(
                new PaymentParseRepository(),
                $cacheService
            )
        );
    }
}
