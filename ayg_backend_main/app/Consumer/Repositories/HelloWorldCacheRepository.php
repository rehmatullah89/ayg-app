<?php

namespace App\Consumer\Repositories;

use App\Consumer\Services\CacheService;
use Predis\Client;

/**
 * Class HelloWorldCacheRepository
 * @package App\Consumer\Repositories
 *
 * This class is the demo of structure to show the working of Repository and specially caching the data from Parse
 *
 * The constructor of this class calls Parse Repository of respected Parse Class and the Cache Service object to create the cache
 */
class HelloWorldCacheRepository implements HelloWorldRepositoryInterface
{
    /**
     * @var HelloWorldRepositoryInterface
     *
     * class that implements HelloWorldRepositoryInterface
     */
    private $decorator;
    /**
     * @var Client
     * Redis client, already connected
     */
    private $cacheService;

    /**
     * HelloWorldCacheRepository constructor.
     * @param HelloWorldRepositoryInterface $helloWorldRepository
     * @param CacheService $cacheService
     */
    public function __construct(HelloWorldRepositoryInterface $helloWorldRepository, CacheService $cacheService)
    {
        $this->decorator = $helloWorldRepository;
        $this->cacheService = $cacheService;
    }

    /**
     * @param $userId
     * @return string
     *
     * In this case we directly call "inner repository interface - decorator"
     * But this is the place where call to the redis can be called,
     *
     */
    public function getById($userId)
    {
        return $this->decorator->getById($userId);
    }
}