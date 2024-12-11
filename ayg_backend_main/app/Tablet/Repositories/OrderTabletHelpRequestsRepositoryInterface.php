<?php

namespace App\Tablet\Repositories;

use App\Tablet\Entities\OrderTabletHelpRequest;

/**
 * Interface OrderTabletHelpRequestsRepositoryInterface
 * @package App\Tablet\Repositories
 */
interface OrderTabletHelpRequestsRepositoryInterface
{
    /**
     * @param $orderId
     * @param $content
     * @return OrderTabletHelpRequest
     *
     * Add Order Help Request for particular order
     */
    public function add($orderId, $content);

    /**
     * @param array $orderIds
     * @return OrderTabletHelpRequest[]
     *
     * Gets all non-resolved help requests by orderID
     */
    public function getNotResolvedByOrderIds(array $orderIds);

}