<?php
namespace App\Delivery\Services;

class DeliveryServiceFactory extends Service
{
    public static function create():DeliveryService
    {
        return new DeliveryService();
    }
}
