<?php
namespace App\Consumer\Middleware;

use App\Consumer\Exceptions\UserAuthApiKeyCanNotBeSetAsUsedException;
use App\Consumer\Exceptions\UserAuthException;
use App\Consumer\Helpers\ApiHelper;
use App\Consumer\Helpers\UserAuthHelper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use App\Consumer\Services\UserAuthService;
use App\Consumer\Services\UserAuthServiceFactory;

class UserAuthMiddleware
{
    public static function onlyCustomSessionGuard(\Slim\Route $route)
    {
        $params = $route->getParams();
        if (!UserAuthHelper::isSessionTokenFromCustomSessionManagement($params['sessionToken'])) {
            return json_error("AS_005", "", "Incorrect API Call. ");
        }
    }

    public static function addCurrentUserAsFirstParam(\Slim\Route $route)
    {
        if (!isset($GLOBALS['user'])) {
            throw new UserAuthException('User not found');
        }
        $params = $route->getParams();
        array_unshift($params, ParseUserIntoUserMapper::map($GLOBALS['user']));
        $route->setParams($params);
    }

    public static function addCurrentUserAsFirstAndRemoveApiKeysFromParams(\Slim\Route $route)
    {
        if (!isset($GLOBALS['user'])) {
            throw new UserAuthException('User not found');
        }
        $params = $route->getParams();

        unset($params['apikey']);
        unset($params['epoch']);
        unset($params['sessionToken']);

        array_unshift($params, ParseUserIntoUserMapper::map($GLOBALS['user']));


        //json_error(json_encode($params),'','');

        $route->setParams($params);
    }

    public static function clearUserAndSessionAndCreateNewOneWhenSessionTokenNotSet(\Slim\Route $route)
    {
        $params = $route->getParams();
        if ($params['sessionToken'] !== "0") {
            return;
        }

        global $app;
        $deviceArray = $app->request()->post('deviceArray'); // URL Decoded before using

        // create a new session for the user
        $userAuthService = UserAuthServiceFactory::create();
        $userSession = $userAuthService->createUserSessionForNewUser(decodeDeviceArray($deviceArray));

        $params['sessionToken'] = $userSession->getToken();
        $route->setParams($params);
    }

    public static function apiAuthWithFullAccessSessionOnly(\Slim\Route $route)
    {
        $params = $route->getParams();
        if (!UserAuthHelper::isSessionTokenFromCustomSessionManagement($params['sessionToken'])) {
            apiAuth($route);
        } else {
            self::authWithNewSessionToken($route, $params, true);
        }
    }

    public static function apiAuthWithoutActiveAccessNoExitWhenNoPhone(\Slim\Route $route)
    {
        $params = $route->getParams();
        if (!UserAuthHelper::isSessionTokenFromCustomSessionManagement($params['sessionToken'])) {
            apiAuthWithoutActiveAccessNoExit($route);
        } else {
            self::authWithNewSessionToken($route, $params, false, false);
        }
    }


    // @todo check if really it is good with respect to changes
    public static function apiAuthWithoutActiveAccess(\Slim\Route $route)
    {
        $params = $route->getParams();
        if (!UserAuthHelper::isSessionTokenFromCustomSessionManagement($params['sessionToken'])) {
            apiAuthWithoutActiveAccess($route);
        } else {
            self::authWithNewSessionToken($route, $params, false);
        }
    }

    public static function apiAuthWithoutSession(\Slim\Route $route)
    {
        $params = $route->getParams();
        if (!UserAuthHelper::isSessionTokenFromCustomSessionManagement($params['sessionToken'])) {
            apiAuthWithoutSession($route);
        } else {
            // only checks correctness of api keys
            $apiKeySalt = $GLOBALS['env_AppRestAPIKeySalt'];

            if (!ApiHelper::isApiFormationCorrect($params['apikey'], $params['epoch'], UserAuthHelper::getConsumerApiKeySalts())) {
                json_error("AS_002", "", "API Key invalid! Invalid API secret used or stale epoch.", 1);
            }
        }
    }


