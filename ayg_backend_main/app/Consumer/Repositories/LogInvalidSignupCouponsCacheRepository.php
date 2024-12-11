<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Coupon;
use App\Consumer\Entities\LogInvalidSignupCoupons;
use App\Consumer\Services\CacheService;
use Predis\Client;

/**
 * Class LogInvalidSignupCouponsCacheRepository
 * @package App\Consumer\Repositories
 */
class LogInvalidSignupCouponsCacheRepository implements LogInvalidSignupCouponsRepositoryInterface
{
    /**
     * @var LogInvalidSignupCouponsRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService Redis client, already connected
     */
    private $cacheService;

    /**
     * UserCouponCacheRepository constructor.
     * @param LogInvalidSignupCouponsRepositoryInterface $LogInvalidSignupCouponsRepository
     * @param CacheService $cacheService
     */
    public function __construct(LogInvalidSignupCouponsRepositoryInterface $LogInvalidSignupCouponsRepository, CacheService $cacheService)
    {
        $this->decorator = $LogInvalidSignupCouponsRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $userId
     * @param $couponId
     * @return LogInvalidSignupCoupons
     */
    public function add($userId, $couponId)
    {
        return $this->decorator->add($userId, $couponId);
    }
}