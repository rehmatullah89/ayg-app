<?php

use Parse\ParseUser;
use Parse\ParseObject;
use Parse\ParseGeoPoint;
use App\Tablet\Helpers\QueueMessageHelper;
use App\Background\Repositories\PingLogMysqlRepository;


function loginWithSessionToken($sessionToken, $typeOfLogin, $requiresActiveAccount)
{

    $GLOBALS['user'] = array();
    $GLOBALS['userPhones'] = array();

    try {

        $GLOBALS['user'] = ParseUser::become($sessionToken);

        if (empty($GLOBALS['user'])) {

            throw new Exception("User object failed to be pulled.", -1);
        }
    } catch (Exception $ex) {


        if ($typeOfLogin == 't' && intval($ex->getCode()) == 999999 && $ex->getMessage() == 'Parse Error - Empty response') {
            $sessionHash = md5($GLOBALS['env_SessionTokenHashSalt'] . $sessionToken);
            // get list
            $array = hGetCache('__PARSE_USER_BECOME_FAIL', $sessionHash, 1);
            $array[] = time();
            // add to redis
            hSetCache('__PARSE_USER_BECOME_FAIL', $sessionHash, $array, 1);

            // if last 3 fails was in 3 x ping interval (+ 5 seconds buffer), it means that backend (parse) is down
            if ((count($array) >= 3) && ($array[count($array) - 3] > time() - 3 * $GLOBALS['env_TabletAppDefaultPingIntervalInSecs'] - 5)) {
                json_error("AS_9001", 'API maintenance is on.', "API maintenance is on.", 3);
            }

            json_echo(json_encode(['please_wait' => true]));
        }
        if ($typeOfLogin == 'c' && intval($ex->getCode()) == 999999 && $ex->getMessage() == 'Parse Error - Empty response') {

            // @todo - think how to handle consumer
            return json_error_return_array("AS_015", "",
                "Not a valid session - " . $ex->getMessage() . " - " . $ex->getCode() . " - ", 1);
        }

        // Send a non-auth failure error when we have a bad request
        // $ex->getCode() = -1 = Bad Request
        // $ex->getCode() = 6 = Could not resolve host
        // $ex->getCode() = 35 = RSA Bad signature
        // $ex->getCode() = 52 = Empty reply from server 
        if (intval($ex->getCode()) == -1
            || intval($ex->getCode()) == 6
            || intval($ex->getCode()) == 35
            || intval($ex->getCode()) == 52
        ) {

            // log connect failure
            $logRetailerConnectFailureMessage = QueueMessageHelper::getLogRetailerConnectFailureMessage(encryptStringInMotion($sessionToken),
                time());

            try {

                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue->sendMessage(
                    $logRetailerConnectFailureMessage, 0
                );
            } catch (Exception $ex2) {

                // non-exiting warning
                json_error("AS_036", "", "User Connect Failure log failed" . $ex2->getMessage(), 2, 1);
            }

            return json_error_return_array("AS_1000", "",
                "Session Validation Failed - " . $ex->getMessage() . " - " . $ex->getCode(), 2);
        }

        // $ex->getCode() = 209 = Session invalid
        // User being logged out
        // Log event
        if ($ex->getCode() == 209 && strcasecmp($typeOfLogin, 'c') == 0) {

            if ($GLOBALS['env_LogUserActions']) {

                try {

                    $sessionDevice = parseExecuteQuery(array("sessionTokenRecall" => $sessionToken), "SessionDevices",
                        "", "", array("user"), 1);

                    if (count_like_php5($sessionDevice) > 0) {

                        // Logout session
                        $sessionDevice->set("isActive", false);
                        $sessionDevice->set("sessionEndTimestamp", time());
                        $sessionDevice->save();

                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_invalid_session",
                                "content" =>
                                    array(
                                        "objectId" => $sessionDevice->get('user')->getObjectId(),
                                        "timestamp" => time()
                                    )
                            )
                        );
                    }
                } catch (Exception $ex2) {

                    $response = json_decode($ex2->getMessage(), true);
                    json_error($response["error_code"], "",
                        "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                }
            }
        }

        /*
        // if session is not valid due to ParseClient::_request('GET', 'users/me', $sessionToken); empty response
        $shouldReturnAS15error = true;
        if ($typeOfLogin=='t' && $ex->getCode()==2 && $ex->getMessage()=='Invalid argument supplied for foreach()'){
            // add log about fail login

            $currentTimestamp = time();
            $filePath = __DIR__.'/../storage/parse_become_errors/'.$sessionToken.'.txt';
            if (!file_exists($filePath)){
                $shouldReturnAS15error = false;
            }else{
                $errorTimeStamps = file($filePath, FILE_IGNORE_NEW_LINES);
                if (count($errorTimeStamps)==1){
                    $shouldReturnAS15error = false;
                }else{
                    // if 2 last errors was in last 3 minutes, logout
                    $timestamp1 = $errorTimeStamps[count($errorTimeStamps) - 1];
                    $timestamp2 = $errorTimeStamps[count($errorTimeStamps) - 2];
                    if ($timestamp1 > $currentTimestamp - 180 && $timestamp2 > $currentTimestamp - 180){
                        $shouldReturnAS15error = true;
                    }else{
                        $shouldReturnAS15error = false;
                    }
                }
            }

            if (!isset($errorTimeStamps)){
                $errorTimeStamps=[];
            }

            $errorTimeStamps[]=$currentTimestamp;
            file_put_contents($filePath, implode(PHP_EOL, $errorTimeStamps));
        }
        */
        return json_error_return_array("AS_015", "",
            "Not a valid session - " . $ex->getMessage() . " - " . $ex->getCode() . " - ", 1);

        //if ($shouldReturnAS15error){
        //    return json_error_return_array("AS_015", "","Not a valid session - " .$ex->getMessage() . " - " .$ex->getCode(). " - ",1);
        //}else{
        //    return json_error_return_array("AS_015", "","TEST Not a valid session (request skipped) - " .$ex->getMessage() . " - " .$ex->getCode(). " - ",1,1);
        //}

        //json_echo(json_encode(['please_wait'=>true]));
    }

    // Check if this session is still active in SessionDevices
    $sessionDevice = parseExecuteQuery(array(
        "user" => $GLOBALS['user'],
        "sessionTokenRecall" => $sessionToken,
        "isActive" => true
    ), "SessionDevices", "", "", array(), 1, false, [
        "cacheKey" => getCacheKeyForSessionDevice($GLOBALS['user']->getObjectId(), $sessionToken),
        "ttl" => "EOW",
        "cacheOnlyWhenResult" => true
    ]);

    // No row found
    if (count_like_php5($sessionDevice) == 0) {

        try {

            logoutUser($GLOBALS['user']->getObjectId());
        } catch (Exception $ex) {

            json_error("AS_443", "",
                "Old session clean up failed for user = " . $GLOBALS['user']->getObjectId() . ' - ' . $ex->getMessage(),
                2);
        }

        return json_error_return_array("AS_015", "", "Not a valid session - No row in SessionDevice", 1);
    }

    // Pull all verified phones
    if (strcasecmp($typeOfLogin, 'c') == 0) {

        $GLOBALS['userPhones'] = parseExecuteQuery(array("user" => $GLOBALS['user'], "phoneVerified" => true),
            "UserPhones", "", "", array(), 1, false, [
                "cacheKey" => getCacheKeyForUserPhone($GLOBALS['user']->getObjectId()),
                "ttl" => "EOW",
                "cacheOnlyWhenResult" => true
            ]);
    }

    // Verify if the user is active, else give an error
    $error_array = checkUserAccountReadyStatus($typeOfLogin, $requiresActiveAccount);

    if ($error_array["isReadyForUse"] == false) {

        if ($error_array["error_severity"] == 1) {

            // Logout user
            logoutUser($GLOBALS['user']->getObjectId());
        }

        return $error_array;
    }

    return json_error_return_array("");
}

