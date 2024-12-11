<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class OrderStatus extends Entity
{
    const PARTNER_STATUS_CODE_CANCELED = 3;
    /**
     * @var
     */
    private $orderId;
    /**
     * @var int
     */
    private $partnerId;
    /**
     * @var int|null
     */
    private $partnerStatusCode;
    /**
     * @var string|null
     */
    private $partnerStatusDisplay;

    public function __construct(
        $orderId,
        int $partnerId,
        ?int $partnerStatusCode,
        ?string $partnerStatusDisplay
    ) {
        $this->orderId = $orderId;
        $this->partnerId = $partnerId;
        $this->partnerStatusCode = $partnerStatusCode;
        $this->partnerStatusDisplay = $partnerStatusDisplay;
    }

    /**
     * @return mixed
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @return int
     */
    public function getPartnerId(): int
    {
        return $this->partnerId;
    }

    /**
     * @return int|null
     */
    public function getPartnerStatusCode()
    {
        return $this->partnerStatusCode;
    }

    /**
     * @return null|string
     */
    public function getPartnerStatusDisplay()
    {
        return $this->partnerStatusDisplay;
    }

    /**
     * @param int|null $partnerStatusCode
     */
    public function setPartnerStatusCode($partnerStatusCode)
    {
        $this->partnerStatusCode = $partnerStatusCode;
    }

    /**
     * @param null|string $partnerStatusDisplay
     */
    public function setPartnerStatusDisplay($partnerStatusDisplay)
    {
        $this->partnerStatusDisplay = $partnerStatusDisplay;
    }

    public function isCanceled()
    {
        if ($this->getPartnerStatusCode() == self::PARTNER_STATUS_CODE_CANCELED) {
            return true;
        }
        return false;
    }

}
