<?php

function checkAPIKeyInLog($apikey, $objectId)
{

    $cacheKey = getAPIKeyCacheKey($objectId, $apikey);

    // Show error if APIKey was already used
    // And env variable for Load Testing is NOT set
    // if(doesCacheExist($cacheKey)) {

    // If the count of times this cache was used greater than equal to max allowed
    if (intval(getCache($cacheKey)) >= $GLOBALS['env_APIKeyMaxUsage']) {

        return $cacheKey;
    }

    return "";
}

function addAPIKeyToKey($apikey, $cacheKey, $objectId)
{

    if (empty($objectId)) {

        return false;
    }

    // If test is in progress, skip adding to the cache but instead put a fake key to simulate the operations
    if (intval($GLOBALS['env_AppRestAPIKeyForLoadTesting']) == 1) {

        incrAPIKeyUsage($cacheKey . "_loadtesting", $GLOBALS['env_RestAPITokenExpiryInMins'] * 60);
        // setCache($cacheKey . "_loadtesting", true, 0, $GLOBALS['env_RestAPITokenExpiryInMins']*60);
    } // Else Add API key to cache store for env_RestAPITokenExpiryInMins
    else {

        incrAPIKeyUsage($cacheKey, $GLOBALS['env_RestAPITokenExpiryInMins'] * 60);
        // setCache($cacheKey, true, 0, $GLOBALS['env_RestAPITokenExpiryInMins']*60);
    }

    return true;
}

// Standard apiAuth function
// Verifies API key for App
// Requires a valid sessionToken
// Verfies if the user is active and has access to the app its token is for (e.g. hasConsumerAccess = false will not allow delivery app session token to be used with consumer app)
function apiAuth(\Slim\Route $route)
{
    logCal();

    // Generate route cache name
    setRouteCacheName($route);

    // Get Auth parameters
    $params = $route->getParams();

    // Ensure session token is not empty
    if (empty($params['sessionToken'])) {

        json_error("AS_029", "", "Empty session token", 2);
    }

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], $params['sessionToken'], true);

    if (!$error_array["isReadyForUse"]) {

        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"]);
    }
}

// Standard apiAuthAdmin function
// Verifies API key for App
// Requires a valid sessionToken
// Verfies if the user is active and has access to the app its token is for (e.g. hasConsumerAccess = false will not allow delivery app session token to be used with consumer app)
function apiAuthAdmin(\Slim\Route $route)
{
    logCal();

    // Generate route cache name
    setRouteCacheName($route);

    // Get Auth parameters
    $params = $route->getParams();

    // Ensure session token is not empty
    if (empty($params['sessionToken'])) {

        json_error("AS_029", "", "Empty session token", 2);
    }

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], $params['sessionToken'], true);


    $hasAdminAccess = checkIfUserHasAdminAccess();
    if (!$hasAdminAccess) {
        json_error("AS_021", "", "Unauthorized Access", 2);
    }

    if (!$error_array["isReadyForUse"]) {

        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"]);
    }
}

// apiAuth function without active account
// Verifies API key for App
// Requires a valid sessionToken, but doesn't require an active account
// To be used for methods that can be accessed after logging in but not requring active account, e.g. phone validation, beta apply, etc
function apiAuthWithoutActiveAccess(\Slim\Route $route, $showError = true)
{
    logCal();

    // Generate route cache name
    setRouteCacheName($route);

    // Get Auth parameters
    $params = $route->getParams();

    // Ensure session token is not empty
    if (empty($params['sessionToken'])) {

        json_error("AS_029", "", "Empty session token", 2);
    }

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], $params['sessionToken'], false);

    if (!$error_array["isReadyForUse"]) {

        if ($showError) {

            json_error($error_array["error_code"], $error_array["error_message_user"],
                $error_array["error_message_log"], $error_array["error_severity"]);
        } else {

            // no exiting
            json_error($error_array["error_code"], $error_array["error_message_user"],
                $error_array["error_message_log"], $error_array["error_severity"], 1);

            return $error_array;
        }
    }
}

// apiAuth function that calls apiAuthWithoutActiveAccess
// But it won't exit on error, instead the errors will be returned
// Used in /user/checkin
function apiAuthWithoutActiveAccessNoExit(\Slim\Route $route)
{
    logCal();

    $error_array = apiAuthWithoutActiveAccess($route, false);

    // error_array not displayed
}

// apiAuth function withOUT sessionToken
// Verifies API key for App
// Does NOT require a valid sessionToken
// Does NOT require an active account
// To be used for methods that can be accessed without logging in, e.g. signup
function apiAuthWithoutSession(\Slim\Route $route)
{

    logCal();

    // Generate route cache name
    setRouteCacheName($route);

    // Get Auth parameters
    $params = $route->getParams();

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], 0, false);

    if (!$error_array["isReadyForUse"]) {

        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"]);
    }
}

