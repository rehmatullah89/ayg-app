<?php

namespace App\Delivery\Repositories;

use App\Delivery\Entities\OrderDetailed;
use App\Delivery\Entities\OrderDeliveryStatus;
use App\Delivery\Entities\User;
use App\Delivery\Services\CacheService;


class OrderDeliveryStatusCacheRepository extends ParseRepository implements OrderDeliveryStatusRepositoryInterface
{
    /**
     * @var OrderDeliveryStatusRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(OrderDeliveryStatusRepositoryInterface $orderRepository, CacheService $cacheService)
    {
        $this->decorator = $orderRepository;
        $this->cacheService = $cacheService;
    }

    public function changeDeliveryStatus(
        OrderDetailed $order,
        OrderDeliveryStatus $fromDelivery,
        OrderDeliveryStatus $toDeliveryStatus,
        User $user,
        bool $completeOrder
    ): bool {
        return $this->decorator->changeDeliveryStatus(
            $order, $fromDelivery, $toDeliveryStatus, $user, $completeOrder
        );
    }

    public function getDeliveryUserByOrderDetailed(
        OrderDetailed $order
    ): ?User
    {
        return $this->decorator->getDeliveryUserByOrderDetailed($order);
    }
}
