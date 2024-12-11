<?php

use App\Consumer\Repositories\UserCreditParseRepository;

/**
 *  adds Credits to user's accounts
 * @param string $userId - user Id
 * @param string $orderId - connected Order Id - when credits are applied due to order cancellation, this should be that order Id, otherwise set null
 * @param integer $creditsInCent - amount of credits in cents
 * @param string $reasonForCredit - description why credits were applied
 * @param string $reasonForCreditCode - description why credits code were applied
 * @param string $signupCouponId - when credits are related with the signup promoCode (which is Coupon in Parse DB) this should be Id of that Coupon Id, otherwise set null
 * @param string $userReferralId - when credits are related with the signup referral code, otherwise set null
 */
function addCreditsToUsersAccount($userId, $orderId, $creditsInCent, $reasonForCredit, $reasonForCreditCode, $signupCouponId, $userReferralId, $minOrderTotalField, $minOrderTotalValue)
{
    $userCreditRepository = new UserCreditParseRepository();
    $userCreditRepository->add($userId, $orderId, $creditsInCent, $reasonForCredit, $reasonForCreditCode, $signupCouponId, $userReferralId, $minOrderTotalField, $minOrderTotalValue);
}