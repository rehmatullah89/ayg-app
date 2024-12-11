<?php
namespace App\Consumer\Middleware;

use App\Consumer\Helpers\ApiHelper;
use App\Consumer\Helpers\UserAuthHelper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use App\Consumer\Services\UserAuthService;
use App\Consumer\Services\UserAuthServiceFactory;
use Slim\Route;

/**
 * Class ApiMiddleware
 * @package App\Consumer\Middleware
 *
 * This class is executed to authenticate the Endpoint Calls
 * It is executed before the call is reached to the Controller
 *
 * The methods inside the class uses predefined route authentication function
 * to authenticate route of Endpoint calls.
 */
class ApiMiddleware
{
    /**
     * @param \Slim\Route $route
     *
     * method for standard user authentication by get values:
     * apikey, epoch, sessionToken
     * for now it is a wrapper for global function and supports json_error error handlers
     * also sends User Entity as first parameter
     */
    public static function apiAuth(\Slim\Route $route)
    {
        apiAuth($route);

        $params = $route->getParams();
        $user = ParseUserIntoUserMapper::map($GLOBALS['user']);

        unset($params['apikey']);
        unset($params['epoch']);
        unset($params['sessionToken']);

        $route->setParams(array_merge([$user], $params));
    }

    /**
     * @param \Slim\Route $route
     *
     * method for standard Admin user authentication by get values:
     * apikey, epoch, sessionToken
     * for now it is a wrapper for global function and supports json_error error handlers
     * also sends User Entity as first parameter
     */
    public static function apiAuthAdmin(\Slim\Route $route)
    {
        apiAuthAdmin($route);

        $parseUser = $GLOBALS['user'];
        $user = ParseUserIntoUserMapper::map($parseUser);

        $params = $route->getParams();
        unset($params['apikey']);
        unset($params['epoch']);
        unset($params['sessionToken']);

        $route->setParams(array_merge([$user], $params));
    }


    /**
     * @param Route $route
     *
     * method for standard user authentication by get values:
     * apikey, epoch, sessionToken
     * for now it is a wrapper for global function and supports json_error error handlers
     * allows user to use API even without ActiveAccess
     * also sends User Entity as first parameter
     */
    public static function authWithoutActiveAccess(\Slim\Route $route)
    {
        apiAuthWithoutActiveAccess($route);
        $user = ParseUserIntoUserMapper::map($GLOBALS['user']);
        $params = $route->getParams();

        unset($params['apikey']);
        unset($params['epoch']);
        unset($params['sessionToken']);

        $route->setParams(array_merge([$user], $params));
    }
}