function generatePasswordHash($password)
{

    return md5($password . $GLOBALS['env_PasswordHashSalt']);
}

function loginUser($email, $password, $typeOfLogin = 'c', $showError = true)
{

    // Login user to the values can be set
    try {
        $GLOBALS['user'] = ParseUser::logIn(createUsernameFromEmail($email, $typeOfLogin),
            generatePasswordHash($password));
    } // Login failed
    catch (Exception $ex) {

        return json_error_return_array("AS_020", "Your username or password is incorrect. Please check and try again.",
            "User authentication failed with username and password (" . $email . ") - " . $ex->getMessage(), 3);
    }

    // Check if user is locked
    if ($GLOBALS['user']->get("isLocked") == true) {

        return json_error_return_array("AS_034",
            "Your account has been locked. Please contact customer service for support. Please visit atyourgate.com",
            "", 2);
    }

    // Pull all verified phones
    // $GLOBALS['userPhones'] = parseExecuteQuery(array("user" => $GLOBALS['user'], "phoneVerified" => true), "UserPhones");

    // Get hasAccess flag values for the typeOfLogin requested
    // $hasAccess = strcasecmp(strtolower($typeOfLogin), "c") == 0 ? $GLOBALS['user']->get('hasConsumerAccess') : $GLOBALS['user']->get('hasDeliveryAccess');

    // If the password was correct, check if the user is active, and has access to the app that is requesting sign in
    /*
    $responseArray = checkUserAccountReadyStatus($typeOfLogin, true);

    if($responseArray["isReadyForUse"] == false) {

        if($responseArray["error_severity"] == 1) {

            // Logout user
            logout User();
        }

        if($showError) {

            // Show error
            json_error($responseArray["error_code"], $responseArray["error_message_user"], $responseArray["error_message_log"], $responseArray["error_severity"]);
        }

        // Login failed
        return $responseArray;
    }
    */

    // Else return authenticated user object
    return [];
}


// this function is triggered when user is successfully signed in,
// created new entries in User devices, Session Devices and checkin user
function afterSignInSuccess($email, $type, $deviceArrayDecoded)
{

    $currentSessionToken = ParseUser::getCurrentUser()->getSessionToken();
    $responseArray = array("u" => $currentSessionToken . '-' . $type);

    //////////////////////////////////////////////////////////////////////////////////////////
    // Logout all other sessions for this user
    // List all existing active Session Devices
    $objSessionDevices = parseExecuteQuery([
        "isActive" => true,
        "__NE__sessionTokenRecall" => $currentSessionToken,
        "user" => $GLOBALS['user']
    ], "SessionDevices");

    // Traverse through all SessionDevice
    // Expire existing Session Devices and logout all sessions
    $sessionsCleared = [];
    foreach ($objSessionDevices as $obj) {

        $obj->set("sessionEndTimestamp", time());
        $obj->set("isActive", false);
        $obj->save();

        if (!in_array($currentSessionToken, $sessionsCleared)) {

            try {

                if (\App\Consumer\Helpers\UserAuthHelper::isSessionTokenFromCustomSessionManagementWithoutTypeIndicator($obj->get('sessionTokenRecall'))){
                    // set session as inactive
                    // set Parse SessionDevices as inactive
                        $userAuthService = \App\Consumer\Services\UserAuthServiceFactory::create();

                        $userAuthService->deactivateCustomSessionAndDeactivateParseSessionDevicesBySessionTokenWithoutTypeIndicator($obj->get('sessionTokenRecall'));
                }else{
                    logoutUser($GLOBALS['user']->getObjectId(), $obj->get('sessionTokenRecall'), false,
                        $currentSessionToken);
                }
            } catch (Exception $ex) {

                json_error("AS_443", "",
                    "Old session clean up failed for signin email = " . $email . ' sessionToken = ' . $obj->get('sessionTokenRecall') . " - " . $ex->getMessage()
                    . $ex->getLine()
                    . $ex->getFile()
                    . $ex->getTraceAsString()

                    ,
                    2, 1);
            }
        }

        $sessionsCleared[] = $currentSessionToken;
    }
    //////////////////////////////////////////////////////////////////////////////////////////

        // Create row in UserDevices
        $objUserDevice = createUserDevice($GLOBALS['user'], $deviceArrayDecoded);

        // Create row in SessionDevices
        $objUserSessionDevice = createSessionDevice($objUserDevice, $deviceArrayDecoded, $currentSessionToken,
            $GLOBALS['user']);

        // Check and Create a SQS action if we need to create a new device in OneSignal
        createOneSignalViaQueue($objUserDevice);

        // Update checkin time
        // This ensures the subsequent /checkin will skip the Device updation step
        setCacheCheckinInfo($GLOBALS['user']->getObjectId(), [$objUserDevice, $objUserSessionDevice]);

        // sign in succeeded, clear sign in attempts
        clearCountOfAttempt("SIGNIN", $email);



    return $responseArray;
}


function logoutUser(
    $userObjectId,
    $sessionTokenProvided = "",
    $clearSessionDevices = true,
    $currentToken = "",
    $userSignedOut = false
) {

    // Delete last know checkin info
    delCacheCheckinInfo($userObjectId);

    // If not token provided, get it for the current user
    if (empty($sessionTokenProvided)) {

        $sessionToken = ParseUser::getCurrentUser()->getSessionToken();
        $GLOBALS['user'] = array();
        $GLOBALS['userPhones'] = array();
    } else {
        $sessionToken = $sessionTokenProvided;

        // If a session token was provided then become that session so it can be logged out
        ParseUser::become($sessionToken);
    }

    // Deactive SessionDevice row
    if ($clearSessionDevices) {

        logoutUserFromSessionDevice($sessionToken, $userSignedOut);
    }

    // Logout user
    ParseUser::logOut();

    // Assume the original token
    if (!empty($currentToken)) {

        // If a current session token was provided then become that session so user can remain logged in with this token
        ParseUser::become($currentToken);
    }
}

function logoutUserFromSessionDevice($sessionToken, $userSignedOut = false)
{

    $objUpdateSessionDevice = parseExecuteQuery(array("sessionTokenRecall" => $sessionToken), "SessionDevices");

    // End the session for the Device
    foreach ($objUpdateSessionDevice as $obj) {

        $obj->set("sessionEndTimestamp", time());

        if ($userSignedOut == true) {

            $obj->set("userRequestedSignOut", true);
        }

        $obj->set("isActive", false);
        $obj->save();
    }
}

