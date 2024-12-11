<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\DeliveryAssignment;
use App\Consumer\Helpers\CacheExpirationHelper;
use App\Consumer\Helpers\CacheKeyHelper;
use App\Consumer\Services\CacheService;

/**
 * Class DeliveryAssignmentCacheRepository
 * @package App\Consumer\Repositories
 */
class DeliveryAssignmentCacheRepository implements DeliveryAssignmentRepositoryInterface
{
    /**
     * @var DeliveryAssignmentRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * UserCouponCacheRepository constructor.
     * @param DeliveryAssignmentRepositoryInterface $deliveryAssignmentRepository
     * @param CacheService $cacheService
     */
    public function __construct(DeliveryAssignmentRepositoryInterface $deliveryAssignmentRepository, CacheService $cacheService)
    {
        $this->decorator = $deliveryAssignmentRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $orderId
     * @return DeliveryAssignment|null
     */
    public function getCompletedDeliveryAssignmentWithDeliveryByOrderId($orderId)
    {
        $cacheKey = CacheKeyHelper::getCompletedDeliveryAssignmentWithDeliveryByOrderIdKey($orderId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $lastOrderRating = $this->decorator->getCompletedDeliveryAssignmentWithDeliveryByOrderId($orderId);

        $this->cacheService->setCache(
            $cacheKey,
            $lastOrderRating,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $lastOrderRating;
    }
}