<?php
namespace App\Consumer\Entities;

class DeliveryAvailability extends Entity
{
    /*
    const NOT_AVAILABLE_REASON_AIRPORT_NOT_DELIVERY_READY = '';
    const NOT_AVAILABLE_REASON_RETAILER_DOES_NOT_HAVE_DELIVERY = '';
    const NOT_AVAILABLE_REASON_RETAILER_IS_NOT_CURRENTLY_OPEN = '';
    const NOT_AVAILABLE_REASON_RETAILER_IS_NOT_OPEN_AT_GIVEN_TIME = '';
    const NOT_AVAILABLE_REASON_RETAILER_IS_NOT_CURRENTLY_ACTIVE = '';
    const NOT_AVAILABLE_REASON_DELIVERY_IS_NOT_ACTIVE_AT_GIVEN_TIME = '';
    const NOT_AVAILABLE_REASON_DELIVERY_IS_NOT_CURRENTLY_ACTIVE = '';
    */

    const NOT_AVAILABLE_REASON_GENERIC = 'Delivery is not possible';
    const NOT_AVAILABLE_REASON_LOCATION_RESTRICTION = 'Delivery is available to your location';

            // - if airport is delivery ready
        // - if retailer has delivery
        // - if retailer is not closed at that time (ideally with respect of processing time)
        // - for immediate: checks if retailer is active (pings are correct)
        // - for immediate: if delivery is set to on at the dashboard (to be more precised checks last timestamp which is set by loopers when delivery is set to on)
        // - for future: checks if it fits delivery plan (ideally with respect of processing time)


    /**
     * @var bool
     */
    private $isAvailable;
    /**
     * @var null|string
     */
    private $info;

    public function __construct(
        bool $isAvailable,
        ?string $info
    ) {
        $this->isAvailable = $isAvailable;
        $this->info = $info;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function getReasonToBeNotAvailable():?string
    {
        if ($this->isAvailable == true) {
            return null;
        }

        return $this->info;
    }

    public function getReasonToBeNotAvailableAsString(): string
    {
        return (string)$this->getReasonToBeNotAvailable();
    }
}
