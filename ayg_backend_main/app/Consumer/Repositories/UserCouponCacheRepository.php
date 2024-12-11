<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Coupon;
use App\Consumer\Services\CacheService;
use Predis\Client;

/**
 * Class UserCouponCacheRepository
 * @package App\Consumer\Repositories
 */
class UserCouponCacheRepository implements UserCouponRepositoryInterface
{
    /**
     * @var UserCouponRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService Redis client, already connected
     */
    private $cacheService;

    /**
     * UserCouponCacheRepository constructor.
     * @param UserCouponRepositoryInterface $userCouponRepository
     * @param CacheService $cacheService
     */
    public function __construct(UserCouponRepositoryInterface $userCouponRepository, CacheService $cacheService)
    {
        $this->decorator = $userCouponRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $userId
     * @param $couponId
     * @param $addedOnStep
     * @return \App\Consumer\Entities\UserCoupon
     */
    public function add($userId, $couponId, $addedOnStep)
    {
        return $this->decorator->add($userId, $couponId, $addedOnStep);
    }
}