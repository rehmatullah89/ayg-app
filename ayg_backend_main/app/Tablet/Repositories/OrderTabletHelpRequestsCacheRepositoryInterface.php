<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\OrderTabletHelpRequest;
use App\Tablet\Services\CacheService;

/**
 * Class OrderTabletHelpRequestsCacheRepositoryInterface
 * @package App\Tablet\Repositories
 */
class OrderTabletHelpRequestsCacheRepositoryInterface implements OrderTabletHelpRequestsRepositoryInterface
{
    /**
     * @var OrderTabletHelpRequestsRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    /**
     * HelloWorldCacheRepository constructor.
     * @param OrderTabletHelpRequestsRepositoryInterface $orderTabletHelpRequestsRepository
     * @param CacheService $cacheService
     */
    public function __construct(OrderTabletHelpRequestsRepositoryInterface $orderTabletHelpRequestsRepository, CacheService $cacheService)
    {
        $this->decorator = $orderTabletHelpRequestsRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $orderId
     * @param $content
     * @return OrderTabletHelpRequest
     *
     *  Add Order Help Request for particular order
     */
    public function add($orderId, $content)
    {
        return $this->decorator->add($orderId, $content);
    }

    /**
     * @param array $orderIds
     * @return OrderTabletHelpRequest[]
     *
     *  Gets all non-resolved help requests by orderID
     */
    public function getNotResolvedByOrderIds(array $orderIds)
    {
        return $this->decorator->getNotResolvedByOrderIds($orderIds);
    }
}