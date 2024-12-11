<?php
namespace App\Delivery\Middleware;

use App\Delivery\Errors\ApiAuthError;
use App\Delivery\Errors\ErrorPrefix;
use App\Delivery\Errors\LoginFailureAppAccessError;
use App\Delivery\Errors\OtherApplicationError;
use App\Delivery\Mappers\ParseUserIntoUserMapper;
use App\Delivery\Responses\Response;

class ApiMiddleware
{
    public static function apiAuthWithoutSession(\Slim\Route $route)
    {
        // below is rewritten apiAuthWithoutSession($route) so it can throw new type of error;
        // apiAuthWithoutSession($route);

        // Generate route cache name
        setRouteCacheName($route);

        // Get Auth parameters
        $params = $route->getParams();

        // Validate if API Key is good, if so return the Parse Object Id
        // last parameter = t means that this uses api key salt for tablet app
        $error_array = validateAPIKey($params['apikey'], $params['epoch'], 0, false, 'd');

        if (!$error_array["isReadyForUse"]) {
            //json error with 5th parameter 1 - only logs error
            json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"], $error_array["error_severity"], 1);
            (new Response(null, null, new ApiAuthError(
                ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_MIDDLEWARE
            )))->returnAccessUnauthorizedJson();
        }

        $params = $route->getParams();
        unset($params['apikey']);
        unset($params['epoch']);
        unset($params['sessionToken']);

        // no user Entity in this middleware
        $route->setParams($params);
    }

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
        // below is rewritten apiAuth($route) so it can throw new type of error;

        // Generate route cache name
        setRouteCacheName($route);

        // Get Auth parameters
        $params = $route->getParams();

        // Ensure session token is not empty
        if (empty($params['sessionToken'])) {
            //json error with 5th parameter 1 - only logs error
            json_error("AS_029", "", "Empty session token", 2, 1);

            (new Response(null, null, new ApiAuthError(
                ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_MIDDLEWARE
            )))->returnAccessUnauthorizedJson();
        }

        $params['sessionToken'] = urldecode(urldecode($params['sessionToken']));
        // Validate if API Key is good, if so return the Parse Object Id
        // last parameter = t means that this uses api key salt for tablet app
        $error_array = validateAPIKey($params['apikey'], $params['epoch'], $params['sessionToken'], true, 'd');


        if ($error_array["error_code"] == "AS_1000"
            || $error_array["error_code"] == "AS_001") {
            //Internal server error
            json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"], $error_array["error_severity"], 1);

            (new Response(null, null, new OtherApplicationError(
                ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_MIDDLEWARE, $error_array
            )))->returnAccessInternalServerErrorJson();
        }

        if (!$error_array["isReadyForUse"]) {
            //json error with 5th parameter 1 - only logs error
            json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"], $error_array["error_severity"], 1);

            (new Response(null, null, new OtherApplicationError(
                ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_MIDDLEWARE, $error_array
            )))->returnAccessUnauthorizedJson();
        }

        $user = ParseUserIntoUserMapper::map($GLOBALS['user']);

        if ($user->isHasDeliveryAccess() !== true) {
            (new Response(null, null, new LoginFailureAppAccessError(
                ErrorPrefix::APPLICATION_TABLET . ErrorPrefix::CONTROLLER_MIDDLEWARE, []
            )))->returnAccessUnauthorizedJson();
        }

        $params = $route->getParams();
        unset($params['apikey']);
        unset($params['epoch']);
        unset($params['sessionToken']);

        $route->setParams(array_merge([$user], $params));
    }
}
