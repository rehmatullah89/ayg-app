<?php

namespace App\Consumer\Repositories;

use App\Consumer\Services\CacheService;

class InfoCacheRepository implements InfoRepositoryInterface
{
    private $decorator;
    private $cacheService;

    public function __construct(InfoRepositoryInterface $retailerRepository, CacheService $cacheService)
    {
        $this->decorator = $retailerRepository;
        $this->cacheService = $cacheService;
    }

    public function getAirLines(): array
    {
        return $this->decorator->getAirLines();
    }
}