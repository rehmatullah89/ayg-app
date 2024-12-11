<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\PhoneNumberInput;
use App\Consumer\Entities\UserCredit;
use App\Consumer\Entities\UserPhone;

/**
 * Interface UserPhoneRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface UserPhoneRepositoryInterface
{
    /**
     * @param $userId
     * @param PhoneNumberInput $phoneNumberInput
     * @return UserPhone
     *
     * Adds New Entry
     */
    public function add($userId, PhoneNumberInput $phoneNumberInput);

    /**
     * @param UserPhone $userPhone
     * @param $data
     * @return UserPhone Adds New Entry
     *
     * Updates record
     */
    public function update(UserPhone $userPhone, $data);

    /**
     * @param $userId
     * @param PhoneNumberInput $phoneNumberInput
     * @return UserPhone|null Adds New Entry
     *
     * gets user Phone by user id and phone number
     */
    public function get($userId, PhoneNumberInput $phoneNumberInput);

    public function getById(string $userPhoneId): ?UserPhone;

    /**
     * @param UserPhone $userPhone
     * @return bool
     *
     * deletes UserPhone Entry
     */
    public function deleteUserPhone(UserPhone $userPhone);

    /**
     * @param $userId
     * @param $phoneId
     * @param $verifyCode
     * @return UserPhone
     *
     * changes phoneVerified to true
     */
    public function verifyPhone($userId, $phoneId, $verifyCode);

    /**
     * @param $userId
     * @return int
     *
     * gets number of active phones for a given user
     */
    public function getActiveUserPhoneCountByUserId($userId);

}
