<?php
namespace App\Consumer\Mappers;

use App\Consumer\Entities\UserPhone;
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


    public static function mapFromNumberOnly($phoneCountryCode, $phoneNumber)
    {
        return new UserPhone([
            'id' => null,
            'createdAt' => null,
            'updatedAt' => null,
            'userId' => null,
            'phoneNumberFormatted' => null,
            'phoneNumber' => $phoneNumber,
            'phoneCountryCode' => $phoneCountryCode,
            'phoneVerified' => null,
            'phoneCarrier' => null,
            'SMSNotificationsEnabled' => null,
            'startTimestamp' => null,
            'endTimestamp' => null,
            'isActive' => null,
        ]);
    }
}