function checkUserAccountReadyStatus($typeOfLogin, $requiresActiveAccount)
{

    // If account is locked
    if ($GLOBALS['user']->get('isLocked') == true) {

        return json_error_return_array("AS_028", "",
            "Login failed" . $GLOBALS['user']->get('username') . " - Active Status check - Account Locked", 2);
    }

    $hasAccess = false;
    // Get hasAccess flag values for the typeOfLogin requested
    if (!$hasAccess && (strcasecmp(strtolower($typeOfLogin),
                "c") === 0 && $GLOBALS['user']->get('hasConsumerAccess'))
    ) {
        $hasAccess = true;
    }
    if (!$hasAccess && (strcasecmp(strtolower($typeOfLogin),
                "d") === 0 && $GLOBALS['user']->get('hasDeliveryAccess'))
    ) {
        $hasAccess = true;
    }
    if (!$hasAccess && (strcasecmp(strtolower($typeOfLogin),
                "t") === 0 && $GLOBALS['user']->get('hasTabletPOSAccess'))
    ) {
        $hasAccess = true;
    }

    // check has access to the app that is requesting sign in
    if ($hasAccess == false) {

        return json_error_return_array("AS_027",
            "Login failed" . $GLOBALS['user']->get('username') . " - Active Status check - App access", 1);
    } // else check if the user is active and we require this account to be active for this API call
    else {
        if ($requiresActiveAccount == true) {

            // If account is NOT active and no phones are verified; forces user to see add phone screen
            if (count_like_php5($GLOBALS['userPhones']) == 0 && $GLOBALS['user']->get('isActive') != true) {

                return json_error_return_array("AS_024", "",
                    "Login failed" . $GLOBALS['user']->get('username') . "" . $GLOBALS['user']->getObjectId() . " - Active Status check - No Active Phone",
                    2);
            }

            // Check if the user is active
            if ($GLOBALS['user']->get('isActive') != true) {

                return json_error_return_array("AS_026", "",
                    "Login failed" . $GLOBALS['user']->get('username') . " - Active Status check - isActive", 2);
            }

            // Check if beta is approved
            if ($GLOBALS['user']->get('isBetaActive') != true) {

                return json_error_return_array("AS_025", "",
                    "Login failed" . $GLOBALS['user']->get('username') . " - Active Status check - Beta not activated",
                    2);
            }
        }
    }

    return json_error_return_array("", "", "", 0);
}

function isRegisteredUserByEmail($email, $type)
{

    list($hasConsumerAccess, $hasDeliveryAccess, $hasTabletPOSAccess) = getAccountTypeFlags($type);

    // Based on which type value was provided, check if the account for that type exists for the given email
    if ($hasConsumerAccess) {
        $objParseQueryUserResults = parseExecuteQuery(array(
            "username" => createUsernameFromEmail($email, $type),
            "hasConsumerAccess" => $hasConsumerAccess
        ), "_User", '', '', [], 10000, true);

        if (count_like_php5($objParseQueryUserResults) == 0) {
            $objParseQueryUserResults = parseExecuteQuery(array(
                "email" => $email,
                "hasConsumerAccess" => $hasConsumerAccess
            ), "_User", '', '', [], 10000, true);
        }

    }
    if ($hasDeliveryAccess) {
        $objParseQueryUserResults = parseExecuteQuery(array(
            "username" => createUsernameFromEmail($email, $type),
            "hasDeliveryAccess" => $hasDeliveryAccess
        ), "_User", '', '', [], 10000, true);
    }

    // If no record is found, return false
    if (count_like_php5($objParseQueryUserResults) == 0) {

        return false;
    }

    return true;
}

function createUsernameFromEmail($email, $type)
{

    list($hasConsumerAccess, $hasDeliveryAccess, $hasTabletPOSAccess) = getAccountTypeFlags($type);

    // Based on which type value was provided, return username
    if ($hasConsumerAccess) {
        return $email . "-c";
    }
    if ($hasDeliveryAccess) {
        return $email . "-d";
    }
    if ($hasTabletPOSAccess) {
        return $email . "-t";
    }
}

function createEmailFromUsername($username, $userParseObject = null)
{
    $email = substr($username, 0, -2);
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    if ($userParseObject === null) {
        throw new Exception('Can not get email');
    }

    $userParseObject->fetch(true);
    $email = $userParseObject->get('email');

    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }

    throw new Exception('Can not get email '.$email .' '.$userParseObject->getObjectId());
}

function createTypeFromUsername($username)
{

    return substr($username, -1);
}

function getAccountTypeFlags($type)
{

    // Consumer signup
    if (strcasecmp(strtolower($type), "c") == 0) {

        $hasConsumerAccess = true;
        $hasDeliveryAccess = false;
        $hasTabletPOSAccess = false;
    } // Delivery signup
    elseif (strcasecmp(strtolower($type), "d") == 0) {

        $hasConsumerAccess = false;
        $hasDeliveryAccess = true;
        $hasTabletPOSAccess = false;
    } // Tablet signup
    elseif (strcasecmp(strtolower($type), "t") == 0) {

        $hasConsumerAccess = false;
        $hasDeliveryAccess = false;
        $hasTabletPOSAccess = true;
    } // no access signup
    else {

        $hasConsumerAccess = false;
        $hasDeliveryAccess = false;
        $hasTabletPOSAccess = false;
    }

    return array($hasConsumerAccess, $hasDeliveryAccess, $hasTabletPOSAccess);
}

/*
function findParseUserByUserid( $userid ) {

	$objParseQueryUserResults = parseExecuteQuery(array("objectId" => $userid, "isActive" => true), "_User");

	if(count_like_php5($objParseQueryUserResults) == 0) {

		json_error("AS_010", "", "Parse User Id provided is invalid.", 2);
	}

	return $objParseQueryUserResults[0];
}
*/

