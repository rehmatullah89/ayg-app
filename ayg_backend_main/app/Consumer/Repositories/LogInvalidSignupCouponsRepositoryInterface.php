<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Coupon;
use App\Consumer\Entities\LogInvalidSignupCoupons;

/**
 * Interface LogInvalidSignupCouponsRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface LogInvalidSignupCouponsRepositoryInterface
{

    /**
     * @param $userId
     * @param $couponId
     * @return LogInvalidSignupCoupons
     */
    public function add($userId, $couponId);

}