<?php

namespace App\Consumer\Mappers;

use App\Consumer\Entities\Coupon;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseUserIntoUserMapper
 * @package App\Consumer\Mappers
 */
class ParseCouponIntoCouponMapper
{
    /**
     * @param ParseObject $parseCoupon
     * @return Coupon
     */
    public static function map(ParseObject $parseCoupon)
    {
        return new Coupon([
            'id' => $parseCoupon->getObjectId(),
            'createdAt' => $parseCoupon->getCreatedAt(),
            'updatedAt' => $parseCoupon->getUpdatedAt(),
            'couponCode' => $parseCoupon->get('couponCode'),
            'couponDiscountPCT' => $parseCoupon->get('couponDiscountPCT'),
            'expiresTimestamp' => $parseCoupon->get('expiresTimestamp'),
            'applicableRetailerUniqueIds' => $parseCoupon->get('applicableRetailerUniqueIds'),
            'isRetailerCompensated' => $parseCoupon->get('isRetailerCompensated'),
            'couponDiscountCents' => $parseCoupon->get('couponDiscountCents'),
            'forSignup' => $parseCoupon->get('forSignup'),
        ]);
    }
}
