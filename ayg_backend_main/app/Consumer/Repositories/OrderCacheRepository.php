<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Order;
use App\Consumer\Entities\OrderRating;
use App\Consumer\Services\CacheService;
use Predis\Client;

/**
 * Class OrderCacheRepository
 * @package App\Consumer\Repositories
 */
class OrderCacheRepository implements OrderRepositoryInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService Redis client, already connected
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

    public function getOrderWithRetailer(string $orderId):Order
    {
        return $this->decorator->getOrderWithRetailer($orderId);
    }

    /**
     * @param $orderId
     * @param $userId
     * @return bool returns true if Order found for given order Id and userId
     *
     * returns true if Order found for given order Id and userId
     */
    public function checkIfOrderExistsForAGivenUser($orderId, $userId)
    {
        return $this->decorator->checkIfOrderExistsForAGivenUser($orderId, $userId);
    }

    public function abandonOpenOrdersByUserId(string $userId): void
    {
        $this->decorator->abandonOpenOrdersByUserId($userId);
    }

    public function switchCartOwner(string $fromUserId, string $toUserUserId): void
    {
        $this->decorator->switchCartOwner($fromUserId, $toUserUserId);
    }

    public function saveTipData(string $orderId, ?int $tipAsPercentage, ?int $tipAsFixedValue): Order
    {
        return $this->decorator->saveTipData($orderId, $tipAsPercentage, $tipAsFixedValue);
    }

    public function saveCartItems($data): array
    {
        return $this->decorator->saveCartItems($data);
    }
}