// apiAuth function used for Website functions
// use WebAPI keys
// Does NOT require a valid sessionToken
function apiAuthForWebAPI(\Slim\Route $route)
{

    // Generate route cache name
    setRouteCacheName($route);

    // Get Auth parameters
    $params = $route->getParams();

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], 0, false, 'w');

    if (!$error_array["isReadyForUse"]) {

        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"]);
    }
}

// apiAuth function used for Website functions
// use OpsAPI keys
// Does NOT require a valid sessionToken
function apiAuthForOpsAPI(\Slim\Route $route)
{
    logCal();

    // Generate route cache name
    setRouteCacheName($route);

    // Get Auth parameters
    $params = $route->getParams();

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], 0, false, 'o');

    if (!$error_array["isReadyForUse"]) {

        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"]);
    }
}

// apiAuth function used for Website functions
// JMD
// use PartnerAPI keys
function apiAuthForPartnerAPI(\Slim\Route $route)
{
    logCal();

    // Generate route cache name
    setRouteCacheName($route);

    // Generate page nme
    setPartnerPageName($route);

    // Get Auth parameters
    $params = $route->getParams();

    if (empty($params['sessionToken'])) {

        // JMD
        $responseArray = array("json_resp_status" => -1, "json_resp_message" => "");
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
            ])
        );
    }

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], $params['sessionToken'], false, 'p');

    if (isset($error_array["isReadyForUse"])
        && !$error_array["isReadyForUse"]
    ) {

        $responseArray = array("json_resp_status" => -1, "json_resp_message" => "");

        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"], 1);

        // JMD
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
            ])
        );
    }
}

// Does NOT require a valid sessionToken
function apiAuthWithoutSessionForPartnerAPI(\Slim\Route $route)
{
    logCal();

    // Generate route cache name
    setRouteCacheName($route);

    // Generate page nme
    setPartnerPageName($route);

    // Get Auth parameters
    $params = $route->getParams();

    // Validate if API Key is good, if so return the Parse Object Id
    $error_array = validateAPIKey($params['apikey'], $params['epoch'], 0, false, 'p');

    if (isset($error_array["isReadyForUse"])
        && !$error_array["isReadyForUse"]
    ) {

        $responseArray = array("json_resp_status" => -1, "json_resp_message" => "");

        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"], 1);

        json_echo(
            setRouteCache([

                "jsonEncodedString" => json_encode($responseArray),
            ])
        );
    }
}

function setRouteCacheName(&$route)
{

    $GLOBALS['cacheSlimRouteKey'] = generateRouteCacheName($route);
}

function setPartnerPageName(&$route)
{

    $GLOBALS['partnerPageName'] = generatePartnerPageName($route);
}

// checks if logged user has Admin Access
// JMD
function checkIfUserHasAdminAccess()
{

    return $GLOBALS['user']->get('hasAdminAccess');
}

