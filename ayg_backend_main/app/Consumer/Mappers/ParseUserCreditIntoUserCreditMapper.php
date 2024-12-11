<?php
namespace App\Consumer\Mappers;

use App\Consumer\Entities\User;
use App\Consumer\Entities\UserCredit;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseUserCreditIntoUserCreditMapper
 * @package App\Consumer\Mappers
 */
class ParseUserCreditIntoUserCreditMapper
{
    /**
     * @param ParseObject $parseUser
     * @return UserCredit
     */
    public static function map(ParseObject $parseUser)
    {
        return new UserCredit([
            'id' => $parseUser->getObjectId(),
            'creditsInCents' => $parseUser->get('creditsInCents'),
            'reasonForCredit' => $parseUser->get('reasonForCredit'),
            'reasonForCreditCode' => $parseUser->get('reasonForCreditCode'),
            'user' => $parseUser->get('user'),
            'fromOrder' => $parseUser->get('fromOrder'),
            'signupCoupon' => $parseUser->get('signupCoupon'),
        ]);
    }
}