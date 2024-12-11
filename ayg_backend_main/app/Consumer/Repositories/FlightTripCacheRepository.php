<?php

namespace App\Consumer\Repositories;

use App\Consumer\Services\CacheService;

class FlightTripCacheRepository implements FlightTripRepositoryInterface
{
    /**
     * @var FlightTripRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(FlightTripRepositoryInterface $flightTripRepository, CacheService $cacheService)
    {
        $this->decorator = $flightTripRepository;
        $this->cacheService = $cacheService;
    }

    public function switchFlightTripOwner(string $fromUserId, string $toUserUserId): void
    {
        $this->decorator->switchFlightTripOwner($fromUserId, $toUserUserId);
    }
}
