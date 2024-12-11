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
require $dirpath . 'lib/gatemaps.inc.php';
require $dirpath . 'lib/errorhandlers.php';


use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;

use Parse\ParseFile;

Braintree_Configuration::environment($env_BraintreeEnvironment);
Braintree_Configuration::merchantId($env_BraintreeMerchantId);
Braintree_Configuration::publicKey($env_BraintreePublicKey);
Braintree_Configuration::privateKey($env_BraintreePrivateKey);

// Get List of Payment types associated with Customer
$app->get('/list/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken) {

        $duplicateCallCount = getNewBraintreeCustomerCreateCounter($GLOBALS['user']->getObjectId());

        // Default Array
        $responseArray = array("paymentTypes" => "");

        $paymentsObject = parseExecuteQuery(array("user" => $GLOBALS['user']), "Payments");

        $customerId = "";
        // If Customer was not found, Create Customer Id
        if (count_like_php5($paymentsObject) == 0) {

            if ($duplicateCallCount > 1) {

                //json_error("AS_719", "", "Payment List called multiple times in quick succession", 1);
            }

            $customerId = createBraintreeCustomer();
        } // Else fetch it
        else {

            $customerId = $paymentsObject[0]->get('externalCustomerId');
        }

        // Get Payment Method list
        list($paymentMethods, $uniqueNumberIdentifiers) = getPaymentMethods($customerId);
        unset($uniqueNumberIdentifiers);

        // If Payment methods found
        if (count_like_php5($paymentMethods) > 0) {

            // Log user event
            if ($GLOBALS['env_LogUserActions']) {

                try {

                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                    $workerQueue->sendMessage(
                        array(
                            "action" => "log_user_action_payment_list",
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

            // Encrypt Response
            $encrypted_json = encryptPaymentInfo(json_encode($paymentMethods));

            unset($paymentMethods);

            $responseArray = array("paymentTypes" => $encrypted_json);
            unset($encrypted_json);
        }

        json_echo(
            json_encode($responseArray)
        );
    });




$app->post('/chargeCardForCredits/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    \App\Consumer\Middleware\PaymentChargeCardForCredits::class . '::validate',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::addCurrentUserAsFirstAndRemoveApiKeysFromParams',
    \App\Consumer\Controllers\PaymentController::class . ':chargeCardForCredits'
);



// Create Payment Method type for the given paymentMethodNonce
// NOT CALLED BY ANDROID APP
$app->get('/create/a/:apikey/e/:epoch/u/:sessionToken/paymentMethodNonce/:paymentMethodNonce',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken, $paymentMethodNonce) {

        global $creditCardTypes;

        // Default Array
        $responseArray = array("created" => "0", "token" => "", "paymentTypes" => "");

        $paymentsObject = parseExecuteQuery(array("user" => $GLOBALS['user']), "Payments");

        // If Customer was not found, Create Customer Id
        if (count_like_php5($paymentsObject) == 0) {

            $customerId = createBraintreeCustomer();
        } else {

            $customerId = $paymentsObject[0]->get('externalCustomerId');
        }

        // Get Payment Method list of existing methods
        list($paymentMethods, $uniqueNumberIdentifiers) = getPaymentMethods($customerId);
        unset($paymentMethods);

        // Create Payment Method with Payment Nonce
        try {

            if ($GLOBALS['env_AllowDuplicateAccounts'] == false
                && $GLOBALS['env_AllowDuplicatePaymentMethod'] == false
            ) {

                $options = [
                    'failOnDuplicatePaymentMethod' => true,
                    'verifyCard' => true
                ];
            } else {

                $options = [
                    'verifyCard' => true
                ];
            }

            /*
            $paymentCreate = Braintree_PaymentMethod::create([
                'customerId' => $customerId,
                'paymentMethodNonce' => $paymentMethodNonce,
                'options' => $options
            ]);
            */
            $options = [
                'verifyCard' => false
            ];
            // just checking if verification is fine
            $paymentCreate = Braintree_PaymentMethod::create([
                'customerId' => $customerId,
                'paymentMethodNonce' => $paymentMethodNonce,
                'options' => $options,
            ]);

        } catch (Exception $ex) {

            json_error("AS_703", "", "Payment Method creation failed! " . $ex->getMessage(), 1);
        }

        // Creation failed
        if (!$paymentCreate->success) {

            $errorMessage = "";

            if (isset($paymentCreate->errors)
                && count_like_php5($paymentCreate->errors->deepAll()) > 0
            ) {

                if ($GLOBALS['env_AllowDuplicateAccounts'] == false
                    && $GLOBALS['env_AllowDuplicatePaymentMethod'] == false
                ) {

                    foreach ($paymentCreate->errors->deepAll() as $error) {

                        // Duplicate Payment if using: failOnDuplicatePaymentMethod
                        if ($error->code == 81724) {

                            // Log duplicate usage
                            try {

                                logAccountDuplicateUsage($GLOBALS['user'], [], "Payment usage");
                            } catch (Exception $ex) {

                                json_error("AS_718", "", "Payment duplication log failure " . $ex->getMessage(), 2, 1);
                            }

                            json_error("AS_717",
                                "This card is already associated with an account and may not be added again.",
                                "Payment Method creation failed! " . braintreeErrorCollect($paymentCreate), 3);
                        }
                    }
                }

                $errorMessage = braintreeErrorCollect($paymentCreate);
            }

            // If the Payment type was declined, record its error type
            if (isset($paymentCreate->verification) && isset($paymentCreate->verification->status) && isset($paymentCreate->verification->processorResponseCode) && ((strcasecmp($paymentCreate->verification->status,
                            "processor_declined") == 0) || (strcasecmp($paymentCreate->verification->status,
                            "gateway_rejected") == 0))
            ) {

                //$errorMessage .= "Error Code: " . $paymentCreate->verification["processorResponseCode"] . " - Error Response: " . $paymentCreate->verification["processorResponseText"];
                $errorMessage .= "Error Code: " . $paymentCreate->verification->processorResponseCode . " - Error Response: " . $paymentCreate->verification->processorResponseText;

                json_error("AS_704", "Your payment method was declined.",
                    "Payment Method creation failed! " . $errorMessage, 2);
            } else {

                $cacheId = setUnknownPaymentFailureLogCache($paymentCreate);
                //json_error("AS_704", "",
                //    "Payment Method creation failed! " . $errorMessage . " - Payment dump created in Cache with name: " . $cacheId,
                //    1);
                json_error("AS_704", "Your payment method was declined, please verify your card details.",
                    "Payment Method creation failed! " . $errorMessage, 2);
            }
        }

        $expired = "N";
        if (strlen($paymentCreate->paymentMethod->expired) > 0) {

            $expired = "Y";
        }

        $paymentMethods = array();

        // If credit card was added:
        if (isset($paymentCreate->paymentMethod->cardType)
            && in_array($paymentCreate->paymentMethod->cardType, $creditCardTypes)
        ) {

            // Check if this method already exists
            if (isset($paymentCreate->paymentMethod->uniqueNumberIdentifier)
                && in_array($paymentCreate->paymentMethod->uniqueNumberIdentifier, $uniqueNumberIdentifiers)
            ) {

                // Delete the payment method just added
                deletePaymentMethod($paymentCreate->paymentMethod->token);

                json_error("AS_705",
                    "It seems you already have this payment added. If you wish to update, please delete the existing one before adding.",
                    "Payment Method creation failed because duplicate CCUID found.", 3);
            }

            $paymentMethods = array(
                "token" => $paymentCreate->paymentMethod->token,
                "paymentType" => "credit_card",
                "paymentTypeId" => $paymentCreate->paymentMethod->last4,
                "paymentTypeName" => $paymentCreate->paymentMethod->cardType,
                "paymentTypeIconURL" => $paymentCreate->paymentMethod->imageUrl,
                "expired" => $expired
            );
        } // PayPal
        else {

            $paymentMethods = array(
                "token" => $paymentCreate->paymentMethod->token,
                "paymentType" => "paypal_account",
                "paymentTypeId" => $paymentCreate->paymentMethod->email,
                "paymentTypeName" => "PayPal",
                "paymentTypeIconURL" => $paymentCreate->paymentMethod->imageUrl,
                "expired" => $expired
            );
        }

        // Encrypt Response
        $encrypted_json = encryptPaymentInfo(json_encode($paymentMethods));

        $responseArray = array(
            "created" => "1",
            "token" => $paymentCreate->paymentMethod->token,
            "paymentTypes" => $encrypted_json
        );

        unset($uniqueNumberIdentifiers);
        unset($paymentMethods);
        unset($paymentCreate);
        unset($encrypted_json);

        // Log user event
        if ($GLOBALS['env_LogUserActions']) {

            try {

                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "log_user_action_payment_add",
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

        json_echo(
            json_encode($responseArray)
        );
    });

// Delete Payment type for the given Token
$app->get('/delete/a/:apikey/e/:epoch/u/:sessionToken/token/:token',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken, $token) {

        // Default Array
        $responseArray = array("deleted" => "0");

        $paymentsObject = parseExecuteQuery(array("user" => $GLOBALS['user']), "Payments");

        // Records found, then return
        if (count_like_php5($paymentsObject) > 0) {

            // Get List of Payment Methods
            try {

                // Get List of Payment Methods
                $findClient = Braintree_Customer::find(
                    $paymentsObject[0]->get('externalCustomerId')
                );
            } catch (Exception $ex) {

                json_error("AS_708", "", "Payment Method list fetch failed before delete! " . $ex->getMessage(), 1);
            }

            // Any errors found
            if (isset($findClient->errors) && count_like_php5($findClient->errors->deepAll()) > 0) {

                json_error("AS_716", "", braintreeErrorCollect($findClient), 2, 1);
            }

            if (count_like_php5($findClient->creditCards) > 0 || count_like_php5($findClient->paypalAccounts) > 0) {

                $flag = 0;
                foreach ($findClient->creditCards as $creditCard) {

                    if (strcasecmp($creditCard->token, $token) == 0) {

                        $flag = 1;
                        break;
                    }
                }
                unset($creditCard);

                // Credit Card not found, continue searching in Paypal accounts
                if ($flag == 0) {
                    foreach ($findClient->paypalAccounts as $paypalAccount) {

                        if (strcasecmp($paypalAccount->token, $token) == 0) {

                            $flag = 1;
                            break;
                        }
                    }
                }
                unset($paypalAccount);

                unset($findClient);

                if ($flag == 0) {

                    json_error("AS_701", "", "Token doesn't belong to the user!", 1);
                }

                // Delete Payment Method
                deletePaymentMethod($token);

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {

                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_payment_delete",
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

                $responseArray = array("deleted" => "1");
            }
        }

        json_echo(
            json_encode($responseArray)
        );
    });

// Get Client Token with Customer Id
$app->get('/token/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken) {

        $paymentsCustomerId = parseExecuteQuery(array("user" => $GLOBALS['user']), "Payments");

        // Records found, then return
        if (count_like_php5($paymentsCustomerId) > 0) {

            $customerId = $paymentsCustomerId[0]->get('externalCustomerId');
        } // Create Customer Id
        else {

            $customerId = createBraintreeCustomer();
        }

        $clientToken = Braintree_ClientToken::generate([
            "customerId" => $customerId
        ]);

        $responseArray = array("token" => $clientToken);

        // Log user event
        if ($GLOBALS['env_LogUserActions']) {

            try {

                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "log_user_action_payment_add_begin",
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

        json_echo(
            json_encode($responseArray)
        );
    });

$app->notFound(function () {

    json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

function getPaymentMethods($customerId)
{

    $paymentMethods = array();
    $uniqueNumberIdentifiers = array();

    try {

        // Get List of Payment Methods
        $findClient = Braintree_Customer::find(
            $customerId
        );
    } catch (Exception $ex) {

        json_error("AS_707", "", "Payment Method list fetch failed! " . $ex->getMessage(), 1);
    }

    // Any errors found
    if (isset($findClient->errors) && count_like_php5($findClient->errors->deepAll()) > 0) {

        json_error("AS_716", "", braintreeErrorCollect($findClient), 3, 1);
    }

    // If Credit Cards or Paypal Accounts found
    if (count_like_php5($findClient->creditCards) > 0 || count_like_php5($findClient->paypalAccounts) > 0) {

        $paymentMethods = array();

        // Go through Credit Cards
        foreach ($findClient->creditCards as $creditCard) {

            $expired = "N";
            if (strlen($creditCard->expired) > 0) {

                $expired = "Y";
            }

            $paymentMethods[] = array(
                "token" => $creditCard->token,
                "paymentType" => "credit_card",
                "paymentTypeId" => $creditCard->last4,
                "paymentTypeName" => $creditCard->cardType,
                "paymentTypeIconURL" => $creditCard->imageUrl,
                "expired" => $expired
            );

            // Save the Unique Identifier (only available for Credit Cards)
            $uniqueNumberIdentifiers[] = $creditCard->uniqueNumberIdentifier;
        }
        unset($creditCard);

        // Go through Paypal Accounts
        foreach ($findClient->paypalAccounts as $paypalAccount) {

            $expired = "N";

            $paymentMethods[] = array(
                "token" => $paypalAccount->token,
                "paymentType" => "paypal_account",
                "paymentTypeId" => $paypalAccount->email,
                "paymentTypeName" => "PayPal",
                "paymentTypeIconURL" => $paypalAccount->imageUrl,
                "expired" => $expired
            );

            // Save the PayPal email address
            $uniqueNumberIdentifiers[] = $paypalAccount->email;
        }
        unset($paypalAccount);

        unset($findClient);
    }

    return array($paymentMethods, $uniqueNumberIdentifiers);
}

function deletePaymentMethod($token)
{

    try {

        $deletePayment = Braintree_PaymentMethod::delete($token);

        // Deletion failed
        if (!$deletePayment->success) {

            $errorMessage = "";
            if (isset($deletePayment->errors)
                && count_like_php5($deletePayment->errors->deepAll()) > 0
            ) {

                $errorMessage = braintreeErrorCollect($deletePayment);
            }

            json_error("AS_702", "", "Token deletion failed! " . $errorMessage, 1);
        }
    } catch (Exception $ex) {

        json_error("AS_702", "", "Token deletion failed!" . $ex->getMessage(), 1);
    }
}

function createBraintreeCustomer()
{

    // Create
    $createClient = Braintree_Customer::create([
        'firstName' => $GLOBALS['user']->get('firstName'),
        'lastName' => $GLOBALS['user']->get('lastName'),
        'email' => $GLOBALS['user']->get('email')
    ]);

    // Creation failed
    if (!$createClient->success) {

        $errorMessage = "";
        if (count_like_php5($createClient->errors->deepAll()) > 0) {

            $errorMessage = braintreeErrorCollect($createClient);
        }

        json_error("AS_711", "", "Client Id Creation Failed!", 1);
    }

    $customerId = $createClient->customer->id;

    // Save Client in Parse
    $paymentsCustomerId = new ParseObject("Payments");
    $paymentsCustomerId->set('user', $GLOBALS['user']);
    $paymentsCustomerId->set('externalCustomerId', $customerId);

    try {

        $paymentsCustomerId->save();
    } catch (ParseException $ex) {

        json_error("AS_712", "", "Client Id Save Failed!" . $ex->getMessage(), 1);
    }

    // Else
    return $customerId;
}

?>
