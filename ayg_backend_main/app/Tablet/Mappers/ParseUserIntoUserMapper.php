<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\User;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseUserIntoUserMapper
 * @package App\Tablet\Mappers
 */
class ParseUserIntoUserMapper
{
    /**
     * @param ParseObject $parseUser
     * @return User
     */
    public static function map(ParseObject $parseUser)
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
            'hasTabletPOSAccess' => $parseUser->get('hasTabletPOSAccess'),
            'retailerUserType' => $parseUser->get('retailerUserType'),
        ]);
    }
}