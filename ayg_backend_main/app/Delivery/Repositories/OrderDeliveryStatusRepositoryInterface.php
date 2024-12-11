<?php
namespace App\Delivery\Repositories;

use App\Delivery\Entities\OrderDeliveryStatus;
use App\Delivery\Entities\OrderDetailed;
use App\Delivery\Entities\User;

interface OrderDeliveryStatusRepositoryInterface
{
    public function changeDeliveryStatus(
        OrderDetailed $order,
        OrderDeliveryStatus $fromDelivery,
        OrderDeliveryStatus $toDeliveryStatus,
        User $user,
        bool $completeOrder
    ): bool;


    public function getDeliveryUserByOrderDetailed(
        OrderDetailed $order
    ): ?User;
}
