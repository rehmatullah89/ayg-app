<?php
namespace App\Tablet\Repositories;

use App\Tablet\Helpers\CacheExpirationHelper;
use App\Tablet\Helpers\CacheKeyHelper;
use App\Tablet\Services\CacheService;

/**
 * Class OrderModifierCacheRepository
 * @package App\Tablet\Repositories
 */
class OrderModifierCacheRepository implements OrderModifierRepositoryInterface
{
    /**
     * @var OrderModifierRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * HelloWorldCacheRepository constructor.
     * @param OrderModifierRepositoryInterface $orderModifierRepository
     *
     *  Gets Order Modifier list by Order Id
     * @param CacheService $cacheService
     */
    public function __construct(OrderModifierRepositoryInterface $orderModifierRepository, CacheService $cacheService)
    {
        $this->decorator = $orderModifierRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param string $orderId
     * @return string
     */
    public function getOrderModifiersByOrderId($orderId)
    {
        $cacheKey = CacheKeyHelper::getOrderModifiersByOrderIdKey($orderId);
        $cacheValue = $this->cacheService->getCache($cacheKey);
        if ($cacheValue !== null) {
            return $cacheValue;
        }

        $modifierList = $this->decorator->getOrderModifiersByOrderId($orderId);

        $this->cacheService->setCache(
            $cacheKey,
            $modifierList,
            CacheExpirationHelper::getExpirationTimestampByMethodName(__METHOD__)
        );

        return $modifierList;
    }
}