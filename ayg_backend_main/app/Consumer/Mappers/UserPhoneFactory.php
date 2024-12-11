<?php
namespace App\Consumer\Mappers;

use App\Consumer\Entities\UserPhone;
use Parse\ParseObject;

class UserPhoneFactory
{
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


    public static function mapFromArray(array $data)
    {
        $inputData = [
            'id' => null,
            'createdAt' => null,
            'updatedAt' => null,
            'userId' => null,
            'phoneNumberFormatted' => null,
            'phoneNumber' => null,
            'phoneCountryCode' => null,
            'phoneVerified' => null,
            'phoneCarrier' => null,
            'SMSNotificationsEnabled' => null,
            'startTimestamp' => null,
            'endTimestamp' => null,
            'isActive' => null,
        ];

        foreach ($data as $k=>$v){
            $inputData[$k]=$v;
        }

        return new UserPhone($inputData);
    }
}
