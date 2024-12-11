<?php

namespace App\Consumer\Services;


use App\Consumer\Repositories\OrderDeliveryPlanRepositoryInterface;
use App\Consumer\Repositories\TerminalGateMapRetailerRestrictionsRepositoryInterface;

class DeliveryAvailabilityService extends Service
{
    /**
     * @var TerminalGateMapRetailerRestrictionsRepositoryInterface
     */
    private $terminalGateMapRetailerRestrictionsRepository;

    public function __construct(
        TerminalGateMapRetailerRestrictionsRepositoryInterface $terminalGateMapRetailerRestrictionsRepository
    ) {
        $this->terminalGateMapRetailerRestrictionsRepository = $terminalGateMapRetailerRestrictionsRepository;
    }

    public function isAirportDeliveryReady(string $airportIataCode): bool
    {
        // todo move functionality of isDeliveryAvailableForRetailer here
    }

    public function doesRetailerHaveDelivery($retailerId): bool
    {
        // todo move functionality of isDeliveryAvailableForRetailer here
    }

    public function isRetailerCurrentlyOpen($retailerId): bool
    {
        // todo move functionality of isDeliveryAvailableForRetailer here
    }

    public function isRetailerOpenAtGivenTime($retailerId, int $timestamp): bool
    {
        // todo move functionality of isDeliveryAvailableForRetailer here
    }

    public function isRetailerCurrentlyActiveByPing($retailerId): bool
    {
        // todo move functionality of isDeliveryAvailableForRetailer here
    }

    public function isDeliveryCurrentlyActive($airportIataCode): bool
    {
        // todo move functionality of isDeliveryAvailableForRetailer here
    }

    public function isDeliveryActiveAtGivenTime($airportIataCode, int $timestamp): bool
    {
        // todo move functionality of isDeliveryAvailableForRetailer here
    }

    public function isDeliveryAvailableForRetailerAtLocation($retailerId, $locationId): bool
    {
        $terminalGateMapRetailerRestriction = $this->terminalGateMapRetailerRestrictionsRepository->getDeliveryRestriction(
            $retailerId, $locationId
        );

        if ($terminalGateMapRetailerRestriction == null) {
            return true;
        }

        return false;
    }




    // - if airport is delivery ready
    // - if retailer has delivery
    // - if retailer is not closed at that time (ideally with respect of processing time)
    // - for immediate: checks if retailer is active (pings are correct)
    // - for immediate: if delivery is set to on at the dashboard (to be more precised checks last timestamp which is set by loopers when delivery is set to on)
    // - for future: checks if it fits delivery plan (ideally with respect of processing time)

}
