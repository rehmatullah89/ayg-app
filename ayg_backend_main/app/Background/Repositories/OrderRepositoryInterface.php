<?php
namespace App\Background\Repositories;


use App\Background\Entities\Order;
use App\Background\Entities\OrderList;
use App\Background\Entities\Partners\Grab\OrderStatusList;

interface OrderRepositoryInterface
{
    public function getOpenPartnerOrderStatusList(string $partnerName): OrderStatusList;

    public function getActiveOrdersListByRetailerIdList(array $retailerIdList): OrderList;

    public function changeStatusToPushedToRetailer(Order $order);

    public function changeStatusToAcceptedByRetailer(Order $order);
}
