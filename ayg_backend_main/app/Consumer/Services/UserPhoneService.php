<?php

namespace App\Consumer\Services;


use App\Consumer\Entities\UserPhone;
use App\Consumer\Repositories\UserPhoneRepositoryInterface;

class UserPhoneService extends Service
{
    /**
     * @var UserPhoneRepositoryInterface
     */
    private $userPhoneRepository;
    /**
     * @var TwilioService
     */
    private $twilioService;

    public function __construct(
        UserPhoneRepositoryInterface $userPhoneRepository,
        TwilioService $twilioService
    ) {
        $this->userPhoneRepository = $userPhoneRepository;
        $this->twilioService = $twilioService;
    }

    public function getUserPhone(string $userPhoneId): ?UserPhone
    {
        return $this->userPhoneRepository->getById($userPhoneId);
    }

    public function verifyPhone(UserPhone $userPhone, string $verifyCode)
    {
        $isPhoneVerified = $this->twilioService->authyVerify($userPhone, $verifyCode);
        if (!$isPhoneVerified) {
            return $isPhoneVerified;
        }

        $this->userPhoneRepository->update($userPhone, ['phoneVerified' => true]);

        $cacheKey = getCacheKeyForUserPhone($userPhone->getUserId());
        delCacheByKey($cacheKey);

        return $isPhoneVerified;
    }

}
