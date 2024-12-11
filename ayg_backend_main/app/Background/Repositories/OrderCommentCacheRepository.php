<?php

namespace App\Background\Repositories;

use App\Delivery\Entities\OrderComment;
use App\Delivery\Entities\OrderDetailed;
use App\Delivery\Services\CacheService;

class OrderCommentCacheRepository implements OrderCommentRepositoryInterface
{
    /**
     * @var OrderCommentRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(OrderCommentRepositoryInterface $flightTripRepository, CacheService $cacheService)
    {
        $this->decorator = $flightTripRepository;
        $this->cacheService = $cacheService;
    }

    public function store(OrderComment $orderComment)
    {
        return $this->decorator->store($orderComment);
    }

    public function getByOrderAndTimezone(OrderDetailed $order, string $timezone)
    {
        return $this->decorator->getByOrderAndTimezone($order, $timezone);
    }
}
