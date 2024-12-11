<?php

namespace App\Delivery\Repositories;

use App\Delivery\Entities\Order;
use App\Delivery\Entities\OrderList;
use App\Delivery\Services\CacheService;

/**
 * Class OrderCacheRepository
 * @package App\Delivery\Repositories
 */
class OrderCacheRepository implements OrderRepositoryInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * OrderCacheRepository constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param CacheService $cacheService
     */
    public function __construct(OrderRepositoryInterface $orderRepository, CacheService $cacheService)
    {
        $this->decorator = $orderRepository;
        $this->cacheService = $cacheService;
    }

    public function getActiveOrdersByAirportIataCode(string $airportIataCode, int $page, int $limit): OrderList
    {
        return $this->decorator->getActiveOrdersByAirportIataCode($airportIataCode, $page, $limit);
    }

    public function getCompletedOrdersByAirportIataCode(string $airportIataCode, int $page, int $limit): OrderList
    {
        return $this->decorator->getCompletedOrdersByAirportIataCode($airportIataCode, $page, $limit);
    }

    public function getActiveOrdersCountByAirportIataCode(string $airportIataCode, int $page, int $limit): int
    {
        return $this->decorator->getActiveOrdersCountByAirportIataCode($airportIataCode, $page, $limit);
    }

    public function getCompletedOrdersCountByAirportIataCode(string $airportIataCode, int $page, int $limit): int
    {
        return $this->decorator->getCompletedOrdersCountByAirportIataCode($airportIataCode, $page, $limit);
    }

    public function getOrderByIdAndAirportIataCode(string $orderId, string $airportIataCode): Order
    {
        return $this->decorator->getOrderByIdAndAirportIataCode($orderId, $airportIataCode);
    }
}
