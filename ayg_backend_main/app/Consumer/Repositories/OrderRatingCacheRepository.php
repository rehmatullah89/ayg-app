<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\OrderRating;
use App\Consumer\Helpers\CacheExpirationHelper;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Services\CacheService;

/**
 * Class OrderRatingCacheRepository
 * @package App\Consumer\Repositories
 */
class OrderRatingCacheRepository implements OrderRatingRepositoryInterface
{
    /**
     * @var OrderRatingRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * OrderCacheRepository constructor.
     * @param OrderRatingRepositoryInterface $orderRepository
     * @param CacheService $cacheService
     */
    public function __construct(OrderRatingRepositoryInterface $orderRepository, CacheService $cacheService)
    {
        $this->decorator = $orderRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $orderId
     * @param $userId
     *
     * @return OrderRating|null
     *
     * no cache needed, direct call decorator method
     */
    public function getLastRating($orderId, $userId)
    {
        $cacheKey = CacheKeyHelper::getLastRatingByOrderIdAndUserIdKey($orderId, $userId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $lastOrderRating = $this->decorator->getLastRating($orderId, $userId);

        $this->cacheService->setCache(
            $cacheKey,
            $lastOrderRating,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $lastOrderRating;
    }

    /**
     * @param $orderId
     * @param $userId
     * @param $overAllRating
     * @param $feedback
     * @return OrderRating Add Rating and Feedback to Order and return OrderRating as an response
     *
     * Add Rating and Feedback to Order and return OrderRating as an response
     * no cache needed, direct call
     */
    public function addOrderRatingWithFeedback($orderId, $userId, $overAllRating, $feedback)
    {
        $cacheKey = CacheKeyHelper::getLastRatingByOrderIdAndUserIdKey($orderId, $userId);

        $orderRatingWithFeedback = $this->decorator->addOrderRatingWithFeedback($orderId, $userId, $overAllRating, $feedback);

        $this->cacheService->setCache(
            $cacheKey,
            $orderRatingWithFeedback,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $orderRatingWithFeedback;
    }
}