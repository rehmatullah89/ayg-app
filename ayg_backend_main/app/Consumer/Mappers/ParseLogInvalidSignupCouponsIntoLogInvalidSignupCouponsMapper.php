<?php

namespace App\Consumer\Mappers;

use App\Consumer\Entities\LogInvalidSignupCoupons;
use App\Consumer\Entities\User;
use App\Consumer\Entities\UserCoupon;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseUserCouponIntoUserCouponMapper
 * @package App\Consumer\Mappers
 */
class ParseLogInvalidSignupCouponsIntoLogInvalidSignupCouponsMapper
{
    /**
     * @param ParseObject $parseUser
     * @return LogInvalidSignupCoupons
     */
    public static function map(ParseObject $parseUser)
    {
        return new LogInvalidSignupCoupons([
            'id' => $parseUser->getObjectId(),
            'user' => $parseUser->get('user'),
        ]);
    }
}