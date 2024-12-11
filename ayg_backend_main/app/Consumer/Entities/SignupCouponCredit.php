<?php

namespace App\Consumer\Entities;


/**
 * Class SignupCouponCredit
 * @package App\Consumer\Entities
 */
class SignupCouponCredit extends Entity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;
    /**
     * @var int
     */
    private $creditsInCents;
    /**
     * @var int
     */
    private $creditExpiresTimestamp;

    /**
     * @var string
     */
    private $welcomeMessage;

    /**
     * @var string
     */
    private $welcomeMessageLogoURL;

    /**
     * CouponCredit constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->type = $data['type'];
        $this->creditsInCents = $data['creditsInCents'];
        $this->creditExpiresTimestamp = $data['creditExpiresTimestamp'];
        $this->welcomeMessage = $data['welcomeMessage'];
        $this->welcomeMessageLogoURL = $data['welcomeMessageLogoURL'];
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
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getCreditsInCents()
    {
        return $this->creditsInCents;
    }

    /**
     * @return int
     */
    public function getCreditExpiresTimestamp()
    {
        return $this->creditExpiresTimestamp;
    }

    /**
     * @return string
     */
    public function getWelcomeMessage()
    {
        return $this->welcomeMessage;
    }

    /**
     * @return string
     */
    public function getWelcomeMessageLogoURL()
    {
        return $this->welcomeMessageLogoURL;
    }


}