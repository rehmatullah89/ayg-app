<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\OrderRating;

/**
 * Interface OrderRatingRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface OrderRatingRepositoryInterface
{
    /**
     * @param $orderId
     * @param $userId
     * @return OrderRating|null
     *
     * gets newest order rating by orderId and userId,
     * returns null when no rating added
     */
    public function getLastRating($orderId, $userId);

    /**
     * @param $orderId
     * @param $userId
     * @param $overAllRating
     * @param $feedback
     * @return OrderRating Add Rating and Feedback to Order and return OrderRating as an response
     *
     * Add Rating and Feedback to Order and return OrderRating as an response
     */
    public function addOrderRatingWithFeedback($orderId, $userId, $overAllRating, $feedback);
}