<?php

namespace App\Delivery\Repositories;

use App\Delivery\Entities\UserPhone;
use App\Delivery\Exceptions\Exception;
use App\Delivery\Mappers\ParseUserPhoneIntoUserPhoneMapper;
use Parse\ParseQuery;

class UserPhoneParseRepository implements UserPhoneRepositoryInterface
{
    public function getActiveUserPhoneByUserId(string $userId): UserPhone
    {
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo('objectId', $userId);

        $parseUsersPhoneQuery = new ParseQuery('UserPhones');
        $parseUsersPhoneQuery->matchesQuery('user', $userInnerQuery);
        $parseUsersPhoneQuery->equalTo('isActive', true);
        $parseUsersPhoneQuery->descending('createdAt');
        $parseUsersPhoneQuery->limit(1);
        $parseUsersPhones = $parseUsersPhoneQuery->find();

        if (count($parseUsersPhones) !== 1) {
            throw new Exception('UserPhone for user ' . $userId . ' not found');
        }

        return ParseUserPhoneIntoUserPhoneMapper::map($parseUsersPhones[0]);
    }
}
