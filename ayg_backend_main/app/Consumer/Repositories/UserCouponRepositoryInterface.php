<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Coupon;

/**
 * Interface UserCouponRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface UserCouponRepositoryInterface
{

    /**
     * @param $userId
     * @param $couponId
     * @param $addedOnStep
     * @return \App\Consumer\Entities\UserCoupon
     */
    public function add($userId, $couponId, $addedOnStep);

}