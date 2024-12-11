<?php
namespace App\Consumer\Repositories;

use App\Consumer\Entities\DeliveryAssignment;

/**
 * Interface DeliveryAssignmentRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface DeliveryAssignmentRepositoryInterface
{
    /**
     * @param $orderId
     * @return DeliveryAssignment|null
     */
    public function getCompletedDeliveryAssignmentWithDeliveryByOrderId($orderId);
}