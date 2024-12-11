<?php
namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserCredit;
use App\Consumer\Mappers\ParseOrderIntoOrderMapper;
use App\Consumer\Mappers\ParseUserCreditIntoUserCreditMapper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use Parse\ParseObject;

/**
 * Class HelloWorldParseRepository
 * @package App\Consumer\Repositories
 */
class UserCreditParseRepository extends ParseRepository implements UserCreditRepositoryInterface
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
    ) {
        $parseUser = new ParseObject("_User", $userId);

        if ($orderId !== null) {
            $parseOrder = new ParseObject("Order", $orderId);
        } else {
            $parseOrder = null;
        }

        if ($signupCouponId !== null) {
            $parseSignupCoupon = new ParseObject("Coupons", $signupCouponId);
        } else {
            $parseSignupCoupon = null;
        }

        if ($userReferralId !== null && $userReferralId != '') {
            $parseUserReferral = new ParseObject("UserReferral", $userReferralId);
        } else {
            $parseUserReferral = null;
        }

        $parseUserCredit = new ParseObject("UserCredits");
        $parseUserCredit->set("user", $parseUser);
        $parseUserCredit->set("fromOrder", $parseOrder);
        $parseUserCredit->set("creditsInCents", $creditsInCent);
        $parseUserCredit->set("reasonForCredit", $reasonForCredit);
        $parseUserCredit->set("reasonForCreditCode", $reasonForCreditCode);
        $parseUserCredit->set("signupCoupon", $parseSignupCoupon);
        $parseUserCredit->set("userReferral", $parseUserReferral);
        $parseUserCredit->set("minOrderTotalField", $minOrderTotalField);
        $parseUserCredit->set("minOrderTotalValue", intval($minOrderTotalValue));

        if ($forceNoExpiration) {
            $parseUserCredit->set('expireTimestamp', -1);
        } else {
            if ($creditExpiresTimestamp > 0) {
                $parseUserCredit->set('expireTimestamp', $creditExpiresTimestamp);
            } else {
                // Expires six months (183 days) from application
                $parseUserCredit->set('expireTimestamp', time() + 183 * 24 * 60 * 60);
            }
        }
        $parseUserCredit->save();

        $userCredit = ParseUserCreditIntoUserCreditMapper::map($parseUserCredit);
        $userCredit->setUser(null);
        $userCredit->setFromOrder(null);
        $userCredit->setSignupCoupon(null);

        return $userCredit;

    }
}
