<?php
namespace App\Delivery\Services;

use App\Delivery\Repositories\DeliveryUserCacheRepository;
use App\Delivery\Repositories\DeliveryUserParseRepository;
use App\Tablet\Services\QueueServiceFactory;

class OrderPushNotificationServiceFactory extends Service
{
    public static function create()
    {
        return new OrderPushNotificationService(
			QueueServiceFactory::create(),
            new DeliveryUserParseRepository()
        );
    }
}
