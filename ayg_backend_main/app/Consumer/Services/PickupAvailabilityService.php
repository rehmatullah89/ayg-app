<?php

namespace App\Consumer\Services;


use App\Consumer\Repositories\OrderPickupPlanRepositoryInterface;
use App\Consumer\Repositories\TerminalGateMapRetailerRestrictionsRepositoryInterface;

class PickupAvailabilityService extends Service
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

    public function isAirportPickupReady(string $airportIataCode): bool
    {
        // todo move functionality of isPickupAvailableForRetailer here
    }

    public function doesRetailerHavePickup($retailerId): bool
    {
        // todo move functionality of isPickupAvailableForRetailer here
    }

    public function isRetailerCurrentlyOpen($retailerId): bool
    {
        // todo move functionality of isPickupAvailableForRetailer here
    }

    public function isRetailerOpenAtGivenTime($retailerId, int $timestamp): bool
    {
        // todo move functionality of isPickupAvailableForRetailer here
    }

    public function isRetailerCurrentlyActiveByPing($retailerId): bool
    {
        // todo move functionality of isPickupAvailableForRetailer here
    }

    public function isPickupCurrentlyActive($airportIataCode): bool
    {
        // todo move functionality of isPickupAvailableForRetailer here
    }

    public function isPickupActiveAtGivenTime($airportIataCode, int $timestamp): bool
    {
        // todo move functionality of isPickupAvailableForRetailer here
    }

    public function isPickupAvailableForRetailerAtLocation($retailerId, $locationId): bool
    {
        $terminalGateMapRetailerRestriction = $this->terminalGateMapRetailerRestrictionsRepository->getPickupRestriction(
            $retailerId, $locationId
        );

        if ($terminalGateMapRetailerRestriction == null) {
            return true;
        }

        return false;
    }




    // - if airport is Pickup ready
    // - if retailer has Pickup
    // - if retailer is not closed at that time (ideally with respect of processing time)
    // - for immediate: checks if retailer is active (pings are correct)
    // - for immediate: if Pickup is set to on at the dashboard (to be more precised checks last timestamp which is set by loopers when Pickup is set to on)
    // - for future: checks if it fits Pickup plan (ideally with respect of processing time)

}
