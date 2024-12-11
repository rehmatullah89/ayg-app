<?php

namespace App\Delivery\Repositories;

use App\Delivery\Entities\ItemList;
use App\Delivery\Services\CacheService;

class OrderModifierCacheRepository extends ParseRepository implements OrderModifierRepositoryInterface
{
    /**
     * @var OrderModifierRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(OrderModifierRepositoryInterface $orderModifierRepository, CacheService $cacheService)
    {
        $this->decorator = $orderModifierRepository;
        $this->cacheService = $cacheService;
    }

    public function getItemListByOrderId(string $orderId): ItemList
    {
        return $this->decorator->getItemListByOrderId($orderId);
    }
}
