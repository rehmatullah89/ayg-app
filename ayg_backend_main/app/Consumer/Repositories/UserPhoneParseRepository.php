<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\PhoneNumberInput;
use App\Consumer\Entities\UserPhone;
use App\Consumer\Mappers\ParseUserPhoneIntoUserPhoneMapper;
use Parse\ParseObject;
use Parse\ParseQuery;

/**
 * Class UserPhoneParseRepository
 * @package App\Consumer\Repositories
 */
class UserPhoneParseRepository extends ParseRepository implements UserPhoneRepositoryInterface
{
    /**
     * @param $userId
     * @param $phoneId
     * @param $verifyCode
     * @return UserPhone
     *
     * changes phoneVerified to true
     */
    public function verifyPhone($userId, $phoneId, $verifyCode)
    {
        $parseUser = new ParseObject('_User', $userId);
        $parseUserPhoneQuery = new ParseQuery('UserPhones');
        $parseUserPhoneQuery->equalTo("objectId", $phoneId);
        $parseUserPhoneQuery->equalTo("user", $parseUser);
        $parseUserPhone = $parseUserPhoneQuery->first();
        json_error($parseUserPhone->getObjectId(),'','');

        $parseUserPhone->set('phoneVerified', true);
        $parseUserPhone->save();

        return ParseUserPhoneIntoUserPhoneMapper::map($parseUserPhone);
    }

    /**
     * @param $userId
     * @param PhoneNumberInput $phoneNumberInput
     * @return UserPhone|null
     *
     * gets UserPhone by userId and phone number
     */
    public function get($userId, PhoneNumberInput $phoneNumberInput)
    {
        $parseUser = new ParseObject('_User', $userId);

        $parseUserPhoneQuery = new ParseQuery('UserPhones');
        $parseUserPhoneQuery->equalTo("user", $parseUser);
        $parseUserPhoneQuery->equalTo('phoneCountryCode', strval($phoneNumberInput->getPhoneCountryCode()));
        $parseUserPhoneQuery->equalTo('phoneNumber', strval($phoneNumberInput->getPhoneNumber()));
        $parseUserPhone = $parseUserPhoneQuery->first();

        if (empty($parseUserPhone)) {
            return null;
        }

        return ParseUserPhoneIntoUserPhoneMapper::map($parseUserPhone);
    }

    public function getById(string $userPhoneId): ?UserPhone
    {
        $parseUserPhoneQuery = new ParseQuery('UserPhones');
        $parseUserPhoneQuery->equalTo("objectId", $userPhoneId);
        $parseUserPhone = $parseUserPhoneQuery->first();

        if (empty($parseUserPhone)) {
            return null;
        }

        return ParseUserPhoneIntoUserPhoneMapper::map($parseUserPhone);
    }

    /**
     * @param $userId
     * @param PhoneNumberInput $phoneNumberInput
     * @return UserPhone
     *
     * use parse to create a new entry in UserPhones
     */
    public function add($userId, PhoneNumberInput $phoneNumberInput)
    {
        $parseUser = new ParseObject('_User', $userId);

        $parseUserPhone = new ParseObject("UserPhones");
        $parseUserPhone->set("user", $parseUser);
        $parseUserPhone->set("phoneCountryCode", strval($phoneNumberInput->getPhoneCountryCode()));
        $parseUserPhone->set("phoneNumber", strval($phoneNumberInput->getPhoneNumber()));
        //$parseUserPhone->set("phoneNumberFormatted", $phoneNumberInput->getPhoneNumberFormatted());
        $parseUserPhone->set("phoneCarrier", null);
        $parseUserPhone->set("phoneVerified", false);
        $parseUserPhone->set("SMSNotificationsEnabled", false);
        $parseUserPhone->set("isActive", false);
        $parseUserPhone->set("startTimestamp", time());
        $parseUserPhone->save();

        return ParseUserPhoneIntoUserPhoneMapper::map($parseUserPhone);
    }

    /**
     * @param UserPhone $userPhone
     * @param $data
     * @return UserPhone
     *
     * updates parse class, data array should contain property => value pairs
     */
    public function update(UserPhone $userPhone, $data)
    {
        $parseUserPhone = new ParseObject('UserPhones', $userPhone->getId());
        $parseUserPhone->fetch();
        foreach ($data as $key => $value) {
            $parseUserPhone->set($key, $value);
        }
        $parseUserPhone->save();

        return ParseUserPhoneIntoUserPhoneMapper::map($parseUserPhone);
    }

    /**
     * @param UserPhone $userPhone
     * @return bool
     *
     * user parse to delete UserPhone Entry
     */
    public function deleteUserPhone(UserPhone $userPhone)
    {
        $parseUserPhone = new ParseObject('UserPhones', $userPhone->getId());
        $parseUserPhone->destroy();
    }


    /**
     * @param $userId
     * @return int
     *
     * gets number of active phones for a given user
     * uses Parse to get count
     */
    public function getActiveUserPhoneCountByUserId($userId)
    {
        $parseUserQuery = new ParseQuery('_User');
        $parseUserQuery->equalTo('objectId', $userId);

        $parseUserPhoneQuery = new ParseQuery("UserPhones");
        $parseUserPhoneQuery->equalTo("isActive", true);
        $parseUserPhoneQuery->matchesQuery('user', $parseUserQuery);

        return $parseUserPhoneQuery->count();
    }
}
