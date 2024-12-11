<?php
namespace App\Tablet\Controllers;

use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Responses\Response;
use App\Tablet\Services\CacheService;
use Predis\Client;

/**
 * Class Controller
 * @package App\Tablet\Controllers
 */
class Controller
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var \Slim\Slim
     */
    protected $app;

    /**
     * @var CacheService
     */
    protected $cacheService;

    public function __construct()
    {
        global $app;
        $this->response = new Response(null, null, null);

        $this->cacheService = new CacheService(new Client([
            'host' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_HOST),
            'port' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PORT),
            'password' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PASS),
        ]));

        $this->app = $app;
    }
}