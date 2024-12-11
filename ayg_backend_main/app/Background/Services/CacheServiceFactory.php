<?php
namespace App\Background\Services;

use App\Background\Helpers\ConfigHelper;
use Predis\Client;


class CacheServiceFactory extends Service
{
    public static function create(): CacheService
    {
        return new CacheService(new Client([
            'host' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_HOST),
            'port' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PORT),
            'password' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PASS),
        ]));
    }
}
