<?php
namespace App\Consumer\Controllers;

use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Responses\Response;
use App\Consumer\Services\CacheService;
use App\Consumer\Services\CacheServiceFactory;
use Predis\Client;

/**
 * Class Controller
 * @package App\Delivery\Controllers
 */
class Controller
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * @var \Slim\Slim
     *
     * copy of slim application - needed as "global" variable to get post values
     */
    protected $app;

    /**
     * @var CacheService
     *
     * service used to set and get cache values - for full endpoint
     */
    protected $cacheService;

    /**
     * Controller constructor.
     */
    public function __construct()
    {
        global $app;

        // empty response without any success or error value
        $this->response = new Response(null, null, null);
        $this->cacheService = CacheServiceFactory::create();

        // Slim global variable
        $this->app = $app;
    }

    /**
     * @param $key
     * @void prints json success if key exists
     *
     * if key exists in the cache, it prints success response with value taken from cache
     * if key is equal to null, it does nothing
     */
    protected function returnRouteCacheValueIfExists($key)
    {
        if ($key === null) {
            return;
        }

        $cacheValue = $this->cacheService->getCache($key);
        if ($cacheValue !== null) {
            $this->response->setSuccess($cacheValue)->returnJson();
        }
    }
}
