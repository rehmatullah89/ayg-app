<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\LogInvalidSignupCoupons;
use App\Consumer\Mappers\ParseCouponIntoCouponMapper;
use App\Consumer\Mappers\ParseLogInvalidSignupCouponsIntoLogInvalidSignupCouponsMapper;
use App\Consumer\Mappers\ParseOrderIntoOrderMapper;
use App\Consumer\Mappers\ParseRetailerIntoRetailerMapper;
use App\Consumer\Mappers\ParseUserCouponIntoUserCouponMapper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use Parse\ParseObject;
use Parse\ParseQuery;

/**
 * Class LogInvalidSignupCouponsParseRepository
 * @package App\Consumer\Repositories
 */
class LogInvalidSignupCouponsParseRepository extends ParseRepository implements LogInvalidSignupCouponsRepositoryInterface
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
     * @param $couponCode
     * @return ParseObject|null
     */
    private function getParseCouponByCouponCode($couponCode) {
        $query = new ParseQuery("Coupons");
        $query->equalTo("couponCode", $couponCode);
        $query->equalTo("isActive", true);
        $parseCoupon = $query->first();

        if (empty($parseCoupon)){
            return null;
        }

        $parseCoupon->fetch();
        return $parseCoupon;
    }

    /**
     * @param $userId
     * @param $couponCode
     * @return LogInvalidSignupCoupons
     *
     * Add Invalid Signup Code by User to Parse
     */
    public function add($userId, $couponCode)
    {
        $parseUser = new ParseObject("_User", $userId);
        $parseUser->fetch();

        $parseCoupon = $this->getParseCouponByCouponCode($couponCode);

        if ($parseCoupon===null){
            return null;
        }

        $parseInvalidAttempts = logInvalidSignupCoupons($parseUser, $parseCoupon);

        $invalidAttempts = ParseLogInvalidSignupCouponsIntoLogInvalidSignupCouponsMapper::map($parseInvalidAttempts);

        return $invalidAttempts;
    }
}