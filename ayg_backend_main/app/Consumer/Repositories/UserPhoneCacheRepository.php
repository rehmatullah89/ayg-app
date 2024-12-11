<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\PhoneNumberInput;
use App\Consumer\Entities\UserCredit;
use App\Consumer\Entities\UserPhone;
use App\Consumer\Services\CacheService;
use Predis\Client;

/**
 * Class UserPhoneCacheRepository
 * @package App\Consumer\Repositories
 */
class UserPhoneCacheRepository implements UserPhoneRepositoryInterface
{
    private $decorator;

    /**
     * @var CacheService Redis client, already connected
     */
    private $cacheService;

    /**
     * UserCreditCacheRepository constructor.
     * @param UserPhoneRepositoryInterface $userPhoneRepository
     * @param CacheService $cacheService
     */
    public function __construct(UserPhoneRepositoryInterface $userPhoneRepository, CacheService $cacheService)
    {
        $this->decorator = $userPhoneRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $userId
     * @param PhoneNumberInput $phoneNumberInput
     * @return UserPhone Adds New Entry
     *
     * Adds New Entry
     */
    public function add($userId, PhoneNumberInput $phoneNumberInput)
    {
        return $this->decorator->add($userId, $phoneNumberInput);
    }

    /**
     * @param UserPhone $userPhone
     * @param $data
     * @return UserPhone Adds New Entry
     *
     * Updates record
     */
    public function update(UserPhone $userPhone, $data)
    {
        return $this->decorator->update($userPhone, $data);
    }

    /**
     * @param $userId
     * @param PhoneNumberInput $phoneNumberInput
     * @return UserPhone|null Adds New Entry
     *
     * gets user Phone by user id and phone number
     */
    public function get($userId, PhoneNumberInput $phoneNumberInput)
    {
        return $this->decorator->get($userId, $phoneNumberInput);
    }

    public function getById(string $userPhoneId): ?UserPhone
    {
        return $this->decorator->getById($userPhoneId);
    }


    /**
     * @param $userId
     * @param $phoneId
     * @param $verifyCode
     * @return UserPhone
     *
     * this method calls is not supported by cache so it
     * calls directly same method in the injected object with UserPhoneRepositoryInterface in order to
     * change phoneVerified to true
     */
    public function verifyPhone($userId, $phoneId, $verifyCode)
    {
        return $this->decorator->verifyPhone($userId, $phoneId, $verifyCode);
    }


    /**
     * @param UserPhone $userPhone
     * @return bool
     *
     * this method calls is not supported by cache so it
     * calls directly same method in the injected object with UserPhoneRepositoryInterface in order to
     * delete UserPhone Entry
     */
    public function deleteUserPhone(UserPhone $userPhone)
    {
        return $this->decorator->deleteUserPhone($userPhone);
    }


    /**
     * @param $userId
     * @return int
     *
     * we need a fresh data, so direct call to decorator
     */
    public function getActiveUserPhoneCountByUserId($userId)
    {

        return $this->decorator->getActiveUserPhoneCountByUserId($userId);
    }
}
