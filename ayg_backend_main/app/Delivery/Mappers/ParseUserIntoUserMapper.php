<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\User;
use Parse\ParseObject;


class ParseUserIntoUserMapper
{
    /**
     * @param ParseObject $parseUser
     * @return User
     */
    public static function map(ParseObject $parseUser):User
    {
        return new User([
            'id' => $parseUser->getObjectId(),
            'email' => $parseUser->get('email'),
            'firstName' => $parseUser->get('firstName'),
            'lastName' => $parseUser->get('lastName'),
            'profileImage' => $parseUser->get('profileImage'),
            'airEmpValidUntilTimestamp' => $parseUser->get('airEmpValidUntilTimestamp'),
            'emailVerified' => $parseUser->get('emailVerified'),
            'typeOfLogin' => $parseUser->get('typeOfLogin'),
            'username' => $parseUser->get('username'),
            'emailVerifyToken' => $parseUser->get('emailVerifyToken'),
            'hasDeliveryAccess' => $parseUser->get('hasDeliveryAccess'),
            'retailerUserType' => $parseUser->get('retailerUserType'),
        ]);
    }
}
