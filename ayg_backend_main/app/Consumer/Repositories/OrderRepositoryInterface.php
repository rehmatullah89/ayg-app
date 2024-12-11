<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Order;
use App\Consumer\Entities\OrderRating;

/**
 * Interface OrderRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface OrderRepositoryInterface
{

    public function getOrderWithRetailer(string $orderId): Order;

    /**
     * @param $orderId
     * @param $userId
     * @return bool returns true if Order found for given order Id and userId
     *
     * returns true if Order found for given order Id and userId
     */
    public function checkIfOrderExistsForAGivenUser($orderId, $userId);

    public function abandonOpenOrdersByUserId(string $userId): void;

    public function switchCartOwner(string $fromUserId, string $toUserUserId): void;

    public function saveTipData(string $orderId, ?int $tipAsPercentage, ?int $tipAsFixedValue): Order;

    public function saveCartItems($data): array;
}
