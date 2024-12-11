<?php

namespace App\Consumer\Mappers;

use App\Consumer\Entities\User;
use App\Consumer\Entities\UserCoupon;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseUserCouponIntoUserCouponMapper
 * @package App\Consumer\Mappers
 */
class ParseUserCouponIntoUserCouponMapper
{
    /**
     * @param ParseObject $parseUser
     * @return UserCoupon
     */
    public static function map(ParseObject $parseUser)
    {
        return new UserCoupon([
            'id' => $parseUser->getObjectId(),
            'addedOnStep' => $parseUser->get('addedOnStep'),
            'user' => $parseUser->get('user'),
            'appliedToOrder' => $parseUser->get('appliedToOrder'),
        ]);
    }
}