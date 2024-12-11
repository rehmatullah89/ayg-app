<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserCredit;

/**
 * Interface UserCreditRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface UserCreditRepositoryInterface
{
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
     * Applies Credit to User with given reason for credit
     */
    public function add(
        $userId,
        $orderId,
        $creditsInCent,
        $creditExpiresTimestamp,
        $reasonForCredit,
        $reasonForCreditCode,
        $signupCouponId,
        $userReferralId,
        $minOrderTotalField,
        $minOrderTotalValue,
        $forceNoExpiration = false
    );

}
