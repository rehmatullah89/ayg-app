<?php
namespace App\Consumer\Entities;

class DeliveryAvailabilityFactory extends Entity
{
    public static function createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime(): DeliveryAvailability
    {
        return new DeliveryAvailability(false, DeliveryAvailability::NOT_AVAILABLE_REASON_GENERIC);
    }

    public static function createFailedDeliveryAvailabilityForUserLocation(): DeliveryAvailability
    {
        return new DeliveryAvailability(false, DeliveryAvailability::NOT_AVAILABLE_REASON_LOCATION_RESTRICTION);
    }

    public static function createGenericSuccessDeliveryAvailability(): DeliveryAvailability
    {
        return new DeliveryAvailability(true, null);
    }
}
