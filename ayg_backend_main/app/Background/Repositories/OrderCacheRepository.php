<?php
namespace App\Background\Repositories;

use App\Background\Entities\Order;
use App\Background\Entities\Partners\Grab\OrderStatusList;
use App\Background\Services\CacheService;
use App\Background\Entities\OrderList;

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

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CacheService $cacheService
    ) {
        $this->decorator = $orderRepository;
        $this->cacheService = $cacheService;
    }

    public function getOpenPartnerOrderStatusList(string $partnerName): OrderStatusList
    {
        return $this->decorator->getOpenPartnerOrderStatusList($partnerName);
    }

    public function getActiveOrdersListByRetailerIdList(array $retailerIdList): OrderList
    {
        return $this->decorator->getActiveOrdersListByRetailerIdList($retailerIdList);
    }

    public function changeStatusToPushedToRetailer(Order $order)
    {
        return $this->decorator->changeStatusToPushedToRetailer($order);
    }

    public function changeStatusToAcceptedByRetailer(Order $order)
    {
        return $this->decorator->changeStatusToAcceptedByRetailer($order);
    }
}
