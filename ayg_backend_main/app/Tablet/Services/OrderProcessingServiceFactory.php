<?php
namespace App\Tablet\Services;

/**
 * Class OrderProcessingServiceFactory
 * @package App\Tablet\Services
 *
 * Creates the instance of OrderProcessingService
 */
class OrderProcessingServiceFactory extends Service
{
    /**
     * @param CacheService $cacheService
     * @return OrderProcessingService
     */
    public static function create(CacheService $cacheService)
    {
        return new OrderProcessingService(
            OrderServiceFactory::create($cacheService),
            RetailerServiceFactory::create($cacheService)
        );
    }
}