function isValidDeviceArray(&$deviceArray)
{
    if(!isset($deviceArray['isWeb']) || $deviceArray['isWeb'] == null || empty($deviceArray['isWeb'])){
        $deviceArray['isWeb'] = "0";
    }

    if (!is_array($deviceArray)
        || count_like_php5($deviceArray) != 15
    ) {

        return false;
    }

    if (!isset($deviceArray["appVersion"]) || empty($deviceArray["appVersion"])) {

        return false;
    } else {
        if (!isset($deviceArray["isIos"]) || empty_zero_allowed($deviceArray["isIos"]) || !is_numeric($deviceArray["isIos"])) {

            return false;
        } else {
            if (!isset($deviceArray["isAndroid"]) || empty_zero_allowed($deviceArray["isAndroid"]) || !is_numeric($deviceArray["isAndroid"])) {

                return false;
            } else {
                    if (!isset($deviceArray["isWeb"]) || empty_zero_allowed($deviceArray["isWeb"]) || !is_numeric($deviceArray["isWeb"])) {

                        return false;
                    } else {
                        if (!isset($deviceArray["deviceType"]) || empty($deviceArray["deviceType"])) {

                            return false;
                        } else {
                            if (!isset($deviceArray["deviceModel"]) || empty($deviceArray["deviceModel"])) {

                                return false;
                            } else {
                                if (!isset($deviceArray["deviceOS"]) || empty($deviceArray["deviceOS"])) {

                                    return false;
                                } else {
                                    if (!isset($deviceArray["deviceId"]) || empty($deviceArray["deviceId"])) {

                                        return false;
                                    } else {
                                        if (!isset($deviceArray["country"]) || empty($deviceArray["country"])) {

                                            return false;
                                        } else {
                                            if (!isset($deviceArray["isOnWifi"]) || empty_zero_allowed($deviceArray["isOnWifi"] || !is_numeric($deviceArray["isOnWifi"]))) {

                                                return false;
                                            } else {
                                                if (!isset($deviceArray["isPushNotificationEnabled"]) || empty_zero_allowed($deviceArray["isPushNotificationEnabled"] || !is_numeric($deviceArray["isPushNotificationEnabled"]))) {

                                                    return false;
                                                } else {
                                                    if (!isset($deviceArray["pushNotificationId"]) || empty_zero_allowed($deviceArray["pushNotificationId"])) {

                                                        return false;
                                                    } else {
                                                        if (!isset($deviceArray["timezoneFromUTCInSeconds"]) || empty_zero_allowed($deviceArray["timezoneFromUTCInSeconds"])) {

                                                            return false;
                                                        } else {
                                                            if (!isset($deviceArray["geoLatitude"]) || empty_zero_allowed($deviceArray["geoLatitude"]) || !is_float_value($deviceArray["geoLatitude"])) {

                                                                return false;
                                                            } else {
                                                                if (!isset($deviceArray["geoLongitude"]) || empty_zero_allowed($deviceArray["geoLongitude"]) || !is_float_value($deviceArray["geoLongitude"])) {

                                                                    return false;
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
            }
        }
    }

    // Add IP address to the array
    $deviceArray["IPAddress"] = getenv('HTTP_X_FORWARDED_FOR') . ' ~ ' . getenv('REMOTE_ADDR');

    try {

        $nearGeoPoint = new ParseGeoPoint(floatval($deviceArray["geoLatitude"]),
            floatval($deviceArray["geoLongitude"]));
    } catch (Exception $ex) {

        return false;
    }

    return true;
}

function randomGreeting($timezoneFromUTCInSeconds = "", $allowEmptyGreeting = true, $withDeliminator = true)
{

    $smsGreetingWithDeliminator = array(
        "0" => "Hi,",
        "1" => "Hi,",
        "2" => "Hi,",
        "3" => "Hi,",
        "4" => "Hi,",
        "5" => "Hi,",
        "6" => "Hi,",
        "7" => "Hi,",
        "8" => "Hi,",
    );

    $smsGreetingWithoutDeliminator = array(
        "0" => "Hi",
        "1" => "Hi",
        "2" => "Hi",
        "3" => "Hi",
        "4" => "Hi",
        "5" => "Hi",
        "6" => "Hi",
        "7" => "Hi",
        "8" => "Hi",
    );

    if ($withDeliminator) {

        $smsGreeting = $smsGreetingWithDeliminator;
        $smsDefaultGreeting = $smsGreetingWithDeliminator[0];
    } else {

        $smsGreeting = $smsGreetingWithoutDeliminator;
        $smsDefaultGreeting = $smsGreetingWithoutDeliminator[0];
    }

    if ($allowEmptyGreeting) {

        $j = count_like_php5($smsGreeting) - 1;
        for ($i = $j; $i < $j + 5; $i++) {

            $smsGreeting[$i] = "";
        }
    }

    if (!empty($timezoneFromUTCInSeconds)) {

        $currentTimezone = date_default_timezone_get();
        date_default_timezone_set('UTC');

        if (date("G", time() + $timezoneFromUTCInSeconds) > 17) {
            $smsGreeting[0] = "Good evening,";
        }     // After 5pm
        else {
            if (date("G", time() + $timezoneFromUTCInSeconds) > 12) {
                $smsGreeting[0] = "Good afternoon,";
            }   // After 12pm
            else {
                if (date("G", time() + $timezoneFromUTCInSeconds) > 6) {
                    $smsGreeting[0] = "Good morning,";
                }     // After 6am
                else {
                    if (date("G", time() + $timezoneFromUTCInSeconds) > 3) {
                        $smsGreeting[0] = "Mornin' Sunshine!";
                    } // REALLY early
                    else {
                        $smsGreeting[0] = "Hi";
                    }
                }
            }
        }

        date_default_timezone_set($currentTimezone);
    } else {

        $smsGreeting[0] = $smsDefaultGreeting;
    }

    return $smsGreeting[mt_rand(0, count_like_php5($smsGreeting) - 1)];
}

function getAuthyCustomMessage($deviceArray)
{

    $timezoneFromUTCInSeconds = getUserTimestamp($deviceArray);

    return randomGreeting($timezoneFromUTCInSeconds,
            false) . " your AtYourGate verification code is:" . $GLOBALS['env_EnvironmentDisplayCodeNoProd'] . " \"{{code}}\". This code will expire in 10 minutes.";
}

function getUserTimestamp(&$deviceArray)
{

    return (time() + $deviceArray["timezoneFromUTCInSeconds"]);
}

function enrichCheckinResponseByUserDeviceData($responseArray, $userDevice)
{
    $responseArray['isPushNotificationEnabled'] = $userDevice->get('isPushNotificationEnabled');
    return $responseArray;
}

function createUserDevice(&$user, &$deviceArray)
{

    $uniqueId = generateDeviceUniqueId($deviceArray);

    // Verify if uniqueId is already in the system
    // Overridden cached
    $objUserDeviceQuery = parseExecuteQuery(array("uniqueId" => $uniqueId, "user" => $user), "UserDevices", "", "",
        array(), 1, false, [
            "cacheKey" => getCacheKeyForUserDevice($user->getObjectId(), $uniqueId),
            "ttl" => "EOW",
            "cacheOnlyWhenResult" => true
        ]);

    // Not Found, so create a new one
    if (count_like_php5($objUserDeviceQuery) == 0) {

        // Initialize
        $oneSignalId = "";
        $pushNotificationId = $deviceArray["pushNotificationId"];
        $objUserDevicePushNotificationQuery = [];

        // If a push notification id was provided, then search if already have a OneSignal ID for it
        if (!empty($pushNotificationId)) {

            $objUserDevicePushNotificationQuery = parseExecuteQuery(array(
                "pushNotificationId" => $pushNotificationId,
                "user" => $user
            ), "UserDevices", "", "updatedAt");
        }

        // Find last row when we had a onesignal id for this deviceId, ordered descending by createdAt
        // $objUserDevicePushNotificationQuery = parseExecuteQuery(array("deviceId" => $deviceArray["deviceId"], "user" => $user), "UserDevices", "", "updatedAt");

        // If a row found with the same device id
        // This happens when device doesn't change but OS info or app version info changes
        if (count_like_php5($objUserDevicePushNotificationQuery) > 0) {

            foreach ($objUserDevicePushNotificationQuery as $obj) {

                // If OneSignal Id is found, then use it for the new row
                if (!empty($obj->get('oneSignalId'))) {

                    $oneSignalId = $obj->get('oneSignalId');
                    break;
                }
            }
        }

        // Create row in UserDevices
        $objUserDevice = new ParseObject("UserDevices");
        $objUserDevice->set("user", $user);
        $objUserDevice->set("isIos", convertToBoolFromInt(intval($deviceArray["isIos"])));
        $objUserDevice->set("isAndroid", convertToBoolFromInt(intval($deviceArray["isAndroid"])));
        $objUserDevice->set("isWeb", convertToBoolFromInt(intval(isset($deviceArray["isWeb"])?$deviceArray["isWeb"]:0)));
        $objUserDevice->set("deviceType", $deviceArray["deviceType"]);
        $objUserDevice->set("deviceModel", $deviceArray["deviceModel"]);
        $objUserDevice->set("deviceOS", $deviceArray["deviceOS"]);
        $objUserDevice->set("deviceId", $deviceArray["deviceId"]);
        $objUserDevice->set("isPushNotificationEnabled",
            convertToBoolFromInt(intval($deviceArray["isPushNotificationEnabled"])));
        $objUserDevice->set("appVersion", $deviceArray["appVersion"]);
        $objUserDevice->set("uniqueId", $uniqueId);
        $objUserDevice->set("pushNotificationId", $pushNotificationId);
        $objUserDevice->set("oneSignalId", $oneSignalId);
        $objUserDevice->save();
    } // This is an existing device, so return its object
    else {

        $objUserDevice = $objUserDeviceQuery;
    }

    return $objUserDevice;
}

/*
function createSessionDeviceFromToken($sessionTokenFrom, $sessionTokenTo) {

	// Fetch the latest SessionDevice row for sessionTokenFrom
	// Overridden cached
	$objSessionDeviceQuery = parseExecuteQuery(array("sessionTokenRecall" => $sessionTokenTo), "SessionDevices", "", "updateAt", array(), 1, false, ["cacheKey" => "s-" . $sessionTokenTo, "ttl" => "EOW", "cacheOnlyWhenResult" => true]);

	// If not found
	if(count_like_php5($objSessionDeviceQuery) == 0) {

		return false;
	}

	$objSessionDevice = new ParseObject("SessionDevices");
	$objSessionDevice->set("userDevice", $objSessionDeviceQuery[0]->get('userDevice'));
	$objSessionDevice->set("sessionTokenRecall", $sessionTokenTo);
	$objSessionDevice->set("geoLocation", $objSessionDeviceQuery[0]->get('userGeoPoint'));
	$objSessionDevice->set("timezoneFromUTCInSeconds", $objSessionDeviceQuery[0]->get('timezoneFromUTCInSeconds'));
	$objSessionDevice->set("IPAddress", $objSessionDeviceQuery[0]->get('IPAddress'));
	$objSessionDevice->set("checkinTimestamp", time());
	$objSessionDevice->set("sessionStartTimestamp", time());
	$objSessionDevice->set("isActive", true);
	$objSessionDevice->save();
}
*/

function createSessionDevice($userDevice, $deviceArray, $sessionToken, $user, $inactive = false)
{

    if (empty($userDevice)) {

        json_error("AS_WWW", "", "User Device not found!", 1);
    }

    // Create row in SessionDevices
    $userGeoPoint = new ParseGeoPoint(floatval($deviceArray["geoLatitude"]), floatval($deviceArray["geoLongitude"]));

    // Verify if there is an active SessionDevice row already in the system for this device

    // Overridden cached
    // $objSessionDeviceQuery = parseExecuteQuery(array("userDevice" => $userDevice, "isActive" => true), "SessionDevices", "", "updatedAt", [], 1, false, ["cacheKey" => getCacheKeyForSessionDevice($user->getObjectId(), $userDevice->getObjectId()), "ttl" => "EOW", "cacheOnlyWhenResult" => true]);

    if ($inactive){
        $objSessionDevice = new ParseObject("SessionDevices");
    }else{

        $objSessionDeviceQuery = parseExecuteQuery(array("userDevice" => $userDevice, "isActive" => true), "SessionDevices",
            "", "updatedAt", [], 1);
        if (count_like_php5($objSessionDeviceQuery) > 0) {

            $objSessionDevice = $objSessionDeviceQuery;

            // Match if the sessionToken associated with the device and current user's sessionToken are same
            // If they don't match, terminate this sessionDevice and enter a new one
            if (strcasecmp($objSessionDevice->get("sessionTokenRecall"), $sessionToken) != 0) {

                $objSessionDevice->set("sessionEndTimestamp", time());
                $objSessionDevice->set("isActive", false);
                $objSessionDevice->save();

                // Add a new row
                $objSessionDevice = new ParseObject("SessionDevices");
            }
        }
        // None found
        // Update row
        else {

            // Find the Session row for the current sessionToken so it can be updated
            $objSessionDeviceQuery = parseExecuteQuery(["sessionTokenRecall" => $sessionToken, "isActive" => true],
                "SessionDevices", "", "updatedAt", [], 1);

            // , false, ["cacheKey" => getCacheKeyForSessionDevice($user->getObjectId(), $sessionToken), "ttl" => "EOW", "cacheOnlyWhenResult" => true]

            if (count_like_php5($objSessionDeviceQuery) > 0) {

                // Just update the existing row
                $objSessionDevice = new ParseObject("SessionDevices", $objSessionDeviceQuery->getObjectId());
            } else {

                // No row was found with the session token, add a new row
                $objSessionDevice = new ParseObject("SessionDevices");
            }
        }
    }

    // If so, use it


    $objSessionDevice->set("userDevice", $userDevice);
    $objSessionDevice->set("user", $user);
    $objSessionDevice->set("sessionTokenRecall", $sessionToken);
    $objSessionDevice->set("geoLocation", $userGeoPoint);
    $objSessionDevice->set("timezoneFromUTCInSeconds", intval($deviceArray["timezoneFromUTCInSeconds"]));
    $objSessionDevice->set("country", $deviceArray["country"]);
    $objSessionDevice->set("isOnWifi", convertToBoolFromInt(intval($deviceArray["isOnWifi"])));
    $objSessionDevice->set("checkinTimestamp", time());
    $objSessionDevice->set("sessionStartTimestamp", time());
    $ipAddress = isset($deviceArray["IPAddress"]) ? $deviceArray["IPAddress"] : '';
    $objSessionDevice->set("IPAddress", sanitize($ipAddress));
    if ($inactive){
        $objSessionDevice->set("isActive", false);
    }else{
        $objSessionDevice->set("isActive", true);
    }

    try {

        $objSessionDevice->save();
    } catch (Exception $ex) {

        json_error("AS_039", "", "Checkin failed - " . $ex->getMessage().json_encode([$ex->getLine(),$ex->getFile(),$ex->getTraceAsString()]), 2);
    }

    return $objSessionDevice;
}

function generateDeviceUniqueId(&$deviceArray)
{

    // Return hash for the device to be used as uniqueId
    return md5(
        $deviceArray["isIos"] . '~' .
        $deviceArray["isAndroid"] . '~' .
        (isset($deviceArray["isWeb"])?$deviceArray["isWeb"]:0) . '~' .
        $deviceArray["deviceType"] . '~' .
        $deviceArray["deviceModel"] . '~' .
        $deviceArray["deviceOS"] . '~' .
        $deviceArray["deviceId"] . '~' .
        $deviceArray["pushNotificationId"] . '~' .
        $deviceArray["appVersion"]
    );
}

// Prepare Device Array for use
function prepareDeviceArray($objSessionDevices)
{

    $deviceArrayPrepared = array();

    // SessionDevices
    // $deviceArrayPrepared["userDevice"] = $objSessionDevices->get('userDevice');
    $deviceArrayPrepared["sessionTokenRecall"] = $objSessionDevices->get('sessionTokenRecall');
    $deviceArrayPrepared["geoLocation"] = $objSessionDevices->get('geoLocation');
    $deviceArrayPrepared["timezoneFromUTCInSeconds"] = $objSessionDevices->get('timezoneFromUTCInSeconds');
    $deviceArrayPrepared["country"] = $objSessionDevices->get('country');
    $deviceArrayPrepared["isOnWifi"] = $objSessionDevices->get('isOnWifi');
    $deviceArrayPrepared["checkinTimestamp"] = $objSessionDevices->get('checkinTimestamp');
    $deviceArrayPrepared["sessionStartTimestamp"] = $objSessionDevices->get('sessionStartTimestamp');
    $deviceArrayPrepared["sessionEndTimestamp"] = $objSessionDevices->get('sessionEndTimestamp');
    $deviceArrayPrepared["isActive"] = $objSessionDevices->get('isActive');

    // UserDevices
    $deviceArrayPrepared["isIos"] = $objSessionDevices->get('userDevice')->get('isIos');
    $deviceArrayPrepared["isAndroid"] = $objSessionDevices->get('userDevice')->get('isAndroid');
    $deviceArrayPrepared["isWeb"] = ($objSessionDevices->get('userDevice')->get('isWeb') == null || empty($objSessionDevices->get('userDevice')->get('isWeb')))?0:$objSessionDevices->get('userDevice')->get('isWeb');
    $deviceArrayPrepared["oneSignalId"] = $objSessionDevices->get('userDevice')->get('oneSignalId');
    $deviceArrayPrepared["deviceType"] = $objSessionDevices->get('userDevice')->get('deviceType');
    $deviceArrayPrepared["deviceModel"] = $objSessionDevices->get('userDevice')->get('deviceModel');
    $deviceArrayPrepared["deviceOS"] = $objSessionDevices->get('userDevice')->get('deviceOS');
    $deviceArrayPrepared["deviceId"] = $objSessionDevices->get('userDevice')->get('deviceId');
    $deviceArrayPrepared["pushNotificationId"] = $objSessionDevices->get('userDevice')->get('pushNotificationId');
    $deviceArrayPrepared["isPushNotificationEnabled"] = empty($objSessionDevices->get('userDevice')->get('isPushNotificationEnabled')) ? "0" : $objSessionDevices->get('userDevice')->get('isPushNotificationEnabled');
    $deviceArrayPrepared["appVersion"] = $objSessionDevices->get('userDevice')->get('appVersion');
    $deviceArrayPrepared["uniqueId"] = $objSessionDevices->get('userDevice')->get('uniqueId');

    return $deviceArrayPrepared;
}

function decodeDeviceArray(&$deviceArray)
{

    try {

        $array = @json_decode(@base64_decode(rawurldecode($deviceArray)), true);
    } catch (Exception $ex) {

        return false;
    }

    $array = sanitize_array($array);

    return $array;
}

function generateToken()
{

    srand(time());
    return mt_rand(1, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);
}

function doesPasswordMeetRequirements($password)
{

    // return preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[a-z].*[a-z].*[a-z]).{8,50}$$/', $password);
    // return preg_match('/^(?=.*[A-Z])(?=.*[0-9])(?=.*[a-z]).{8,50}$$/', $password);
    return (strlen($password) >= 7 ? true : false);
}

function getCoreInstructionCode($lastCheckinTimestamp, $deviceAppVersion, $isIos, $isAndroid, $isWeb=false)
{
    logResponse(json_encode([
        'checkcheck',
        $lastCheckinTimestamp,
        $isIos,
        $deviceAppVersion,
    ]));
    // Check minimum app version required
    if ($isIos) {

        $configMinAppVersionReqForAPI = getConfigValue("minAppVersionReqForAPIiOS");
    } else if($isAndroid){

        $configMinAppVersionReqForAPI = getConfigValue("minAppVersionReqForAPIAndroid");
    }else {

        $configMinAppVersionReqForAPI = getConfigValue("minAppVersionReqForAPIWeb");
    }

    $configMinAppVersionReqForAPI = intval(str_replace('.', '', $configMinAppVersionReqForAPI));
    $deviceAppVersion = intval(str_replace('.', '', $deviceAppVersion));

    if (!is_null($configMinAppVersionReqForAPI) && $configMinAppVersionReqForAPI > $deviceAppVersion) {

        return "AS_9002";
    }

    // check if metadata was updated after app cache was built, hence request is to clear it
    // And requires user to be logged out
    $configLastMetadataUpdate = getConfigValue("lastMetadataUpdate");
    if ($configLastMetadataUpdate >= $lastCheckinTimestamp) {
        logResponse(json_encode([
            'checkcheckAS_9003',
            $configLastMetadataUpdate,
            $lastCheckinTimestamp,
            $isIos,
            $deviceAppVersion,
        ]));
        return "AS_9003";
    }

    // check if metadata was updated after app cache was built, hence request is to clear it
    // But doesn't require user to be logged out
    $configLastMetadataUpdateNoLogoutReq = getConfigValue("lastMetadataUpdateNoLogoutReq");
    if ($configLastMetadataUpdateNoLogoutReq >= $lastCheckinTimestamp) {

        // backward compatibility for iOS
        // If user is using version is equal or lower than 1.5.2, then send AS_9003 instead
        if ($isIos == true && $deviceAppVersion <= 152) {

            logResponse(json_encode([
                'AS9003 check',
                $configLastMetadataUpdateNoLogoutReq,
                $lastCheckinTimestamp,
                $isIos,
                $deviceAppVersion,
            ]));

            return "AS_9003";
        } else {
            if ($isIos == true) {

                logResponse(json_encode([
                    'AS9006 check',
                    $configLastMetadataUpdateNoLogoutReq,
                    $lastCheckinTimestamp,
                    $isIos,
                    $deviceAppVersion,
                ]));
                return "AS_9006";
            }
        }
    }

    return "";
}

function getCurrentUserInfo($userDevice = '')
{

    $responseArray = [
        "isLoggedIn" => false,
        "isEmailVerified" => false,
        "isBetaActive" => false,
        "isLocked" => false,
        "isPhoneVerified" => false,
        "isSMSNotificationsEnabled" => false,
        "isSMSNotificationsOptOut" => false,
        "isPushNotificationEnabled" => false,
        "firstName" => "",
        "lastName" => "",
        "email" => "",
        "userId" => "",
        "phoneCountryCode" => "",
        "phoneNumber" => ""
    ];

    if (is_object($GLOBALS['user'])) {

        $responseArray["isEmailVerified"] = $GLOBALS['user']->get('emailVerified') == true ? true : false;
        $responseArray["isBetaActive"] = $GLOBALS['user']->get('isBetaActive') == true ? true : false;
        $responseArray["isLocked"] = $GLOBALS['user']->get('isLocked') == true ? true : false;
        $responseArray["isActive"] = $GLOBALS['user']->get('isActive') == true ? true : false;
        $responseArray["isLoggedIn"] = true;
        $responseArray["firstName"] = $GLOBALS['user']->get('firstName');
        $responseArray["lastName"] = $GLOBALS['user']->get('lastName');
        $responseArray["email"] = $GLOBALS['user']->get('email');
        $responseArray["userId"] = $GLOBALS['user']->getObjectId();

        if (is_object($userDevice)) {

            $responseArray["isPushNotificationEnabled"] = $userDevice->get('isPushNotificationEnabled');
        }
        // $responseArray["objectId"] = $GLOBALS['user']->getObjectId();

        // Get User Phones
        if (count_like_php5($GLOBALS['userPhones']) > 0) {

            $responseArray["isSMSNotificationsEnabled"] = ($GLOBALS['userPhones']->get('SMSNotificationsEnabled') == true && ($GLOBALS['userPhones']->has('SMSNotificationsOptOut') && $GLOBALS['userPhones']->get('SMSNotificationsOptOut') == false)) ? true : false;
            $responseArray["isSMSNotificationsOptOut"] = ($GLOBALS['userPhones']->has('SMSNotificationsOptOut') && $GLOBALS['userPhones']->get('SMSNotificationsOptOut') == true) ? true : false;
            $responseArray["isPhoneVerified"] = $GLOBALS['userPhones']->get('phoneVerified') == true ? true : false;
            $responseArray["phoneCountryCode"] = $GLOBALS['userPhones']->get('phoneCountryCode');
            $responseArray["phoneNumber"] = $GLOBALS['userPhones']->get('phoneNumber');
        }
    }

    return $responseArray;
}

function getAllActiveSessionDevice()
{
    if (isset($GLOBALS['userSessionToken'])) {
        $sessionToken = $GLOBALS['userSessionToken'];
    } else {
        $sessionToken = $GLOBALS['user']->getSessionToken();
    }

    // Find current active session device object
    $sessionDevices = parseExecuteQuery([
//        "sessionTokenRecall" => $GLOBALS['user']->getSessionToken(),
        "sessionTokenRecall" => $sessionToken,
        "isActive" => true
    ], "SessionDevices", "", "", ["userDevice"], 10000, true);

    return $sessionDevices;
}

function getAllSessionDevicesByUser()
{
    // @todo change it to get one by session + all active
    $user = $GLOBALS['user'];
    // Find current active session device object
    $sessionDevices = parseExecuteQuery([
        "user" => $user
    ], "SessionDevices", "", "", ["userDevice"], 10000, true);

    return $sessionDevices;
}


function getCurrentSessionDevice()
{
    if (isset($GLOBALS['userSessionToken'])) {
        $sessionToken = $GLOBALS['userSessionToken'];
    } else {
        $sessionToken = $GLOBALS['user']->getSessionToken();
    }

    // Find current active session device object
    $sessionDevice = parseExecuteQuery([
//        "sessionTokenRecall" => $GLOBALS['user']->getSessionToken(),
        "sessionTokenRecall" => $sessionToken,
        "isActive" => true
    ], "SessionDevices", "", "", ["userDevice"], 1);

    // End the session for the Device
    if (count_like_php5($sessionDevice) > 0) {

        return $sessionDevice;
    }

    return null;
}

function createOneSignalViaQueue($objUserDevice)
{

    $oneSignalMessageAlreadySend = boolval(getCacheOneSignalQueueMessageSend($objUserDevice->getObjectId()));

    // If Onesignal ID is not available but isPushNotificationEnabled is set to true
    // Then put on SQS to create onesignal ID

    if (empty($objUserDevice->get('oneSignalId'))
        && !empty($objUserDevice->get('pushNotificationId'))
        && !$oneSignalMessageAlreadySend
    ) {

        // Send email verified via Sendgrid via SQS
        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        try {

            // Create a device with a 30 seconds delay
            $workerQueue->sendMessage(
                array(
                    "action" => "onesignal_create_device",
                    "content" =>
                        array(
                            "userDeviceId" => $objUserDevice->getObjectId()
                        )
                ),
                120
            );
        } catch (Exception $ex) {

            // Json array formatted
            return json_error_return_array("AS_1070", "",
                "OneSignal device creation put on queue message failed, userDeviceId = " . $objUserDevice->getObjectId() . " - " . $ex->getMessage(),
                2);
            // return json_decode($ex->getMessage(), true);
        }

        // set cache to prevent sending message multiple times
        setCacheOneSignalQueueMessageSend($objUserDevice->getObjectId());

        /*
        $response = SQSSendMessage($GLOBALS['sqs_client'], $GLOBALS['env_workerQueueConsumerName'],
                array("action" => "onesignal_create_device",
                      "content" =>
                          array(
                              "objectId" => $objUserDevice->getObjectId()
                          )
                    )
                );

        if(is_array($response)) {

            return $response;
        }
        */
    }
}

function checkinUser($deviceArray)
{

    // Decode deviceArray (also sanitizes)
    $deviceArrayDecoded = decodeDeviceArray($deviceArray);

    if (!isValidDeviceArray($deviceArrayDecoded)) {

        json_error("AS_429", "", "Device array was not well-formed - " . json_encode($deviceArrayDecoded), 1);
    }

    // Create row in UserDevices
    $lastUserDevice = createUserDevice($GLOBALS['user'], $deviceArrayDecoded);

    // Create row in SessionDevices
    if (isset($GLOBALS['userSessionToken'])) {
        $sessionToken = $GLOBALS['userSessionToken'];
    } else {
        $sessionToken = $GLOBALS['user']->getSessionToken();
    }

//    $lastSessionDevice = createSessionDevice($lastUserDevice, $deviceArrayDecoded, $GLOBALS['user']->getSessionToken(),
//        $GLOBALS['user']);
    $lastSessionDevice = createSessionDevice($lastUserDevice, $deviceArrayDecoded, $sessionToken, $GLOBALS['user']);



    // Check and Create a queue action if we need to create a new device in OneSignal
    createOneSignalViaQueue($lastUserDevice);

    // Update checkin time
    setCacheCheckinInfo($GLOBALS['user']->getObjectId(), [$lastUserDevice, $lastSessionDevice]);

    // Log checkin
    try {

        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

        $workerQueue->sendMessage(
            array(
                "action" => "log_user_checkin",
                "content" =>
                    array(
                        "objectId" => $GLOBALS['user']->getObjectId(),
                        "sessionObjectId" => $lastSessionDevice->getObjectId()
                    )
            )
        );
    } catch (Exception $ex) {

        $response = json_decode($ex->getMessage(), true);
        json_error($response["error_code"], "", "Checkin queue message failed " . $response["error_message_log"], 1, 1);
    }

    /*
        // Log user event
        if($GLOBALS['env_LogUserActions']) {

            try {

                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                $workerQueue->sendMessage(
                        array("action" => "log_user_action_checkin",
                              "content" =>
                                array(
                                    "objectId" => $GLOBALS['user']->getObjectId(),
                                    "data" => json_encode([]),
                                    "timestamp" => time()
                                )
                            )
                        );
            }
            catch (Exception $ex) {

                $response = json_decode($ex->getMessage(), true);
                json_error($response["error_code"], "", "Log user action queue message failed " . $response["error_message_log"], 1, 1);
            }
        }
    */

    return [$lastUserDevice, $lastSessionDevice];
}


// Send Notification 
function sendBetaActivationNotification($user)
{

    $notificationSent = false;

    // Prepare message
    $messagePrepped = randomGreeting('',
            false) . ' ' . $user->get('firstName') . ', your AtYourGate exclusive access is here! Tap to join and have the airport delivered!';

    // Find the latest Session Devices
    $sessionDevice = parseExecuteQuery(["user" => $user, "isActive" => true], "SessionDevices", "", "createdAt",
        ["userDevice"], 1);

    // Push Notification
    if (count_like_php5($sessionDevice) > 0) {

        $isPushNotificationEnabled = $sessionDevice->get('userDevice')->get('isPushNotificationEnabled');
        $oneSignalId = $sessionDevice->get('userDevice')->get('oneSignalId');

        // If flag is set to true, then send push notification
        if ($isPushNotificationEnabled == true
            && !empty($oneSignalId)
        ) {

            // Send push notification via Queue
            try {

                $notificationSent = true;
                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue->sendMessage(
                    array(
                        "action" => "beta_activate_via_push_notification",
                        "content" =>
                            array(
                                "userDeviceId" => $sessionDevice->get('userDevice')->getObjectId(),
                                "oneSignalId" => $sessionDevice->get('userDevice')->get('oneSignalId'),
                                "message" => [
                                    "text" => $messagePrepped,
                                    "title" => "Account Acceptance!",
                                    "id" => 'beta_access',
                                    "data" => ["beta" => 'approved']
                                ]
                            )
                    )
                );
            } catch (Exception $ex) {

                $notificationSent = false;
                // Json array formatted
                return json_error_return_array("AS_1071", "",
                    "Beta activiate put on queue push notification message failed, sessionDeviceId = " . $sessionDevice->getObjectId() . " - " . $ex->getMessage(),
                    2);
                // return json_decode($ex->getMessage(), true);
            }
        }
    }

    // Get user's Phone Id
    $objUserPhone = parseExecuteQuery(array("user" => $user, "isActive" => true, "phoneVerified" => true), "UserPhones",
        "", "updatedAt", [], 1);

    // Send SMS notification
    if (count_like_php5($objUserPhone) > 0) {

        // If push notification wasn't sent, then assume SMS is enabled
        if ($notificationSent == false
            || $objUserPhone->get('SMSNotificationsEnabled') == true
        ) {

            try {

                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue->sendMessage(
                    array(
                        "action" => "beta_activate_via_sms",
                        "content" =>
                            array(
                                "userPhoneId" => $objUserPhone->getObjectId(),
                                "message" => $messagePrepped
                            )
                    )
                );
            } catch (Exception $ex) {

                // Json array formatted
                return json_error_return_array("AS_1072", "",
                    "Beta activiate put on queue SMS notification message failed, userPhoneId = " . $objUserPhone->getObjectId() . " - " . $ex->getMessage(),
                    2);
            }
        }
    }

    return "";
}

function getLatestUserDevice($user)
{

    // Find latest Session Device
    $objSessionDevice = parseExecuteQuery(array("user" => $user), "SessionDevices", "", "createdAt", ["userDevice"], 1);

    if (!empty($objSessionDevice)) {

        return $objSessionDevice->get('userDevice');
    }

    return [];
}

function getLatestSessionDevice($user)
{

    // Find latest Session Device
    $objSessionDevice = parseExecuteQuery(array("user" => $user), "SessionDevices", "", "createdAt", ["userDevice"], 1);

    if (!empty($objSessionDevice)) {

        return $objSessionDevice;
    }

    return [];
}

function logInstall($deviceArray, $referral)
{

    // Decode deviceArray (also sanitizes)
    $deviceArrayDecoded = decodeDeviceArray($deviceArray);

    if (!isValidDeviceArray($deviceArrayDecoded)) {

        json_error("AS_429", "", "Device array was not well-formed - " . json_encode($deviceArrayDecoded), 1);
    }

    // Create row in zLogInstall
    $userGeoPoint = new ParseGeoPoint(floatval($deviceArrayDecoded["geoLatitude"]),
        floatval($deviceArrayDecoded["geoLongitude"]));

    // Log to table
    $zLogInstall = new ParseObject("zLogInstall");
    $zLogInstall->set("deviceType", $deviceArrayDecoded["deviceType"]);
    $zLogInstall->set("deviceModel", $deviceArrayDecoded["deviceModel"]);
    $zLogInstall->set("deviceOS", $deviceArrayDecoded["deviceOS"]);
    $zLogInstall->set("deviceId", $deviceArrayDecoded["deviceId"]);
    $zLogInstall->set("isIos", convertToBoolFromInt(intval($deviceArrayDecoded["isIos"])));
    $zLogInstall->set("isAndroid", convertToBoolFromInt(intval($deviceArrayDecoded["isAndroid"])));
    $zLogInstall->set("isWeb", convertToBoolFromInt(intval(isset($deviceArrayDecoded["isWeb"])?$deviceArrayDecoded["isWeb"]:0)));
    $zLogInstall->set("appVersion", $deviceArrayDecoded["appVersion"]);
    $zLogInstall->set("geoLocation", $userGeoPoint);
    $zLogInstall->set("timezoneFromUTCInSeconds", intval($deviceArrayDecoded["timezoneFromUTCInSeconds"]));
    $zLogInstall->set("country", $deviceArrayDecoded["country"]);
    $zLogInstall->set("isOnWifi", convertToBoolFromInt(intval($deviceArrayDecoded["isOnWifi"])));
    $zLogInstall->set("IPAddress", sanitize($deviceArrayDecoded["IPAddress"]));
    $zLogInstall->set("referral", sanitize($referral));
    $zLogInstall->save();

    // Log user event
    if ($GLOBALS['env_LogUserActions']) {

        $platform = "Android";
        if (convertToBoolFromInt(intval($deviceArrayDecoded["isIos"])) == true) {

            $platform = "iOS";
        }

        try {

            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

            $workerQueue->sendMessage(
                array(
                    "action" => "log_user_action_install",
                    "content" =>
                        array(
                            "objectId" => "",
                            "id" => $zLogInstall->getObjectId(),
                            "data" => json_encode(["platform" => $platform]),
                            "timestamp" => time()
                        )
                )
            );
        } catch (Exception $ex) {

            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "",
                "Log user action queue message failed " . $response["error_message_log"], 1, 1);
        }
    }
}

function log_user_action($message)
{

    $action = str_replace('log_user_action_', '', $message["action"]);

    // env_LogUserActionsInSlackForAllLocations = true, means log for all locations / airports
    // else only ready airports
    $logToSlack = $GLOBALS['env_LogUserActionsInSlackForAllLocations'];

    if (isset($message['content']['location']['nearAirportIataCode'])
        && !empty($message['content']['location']['nearAirportIataCode'])
    ) {

        $message['content']['location']['locationDisplay'] = $message['content']['location']['nearAirportIataCode'];
    } else {

        $message['content']['location']['locationDisplay'] = $message['content']['location']['state'] . ', ' . $message['content']['location']['country'];
    }

    if (!isset($message['content']['location']['locationSource'])
        || empty($message['content']['location']['locationSource'])
    ) {

        $message['content']['location']['locationSource'] = 'unknown';
    }

    // If at a ready airport
    // Or key order actions
    // JMD
    // 'signup_coupon_failed', 'cart_coupon_failed'
    if ((isset($message['content']['location']['nearAirportIataCode']) && !empty($message['content']['location']['nearAirportIataCode']) && getAirportByIataCode($message['content']['location']['nearAirportIataCode'])->get('isReady') == true)
        || in_array($action, [
            'add_flight',
            'retailer_list',
            'retailer_menu',
            'add_cart',
            'checkout_cart',
            'checkout_start',
            'payment_add',
            'cart_coupon_failed',
            'checkout_complete',
            'checkout_warning',
            'checkout_payment_failed',
            'profile_update',
            'signup_coupon_failed',
            'referral_info'
        ])
    ) {

        $logToSlack = true;
    }

    // Log user action
    try {

        if ($GLOBALS['logsPdoConnection'] instanceof PDO) {
            //$pingLogRepository = new PingLogMysqlRepository($GLOBALS['logsPdoConnection']);
            $pingLogService = \App\Background\Services\LogServiceFactory::create();
            $pingLogService->logUserAction(
                $message["content"]["objectId"],
                $action,
                $message["content"]["data"],
                $message["content"]["location"],
                intval($message["content"]["timestamp"])
            );
        }
    } catch (Exception $ex) {

        return json_error_return_array("AS_1054", "",
            "MySQL log user action!, Post Array=" . json_encode($message) . " -- " . $ex->getMessage(), 1);
    }

    // JMD
    if ($logToSlack
        && $GLOBALS['env_LogUserActionsInSlack']
    ) {

        // Slack it
        $data = json_decode($message['content']['data'], true);
        $slack = new SlackMessage($GLOBALS['env_SlackWH_userActions'], 'env_SlackWH_userActions');
        $slack->setText($message['content']['customerName'] . " (" . date("M j, g:i a",
                intval($message["content"]["timestamp"])) . ")");

        $attachment = $slack->addAttachment();
        $attachment->addField("Date Time:", date("M j, g:i a", intval($message["content"]["timestamp"])), false);
        $attachment->addField("Customer:", $message['content']['customerName'], false);
        $attachment->addField("Action:", $action, false);
        $attachment->addField("Location:", $message['content']['location']['locationDisplay'], false);
        $attachment->addField("Location Source:", $message['content']['location']['locationSource'], false);

        if (!empty($data) && count_like_php5($data) > 0) {

            $attachment->addField("Additional Info:", http_build_query($data, '', ', '), false);
        }

        try {

            // Post to user actions channel
            $slack->send();
        } catch (Exception $ex) {

            return json_error_return_array("AS_1054", "",
                "Slack post failed informing user action!, Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
                1);
        }
    }

    return "";
}

function getLocationForLogForUser($objectId)
{

    // Initialize
    $array = [];

    // Get user
    $user = parseExecuteQuery(["objectId" => $objectId], "_User", "", "", [], 1);

    // Get Session
    $sessionDevice = getLatestSessionDevice($user);

    if (is_array($sessionDevice) && (empty($sessionDevice))){
        $array["nearAirportIataCode"] = '';
        $array["state"] = '';
        $array["country"] = '';
        $array["locationSource"] = '';
        return [$array, ''];
    }

    list($nearAirportIataCode, $locationCity, $locationState, $locationCountry, $locationSource) = getLocationForSession($sessionDevice);
    $array["nearAirportIataCode"] = $nearAirportIataCode;
    $array["state"] = $locationState;
    $array["country"] = $locationCountry;
    $array["locationSource"] = $locationSource;

    return [$array, $user->get('firstName') . ' ' . $user->get('lastName')];
}

function getLatestUserPhone($user)
{

    // Find current active session device object
    $objUserPhone = parseExecuteQuery(array("user" => $user, "isActive" => true, "phoneVerified" => true), "UserPhones",
        "", "updatedAt", [], 1);

    if (count_like_php5($objUserPhone) > 0) {

        return $objUserPhone;
    }

    return [];
}

?>