function validateAPIKey($apikey, $epoch, $sessionToken, $requiresActiveAccount = true, $appType = 'c')
{

    // Override Slim's error handling here since its the first function called for any Slim Route
    // JMD
    set_json_error_handling();

    $apiKeySalt = [];
    // don't use: "c", "d", "t", "a", "o", "p", "w"

    // used d for delivery app

    // for tablet app
    if ($appType == 't') {
        // Use REST API key salt
        $apiKeySalt[] = $GLOBALS['env_RetailerPOSAppRestAPIKeySalt'];
    } // for consumer app
    else {
        if ($appType == 'c') {
            // Use REST API key salt
            //$apiKeySalt = $GLOBALS['env_AppRestAPIKeySalt'];
            $apiKeySalt = \App\Consumer\Helpers\UserAuthHelper::getConsumerApiKeySalts();
        } // for partner app
        else {
            if ($appType == 'p') {
                // Use REST API key salt
                $apiKeySalt[] = $GLOBALS['env_PartnerRestAPIKeySalt'];
            } // for web app
            else {
                if ($appType == 'w') {

                    $apiKeySalt[] = $GLOBALS['env_WebRestAPIKeySalt'];
                } // for ops app
                else {
                    if ($appType == 'o') {

                        $apiKeySalt[] = $GLOBALS['env_OpsRestAPIKeySalt'];
                    }
                    else {
                        if ($appType == 'd') {

                            $apiKeySalt[] = $GLOBALS['env_DeliveryRestAPIKeySalt'];
                        }
                    }
                }
            }
        }
    }

    // Check the API formation is based on approved key
    $error_array = checkAPIFormation($apikey, $epoch, $apiKeySalt);

    if (!$error_array["isReadyForUse"]) {

        return $error_array;
    }

    // If session token is provided, login user
    // JMD
    if (!empty($sessionToken)
        && in_array($appType, ["c", "d", "t", "a"])
    ) {

        $pairs = parseSessionToken($sessionToken);

        // Required components of the session token not found
        if (count_like_php5($pairs) != 2) {

            return json_error_return_array("AS_023", "", "Session Token has incorrect number of substrings", 1);
        } else {

            $sessionTokenDecoded = $pairs[0];
            $typeOfLogin = $pairs[1];
        }

        // Verify type of login is one of the valid types
        if (!in_array($typeOfLogin, array("c", "d", "t", "a"))) {

            return json_error_return_array("AS_023", "", "Session Token has incorrect typeOfLogin value", 1);
        }

        $error_array = loginWithSessionToken($sessionTokenDecoded, $typeOfLogin, $requiresActiveAccount);

        if (!$error_array["isReadyForUse"]) {

            return $error_array;
        }

        // Check API key was not already used and then add it the log
        // We only do this if this is a nonUsercall
        $cacheKey = checkAPIKeyInLog($apikey, $GLOBALS['user']->getObjectId());

        if (!empty($cacheKey)) {

            return json_error_return_array("AS_003", "", "API Key already used.", 2);
        }

        // Add key to log so it can't be used again
        $status = addAPIKeyToKey($apikey, getAPIKeyCacheKey($GLOBALS['user']->getObjectId(), $apikey),
            $GLOBALS['user']->getObjectId());

        if (!$status) {

            return json_error_return_array("AS_016", "", "API Key couldn't be logged as no current user found.", 2);
        }
    }

    // Session Token for Partner access
    if (!empty($sessionToken)
        && strcasecmp($appType, "p") == 0
    ) {

        try {

            $page = $GLOBALS['partnerPageName'];
            $cognito_client = getCognitoClientObject();

            $partnerSession = partnerGetSession($cognito_client, $sessionToken, $page);

            ////////////////
            $GLOBALS['partner']['primaryGroup'] = $partnerSession->get('partner')->get('groupName');
            $GLOBALS['partner']['accessToken'] = $partnerSession->get('accessToken');
            $GLOBALS['partner']['usernameById'] = $cognito_client->getUserViaToken($GLOBALS['partner']['accessToken']);
            ////////////////

            // Session Token doesn't match the Access Token's user as stored in Parse
            // Should never happen
            if (strcasecmp($GLOBALS['partner']['usernameById'], $partnerSession->get('userId')) != 0) {

                return json_error_return_array("AS_1089", "", "Partner Portal - Invalid Token Use", 1);
            }

            $GLOBALS['partner']['formattedAttributes'] = $cognito_client->getUser($GLOBALS['partner']['usernameById']);
            if ($GLOBALS['partner']['formattedAttributes']["internal"]["Enabled"] == false) {

                partnerLogDisabledAccountAttempt($GLOBALS['partner']['usernameById'],
                    $GLOBALS['partner']['primaryGroup'], $page);
                return json_error_return_array("AS_1090", "", "Partner Portal - Account Disabled", 1);
            }
        } catch (CognitoIdentityProviderException $e) {

            if (strcasecmp($e->getAwsErrorMessage(), 'User is disabled') == 0) {

                partnerLogDisabledAccountAttempt($partnerSession->get('userId'), $GLOBALS['partner']['primaryGroup'],
                    $page);
            }

            return json_error_return_array("AS_1090", "",
                "Partner Portal - Account Disabled - " . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage(), 1);
        } catch (Exception $e) {

            return json_error_return_array("AS_1091", "",
                "Partner Portal - Session validation failed - " . json_encode($e->getMessage()), 2);
        }

        // Check API key was not already used and then add it the log
        // We only do this if this is a nonUsercall
        $cacheKey = checkAPIKeyInLog($apikey, md5($GLOBALS['partner']['usernameById']));

        if (!empty($cacheKey)) {

            return json_error_return_array("AS_1087", "", "Partner Portal - API Key already used.", 2);
        }

        // Add key to log so it can't be used again
        $status = addAPIKeyToKey($apikey, getAPIKeyCacheKey(md5($GLOBALS['partner']['usernameById']), $apikey),
            md5($GLOBALS['partner']['usernameById']));

        if (!$status) {

            return json_error_return_array("AS_1088", "",
                "Partner Portal - API Key couldn't be logged as no current user found.", 2);
        }

        partnerLogSuccessSession($sessionToken);
    }

    return json_error_return_array("");
}

function printPostVars(&$postVars, $error_code)
{

    $stringToEcho = '';
    foreach ($postVars as $key => $value) {

        $stringToEcho .= "/" . $key . "/" . $value;
    }

    json_error("AS_" . $error_code, "", "Post Vars with call: " . $stringToEcho, 3, 1);
}

