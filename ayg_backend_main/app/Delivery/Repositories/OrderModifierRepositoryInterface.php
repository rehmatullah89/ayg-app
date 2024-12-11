<?php
namespace App\Delivery\Repositories;

use App\Delivery\Entities\ItemList;

interface OrderModifierRepositoryInterface
{
    public function getItemListByOrderId(string $orderId): ItemList;
}
