<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\OrderDeliveryPlanList;
use App\Consumer\Services\CacheService;

class OrderDeliveryPlanCacheRepository implements OrderDeliveryPlanRepositoryInterface
{
    /**
     * @var OrderDeliveryPlanRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(OrderDeliveryPlanRepositoryInterface $orderDeliveryPlanRepository, CacheService $cacheService)
    {
        $this->decorator = $orderDeliveryPlanRepository;
        $this->cacheService = $cacheService;
    }

    public function getListByAirportIataCode(string $airportIataCode): OrderDeliveryPlanList
    {
        return $this->decorator->getListByAirportIataCode($airportIataCode);
    }
}
