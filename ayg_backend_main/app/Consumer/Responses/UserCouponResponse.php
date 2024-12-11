<?php

namespace App\Consumer\Responses;

use App\Consumer\Entities\SignupCouponCredit;

/**
 * Class UserCouponResponse
 */
class UserCouponResponse extends ControllerResponse implements \JsonSerializable
{
    /**
     * @var string
     *
     * Id of UserCoupon or UserCredit
     */
    private $id;

    /**
     * @var
     *
     * credit or coupon
     */
    private $type;

    /**
     * @var
     */
    private $creditsInCents;

    /**
     * @var
     */
    private $creditExpiresTimestamp;

    /**
     * @var
     */
    private $welcomeMessage;

    /**
     * @var
     */
    private $welcomeMessageLogoURL;

    /**
     * UserCouponResponse constructor.
     * @param $id
     * @param $type
     * @param $creditsInCents
     * @param $welcomeMessage
     * @param $welcomeMessageLogoURL
     */
    public function __construct($id, $type, $creditsInCents, $creditExpiresTimestamp, $welcomeMessage, $welcomeMessageLogoURL)
    {
        $this->id = $id;
        $this->type = $type;
        $this->creditsInCents = $creditsInCents;
        $this->creditExpiresTimestamp = $creditExpiresTimestamp;
        $this->welcomeMessage = $welcomeMessage;
        $this->welcomeMessageLogoURL = $welcomeMessageLogoURL;
    }

    /**
     * @param $couponCredit
     * @return UserCouponResponse
     */
    public static function createFromSignupCouponCredit(SignupCouponCredit $couponCredit)
    {
        return new UserCouponResponse(
            $couponCredit->getId(),
            $couponCredit->getType(),
            $couponCredit->getCreditsInCents(),
            $couponCredit->getCreditExpiresTimestamp(),
            $couponCredit->getWelcomeMessage(),
            $couponCredit->getWelcomeMessageLogoURL()
        );
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }
}