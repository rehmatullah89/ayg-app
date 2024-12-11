<?php

namespace App\Consumer\Entities;

/**
 * Class CouponCredit
 * @package App\Consumer\Entities
 */
/**
 * Class CouponCredit
 * @package App\Consumer\Entities
 */
class CouponCredit extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $userCreditId;
    /**
     * @var int
     */
    private $onSignupAcctCreditsInCents;

    /**
     * @var string
     */
    private $onSignupAcctCreditsWelcomeMsg;

    /**
     * @var string
     */
    private $onSignupAcctCreditsWelcomeLogoFilename;

    /**
     * CouponCredit constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->userCreditId = $data['userCreditId'];
        $this->onSignupAcctCreditsInCents = $data['onSignupAcctCreditsInCents'];
        $this->onSignupAcctCreditsWelcomeMsg = $data['onSignupAcctCreditsWelcomeMsg'];
        $this->onSignupAcctCreditsWelcomeLogoFilename = $data['onSignupAcctCreditsWelcomeLogoFilename'];
    }

    // function called when encoded with json_encode

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    /**
     * @return int
     */
    public function getOnSignupAcctCreditsInCents()
    {
        return $this->onSignupAcctCreditsInCents;
    }

    /**
     * @param int $onSignupAcctCreditsInCents
     */
    public function setOnSignupAcctCreditsInCents($onSignupAcctCreditsInCents)
    {
        $this->onSignupAcctCreditsInCents = $onSignupAcctCreditsInCents;
    }

    /**
     * @return string
     */
    public function getOnSignupAcctCreditsWelcomeMsg()
    {
        return $this->onSignupAcctCreditsWelcomeMsg;
    }

    /**
     * @param string $onSignupAcctCreditsWelcomeMsg
     */
    public function setOnSignupAcctCreditsWelcomeMsg($onSignupAcctCreditsWelcomeMsg)
    {
        $this->onSignupAcctCreditsWelcomeMsg = $onSignupAcctCreditsWelcomeMsg;
    }

    /**
     * @return string
     */
    public function getOnSignupAcctCreditsWelcomeLogoFilename()
    {
        return $this->onSignupAcctCreditsWelcomeLogoFilename;
    }

    /**
     * @param string $onSignupAcctCreditsWelcomeLogoFilename
     */
    public function setOnSignupAcctCreditsWelcomeLogoFilename($onSignupAcctCreditsWelcomeLogoFilename)
    {
        $this->onSignupAcctCreditsWelcomeLogoFilename = $onSignupAcctCreditsWelcomeLogoFilename;
    }

}