    public static function apiAuth(\Slim\Route $route)
    {
        $params = $route->getParams();

        if (!UserAuthHelper::isSessionTokenFromCustomSessionManagement($params['sessionToken'])) {
            apiAuth($route);
        } else {
            self::authWithNewSessionToken($route, $params, false);
        }
    }

    private static function authWithNewSessionToken(
        $route,
        array $params,
        bool $fullAccessRequired,
        bool $exitWhenNoPhone = true
    ) {
        logCal();
        setRouteCacheName($route);


        // new version of middleware is now only used for consumer users
        //$apiKeySalt = $GLOBALS['env_AppRestAPIKeySalt'];

        if (!ApiHelper::isApiFormationCorrect($params['apikey'], $params['epoch'], UserAuthHelper::getConsumerApiKeySalts())) {
            json_error("AS_002", "", "API Key invalid! Invalid API secret used or stale epoch.", 1);
        }

        $userAuthService = UserAuthServiceFactory::create();
        try {
            $activeUserSession = $userAuthService->loginWithNewSessionToken($params['sessionToken']);


            if ($fullAccessRequired && !$activeUserSession->hasFullAccess()) {
                return json_error("AS_015", "", "Not a valid session", 1);
            }

            //@todo think about checking if it is not empty
            $userAuthService->pullAndStoreParseUserIntoGlobals($activeUserSession);


            if ($activeUserSession->isSessionDeviceActive() === false) {
                // handle session devices for switching between parse session management int o
                $userAuthService->activateCustomSessionAndDeactivateParse($activeUserSession);
            } else {
                // check session device correctness
                if ($userAuthService->doesSessionDeviceEntryExist($activeUserSession) === false) {
                    $userAuthService->logout($activeUserSession->getToken());
                    return json_error_return_array("AS_015", "", "Not a valid session - No row in SessionDevice", 1);
                }
            }

        } catch (UserAuthException $exception) {
            $userAuthService->logout($params['sessionToken']);
            return json_error("AS_015", "", "Not a valid session", 1);
        }

        if (!self::isApiFormationFreeToBeUsed($params['apikey'], $GLOBALS['user']->getObjectId())) {
            json_error("AS_003", "", "API Key already used.", 2);
        }

        // Add key to log so it can't be used again
        if (!self::setApiKeyAsUsed($params['apikey'], $GLOBALS['user']->getObjectId())) {
            json_error("AS_016", "", "API Key couldn't be logged as no current user found.", 2);
        }

        if (!self::isUserActiveAndNotBlocked($exitWhenNoPhone)) {
            json_error("AS_016", "", "API Key couldn't be logged as no current user found.", 2);
        }

        try {
            $userAuthService->pullAndStoreParseUserPhonesIntoGlobals();
        } catch (UserAuthException $exception) {
            $errorArray = json_decode($exception->getMessage());
            json_error($errorArray['error_code'], "", $errorArray['error_message_log'],
                $errorArray['error_severity']);
        }
    }

    private static function isApiFormationFreeToBeUsed(string $apikey, string $parseUserObjectId): bool
    {
        $cacheKey = checkAPIKeyInLog($apikey, $parseUserObjectId);

        if (!empty($cacheKey)) {
            false;
        }
        return true;
    }

    private static function setApiKeyAsUsed(string $apiKey, string $parseUserObjectId): bool
    {
        return addAPIKeyToKey($apiKey, getAPIKeyCacheKey($parseUserObjectId, $apiKey), $parseUserObjectId);
    }

    private static function isUserActiveAndNotBlocked(bool $exitWhenNoPhone)
    {
        $error_array = checkUserAccountReadyStatus(UserAuthService::CONSUMER_TYPE_SHORT, true);

        if ($error_array["isReadyForUse"] == false) {
            // no phones
            if ($error_array['error_code'] !== 'AS_024' || $exitWhenNoPhone) {
                throw new UserAuthException(json_encode($error_array));
            }
        }
        return true;
    }
}


