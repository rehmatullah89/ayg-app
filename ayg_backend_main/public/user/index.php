<?php
$allowedOrigins = [
    "http://ayg-deb.test",
    "https://ayg.ssasoft.com",
    "http://ec2-18-116-237-65.us-east-2.compute.amazonaws.com",
    "http://ec2-18-190-155-186.us-east-2.compute.amazonaws.com", // test
    "https://order.atyourgate.com", // prod
];

if (isset($_SERVER["HTTP_REFERER"]) && in_array(trim($_SERVER["HTTP_REFERER"],'/'), $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . trim($_SERVER["HTTP_REFERER"],'/'));
}

require 'dirpath.php';

require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';


use App\Consumer\Controllers\UserController;
use App\Consumer\Helpers\UserAuthHelper;
use App\Consumer\Services\UserAuthServiceFactory;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseSession;
use Parse\ParseACL;

use Httpful\Request;

// Username check
$app->get('/signup/usernameCheck/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email',
    'apiAuthWithoutSession',
    function ($apikey, $epoch, $sessionToken, $type, $email) {

        // Sanitize email
        $email = strtolower(sanitizeEmail(rawurldecode($email)));
        $type = strtolower($type);


        // Validate Inputs
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

            json_error("AS_415", "Please enter a valid email.", "User's email ($email) is invalid", 3);
        } else {
            if (!in_array($type, array("c", "d"))) {

                json_error("AS_416", "", "Invalid account type value provided - " . $type, 1);
            }
        }

        // Check if this email has an account for the provided type
        $isAvailable = isRegisteredUserByEmail($email, $type) == true ? false : true;


        // Log user event only when username is new
        if ($GLOBALS['env_LogUserActions'] && $isAvailable == true) {

            try {

                $action = "log_user_action_signup_begin";
                $cacheName = setCacheForLogging($email, $action);

                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => $action,
                        "content" =>
                            array(
                                "objectId" => "",
                                "id" => $cacheName,
                                "data" => json_encode([]),
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


        $responseArray = array("isAvailable" => $isAvailable);


        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Sign up User
// JMD
$app->post('/signup/a/:apikey/e/:epoch/u/:sessionToken',
    'apiAuthWithoutSession',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // Fetch Post variables
        $postVars = array();

        $postVars['type'] = $type = strtolower(urldecode($app->request()->post('type')));
        $postVars['firstName'] = $firstName = urldecode($app->request()->post('firstName'));
        $postVars['lastName'] = $lastName = urldecode($app->request()->post('lastName'));
        $postVars['password'] = $password = rawurldecode($app->request()->post('password'));
        $postVars['email'] = $email = strtolower(rawurldecode($app->request()->post('email')));
        $postVars['deviceArray'] = $deviceArray = urldecode($app->request()->post('deviceArray'));

        if (empty($type)
            || empty($firstName)
            || empty($lastName)
            || empty($email)
            || empty($password)
            || empty($deviceArray)
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }

        // Decrypt password
        try {
            $password = decryptStringInMotion($password);
        } catch (Exception $ex) {

            json_error("AS_426", "", "Password provided not encrypted.", 1);
        }


        // Sanitize email
        $email = sanitizeEmail($email);

        // Decode deviceArray
        $deviceArrayDecoded = decodeDeviceArray($deviceArray);

        if (!isValidDeviceArray($deviceArrayDecoded)) {

            json_error("AS_420", "", "Device Array not well-formed", 1);
        }

        // Check if we want to disallow blocked devices that have been previously identified
        if ($GLOBALS['env_BlockDevicesAtRegistration'] == true) {

            // Device being used to sign up
            $deviceId = $deviceArrayDecoded["deviceId"];

            // Check if current device is in the Blocked list
            $devicesBlocked = parseExecuteQuery(["deviceId" => $deviceId, "isBlocked" => true], "UserDevicesBlocked",
                "", "", [], 1);

            if (count_like_php5($devicesBlocked) > 0) {

                $duplicateUser = "";

                // Find existing user who has this device for logging
                $objExistingUserWithDevice = parseExecuteQuery(["deviceId" => $deviceId], "UserDevices", "createdAt",
                    "", ["user"], 1);

                if (count_like_php5($objExistingUserWithDevice) > 0) {

                    $duplicateUser = $objExistingUserWithDevice->get("user");
                }

                // Log duplicate usage
                try {

                    logAccountDuplicateUsage("", [$duplicateUser],
                        "Blocked Device - " . $deviceId . " for email - " . $email);
                } catch (Exception $ex) {

                    json_error("AS_483", "", "Blocked Device log failure " . $ex->getMessage(), 2, 1);
                }

                json_error("AS_484",
                    "Your account could not be created. Please contact customer service for assistance. Refer to code 484.",
                    "Blocked deviceId addition - " . $email, 1);
            }
        }

        // Password requirements
        if (!doesPasswordMeetRequirements($password)) {

            json_error("AS_425", "Password requirements not met.", "Password requirements not met.", 1);
        } // Validate Email
        else {
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {

                json_error("AS_401", "Please enter a valid email.", "User's email ($email) is invalid", 1);
            } // Validate name
            else {
                if (empty($firstName) || empty($lastName)
                    || strlen($firstName) < 1 || strlen($lastName) < 1
                ) {

                    json_error("AS_402", "First or Last name too short.",
                        "User's name is too short ($firstName $lastName)", 1);
                } // Validate account type
                else {
                    if (!in_array(strtolower($type), array("c", "d"))) {

                        json_error("AS_419", "", "Invalid account type value provided - " . $type, 1);
                    }
                    // Verify if this email has any type of account
                    // If user exists in with any type of account, this API should not have been called
                    else {
                        if (isRegisteredUserByEmail($email, $type)) {

                            json_error("AS_421", "This email is in use by another account",
                                "User's email ($email) found with existing user", 1);
                        } else {

                            // Connect to Queue
                            try {

                                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                            } catch (Exception $ex) {

                                $response = json_decode($ex->getMessage(), true);
                                json_error($response["error_code"], "",
                                    "User signup failed! " . $response["error_message_log"], 1);
                            }

                            // Insert User to the User Class
                            $username = createUsernameFromEmail($email, $type);

                            $objParseInsertUser = new ParseUser();
                            $objParseInsertUser->set("username", $username);
                            $objParseInsertUser->set("password", generatePasswordHash($password));
                            $objParseInsertUser->set("email", $email);
                            $objParseInsertUser->set("firstName", $firstName);
                            $objParseInsertUser->set("lastName", $lastName);
                            // $objParseInsertUser->set("emailVerified", false);
                            $objParseInsertUser->set("isActive", false);
                            $objParseInsertUser->set("isLocked", false);
                            $objParseInsertUser->set("typeOfLogin", $type);
                            $objParseInsertUser->set("isBetaActive",
                                convertToBoolFromInt(intval($GLOBALS['env_isOpenForGeneralAvailability'])));
                            $objParseInsertUser->set("airEmpValidUntilTimestamp", 0);

                            // Get flags that should be set for access
                            list($hasConsumerAccess, $hasDeliveryAccess) = getAccountTypeFlags($type);

                            // Provide access to one of the apps
                            if ($hasConsumerAccess) {

                                $objParseInsertUser->set("hasConsumerAccess", $hasConsumerAccess);
                            } else {

                                $objParseInsertUser->set("hasDeliveryAccess", $hasDeliveryAccess);
                            }

                            // Add admin role to the ACL
                            // $__defaultACL = new ParseACL();
                            // $__defaultACL->setPublicReadAccess(true);
                            // $__defaultACL->setRoleReadAccessWithName("admin", true);
                            // $__defaultACL->setRoleWriteAccessWithName("admin", true);
                            // $objParseInsertUser->setACL($__defaultACL);

                            // Sign up
                            $objParseInsertUser->signUp();

                            // Set emailVerified as false
                            $objParseInsertUser->set("emailVerified", false);
                            $objParseInsertUser->save(true);

                            // Log password change event
                            json_error("AS_3003", "", "User signup " . $email, 3, 1);

                            // Get Session token of the user who is now logged in
                            $sessionToken = $objParseInsertUser->getSessionToken();

                            // Return session token
                            $responseArray = array("u" => $sessionToken . '-' . $type);

                            // Create row in UserDevices
                            $objUserDevice = createUserDevice($objParseInsertUser, $deviceArrayDecoded);

                            // Create row in SessionDevices
                            $objUserSessionDevice = createSessionDevice($objUserDevice, $deviceArrayDecoded,
                                $sessionToken, $objParseInsertUser);

                            // Check and Create a SQS action if we need to create a new device in OneSignal
                            $response = createOneSignalViaQueue($objUserDevice);

                            if (is_array($response)) {

                                json_error($response["error_code"], "",
                                    $response["error_message_log"] . " Create Onesignal Id failed due to Queue - " . $objParseInsertUser->getObjectId(),
                                    1, 1);
                            }

                            // Generate and assign a referral code to the user
                            generateReferralCode($objParseInsertUser);

                            // Send email verified via Sendgrid via Queue
                            try {

                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueEmailConsumerName']);
                                $workerQueue->sendMessage(
                                    array(
                                        "action" => "email_verify_on_signup",
                                        "content" =>
                                            array(
                                                "objectId" => $objParseInsertUser->getObjectId(),
                                                // "email" => $email,
                                                // "first_name" => $firstName,
                                                // "last_name" => $lastName,
                                                // "remote_ip" => urlencode(getenv('HTTP_X_FORWARDED_FOR') . ' ~ ' . getenv('REMOTE_ADDR')),

                                                // "type" => $type,
                                                "app" => $objUserDevice->get('isIos') == true ? 'iOS' : 'Android'
                                            )
                                    ),
                                    120 // delay to allow the promo code to be captured by then
                                );
                            } catch (Exception $ex) {

                                json_error("AS_456", "",
                                    "Verify email send failed due to Queue for " . $objParseInsertUser->getObjectId() . ' with error ' . $ex->getMessage(),
                                    1, 1);
                            }

                            /*
                            $response = SQSSendMessage($GLOBALS['sqs_client'], $GLOBALS['env_workerQueueConsumerName'],
                                    array("action" => "email_verify_on_signup",
                                          "content" =>
                                              array(
                                                  "objectId" => $objParseInsertUser->getObjectId(),
                                                  "email" => $email,
                                                  "first_name" => $firstName,
                                                  "last_name" => $lastName,
                                                  "type" => $type
                                              )
                                        )
                                    );

                            if(is_array($response)) {

                                json_error($response["error_code"], "", $response["error_message_log"] . " Verify email send failed due to SQS - " . $objParseInsertUser->getObjectId(), 1, 1);
                            }
                            */

                            unset($objParseInsertUser);
                        }
                    }
                }
            }
        }

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Sign up User - Add Phone
// @todo - GUEST we might change middleware to apiAuth()
$app->get(
    '/addPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneCountryCode/:phoneCountryCode/phoneNumber/:phoneNumber',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    //'apiAuthWithoutActiveAccess',
    function ($apikey, $epoch, $sessionToken, $phoneCountryCode, $phoneNumber) {

        // Send verification code to phone
        // print_r(ParseUser::getCurrentUser()->getSessionToken());gracefulExit();
        // print_r(getCurrentSessionDevice(ParseUser::getCurrentUser()->getSessionToken()));gracefulExit();

        // Check if already have cache for this
        // Anti flooding mechanism that controls how many times we send messages per time interval
        getRouteCache();

        $phoneNumber = floatval($phoneNumber);
        $phoneCountryCode = intval($phoneCountryCode);

        // Record attempt with a max of 50 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("ADDPHONE", $phoneCountryCode . $phoneNumber, 50, 60);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_444", "You have reached maximum attempts allowed. Please try again in an hour.",
                "Max attempts for Phone Add reached ", 1);
        }

        // Check if any phone already exists
        $objUserPhonesFind = parseExecuteQuery(array("user" => $GLOBALS['user']), "UserPhones");

        // Check if we disallow duplicate phone numbers
        // Check if this number was associated with another account
        if (
            (UserAuthHelper::isSessionTokenFromCustomSessionManagement($sessionToken) == false)
            &&
            $GLOBALS['env_AllowDuplicateAccounts'] == false
            &&
            $GLOBALS['env_AllowDuplicatePhoneRegistration'] == false
        ) {
            // If the phone number is whitelisted, then allow duplicate to be created
            $objUserPhonesWhiteList = parseExecuteQuery([
                "phoneNumber" => strval($phoneNumber),
                "phoneCountryCode" => strval($phoneCountryCode),
                "allowForDuplicateAccount" => true
            ], "UserPhonesWhiteList");

            if (count_like_php5($objUserPhonesWhiteList) == 0) {

                $objUserPhonesExisting = parseExecuteQuery([
                    "phoneNumber" => strval($phoneNumber),
                    "phoneCountryCode" => strval($phoneCountryCode),
                    "phoneVerified" => true
                ], "UserPhones", "", "", ["user"]);

                if (count_like_php5($objUserPhonesExisting) > 0) {

                    // Log duplicate usage
                    try {

                        logAccountDuplicateUsage($GLOBALS['user'], [$objUserPhonesExisting[0]->get("user")],
                            "Same Phone - " . $phoneCountryCode . "-" . $phoneNumber);
                    } catch (Exception $ex) {

                        json_error("AS_482", "", "Phone Number Duplicate log failure " . $ex->getMessage(), 2, 1);
                    }

                    // Log user event
                    if ($GLOBALS['env_LogUserActions']) {

                        try {

                            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                            $workerQueue->sendMessage(
                                array(
                                    "action" => "log_user_action_duplicate_phone_add",
                                    "content" =>
                                        array(
                                            "objectId" => $GLOBALS['user']->getObjectId(),
                                            "data" => json_encode([]),
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

                    json_error("AS_478",
                        "This number is associated with another account. To reset password for your account, please tap the back button and then Forgot Password on the login screen. Alternatively, you can contact customer service at help@atyourgate.com for assistance.",
                        "Duplicate phone addition", 1);
                }
            }
        }

        //json_error("AS_452 ".$GLOBALS['user']->getObjectId(), "", "No additional phones can be added as an active one already exists", 1);
        // If we have active phones, exit
        foreach ($objUserPhonesFind as $obj) {

            if ($obj->get('phoneVerified')) {

                json_error("AS_452", "", "No additional phones can be added as an active one already exists", 1);
            } else {
                if (strcasecmp($obj->get('phoneNumber'), $phoneNumber) == 0) {

                    $objUserPhones = new ParseObject("UserPhones", $obj->getObjectId());
                    break;
                }
            }
        }

        // If the phone number is blocked
        $objUserPhonesWhiteList = parseExecuteQuery([
            "phoneNumber" => strval($phoneNumber),
            "phoneCountryCode" => strval($phoneCountryCode),
            "isBlocked" => true
        ], "UserPhonesWhiteList");

        if (count_like_php5($objUserPhonesWhiteList) > 0) {

            // Log duplicate usage
            try {

                logAccountDuplicateUsage($GLOBALS['user'], "",
                    "Blocked Phone - " . $phoneCountryCode . "-" . $phoneNumber);
            } catch (Exception $ex) {

                json_error("AS_482", "", "Phone Number blocked log failure " . $ex->getMessage(), 2, 1);
            }

            // Log user event
            if ($GLOBALS['env_LogUserActions']) {

                try {

                    //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                    $workerQueue->sendMessage(
                        array(
                            "action" => "log_user_action_blocked_phone_add",
                            "content" =>
                                array(
                                    "objectId" => $GLOBALS['user']->getObjectId(),
                                    "data" => json_encode(["phoneNumber" => strval($phoneNumber)]),
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

            json_error("AS_478",
                "This number cannot be added at this time. You can contact customer service at help@atyourgate.com for assistance.",
                "Blocked phone addition - " . strval($phoneNumber), 1);
        }

        // Else continue to add
        $customMessage = getAuthyCustomMessage(prepareDeviceArray(getCurrentSessionDevice()));

        //$authyResponse = authyPhoneVerificationAPI($GLOBALS['env_AuthyPhoneStartURL'], 'post', array("phone_number" => $phoneNumber, "country_code" => $phoneCountryCode, "via" => "sms", "locale" => "en", "custom_message" => $customMessage));
        $authyResponse = authyPhoneVerificationAPI($GLOBALS['env_AuthyPhoneStartURL'], 'post', array(
            "phone_number" => $phoneNumber,
            "country_code" => $phoneCountryCode,
            "via" => "sms",
            "locale" => "en"
        ));
        logResponse(json_encode(['start authy response ', $authyResponse, 'end authy response ']));
        // Authy code send failed
        if (!$authyResponse->success) {

            $message = "";
            if (isset($authyResponse->errors) && isset($authyResponse->errors->message)) {

                $message = $authyResponse->errors->message;
            }

            // If the error was that the phone number provided was a Landline (no SMS can be sent to it)
            if (intval($authyResponse->error_code) == 60082) {

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_phone_add_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode(["reasonCode" => "60082", "reason" => "Landline number"]),
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

                json_error("AS_462",
                    "The number provided is a landline number. Please enter a mobile number to which a text message can be sent.",
                    "Authy verification send failed. " . $authyResponse->error_code . " - " . $message, 2);
            } // If the error was that the phone has not been provisioned
            else {
                if (intval($authyResponse->error_code) == 60083) {

                    // Log user event
                    if ($GLOBALS['env_LogUserActions']) {

                        try {

                            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                            $workerQueue->sendMessage(
                                array(
                                    "action" => "log_user_action_phone_add_failed",
                                    "content" =>
                                        array(
                                            "objectId" => $GLOBALS['user']->getObjectId(),
                                            "data" => json_encode([
                                                "reasonCode" => "60083",
                                                "reason" => "Invalid number - " . $message
                                            ]),
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

                    json_error("AS_479",
                        "The number provided is invalid. Please enter a valid mobile number to which a text message can be sent.",
                        "Authy verification send failed. " . $authyResponse->error_code . " - " . $message, 2);
                } // If the error was that the phone number was invalid
                else {
                    if (intval($authyResponse->error_code) == 60033) {

                        // Log user event
                        if ($GLOBALS['env_LogUserActions']) {

                            try {

                                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                                $workerQueue->sendMessage(
                                    array(
                                        "action" => "log_user_action_phone_add_failed",
                                        "content" =>
                                            array(
                                                "objectId" => $GLOBALS['user']->getObjectId(),
                                                "data" => json_encode([
                                                    "reasonCode" => "60033",
                                                    "reason" => "Invalid number - " . $message
                                                ]),
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

                        json_error("AS_479",
                            "The number provided is invalid. Please enter a valid mobile number to which a text message can be sent.",
                            "Authy verification send failed. " . $authyResponse->error_code . " - " . $message, 2);
                    } else {

                        // Log user event
                        if ($GLOBALS['env_LogUserActions']) {

                            try {

                                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                                $workerQueue->sendMessage(
                                    array(
                                        "action" => "log_user_action_phone_add_failed",
                                        "content" =>
                                            array(
                                                "objectId" => $GLOBALS['user']->getObjectId(),
                                                "data" => json_encode([
                                                    "reasonCode" => "000000",
                                                    "reason" => "Code send failed - " . $message
                                                ]),
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

                        json_error("AS_422", "",
                            "Authy verification send failed. " . $authyResponse->error_code . " - " . $message, 1);
                    }
                }
            }
        }

        // Carrier blocked check
        $phoneCarrier = (isset($authyResponse->carrier) ? strtolower($authyResponse->carrier) : "");

        if (!empty($phoneCarrier)) {

            // If carrier is blocked
            $objUserPhonesCarrierBlocked = parseExecuteQuery([], "UserPhonesCarrierBlocked");

            if (count_like_php5($objUserPhonesCarrierBlocked) > 0) {

                $matched = false;
                foreach ($objUserPhonesCarrierBlocked as $carrierBlocked) {

                    if (preg_match("/" . $carrierBlocked->get('carrierNameTags') . "/si", $phoneCarrier)) {

                        $matched = true;
                        break;
                    }
                }

                if ($matched == true) {

                    // Log blocked usage
                    try {

                        logAccountDuplicateUsage($GLOBALS['user'], "",
                            "Blocked Carrier - " . $phoneCountryCode . "-" . $phoneNumber . "-" . $phoneCarrier);
                    } catch (Exception $ex) {

                        json_error("AS_482", "",
                            "Phone Number blocked due to Carrier, log failure " . $ex->getMessage(), 2, 1);
                    }

                    // Log user event
                    if ($GLOBALS['env_LogUserActions']) {

                        try {

                            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                            $workerQueue->sendMessage(
                                array(
                                    "action" => "log_user_action_blocked_phone_add",
                                    "content" =>
                                        array(
                                            "objectId" => $GLOBALS['user']->getObjectId(),
                                            "data" => json_encode(["phoneCarrier" => $phoneCarrier]),
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

                    json_error("AS_478",
                        "This number cannot be added at this time. You can contact customer service at help@atyourgate.com for assistance.",
                        "Blocked phone addition due to carrier - " . strval($phoneCarrier), 1);
                }
            }
        }

        $phoneNumberDetails = array(
            "authyId" => $authyResponse->uuid,
            "phoneNumberFormatted" => trim(str_replace(".", "",
                str_replace("Text message sent to ", "", $authyResponse->message))),
            "carrier" => (isset($authyResponse->carrier) ? $authyResponse->carrier : "")
        );

        // Add row to UserPhones
        if (!isset($objUserPhones)) {

            $objUserPhones = new ParseObject("UserPhones");
        }

        $objUserPhones->set("user", $GLOBALS['user']);
        $objUserPhones->set("phoneCountryCode", strval($phoneCountryCode));
        $objUserPhones->set("phoneNumber", strval($phoneNumber));
        $objUserPhones->set("phoneNumberFormatted", $phoneNumberDetails["phoneNumberFormatted"]);
        $objUserPhones->set("phoneCarrier", $phoneNumberDetails["carrier"]);
        $objUserPhones->set("phoneVerified", false);
        $objUserPhones->set("SMSNotificationsEnabled", $GLOBALS['env_UserSMSNotificationsEnabledOnSignup']);
        $objUserPhones->set("SMSNotificationsOptOut", false);
        $objUserPhones->set("authyId", $phoneNumberDetails["authyId"]);
        $objUserPhones->set("isActive", false);
        $objUserPhones->set("startTimestamp", time());
        $objUserPhones->save();

        $responseArray = array("phoneId" => $objUserPhones->getObjectId());

        // Respond with 10 seconds of caching
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 10
            ])
        );
    });

$app->get('/signInByPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneId/:phoneId/verifyCode/:verifyCode',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::onlyCustomSessionGuard',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::addCurrentUserAsFirstParam',
    \App\Consumer\Controllers\UserController::class . ':signInByPhone'
);


// Sign up User - Verify Phone
$app->get('/verifyPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneId/:phoneId/verifyCode/:verifyCode',
    //\App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    'apiAuthWithoutActiveAccess',
    function ($apikey, $epoch, $sessionToken, $phoneId, $verifyCode) {

        // Find UserPhone
        $objUpdateUserPhones = parseExecuteQuery(array("objectId" => $phoneId), "UserPhones");
        if (count_like_php5($objUpdateUserPhones) == 0) {

            json_error("AS_424", "", "Invalid phone Id", 1);
        }

        // If already verified
        if ($objUpdateUserPhones[0]->get('phoneVerified') == true) {

            $responseArray = array("verified" => true);
        } else {

            // Record attempt with a max of 50 attempts allowed per 60 mins
            $isUserAllowedToContinue = addToCountOfAttempt("VERIFYPHONE", $phoneId, 50, 60);

            // JMD
            // verify if max attempts reached
            if (!$isUserAllowedToContinue) {

                json_error("AS_445", "You have reached maximum attempts allowed. Please try again in an hour.",
                    "Max attempts for Phone Verify reached ", 1);
            }

            // Verify phone
            $authyResponse = authyPhoneVerificationAPI($GLOBALS['env_AuthyPhoneCheckURL'], 'get', array(
                "phone_number" => $objUpdateUserPhones[0]->get('phoneNumber'),
                "country_code" => $objUpdateUserPhones[0]->get('phoneCountryCode'),
                "verification_code" => $verifyCode
            ));

            // Authy code send failed
            if (!$authyResponse->success) {

                // JMD
                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_phone_add_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "reasonCode" => "000001",
                                            "reason" => "Verification failed - " . json_encode($authyResponse)
                                        ]),
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

                $responseArray = array("verified" => false);
                json_error("AS_446", "", "Phone verification code failed: " . json_encode($authyResponse), 1, 1);
            } else {

                // Add row to UserPhones
                $objUpdateUserPhones[0]->set("phoneVerified", true);
                $objUpdateUserPhones[0]->set("isActive", true);
                $objUpdateUserPhones[0]->save();

                // Activate user
                $GLOBALS['user']->set('isActive', true);
                $GLOBALS['user']->save();

                $responseArray = array("verified" => true);
            }
        }

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Sign in User with username and password
$app->post('/signin/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthWithoutSession',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        $responseArray = userSignIn($app);

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Checkin
//$app->post('/checkin/a/:apikey/e/:epoch/u/:sessionToken',
    $app->post(
        '/checkin/a/:apikey/e/:epoch/u/:sessionToken',
        \App\Consumer\Middleware\UserAuthMiddleware::class . '::clearUserAndSessionAndCreateNewOneWhenSessionTokenNotSet',
        \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccessNoExitWhenNoPhone',
//        'apiAuthWithoutActiveAccessNoExit',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        if (empty($GLOBALS['user'])) {

            json_error("AS_015", "", "Invalid session token", 2);
        }

        // Fetch Post variables
        $postVars = array();
        $postVars['deviceArray'] = $deviceArray = $app->request()->post('deviceArray'); // URL Decoded before using

        if (empty($deviceArray)) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }

        ////////////////////////////////////////////////////////////////////////////////////
        // Get the latest user device row
        $lastCheckinTimestamp = 0;
        $appVersion = '100.0.0';
        $checkedIn = false;
        $lastUserDevice = '';
        $lastSessionDevice = '';
        ////////////////////////////////////////////////////////////////////////////////////

        // This is needed because /checkin is called right after /signin, the lag between Parse saving new SessionDevices is longer and leads two records being created.
        // Also this call shouldn't be updated Devices everytime; this cache ensures we create an interval during which /checkins are ignored

        // Get from cache
        $checkCacheInfo = getCacheCheckinInfo($GLOBALS['user']->getObjectId());
        //$checkCacheInfo=null;
        // if(doesCacheCheckinInfoExist($GLOBALS['user']->getObjectId()) == true) {
        if (!empty($checkCacheInfo)
            && count_like_php5($checkCacheInfo) > 0
        ) {

            $lastUserDevice = $checkCacheInfo[0];
            $lastSessionDevice = $checkCacheInfo[1];

            $lastCheckinTimestamp = $lastSessionDevice->get('checkinTimestamp');
            $appVersion = $lastSessionDevice->get("userDevice")->get("appVersion");
            $isIos = $lastSessionDevice->get('userDevice')->get('isIos');
            $isAndroid = $lastSessionDevice->get('userDevice')->get('isAndroid');
            $isWeb = ($lastSessionDevice->get('userDevice')->get('isWeb') == null || $lastSessionDevice->get('userDevice')->get('isWeb') == "")?false:$lastSessionDevice->get('userDevice')->get('isWeb');
        } else {

            // Get Current devices as last devices before we checkin
            $lastSessionDevice = getCurrentSessionDevice($GLOBALS['user']->getSessionToken());

            if (!is_null($lastSessionDevice)
                && count_like_php5($lastSessionDevice) > 0
            ) {

                $lastUserDevice = $lastSessionDevice->get('userDevice');
                $lastCheckinTimestamp = $lastSessionDevice->get('checkinTimestamp');
                $userDevice = $lastSessionDevice->get('userDevice');
                $userDevice->fetch();
                $appVersion = $userDevice->get('appVersion');
            } else {

                $lastSessionDevice = [];
            }

            $checkedIn = true;
            list($updatedUserDevice, $updatedSessionDevice) = checkinUser($deviceArray);


            $lastSessionDevice = $updatedSessionDevice;
            $isIos = $lastSessionDevice->get('userDevice')->get('isIos');
            $isAndroid = $lastSessionDevice->get('userDevice')->get('isAndroid');
            $isWeb = ($lastSessionDevice->get('userDevice')->get('isWeb') == null || $lastSessionDevice->get('userDevice')->get('isWeb') == "")?false:$lastSessionDevice->get('userDevice')->get('isWeb');
            $lastUserDevice = $updatedUserDevice;
        }

        // Prepare response array
        $responseArray = getCurrentUserInfo($lastUserDevice);

        // Get last known checkin timestamp and app version
        // if(count_like_php5($lastSessionDevice) > 0) {

        // 	$lastCheckinTimestamp = $lastSessionDevice->get('checkinTimestamp');
        // 	$appVersion = $lastSessionDevice->get("userDevice")->get("appVersion");
        // }

        logResponse(json_encode([
            'COREINSTRUCTION',
            $lastCheckinTimestamp,
            $appVersion,
            $isIos,
            $isAndroid,
            $isWeb
        ]));

        // Add core instruction codes, such as AS_900x
        $responseArray["coreInstructionCode"] = getCoreInstructionCode($lastCheckinTimestamp, $appVersion, $isIos, $isAndroid, $isWeb);

        // If a core instruction code was generated,
        // and cache was used (aka checkin not performed), then checkin so the next time the updated checkin is used
        if (!empty($responseArray["coreInstructionCode"])
            && $checkedIn == false
        ) {

            list($lastUserDevice, $lastSessionDevice) = checkinUser($deviceArray);
        }

        // update $responseArray by values from $lastUserDevice
        // if ($checkedIn){
        //     $responseArray = enrichCheckinResponseByUserDeviceData($responseArray, $updatedUserDevice);
        // }


        $responseArray['defaultOrderTimeWindowBeforeFlight'] = $GLOBALS['env_DefaultOrderTimeWindowBeforeFlight'];
        $responseArray['deliveryTimeWindowIncrements'] = $GLOBALS['env_DeliveryTimeWindowIncrements'];
        $responseArray['minTimeWindowBeforeFlight'] = $GLOBALS['env_MinTimeWindowBeforeFlight'];

        // Referral enabled
        $responseArray['isReferralProgramEnabled'] = $GLOBALS['env_UserReferralRewardEnabled'];




        if (!UserAuthHelper::isSessionTokenFromCustomSessionManagement($sessionToken)){
            $userAuthService = \App\Consumer\Services\UserAuthServiceFactory::create();
            try {
                $userSession = $userAuthService->attachUserSessionToExistingLoggedInParseUser(
                    $GLOBALS['user'],
                    decodeDeviceArray($deviceArray),
                    $responseArray);
            }catch (\App\Consumer\Exceptions\UserCustomIdentifierCanNotBeCreatedException $exception){
                return json_error_return_array("AS_015", "",
                    "Not a valid session - " . $exception->getMessage(), 1);
            }
            $responseArray['newSessionToken'] = $userSession->getToken();
            $responseArray['newSessionHasFullAccess'] = $userSession->hasFullAccess();
        }else{
            if (isset($GLOBALS['userSession'])){
                $responseArray['newSessionToken'] = $GLOBALS['userSession']->getToken();
                $responseArray['newSessionHasFullAccess'] = $GLOBALS['userSession']->hasFullAccess();
            }
        }

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Install log
$app->post('/install/a/:apikey/e/:epoch/u/:sessionToken/referral/:referral',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutSession',
    //'apiAuthWithoutSession',
    function ($apikey, $epoch, $sessionToken, $referral) use ($app) {

        // Fetch Post variables
        $postVars = array();
        $postVars['deviceArray'] = $deviceArray = $app->request()->post('deviceArray'); // URL Decoded before using

        if (empty($deviceArray)) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }

        logInstall($deviceArray, $referral);


        $responseArray['logged'] = true;

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });


$app->post('/addProfileData/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    \App\Consumer\Middleware\UserAddProfileDataMiddleware::class . '::validate',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::addCurrentUserAsFirstParam',
    \App\Consumer\Controllers\UserController::class . ':addProfileData'
);


// Change Profile options -- First Name, Last Name, SMSNotificationsEnabled
$app->post('/profile/update/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // Fetch Post variables
        $postVars = array();

        $postVars['firstName'] = $firstName = urldecode($app->request()->post('firstName'));
        $postVars['lastName'] = $lastName = urldecode($app->request()->post('lastName'));
        $postVars['SMSNotificationsEnabled'] = $SMSNotificationsEnabled = urldecode($app->request()->post('SMSNotificationsEnabled'));
        // $postVars['pushNotificationsEnabled'] = $pushNotificationsEnabled = urldecode($app->request()->post('pushNotificationsEnabled'));

        if (empty($firstName)
            || empty($lastName)
            // || empty($pushNotificationsEnabled)
            || empty($SMSNotificationsEnabled)
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        } else {

            // printPostVars($postVars, "862");
        }

        // Validate name
        if (empty($firstName) || empty($lastName)
            || strlen($firstName) < 1 || strlen($lastName) < 1
        ) {

            json_error("AS_402", "First or Last name too short.", "User's name is too short ($firstName $lastName)", 1);
        } // Check SMSNotificationsEnabled
        else {
            if (strcasecmp($SMSNotificationsEnabled, 'true') != 0
                && strcasecmp($SMSNotificationsEnabled, 'false') != 0
            ) {

                json_error("AS_454", "", "Invalid value for SMSNotificationsEnabled", 1);
            }
            // Check pushNotificationsEnabled
            // else if(strcasecmp($pushNotificationsEnabled, 'true')!=0
            // 	&& strcasecmp($pushNotificationsEnabled, 'false')!=0) {

            // 	json_error("AS_454", "", "Invalid value for pushNotificationsEnabled", 1);
            // }
            // Update values
            else {

                // Verify for SMS Opt out
                // User enable SMS but had Opt'd out
                $optInMessage = "";
                if ($GLOBALS['userPhones']->get('SMSNotificationsEnabled') == false
                    && convertToBool($SMSNotificationsEnabled) == true
                    && $GLOBALS['userPhones']->get('SMSNotificationsOptOut') == true
                ) {

                    $optInMessage = "Since you had Opt'd out of SMS, to fully enable SMS you must Opt in manually. Please text START to +1-703-293-5636 to renable messages.";
                }

                $oldFirstName = $GLOBALS['user']->get('firstName');
                $oldLastName = $GLOBALS['user']->get('lastName');
                $oldSMSNotificationsEnabled = $GLOBALS['userPhones']->get('SMSNotificationsEnabled') == true ? 1 : 0;
                $newSMSNotificationsEnabled = convertToBool($SMSNotificationsEnabled) == true ? 1 : 0;

                // in case it is custom session management, we are not updating user, only SMSNotificationsEnabled setting

                if (UserAuthHelper::isSessionTokenFromCustomSessionManagement($sessionToken) === false){
                    $GLOBALS['user']->set('firstName', $firstName);
                    $GLOBALS['user']->set('lastName', $lastName);
                    $GLOBALS['user']->save();
                }

                $GLOBALS['userPhones']->set('SMSNotificationsEnabled', convertToBool($SMSNotificationsEnabled));
                $GLOBALS['userPhones']->save();

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {

                        $profileUpdates = [
                            "oldFirstName" => $oldFirstName,
                            "oldLastName" => $oldLastName,
                            "oldSMSNotificationsEnabled" => $oldSMSNotificationsEnabled,
                            "newFirstName" => $firstName,
                            "newLastName" => $lastName,
                            "newSMSNotificationsEnabled" => $newSMSNotificationsEnabled,
                            "optInMessage" => $optInMessage
                        ];
                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_profile_update",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode($profileUpdates),
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

                // Remove Phone cache
                delCacheByKey(createDBQueryCacheKeyWithProvidedName('UserPhones',
                    "uphone-" . $GLOBALS['user']->getObjectId()));

                // Update Push notifications flag
                // $userDevice = parseExecuteQuery(array("user" => $GLOBALS['user']), "UserDevices", "", "createdAt", [], 1);
                // $userDevice->set("isPushNotificationEnabled", convertToBool($pushNotificationsEnabled));
                // $userDevice->save();

                // Remove /checkin cache
                delCacheCheckinInfo($GLOBALS['user']->getObjectId());

                $responseArray = array("changed" => true, "optInMessage" => $optInMessage);
            }
        }

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Change Profile options -- Apply for Air Employee
$app->post('/profile/update/airEmployee/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // Fetch Post variables
        $postVars = array();

        $postVars['employerName'] = $employerName = urldecode($app->request()->post('employerName'));
        $postVars['employeeSince'] = $employeeSince = urldecode($app->request()->post('employeeSince'));
        $postVars['employmentCardImage'] = $employmentCardImage = $app->request()->post('employmentCardImage');

        if (empty($employerName)
            || empty($employeeSince)
            || empty($employmentCardImage)
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        } else {

            printPostVars($postVars, "862");
        }

        // Register Stream wrapper
        stream_wrapper_register("var", "VariableStream") or json_error("AS_5102", "", "Stream Wrapper registry failed",
            1);
        if (!check_base64_pngimage($employmentCardImage)) {

            json_error("AS_5103", "", "Provided Employee Id employmentCardImage is not a valid image.", 1);
        }

        $employmentCardImage = base64_decode($employmentCardImage);

        $airEmployee = new ParseObject("AirEmployeeRequests");
        $airEmployee->set('user', $GLOBALS['user']);
        $airEmployee->set('employerName', $employerName);
        $airEmployee->set('employeeSince', $employeeSince);
        $airEmployee->set('rejectionReason', "");
        $airEmployee->set('status', 1);
        $airEmployee->save();

        // S3 Upload Bug Image
        $s3_client = getS3ClientObject();
        $keyWithFolderPath = getS3KeyPath_FilesAirEmployee() . '/' . $airEmployee->getObjectId() . '.png';
        $employmentCardImageURL = S3UploadFileWithContents($s3_client, $GLOBALS['env_S3BucketName'], $keyWithFolderPath,
            $employmentCardImage, false);

        if (is_array($employmentCardImageURL)) {

            json_error($employmentCardImageURL["error_code"], "",
                $employmentCardImageURL["error_message_log"] . " Air employee card Image save failed", 1, 1);
        }

        // Update employmentCardImage image name
        $airEmployee->set('employmentCardImage', $airEmployee->getObjectId() . '.png');
        $airEmployee->save();

        $customerName = $GLOBALS['user']->get('firstName') . ' ' . $GLOBALS['user']->get('lastName');

        // Slack it
        $slack = new SlackMessage($GLOBALS['env_SlackWH_contactForm'], 'env_SlackWH_contactForm');
        $slack->setText("Air Employee Request");

        $attachment = $slack->addAttachment();
        $attachment->addField("Customer:", $customerName, true);
        $attachment->addField("When:", date("M j, g:i a", time()), true);
        $attachment->addField("By:", $customerName, true);

        try {

            $slack->send();
        } catch (Exception $ex) {

            json_error("AS_1054", "",
                "Slack post failed informing Air Employee signup! Post Array=" . json_encode($attachment->getAttachment()) . " -- " . $ex->getMessage(),
                1, 1);
        }

        $responseArray = array("applied" => "1");

        json_echo(
            json_encode($responseArray)
        );
    });

// Change Profile options -- Verify if Air Employee
$app->get('/profile/verify/isAirEmployee/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        // Check if the user has this flight already in their trips
        $airEmployeeRequests = parseExecuteQuery(["user" => $GLOBALS['user']], "AirEmployeeRequests", "", "updatedAt",
            [], 1);

        if (count_like_php5($airEmployeeRequests) > 0) {

            $responseArray = [
                "status" => $airEmployeeRequests->get('status'),
                "rejectionReason" => $airEmployeeRequests->get('rejectionReason')
            ];
        } else {

            $responseArray = ["status" => 0, "rejectionReason" => ""];
        }

        json_echo(
            json_encode($responseArray)
        );
    });


// Change Email & Username
$app->get('/profile/changeEmail/a/:apikey/e/:epoch/u/:sessionToken/newEmail/:newEmail',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    function ($apikey, $epoch, $sessionToken, $newEmail) {

        // Check if already have cache for this
        getRouteCache();

        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("VERIFYEMAIL", $GLOBALS['user']->getObjectId(), 10, 60);

        // verify if max attempts reached
        if(!$isUserAllowedToContinue) {

            json_error("AS_447", "You have reached maximum attempts allowed. Please try again in an hour.", "Max attempts for Email Verify resend reached ", 1);
        }

        // Sanitize email
        $newEmail = strtolower(sanitizeEmail(rawurldecode($newEmail)));

        if(empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {

            json_error("AS_401", "Please enter a valid email.", "User's email ($newEmail) is invalid", 1);
        }
        else if(strcasecmp($newEmail, createEmailFromUsername($GLOBALS['user']->get('username'),$GLOBALS['user']))==0) {

            json_error("AS_401", "Your current and new email are the same.", "Same email address used as current ($newEmail)", 1);
        }
        // Verify if this email has any type of account
        // If user exists in with any type of account, this API should not have been called
        else if(isRegisteredUserByEmail($newEmail, $GLOBALS['user']->get('typeOfLogin'))) {

            json_error("AS_421", "This email address is registered with another account.", "User's requested new email ($newEmail) found with existing user", 3);
        }

        // Connect to Queue
        // $GLOBALS['sqs_client'] = getSQSClientObject();
        try {

            // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        }
        catch (Exception $ex) {

            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", "User Change email failed! " . $response["error_message_log"], 1);
        }



        // Update username and email addresses
        $objUser = new ParseObject("_User", $GLOBALS['user']->getObjectId());

        //in case that is new session management, username is not changed
        if (UserAuthHelper::isSessionTokenFromCustomSessionManagement($sessionToken) === false){
            $objUser->set("username", createUsernameFromEmail($newEmail, $GLOBALS['user']->get('typeOfLogin')));
        }

        $objUser->set("email", $newEmail);
        $objUser->save(true);

        // Send email verified via Sendgrid via SQS
        try {

            // Send email verify link via Sendgrid via Queue
            $workerQueue->sendMessage(
                array("action" => "email_verify",
                    "content" =>
                        array(
                            "objectId" => $GLOBALS['user']->getObjectId(),
                            // "email" => $newEmail,
                            // "type" => $GLOBALS['user']->get('typeOfLogin'),
                            // "first_name" => $GLOBALS['user']->get('firstName'),
                            // "last_name" => $GLOBALS['user']->get('lastName'),
                        )
                )
            );
        }
        catch (Exception $ex) {

            json_error("AS_457", "", "Verify email send failed due to Queue for " . $GLOBALS['user']->getObjectId() . ' with error ' . $ex->getMessage(), 1);
        }

        $responseArray = array("changed" => true);

        // Respond with 10 seconds of caching
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 10
            ])
        );
    });


$app->get('/signup/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthWithoutActiveAccess',
    function ($apikey, $epoch, $sessionToken, $newEmail) {

        // Check if already have cache for this
        getRouteCache();

        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("VERIFYEMAIL", $GLOBALS['user']->getObjectId(), 10, 60);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_447", "You have reached maximum attempts allowed. Please try again in an hour.",
                "Max attempts for Email Verify resend reached ", 1);
        }

        // Sanitize email
        $newEmail = strtolower(sanitizeEmail(rawurldecode($newEmail)));

        if (empty($newEmail) || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {

            json_error("AS_401", "Please enter a valid email.", "User's email ($newEmail) is invalid", 1);
        } else {
            if (strcasecmp($newEmail, createEmailFromUsername($GLOBALS['user']->get('username'),$GLOBALS['user'])) == 0) {

                json_error("AS_401", "Your current and new email are the same.",
                    "Same email address used as current ($newEmail)", 1);
            }
            // Verify if this email has any type of account
            // If user exists in with any type of account, this API should not have been called
            else {
                if (isRegisteredUserByEmail($newEmail, $GLOBALS['user']->get('typeOfLogin'))) {

                    json_error("AS_421", "This email address is registered with another account.",
                        "User's requested new email ($newEmail) found with existing user", 3);
                }
            }
        }

        // Connect to Queue
        // $GLOBALS['sqs_client'] = getSQSClientObject();
        try {

            // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        } catch (Exception $ex) {

            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", "User Change email failed! " . $response["error_message_log"], 1);
        }

        // Update username and email addresses
        $objUser = new ParseObject("_User", $GLOBALS['user']->getObjectId());
        $objUser->set("username", createUsernameFromEmail($newEmail, $GLOBALS['user']->get('typeOfLogin')));
        $objUser->set("email", $newEmail);
        $objUser->save(true);

        // Send email verified via Sendgrid via SQS
        try {

            // Send email verify link via Sendgrid via Queue
            $workerQueue->sendMessage(
                array(
                    "action" => "email_verify",
                    "content" =>
                        array(
                            "objectId" => $GLOBALS['user']->getObjectId(),
                            // "email" => $newEmail,
                            // "type" => $GLOBALS['user']->get('typeOfLogin'),
                            // "first_name" => $GLOBALS['user']->get('firstName'),
                            // "last_name" => $GLOBALS['user']->get('lastName'),
                        )
                )
            );
        } catch (Exception $ex) {

            json_error("AS_457", "",
                "Verify email send failed due to Queue for " . $GLOBALS['user']->getObjectId() . ' with error ' . $ex->getMessage(),
                1);
        }

        $responseArray = array("changed" => true);

        // Respond with 10 seconds of caching
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 10
            ])
        );
    });

// Profile Update - Change Password
$app->post('/profile/changePassword/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) use ($app) {


        // temporary save of the username
        $username = strtolower($GLOBALS['user']->get('username'));
        $email = strtolower($GLOBALS['user']->get('email'));
        $first_name = $GLOBALS['user']->get('firstName');
        $last_name = $GLOBALS['user']->get('lastName');
        $typeOfLogin = $GLOBALS['user']->get('typeOfLogin');

        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("CHGPWD", $username, 10, 60);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_446", "You have reached maximum attempts allowed. Please try again in an hour.",
                "Max attempts for Change password reached ", 1);
        }

        // Fetch Post variables
        $postVars = array();
        $postVars['oldPassword'] = $oldPassword = rawurldecode($app->request()->post('oldPassword'));
        $postVars['newPassword'] = $newPassword = rawurldecode($app->request()->post('newPassword'));

        if (empty($oldPassword)
            || empty($newPassword)
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }

        list($sessionToken, $type) = parseSessionToken($sessionToken);


        // Decrypt password
        try {

            $oldPassword = decryptStringInMotion($oldPassword);
            $newPassword = decryptStringInMotion($newPassword);
        } catch (Exception $ex) {

            json_error("AS_430", "", "Password provided not encrypted.", 1);
        }

        // Check if both passwnews are same
        if (strcasecmp($newPassword, $oldPassword) == 0) {

            json_error("AS_434", "Your old and new password cannot be same.", "Old and new passwords are same.", 1);
        } // Password requirements for new password
        else {
            if (!doesPasswordMeetRequirements($newPassword)) {

                json_error("AS_432", "Password requirements not met for new password.",
                    "Password requirements not met.", 1);
            }
        }

        // Record sign in attempt
        $isUserAllowedToContinue = addToCountOfAttempt("SIGNIN", $username, 10, 60);

        // Max attempts reached, notify user
        if (!$isUserAllowedToContinue) {

            $GLOBALS['user']->set("isLocked", true);
            $GLOBALS['user']->save(true);

            json_error("AS_437", "You have exceeded maximum login attempts.",
                "Max sign in attempts reached; account locked.", 1);
        }

        // save current user
        $userCurrent = $GLOBALS['user'];

        // Current session token
        $currentSessionToken = parseSessionToken($sessionToken)[0];


        // login with old password to verify it is the correct current password
        $error_array = loginUser($email, $oldPassword, $type);

        // Check if the login failed due to username/password being incorrect
        if (count_like_php5($error_array) > 0) {

            // Notify user
            json_error($error_array["error_code"], $error_array["error_message_user"],
                $error_array["error_message_log"], $error_array["error_severity"]);
        } // Success - Change password
        else {
            // Change with master key

            $userObjectId = $GLOBALS['user']->getObjectId();
            $GLOBALS['user']->set('password', generatePasswordHash($newPassword));
            $GLOBALS['user']->save(true);


            // logout temp session
            logoutUser(
                $GLOBALS['user']->getObjectId(),
                $GLOBALS['user']->getSessionToken(),
                false,
                $currentSessionToken
            );

            // List all existing active Session Devices
            $objSessionDevices = parseExecuteQuery([
                "isActive" => true,
                "__NE__sessionTokenRecall" => $currentSessionToken,
                "user" => $userCurrent
            ], "SessionDevices");

            // Traverse through all SessionDevice
            // Expire existing Session Devices and logout all sessions except current one
            $sessionsCleared = [];
            foreach ($objSessionDevices as $obj) {

                $obj->set("sessionEndTimestamp", time());
                $obj->set("isActive", false);
                $obj->save();

                if (!in_array($obj->get('sessionTokenRecall'), $sessionsCleared)) {

                    try {

                        logoutUser($userCurrent->getObjectId(), $obj->get('sessionTokenRecall'), false);
                    } catch (Exception $ex) {

                        json_error("AS_443", "",
                            "Old session token clean up failed at forgot password change userObjectId = " . $userObjectId . ' sessionToken = ' . $obj->get('sessionTokenRecall') . " - " . $ex->getMessage(),
                            2, 1);
                    }
                }

                $sessionsCleared[] = $obj->get('sessionTokenRecall');
            }

            // Response
            $responseArray = array("changed" => true);

            // sign in succeeded, clear sign in attempts
            clearCountOfAttempt("SIGNIN", $username);

            // Put on Queue to email about password change
            // Connect to Queue
            // $GLOBALS['sqs_client'] = getSQSClientObject();
            try {

                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueEmailConsumerName']);

                // Send password change confirmation via Sendgrid via Queue
                $workerQueue->sendMessage(
                    array(
                        "action" => "email_password_reset_confirmation",
                        "content" =>
                            array(
                                "email" => $email,
                                "type" => $typeOfLogin
                                // "first_name" => $first_name,
                                // "last_name" => $last_name,
                            )
                    )
                );
            } catch (Exception $ex) {

                json_error("AS_458", "",
                    "email_password_reset_confirmation failed due to Queue for " . $userObjectId . ' with error ' . $ex->getMessage(),
                    1);
            }

            /*
            $response = SQSSendMessage($GLOBALS['sqs_client'], $GLOBALS['env_workerQueueConsumerName'],
                        array("action" => "email_password_reset_confirmation",
                              "content" =>
                                  array(
                                      "email" => $email,
                                      "first_name" => $first_name,
                                      "last_name" => $last_name,
                                  )
                            )
                        );

            if(is_array($response)) {

                json_error($response["error_code"], "", $response["error_message_log"] . " email_password_reset_confirmation failed due to SQS", 1);
            }
            */

            // Log password change event
            json_error("AS_3002", "", "Pwd change for " . $email, 3, 1);

            // clear password change attempts
            clearCountOfAttempt("CHGPWD", $username);
        }

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Send Email Verify email again
$app->get('/profile/emailVerifyResend/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    //'apiAuthWithoutActiveAccess',
    function ($apikey, $epoch, $sessionToken) {

        // If email is already verified; printe error and stop
        if ($GLOBALS['user']->get('emailVerified') == true) {

            json_error("AS_442", "", "Email already verified for user", 1);
        }

        // Check if already have cache for this
        getRouteCache();

        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("VERIFYEMAIL", $GLOBALS['user']->getObjectId(), 10, 60);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_447", "You have reached maximum attempts allowed. Please try again in an hour.",
                "Max attempts for Email Verify resend reached ", 1);
        }

        // Connect to Queue
        // $GLOBALS['sqs_client'] = getSQSClientObject();
        try {

            // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueEmailConsumerName']);

            // Send email verify link via Sendgrid via Queue
            $workerQueue->sendMessage(
                array(
                    "action" => "email_verify",
                    "content" =>
                        array(
                            "objectId" => $GLOBALS['user']->getObjectId(),
                            // "email" => $GLOBALS['user']->get('email'),
                            // "type" => $GLOBALS['user']->get('typeOfLogin'),
                            // "first_name" => $GLOBALS['user']->get('firstName'),
                            // "last_name" => $GLOBALS['user']->get('lastName'),
                        )
                )
            );
        } catch (Exception $ex) {

            json_error("AS_459", "",
                "email_verify send failed due to Queue for " . $GLOBALS['user']->getObjectId() . ' with error ' . $ex->getMessage(),
                1);
        }

        $responseArray = array("status" => true);

        // Respond with 10 seconds of caching
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 10
            ])
        );
    });

// Forgot - Request Token
// JMD
$app->get('/forgot/requestToken/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email', 'apiAuthWithoutSession',

    function ($apikey, $epoch, $sessionToken, $type, $email) {

        // Check if already have cache for this
        getRouteCache();

        // Sanitize email
        $email = strtolower(sanitizeEmail($email));
        $type = strtolower($type);

        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("FGTREQTOKEN", $email, 10, 60);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_448", "You have reached maximum attempts allowed. Please try again in an hour.",
                "Max attempts for Forgot Request Token reached ", 1);
        }

        // Find User to make sure it exists
        $findUser = parseExecuteQuery(array("username" => createUsernameFromEmail($email, $type)), "_User", "", "", [],
            1);

        // User found send, send code
        if (count_like_php5($findUser) > 0) {

            // Check if email has been verified
            /*
            if($findUser->get('emailVerified') != true) {

                json_error("AS_452", "", "Email address not verified, hence forgot password can't be sent", 3);
            }
            */

            // Connect to Queue
            // $GLOBALS['sqs_client'] = getSQSClientObject();
            try {

                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueEmailConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "email_forgot_password",
                        "content" =>
                            array(
                                "email" => $email,
                                "type" => $findUser->get("typeOfLogin"),
                                // "first_name" => $findUser->get('firstName'),
                                // "last_name" => $findUser->get('lastName'),
                            )
                    )
                );
            } catch (Exception $ex) {

                json_error("AS_460", "",
                    "email_forgot_password email send failed due to Queue for " . $findUser->getObjectId() . ' with error ' . $ex->getMessage(),
                    1);
            }
        }

        // Response of true is sent regardless user is found or not
        $responseArray = array("status" => true);

        // Respond with 10 seconds of caching

        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 10
            ])
        );
    });

// Forgot - Validate Token
$app->get('/forgot/validateToken/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email/token/:token',
    'apiAuthWithoutSession',
    function ($apikey, $epoch, $sessionToken, $type, $email, $token) {

        $email = strtolower(sanitizeEmail(rawurldecode($email)));
        $type = strtolower($type);

        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("FGTVALTOKEN", $email, 10, 60);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_449", "You have reached maximum attempts allowed. Please try again in an hour.",
                "Max attempts for Forgot Validate Token reached ", 1);
        }

        // Find if the user exists
        $findUser = parseExecuteQuery(array("username" => createUsernameFromEmail($email, $type)), "_User");

        // Get token from cache
        $forgotToken = forgotGetToken($email);

        // User found send, send code
        // If token is empty (cache expired or didn't exist) or doesn't match
        if (count_like_php5($findUser) > 0
            && !empty($forgotToken)
            && $forgotToken == intval($token)
        ) {

            $responseArray = array("status" => true);
        } else {

            $responseArray = array("status" => false);

            json_error("AS_439", "The code provided is invalid.", "Invalid Forgot Token", 3, 1);
        }

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Forgot - Change password
$app->post('/forgot/changePassword/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthWithoutSession',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // Fetch Post variables
        $postVars = array();
        $postVars['email'] = $email = strtolower(rawurldecode($app->request()->post('email')));
        $postVars['type'] = $type = strtolower(urldecode($app->request()->post('type')));
        $postVars['token'] = $token = urldecode($app->request()->post('token'));
        $postVars['newPassword'] = $newPassword = rawurldecode($app->request()->post('newPassword'));

        if (empty($email) ||
            empty($token) ||
            empty($newPassword)
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }

        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("FGTCHGPWD", $email, 10, 60);

        // verify if max attempts reached
        if (!$isUserAllowedToContinue) {

            json_error("AS_450", "You have reached maximum attempts allowed. Please try again in an hour.",
                "Max attempts for Forgot Change Password Token reached ", 1);
        }

        // Find user if it exists
        $findUser = parseExecuteQuery(array("username" => createUsernameFromEmail($email, $type)), "_User", "", "", [],
            1);

        // Get token from cache
        $forgotToken = forgotGetToken($email);


        // User found send, send code
        // Change password if token matches

        if (!empty($forgotToken)
            && $forgotToken == intval($token)
        ) {

            // Decrypt password
            try {

                $newPassword = decryptStringInMotion($newPassword);
            } catch (Exception $ex) {

                json_error("AS_440", "", "Password provided not encrypted.", 1);
            }

            // Password requirements for new password
            if (!doesPasswordMeetRequirements($newPassword)) {

                json_error("AS_441", "Password requirements not met for new password.",
                    "Password requirements not met.", 1);
            }

            // Connect to Queue
            // $GLOBALS['sqs_client'] = getSQSClientObject();
            try {

                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            } catch (Exception $ex) {

                $response = json_decode($ex->getMessage(), true);
                json_error($response["error_code"], "",
                    "User Change password failed! " . $response["error_message_log"], 1);
            }

            // Change with master key
            $findUser->set('password', generatePasswordHash($newPassword));
            $findUser->save(true);

            // List all existing active Session Devices
            $objSessionDevices = parseExecuteQuery(["isActive" => true, "user" => $findUser], "SessionDevices");

            // Traverse through all SessionDevice
            // Expire existing Session Devices and logout all sessions
            foreach ($objSessionDevices as $obj) {

                $obj->set("sessionEndTimestamp", time());
                $obj->set("isActive", false);
                $obj->save();

                try {

                    logoutUser($findUser->getObjectId(), $obj->get('sessionTokenRecall'), false);
                } catch (Exception $ex) {

                    json_error("AS_443", "",
                        "Old session token failed email = " . $email . ' sessionToken = ' . $obj->get('sessionTokenRecall') . " - " . $ex->getMessage(),
                        2, 1);
                }
            }

            // remove forgot token
            forgotRemoveToken($email);

            // clear any sign in max attempts
            clearCountOfAttempt("SIGNIN", $email);

            // Send token via Sendgrid via Queue
            try {

                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueEmailConsumerName']);

                // Send email verify link via Sendgrid via Queue
                $workerQueue->sendMessage(
                    array(
                        "action" => "email_password_reset_confirmation",
                        "content" =>
                            array(
                                "email" => $email,
                                "type" => $findUser->get('typeOfLogin')
                                // "first_name" => $findUser->get('firstName'),
                                // "last_name" => $findUser->get('lastName'),
                            )
                    )
                );
            } catch (Exception $ex) {

                json_error("AS_461", "",
                    "email_password_reset_confirmation email send failed due to Queue for " . $findUser->getObjectId() . ' with error ' . $ex->getMessage(),
                    1, 1);
            }

            // Log password change event
            json_error("AS_3001", "", "Pwd change for " . $email, 3, 1);

            $responseArray = array("status" => true);
        } else {

            $responseArray = array("status" => false);
        }

        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// User Info
$app->get('/info/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        $currentSessionToken = parseSessionToken($sessionToken)[0];

        // Find latest user device

        //logResponse(json_encode($currentSessionToken));
        $objSessionDevice = parseExecuteQuery(array("sessionTokenRecall" => $currentSessionToken, "isActive" => true),
            "SessionDevices", "", "createdAt", ["userDevice"], 1);

        $responseArray = getCurrentUserInfo($objSessionDevice->get("userDevice"));

        list($creditMapApplied, $availableCredits, $referralSignupCreditApplied) = getAvailableUserCreditsViaMap($GLOBALS['user']);
        $responseArray['availableCredits'] = $availableCredits;
        $responseArray['availableCreditsDisplay'] = dollar_format($availableCredits);

        if (isset($GLOBALS['userSession'])){
            $responseArray['newSessionHasFullAccess'] = $GLOBALS['userSession']->hasFullAccess();
        }

        json_echo(
            json_encode($responseArray)
        );
    });

// Sign out
$app->get('/signout/a/:apikey/e/:epoch/u/:sessionToken',
    // 'apiAuthWithoutActiveAccess',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    function ($apikey, $epoch, $sessionToken) {

        // Response
        $responseArray = array("status" => true);





        // logout session
        try {

            $parseUserId = $GLOBALS['user']->getObjectId();

            if (UserAuthHelper::isSessionTokenFromCustomSessionManagement($sessionToken)){
                delCacheCheckinInfo($parseUserId);

                $userAuthService = UserAuthServiceFactory::create();
                $userAuthService->logout($sessionToken);

            }else{
                logoutUser($GLOBALS['user']->getObjectId(), $GLOBALS['user']->getSessionToken(), true, "", true);
            }


            // Log user event
            if ($GLOBALS['env_LogUserActions']) {

                try {

                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                    $workerQueue->sendMessage(
                        array(
                            "action" => "log_user_action_logout",
                            "content" =>
                                array(
                                    "objectId" => $parseUserId,
                                    "data" => json_encode([]),
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


            // deactivate custom sessions when exists
            $userAuthService = UserAuthServiceFactory::create();
            $userAuthService->logoutCustomSessionByParseUserId($parseUserId);

        } catch (Exception $ex) {

            json_error("AS_438", "", "Sign out failed " . $ex->getMessage(), 2, 1);

            $responseArray = array("status" => false);
        }





        // Respond without caching
        json_echo(
            json_encode($responseArray)
        );
    });

// Email Verify (Called Externally)
$app->get('/emailVerify/t/:token',
    function ($token) {

        $token = sanitize($token);

        /*
        // Record attempt with a max of 10 attempts allowed per 60 mins
        $isUserAllowedToContinue = addToCountOfAttempt("EMAILVERIFYTOKEN", $token, 10, 60);

        // verify if max attempts reached
        if(!$isUserAllowedToContinue) {

            // non exit
            json_error("AS_451", "You have reached maximum attempts allowed. Please try again in an hour.", "Max attempts for Email Verify Token reached (" . emailVerifyGetToken($token) . ")", 3, 1);
        }
        */

        // Get token from cache
        $objectId = emailVerifyGetToken($token);

        // If no token was found
        if (empty($objectId)) {

            // non exit
            json_error("AS_480", "", "Invalid token provided", 3, 1);


            $dir = __DIR__;
            $file = $dir . '/../../' . $GLOBALS['env_HerokuSystemPath'] . $GLOBALS['env_EmailTemplatePath'] . 'email_verify_failed' . '.html';
            if (!file_exists($file)) {
                gracefulExit();
            }

            echo(file_get_contents($file));
            gracefulExit();

            //echo(emailFetchFileContent('email_verify_failed' . '.html'));
            //gracefulExit();
        }

        $findUser = array();

        if (!empty($objectId)) {

            $findUser = parseExecuteQuery(array("objectId" => $objectId), "_User", "", "", [], 1);


        }
        // User found, mark verified
        // If token is empty (cache expired or didn't exist) or doesn't match
        if (count_like_php5($findUser) > 0) {


            // Mark email verified
            $findUser->set("emailVerified", true);
            $findUser->save(true);

            // Delete Token
            //emailVerifyRemoveToken($token);

            $dir = __DIR__;
            $file = $dir . '/../../' . $GLOBALS['env_HerokuSystemPath'] . $GLOBALS['env_EmailTemplatePath'] . 'email_verify_confirmation' . '.html';
        } // Invalid Token
        else {
            $dir = __DIR__;
            $file = $dir . '/../../' . $GLOBALS['env_HerokuSystemPath'] . $GLOBALS['env_EmailTemplatePath'] . 'email_verify_failed' . '.html';
        }

        if (!file_exists($file)) {
            gracefulExit();
        }

        echo(file_get_contents($file));
        gracefulExit();
    });

$app->get('/signup/promo/a/:apikey/e/:epoch/u/:sessionToken/couponCode/:couponCode',
    // \App\Consumer\Middleware\ApiMiddleware::class . '::authWithoutActiveAccess',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::addCurrentUserAsFirstAndRemoveApiKeysFromParams',
    UserController::class . ':addCouponForSignup'
);

$app->get('/addpromo/a/:apikey/e/:epoch/u/:sessionToken/couponCode/:couponCode',
    //\App\Consumer\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithoutActiveAccess',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::addCurrentUserAsFirstAndRemoveApiKeysFromParams',
    UserController::class . ':addCouponAfterSignup'
);


/**
 * Jira ticket: MVP-1280
 * This method is called by route: POST /applyCreditsToUser/a/:apikey/e/:epoch/u/:sessionToken
 * Data in POST: 'creditsInCents', 'reasonForCredit', 'reasonForCreditCode', orderId', 'userId'
 *
 * Admin User applies credit to Consumer User for any reason
 */
$app->post('/applyCreditsToUser/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\ApiMiddleware::class . '::apiAuthAdmin',
    \App\Consumer\Middleware\OrderApplyCreditsToUserMiddleware::class . '::validate',
    UserController::class . ':applyCreditsToUser'
);

$app->get('/addPhoneWithTwilio/a/:apikey/e/:epoch/u/:sessionToken/phoneCountryCode/:phoneCountryCode/phoneNumber/:phoneNumber',
    \App\Consumer\Middleware\ApiMiddleware::class . '::authWithoutActiveAccess',
    UserController::class . ':addPhoneWithTwilio'
)->name('user-add-phone-twilio');

$app->get('/verifyPhoneWithTwilio/a/:apikey/e/:epoch/u/:sessionToken/phoneId/:phoneId/verifyCode/:verifyCode',
    \App\Consumer\Middleware\ApiMiddleware::class . '::authWithoutActiveAccess',
    UserController::class . ':verifyPhoneWithTwilio'
);

$app->get('/refer/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        // Check if already have cache for this
        $namedCacheKey = generateReferStatusCacheKey($GLOBALS['user']);
        // getRouteCache($namedCacheKey);

        $responseArray = [];

        // Check if the program is enabled
        if ($GLOBALS['env_UserReferralRewardEnabled'] == false) {

            json_echo(
                setRouteCache([
                    "cacheSlimRouteNamedKey" => $namedCacheKey,
                    "jsonEncodedString" => json_encode($responseArray),
                    // "expireInSeconds" => 10*60
                ])
            );
        }

        // Log user event
        if ($GLOBALS['env_LogUserActions']) {

            try {

                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "log_user_action_referral_info",
                        "content" =>
                            array(
                                "objectId" => $GLOBALS['user']->getObjectId(),
                                "data" => json_encode([]),
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

        $userReferral = parseExecuteQuery(["user" => $GLOBALS['user']], "UserReferral", "", "", [], 1);

        $responseArray["referralCode"] = getReferralCode($GLOBALS['user'], $userReferral);
        $responseArray["totalBalanceDollarFormatted"] = dollar_format(getAvailableUserCreditsViaMap($GLOBALS['user'])[1]);
        $responseArray["totalEarnedDollarFormatted"] = dollar_format_no_cents(getTotalReferralRewardEarned($GLOBALS['user']));
        $responseArray["offerTextDisplay"] = 'REFER, GIVE ' . dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']) . ' & EARN ' . dollar_format_no_cents($GLOBALS['env_UserReferralRewardInCents']);


        //$responseArray["sampleReferTextDisplay"] = 'Checkout the AtYourGate app. They deliver food to your gate at the airport! Get ' . dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']) . ' off your next order (over ' . dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']) . ') when you use my code ' . $responseArray["referralCode"] . '. Download now: www.atyourgate.com/download/?r=' . $responseArray["referralCode"];
        $responseArray["sampleReferTextDisplay"] = 'Check out the AtYourGate app. They deliver food to your gate at the airport! Get ' . dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']) . ' off your next order (over ' . dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']) . ') when you use my code ' . $responseArray["referralCode"] . '. Download now: https://qr.atyourgate.com/refer/?code=RefCodeNum=' . $responseArray["referralCode"];

        $responseArray["sampleReferTitleDisplay"] = 'Get ' . dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']) . ' OFF your airport food delivery!';

        $responseArray["rulesOverviewTextDisplay"] = 'You earn ' . dollar_format_no_cents($GLOBALS['env_UserReferralRewardInCents']) . ' credits for every friend who places their first order. To make it sweeter, they too get ' . dollar_format_no_cents($GLOBALS['env_UserReferralOfferInCents']) . ' (spend over ' . dollar_format_no_cents($GLOBALS['env_UserReferralMinSpendInCentsForOfferUse']) . '). So spread the word!';

        $responseArray["referralProgramRulesLink"] = $GLOBALS['env_UserReferralRulesLink'];

        // Respond with 10 mins of caching
        json_echo(
            setRouteCache([
                "cacheSlimRouteNamedKey" => $namedCacheKey,
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 10 * 60
            ])
        );
    });




$app->notFound(function () {

    json_error("AS_005", "", "Incorrect API Call.");
});

function userSignIn($app)
{

    // Fetch Post variables
    $postVars = array();

    // json_error("AS_USERNAME", "", "Raw Username - " . $app->request()->post('username'), 3, 1);

    $postVars['username'] = $email = strtolower(sanitizeEmail(rawurldecode($app->request()->post('username'))));
    $postVars['deviceArray'] = $deviceArray = urldecode($app->request()->post('deviceArray'));

    $postVars['password'] = $password = rawurldecode($app->request()->post('password'));
    $postVars['type'] = $type = strtolower(urldecode($app->request()->post('type')));

    // json_error("AS_USERNAME_SANITZED", "", "Sanitized Username - " . $email, 3, 1);

    if (empty($email)
        || empty($password)
        || empty($type)
        || empty($deviceArray)
    ) {

        json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
    }

    // Decrypt password
    try {

        $password = decryptStringInMotion($password);
        // json_error("AS_PASS", "", "Password - " . $password, 3, 1);
    } catch (Exception $ex) {

        json_error("AS_428", "", "Password provided not encrypted - " . $ex->getMessage(), 1);
    }

    // Decode deviceArray (also sanitizes)
    $deviceArrayDecoded = decodeDeviceArray($deviceArray);

    if (!isValidDeviceArray($deviceArrayDecoded)) {

        json_error("AS_423", "", "Device array was not well-formed - " . json_encode($deviceArrayDecoded), 1);
    }

    // Record sign in attempt
    //$isUserAllowedToContinue = addToCountOfAttempt("SIGNIN", $email, 10, 60);
    $isUserAllowedToContinue = true;
    // verify if max attempts reached
    if (!$isUserAllowedToContinue) {

        // $obJUserLocked = parseExecuteQuery(array("email" => $email), "_User");
        // $obJUserLocked[0]->set("isLocked", true);
        // $obJUserLocked[0]->save(true);

        //json_error("AS_435", "You have exceeded maximum login attempts.", "Max sign in attempts reached; account locked.", 1);
        return json_error_return_array("AS_435 ", "You have exceeded maximum login attempts.",
            "Max sign in attempts reached; account locked.", 1);

    }

    // Login users
    $error_array = loginUser($email, $password, $type, false);
    // $error_array = [];

    // Check if the login failed due to username/password being incorrect
    if (count_like_php5($error_array) > 0) {

        // Notify user
        json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"],
            $error_array["error_severity"]);
    } // Success
    else {
        // check if user is has rights for consumer app
        $currentUser = $GLOBALS['user'];
        if ($currentUser->get('hasConsumerAccess') !== true) {
            logoutUser($currentUser->getObjectId());
            json_error("AS_417", 'User without consumer access tries to login to the consumer app',
                'User ' . $currentUser->getObjectId() . ' without consumer access tries to login to the consumer app',
                1);
        }

        $responseArray = afterSignInSuccess($email, $type, $deviceArrayDecoded);
    }
    return $responseArray;
}

$app->run();

?>
