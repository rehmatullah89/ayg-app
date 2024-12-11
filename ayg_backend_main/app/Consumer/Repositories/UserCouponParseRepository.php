<?php

namespace App\Consumer\Repositories;

use App\Consumer\Mappers\ParseCouponIntoCouponMapper;
use App\Consumer\Mappers\ParseOrderIntoOrderMapper;
use App\Consumer\Mappers\ParseRetailerIntoRetailerMapper;
use App\Consumer\Mappers\ParseUserCouponIntoUserCouponMapper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use Parse\ParseObject;
use Parse\ParseQuery;

/**
 * Class UserCouponParseRepository
 * @package App\Consumer\Repositories
 */
class UserCouponParseRepository extends ParseRepository implements UserCouponRepositoryInterface
{

    /**
     * @param $couponCode
     * @return \App\Consumer\Entities\Coupon
     */
    private function getCouponByCouponCode($couponCode)
    {
        $query = new ParseQuery("Coupons");
        $query->equalTo("couponCode", $couponCode);
        $query->equalTo("isActive", true);
        $parseCoupon = $query->first();
        $parseCoupon->fetch();
        $coupon = ParseCouponIntoCouponMapper::map($parseCoupon);
        return $coupon;
    }

    /**
     * @param $userId
     * @param $couponCode
     * @param $addedOnStep
     * @return \App\Consumer\Entities\UserCoupon
     * @internal param $orderId
     * @internal param $couponId
     */
    public function add($userId, $couponCode, $addedOnStep)
    {
        $parseUser = new ParseObject("_User", $userId);
        $parseUser->fetch();

        $coupon = $this->getCouponByCouponCode($couponCode);
        $couponId = $coupon->getId();

        $parseCoupon = new ParseObject("Coupons", $couponId);
        $parseCoupon->fetch();

        $parseUserCoupon = new ParseObject("UserCoupons");
        $parseUserCoupon->set("user", $parseUser);
        $parseUserCoupon->set("coupon", $parseCoupon);
        $parseUserCoupon->set("addedOnStep", $addedOnStep);
        $parseUserCoupon->save();

        $userCoupon = ParseUserCouponIntoUserCouponMapper::map($parseUserCoupon);

        return $userCoupon;

    }
}