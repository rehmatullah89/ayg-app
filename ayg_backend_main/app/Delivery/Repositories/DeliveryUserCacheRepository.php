<?php

namespace App\Delivery\Repositories;

use App\Delivery\Services\CacheService;

/**
 * Class OrderCacheRepository
 * @package App\Delivery\Repositories
 */
class DeliveryUserCacheRepository extends ParseRepository implements DeliveryUserRepositoryInterface
{
    /**
     * @var DeliveryUserRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(DeliveryUserRepositoryInterface $orderRepository, CacheService $cacheService)
    {
        $this->decorator = $orderRepository;
        $this->cacheService = $cacheService;
    }

    public function getDeliveryUserAirportIataCode($deliveryUserId): string
    {
        return $this->decorator->getDeliveryUserAirportIataCode($deliveryUserId);
    }
}
