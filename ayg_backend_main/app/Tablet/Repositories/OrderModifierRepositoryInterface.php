<?php

namespace App\Tablet\Repositories;

use App\Tablet\Entities\OrderModifier;

/**
 * Interface OrderModifierRepositoryInterface
 * @package App\Tablet\Repositories
 */
interface OrderModifierRepositoryInterface
{
    /**
     * @param string $orderId
     * @return OrderModifier[]
     *
     * Gets Order Modifier list by Order Id
     */
    public function getOrderModifiersByOrderId($orderId);

}