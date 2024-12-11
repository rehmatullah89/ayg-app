<?php
namespace App\Background\Mappers;

use App\Background\Entities\User;
use Parse\ParseObject;

class ParseUserIntoUserMapper
{
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
