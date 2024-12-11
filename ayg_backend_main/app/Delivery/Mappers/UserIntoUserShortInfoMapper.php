<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\User;
use App\Delivery\Entities\UserShortInfo;

class UserIntoUserShortInfoMapper
{
    public static function map(User $user): UserShortInfo
    {
        return new UserShortInfo(
            $user->getId(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getEmail()
        );
    }
}
