<?php
namespace App\Consumer\Mappers;

use App\Consumer\Entities\User;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * Class ParseUserIntoUserMapper
 * @package App\Consumer\Mappers
 */
class ParseUserIntoUserMapper
{
    /**
     * @param ParseObject $parseUser
     * @return User
     *
     * map Parse User object into Entity User Object
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
            'hasConsumerAccess' => $parseUser->get('hasConsumerAccess'),
        ]);
    }
}
