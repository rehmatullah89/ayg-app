<?php

namespace App\Dashboard\Services;

use Predis\Client;

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
     * @return mixed
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
     * @param $value
     * @param int $expirationTimestamp
     * @return void sets cache value for route
     *
     * sets cache value for a given key
     * if expirationTimestamp is different then 0, set cache to expire in given expirationTimestamp
     */
    public function setCacheWithoutJsonEncoding($key, $value, $expirationTimestamp)
    {
        if ($key === null) {
            return;
        }
        $this->cacheClient->set($key, $value);

        if ($expirationTimestamp != 0) {
            $this->cacheClient->expireat($key, $expirationTimestamp);
        }
    }
}
