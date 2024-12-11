<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\UserPhone;
use Parse\ParseObject;

/**
 * Class ParseUserPhoneIntoUserPhoneMapper
 * @package App\Consumer\Mappers
 */
class ParseUserPhoneIntoUserPhoneMapper
{
    /**
     * @param ParseObject $parseUserPhone
     * @return UserPhone
     */
    public static function map(ParseObject $parseUserPhone)
    {
        return new UserPhone([
            'id' => $parseUserPhone->getObjectId(),
            'createdAt' => $parseUserPhone->getCreatedAt(),
            'updatedAt' => $parseUserPhone->getUpdatedAt(),
            'userId' => $parseUserPhone->get('user')->getObjectId(),
            'phoneNumberFormatted' => $parseUserPhone->get('phoneNumberFormatted'),
            'phoneNumber' => $parseUserPhone->get('phoneNumber'),
            'phoneCountryCode' => $parseUserPhone->get('phoneCountryCode'),
            'phoneVerified' => $parseUserPhone->get('phoneVerified'),
            'phoneCarrier' => $parseUserPhone->get('phoneCarrier'),
            'SMSNotificationsEnabled' => $parseUserPhone->get('SMSNotificationsEnabled'),
            'startTimestamp' => $parseUserPhone->get('startTimestamp'),
            'endTimestamp' => $parseUserPhone->get('endTimestamp'),
            'isActive' => $parseUserPhone->get('isActive'),
        ]);
    }

}
