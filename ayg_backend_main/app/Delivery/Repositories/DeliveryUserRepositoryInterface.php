<?php
namespace App\Delivery\Repositories;

interface DeliveryUserRepositoryInterface
{
    public function getDeliveryUserAirportIataCode($deliveryUserId): string;
}
