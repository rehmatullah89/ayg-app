<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserCredit;
use App\Consumer\Services\CacheService;
use Predis\Client;

/**
 * Class UserCreditCacheRepository
 * @package App\Consumer\Repositories
 */
class UserCreditCacheRepository implements UserCreditRepositoryInterface
{
    /**
     * @var UserCreditRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService Redis client, already connected
     */
    private $cacheService;

    /**
     * UserCreditCacheRepository constructor.
     * @param UserCreditRepositoryInterface $userCreditRepository
     * @param CacheService $cacheService
     */
    public function __construct(UserCreditRepositoryInterface $userCreditRepository, CacheService $cacheService)
    {
        $this->decorator = $userCreditRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $userId
     * @param $orderId
     * @param $creditsInCent
     * @param $creditExpiresTimestamp
     * @param $reasonForCredit
     * @param $reasonForCreditCode
     * @param $signupCouponId
     * @param $userReferralId
     * @param $minOrderTotalField
     * @param $minOrderTotalValue
     * @return UserCredit Applies Credit to User with given reason for credit
     *
     *
     *
     *
     * Applies Credit to User with given reason for credit
     */
    public function add($userId, $orderId, $creditsInCent, $creditExpiresTimestamp, $reasonForCredit, $reasonForCreditCode, $signupCouponId, $userReferralId, $minOrderTotalField, $minOrderTotalValue, $forceNoExpiration = false)
    {
        return $this->decorator->add($userId, $orderId, $creditsInCent, $creditExpiresTimestamp, $reasonForCredit, $reasonForCreditCode, $signupCouponId, $userReferralId, $minOrderTotalField, $minOrderTotalValue, $forceNoExpiration);
    }
}
