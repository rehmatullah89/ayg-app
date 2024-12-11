<?php

namespace App\Consumer\Services;

use App\Consumer\Helpers\ConfigHelper;
use Predis\Client;

/**
 * Class CacheService
 * @package App\Consumer\Services
 */
class CacheService extends Service
{
    /**
     * @var Client
     */
    private $cacheClient;

    /**
     * CacheService constructor.
     * @param Client $cacheClient
     */
    public function __construct(Client $cacheClient)
    {
        $this->cacheClient = $cacheClient;
    }

    /**
     * @param $key
     * @param $value
     * @param int $expirationTimestamp
     * @return void sets cache value for route
     *
     * sets cache value for a given key
     * if expirationTimestamp is different then 0, set cache to expire in given expirationTimestamp
     */
    public function setCache($key, $value, $expirationTimestamp)
    {
        if ($key === null) {
            return;
        }
        $value = serialize($value);
        $this->cacheClient->set($key, $value);

        if ($expirationTimestamp != 0) {
            $this->cacheClient->expireat($key, $expirationTimestamp);
        }
    }

    /**
     * @param $key
     * @return mixed|null
     *
     * gets cache value for a given key
     */
    public function getCache($key)
    {
        $value = $this->cacheClient->get($key);
        $value = unserialize($value);
        if ($value === false) {
            return null;
        }
        return $value;
    }

    /**
     * @param $key
     * @param $expiresInSeconds number of seconds till expired
     * @return int
     *
     * creates cache if not exists (with initial value 0)
     * increase counter in cache,
     * set ttl to given time
     */
    public function increaseAttempts($key, $expiresInSeconds)
    {
        if (!$this->cacheClient->exists($key)){
            $this->cacheClient->set($key,1);
        }else{
            return $this->cacheClient->incr($key);
        }

        $this->cacheClient->expire($key, $expiresInSeconds);
    }

}