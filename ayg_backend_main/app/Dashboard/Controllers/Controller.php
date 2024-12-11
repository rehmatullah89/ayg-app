<?php
namespace App\Dashboard\Controllers;

use App\Dashboard\Helpers\ConfigHelper;
use App\Dashboard\Responses\Response;
use App\Dashboard\Services\CacheService;
use Predis\Client;

/**
 * Class Controller
 * @package App\Dashboard\Controllers
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