function parseSessionToken($sessionToken)
{

    return explode("-", urldecode($sessionToken));
}

function checkAPIFormation($apikey, $epoch, $restAPIKeySalt)
{
    $microsecondsSince1970 = microtime(true) * 1000;

    $success = false;
    foreach ($restAPIKeySalt as $device=>$salt) {

        // If API Key doesn't match or epoch is older than env_RestAPITokenExpiryInMins minutes
        if ($apikey != md5($epoch . $salt) || $epoch < ($microsecondsSince1970 - ($GLOBALS['env_RestAPITokenExpiryInMins'] * 60 * 1000))) {

            continue;
        } // If one of them matches mark success and break
        else {

            $GLOBALS['currentlyOperatedDevice'] = $device;
            $success = true;
            break;
        }
    }


    if (!$success) {

        return json_error_return_array("AS_002", "", "API Key invalid! Invalid API secret used or stale epoch.", 1);
    }

    return json_error_return_array("", "", "");
}

function incrAPIKeyUsage($key, $expireInSeconds)
{

    $setCacheExpiry = false;

    // If cache doesn't exist then we will set the cache after incrementing it
    if (!doesCacheExist($key)) {

        $setCacheExpiry = true;
    }

    // Increment the counter
    $counterOfUsage = incrCache($key);

    // Set cache expiry time
    if ($setCacheExpiry) {

        setCacheExpire($key, $expireInSeconds);
    }

    return $counterOfUsage;
}

function logCal()
{
    if (getenv('env_EnvironmentDisplayCode') == 'PROD') {
        return true;
    }

    $logFile = __DIR__ . '/../storage/logs/api_call.php';

    if (!file_exists($logFile)) {
        $fHandle = fopen($logFile, 'w');
        fwrite($fHandle, '<?php' . "\n");
    } else {
        $fHandle = fopen($logFile, 'a');
    }

    unset($GLOBALS['api_call_logging_details']['start_microtime']);
    unset($GLOBALS['api_call_logging_details']['hash']);
    //$GLOBALS['api_call_logging_details'] = ''

    $startMicrotime = microtime(true);
    $hash = md5(microtime(true) . '-' . rand(0, 10000)) . uniqid('', true);

    fwrite(
        $fHandle,
        'hash ' . $hash . "\n" .
        'REQUEST: ' . $_SERVER['REQUEST_METHOD'] . ', date: ' . date('Y-m-d H:i:s') . "\n" .
        'start microtime: ' . $startMicrotime . "\n" .
        $_SERVER['REQUEST_URI'] . PHP_EOL . var_export($_REQUEST, true) . PHP_EOL);
    fclose($fHandle);

    $GLOBALS['api_call_logging_details']['start_microtime'] = $startMicrotime;
    $GLOBALS['api_call_logging_details']['hash'] = $hash;

}

function logResponse($json, $fullInfo=true, $notJsonForm=false)
{
    if (getenv('env_EnvironmentDisplayCode') == 'PROD') {
        return true;
    }

    $logFile = __DIR__ . '/../storage/logs/api_call.php';
    if (!file_exists($logFile)) {
        $fHandle = fopen($logFile, 'w');
        fwrite($fHandle, '<?php' . "\n");
    } else {
        $fHandle = fopen($logFile, 'a');
    }


    if (isset($GLOBALS['api_call_logging_details']['start_microtime'])) {
        $startMicrotime = $GLOBALS['api_call_logging_details']['start_microtime'];
        $endMicrotime = microtime(true);
        $spendMicrotime = $endMicrotime - $startMicrotime;
    } else {
        $endMicrotime = 'DONT KNOW';
        $spendMicrotime = 'DONT KNOW';
    }

    if (isset($GLOBALS['api_call_logging_details']['hash'])) {
        $hash = $GLOBALS['api_call_logging_details']['hash'];
    }else{
        $hash = 'DONT KNOW';
    }

if ($notJsonForm===true){

    }else{
    $json = json_encode(json_decode($json), JSON_PRETTY_PRINT);
}

    if ($fullInfo){
        fwrite(
            $fHandle,
            'hash ' . $hash . "\n" .
            'RESPONSE: date: ' . date('Y-m-d H:i:s') . "\n" .
            'end microtime: ' . $endMicrotime . "\n" .
            'it took: ' . $spendMicrotime . "s\n" .
            $_SERVER['REQUEST_URI'] . PHP_EOL . var_export($json, true) . PHP_EOL);
    }else{
        fwrite(
            $fHandle,
            $json
        );
    }

    fclose($fHandle);
}

?>
