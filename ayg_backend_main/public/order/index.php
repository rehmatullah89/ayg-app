<?php


$allowedOrigins = [
    "http://ayg-deb.test",
    "https://ayg.ssasoft.com",
    "http://ec2-18-116-237-65.us-east-2.compute.amazonaws.com",
    "http://ec2-18-190-155-186.us-east-2.compute.amazonaws.com", // test
    "https://order.atyourgate.com", // prod
];

if (isset($_SERVER["HTTP_REFERER"]) && in_array(trim($_SERVER["HTTP_REFERER"], '/'), $allowedOrigins)) {
    header("Access-Control-Allow-Origin: " . trim($_SERVER["HTTP_REFERER"], '/'));
}

require 'dirpath.php';
require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use App\Consumer\Controllers\OrderController;
use App\Consumer\Dto\PartnerIntegration\CartItemList;
use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Responses\InfoTipsValuesResponse;
use App\Consumer\Services\CacheServiceFactory;
use App\Consumer\Services\OrderServiceFactory;
use App\Consumer\Helpers\QueueMessageHelper;
use App\Consumer\Services\QueueServiceFactory;
use App\Consumer\Helpers\OrderHelper;
use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Httpful\Request;

Braintree_Configuration::environment($env_BraintreeEnvironment);
Braintree_Configuration::merchantId($env_BraintreeMerchantId);
Braintree_Configuration::publicKey($env_BraintreePublicKey);
Braintree_Configuration::privateKey($env_BraintreePrivateKey);


// Get open Order without a retailer
$app->get('/getOpenOrder/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available Open order for user
        $order->fetchOpenOrder();

        try {

            // Validate if we found the order
            $order->performChecks();

            // If no exceptions, thrown fetch order info
            $responseArray = array("orderId" => $order->getOrderId(), "retailerId" => $order->getRetailerId());
        } catch (Exception $ex) {

            $responseArray = array("orderId" => "", "retailerId" => "");
        }

        json_echo(
            json_encode($responseArray)
        );
    });


// Create internal Order for the User and Retailer
$app->get('/initiate/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:uniqueRetailerId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $uniqueRetailerId) {

        //////////////////////////////////////////////////
        // When provided Retailer != Existing Open Order
        // Order Id, which cannot be closed automatically (has items in cart)
        $openOrderId = "";

        // Retailer Id
        $openRetailerId = "";
        //////////////////////////////////////////////////

        //////////////////////////////////////////////////
        // New Order that has been opened (if opened)
        $orderObjectId = "";

        // Status of the Order currently open (either newly opened or of the existing one)
        $status = 0;

        // Indicates if we closed the current order, which was empty
        $openOrderWasClosed = false;
        //////////////////////////////////////////////////

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available Open order
        $order->fetchOpenOrder();

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            // Validate if we found the order
            $order->performChecks();

            // If no exceptions thrown
            $openOrderFound = true;
        } catch (Exception $ex) {

            $openOrderFound = false;
        }
        ////////////////////////////////////////////////

        // Open Order was found
        if ($openOrderFound) {

            // If current open order is of a different retailer Id
            // And if the order is empty, the we can close it and open the newly requested one with a diff retailer
            if (strcasecmp($order->getRetailerId(), $uniqueRetailerId) != 0
                && $order->isOrderEmpty()
            ) {

                // Close this order so we can open a new one
                if ($order->closeOrder()) {

                    $openOrderWasClosed = true;
                }
            }

            // If this order wasn't closed, then send the order info back
            if ($openOrderWasClosed == false) {

                $openOrderId = $order->getOrderId();
                $openRetailerId = $order->getRetailerId();
                $status = $order->getStatus();
            }
        }

        // No Open order was found
        // Or we closed the current opened order
        if (!$openOrderFound || $openOrderWasClosed) {

            // Open a new order
            $newOrder = new Order($GLOBALS['user']);

            try {

                $newOrder->createNewOrder($uniqueRetailerId);
            } catch (Exception $ex) {

                json_error("AS_801", "", "New Order Creation Failed! " . $ex->getMessage(), 1);
            }

            $orderObjectId = $newOrder->getOrderId();
            $status = $newOrder->getStatus();
        }

        // Return its object id
        $responseArray = array(
            "orderId" => $orderObjectId,
            "status" => $status,
            "openOrderId" => $openOrderId,
            "openRetailerId" => $openRetailerId
        );

        json_echo(
            json_encode($responseArray)
        );
    });

// Reset internal Order by Retailer Id
$app->get('/close/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:uniqueRetailerId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $uniqueRetailerId) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available Open order by Retailer
        $order->fetchOpenOrderByRetailerId($uniqueRetailerId);

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            // Validate if we found the order
            $order->performChecks();

            // If no exceptions thrown, close order
            $order->closeOrder();

            $responseArray = array("reset" => "1");
        } catch (Exception $ex) {

            $responseArray = array("reset" => "0");

            json_error("AS_857", "",
                "Order Id not found for closing uniqueRetailerId = " . $uniqueRetailerId . " - " . $ex->getMessage(),
                1);
        }
        ////////////////////////////////////////////////

        json_echo(
            json_encode($responseArray)
        );
    });

// Reset internal Order by Order Id
$app->get('/close/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available Open order by order id
        $order->fetchOpenOrderByOrderId($orderId);

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            // Validate if we found the order
            $order->performChecks();

            // If no exceptions thrown, close order
            $order->closeOrder();

            $responseArray = array("reset" => "1");
        } catch (Exception $ex) {

            $responseArray = array("reset" => "0");

            json_error("AS_856", "", "Order Id not found for closing orderId = " . $orderId . " - " . $ex->getMessage(),
                1);
        }

        json_echo(
            json_encode($responseArray)
        );
    });

// Get Order Status rows
$app->get('/status/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId) {

        $responseArray = getPrintableOrderStatusList($orderId, false);

        json_echo(
            json_encode($responseArray)
        );
    });

// Get Order Status rows
$app->get('/statusFull/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId) {

        $responseArray = getPrintableOrderStatusList($orderId, true);

        json_echo(
            json_encode($responseArray)
        );
    });

// Add items to cart
$app->post('/addItem/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // Fetch Post variables
        $postVars = array();

        $postVars['orderId'] = $app->request()->post('orderId');
        $postVars['orderItemId'] = $app->request()->post('orderItemId');
        $postVars['uniqueRetailerItemId'] = $app->request()->post('uniqueRetailerItemId');
        $postVars['itemQuantity'] = $app->request()->post('itemQuantity');
        $postVars['itemComment'] = $app->request()->post('itemComment');
        $postVars['options'] = urldecode($app->request()->post('options'));

        $responseArray = add2Cart($postVars);

        json_echo(
            json_encode($responseArray)
        );
    });

// Add items to cart
$app->post('/add2Cart/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    \App\Consumer\Controllers\OrderController::class . ':add2CartItems'
);
/*$app->post('/add2Cart/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // Fetch Post variables
        $postVars = array();

        $postVars['orderId'] = $app->request()->post('orderId');
        $postVars['orderItemId'] = $app->request()->post('orderItemId');
        $postVars['uniqueRetailerItemId'] = $app->request()->post('uniqueRetailerItemId');
        $postVars['itemQuantity'] = $app->request()->post('itemQuantity');
        $postVars['itemComment'] = $app->request()->post('itemComment');
        $postVars['options'] = htmlspecialchars_decode($app->request()->post('options'));

        $responseArray = add2Cart($postVars);

        json_echo(
            json_encode($responseArray)
        );
    });*/

// Delete items from cart
$app->get('/deleteItem/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/orderItemId/:orderItemId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId, $orderItemId) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available order by order id
        $order->fetchOrderByOrderId($orderId);

        $order->fetchOrderModifiers();

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            $rules = [
                "exists" => true,
                "statusInList" => listStatusesForCart()
            ];

            // Validate if we found the order
            $order->performChecks($rules);
        } catch (Exception $ex) {

            json_error("AS_805", "",
                "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
        }

        // Deleting Item
        if (!empty($orderItemId) && $orderItemId != "0") {

            if (empty($order->getModifier($orderItemId))) {

                json_error("AS_893", "", "Cart Operation Failed! - ModifierId not found.", 1);
            }

            try {

                $orderModifiers = parseExecuteQuery(["objectId" => $orderItemId], "OrderModifiers", "", "",
                    ["order", "order.retailer", "order.retailer.location", "retailerItem"], 1);
                $order->deleteFromCart($orderItemId);

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {
                        $orderModifiers->get('order')->get('retailer')->fetch();
                        $orderModifiers->get('order')->get('retailer')->get('location')->fetch();
                        $retailerName = $orderModifiers->get('order')->get('retailer')->get('retailerName') . ' (' . $orderModifiers->get('order')->get('retailer')->get('location')->get('locationDisplayName') . ')';
                        $airportIataCode = $orderModifiers->get('order')->get('retailer')->get('airportIataCode');
                        $retailerUniqueId = $orderModifiers->get('order')->get('retailer')->get('uniqueId');
                        $uniqueRetailerItemId = $orderModifiers->get('retailerItem')->get('uniqueId');

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);


                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_delete_item_cart",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "retailer" => $retailerName,
                                            "actionForRetailerAirportIataCode" => $airportIataCode,
                                            "airportIataCode" => $airportIataCode,
                                            "orderId" => $order->getObjectId(),
                                            "retailerUniqueId" => $retailerUniqueId,
                                            "uniquetailerItemId" => $uniqueRetailerItemId
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
            } catch (Exception $ex) {

                json_error("AS_814", "",
                    "Item deletion from Cart failed! Order Modifier Object Id: " . $orderItemId . "; Exception: " . $ex->getMessage(),
                    1);
            }
        }

        // If the customer was acquired by referral and has available credits
        if (wasUserBeenAcquiredViaReferral($order->getUserObject())
            && getAvailableUserCreditsViaMap($order->getUserObject())[1] > 0
        ) {

            // build the cart again
            // check if there is a coupon and if the cart now has referral credits
            // If so, remove any coupons that might been added before the item was added or removed if the referral credit is on the order, this is stop double dipping
            if (doesOrderHaveReferralSignupCreditApplied($order->getDBObj())) {

                $order->removeCoupon();
            }
        }

        // Drop Cart cache
        $namedCacheKey = 'cart' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        $namedCacheKey = 'cartv2' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        // JMD
        delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getObjectId()));

        $responseArray = array("deleted" => "1");

        json_echo(
            json_encode($responseArray)
        );
    });


// Apply Tip
$app->get('/tip/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/tipPct/:tipPct',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken, $orderId, $tipPct) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available order by order id
        $order->fetchOrderByOrderId($orderId);

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            $rules = [
                "exists" => true,
                "statusInList" => listStatusesForCart()
            ];

            // Validate if we found the order
            $order->performChecks($rules);
        } catch (Exception $ex) {

            json_error("AS_816", "",
                "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
        }

        // Apply Tip
        try {

            $order->applyTip($tipPct);
        } catch (Exception $ex) {

            json_error("AS_818", "", "Tip apply failed - " . $ex->getMessage(), 1);
        }

        // Drop Cart cache
        $namedCacheKey = 'cart' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        $namedCacheKey = 'cartv2' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getObjectId()));

        $responseArray = array("applied" => "1");

        json_echo(
            json_encode($responseArray)
        );
    });

// Apply coupon
$app->get('/coupon/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/code/:couponCode',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',

    function ($apikey, $epoch, $sessionToken, $orderId, $couponCode) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available order by order id
        // JMD
        $order->fetchOrderByOrderId($orderId);
        $airportIataCode = $order->getAirportIataCode();

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            $rules = [
                "exists" => true,
                "statusInList" => listStatusesForCart()
            ];

            // Validate if we found the order
            $order->performChecks($rules);
        } catch (Exception $ex) {

            json_error("AS_816", "",
                "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
        }

        $couponCode = trim($couponCode);

        // Remove coupon	
        if (empty($couponCode) || $couponCode == '0') {

            $order->removeCoupon();

            $responseArray = array("applied" => 1, "comments" => "Coupon removed");
        } // Apply coupon
        else {

            // Validate coupon
            $userCreditAppliedMap = getUserCreditAppliedMapViaCache($order->getDBObj());
            list($couponObj, $isValid, $invalidReasonUser, $invalidReasonLog, $invalidErrorCode, $isReferralCode) = fetchValidCoupon("",
                $couponCode, $order->getRetailer(), $GLOBALS['user'], false, true, $userCreditAppliedMap);

            if ($isValid == false) {

                try {

                    //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);


                    $workerQueue->sendMessage(

                        array(
                            "action" => "log_user_action_cart_coupon_failed",
                            "content" =>
                                array(
                                    "objectId" => $GLOBALS['user']->getObjectId(),
                                    "data" => json_encode([
                                        "coupon" => $couponCode,
                                        "reason" => $invalidReasonLog,
                                        "actionForRetailerAirportIataCode" => $airportIataCode,
                                        "airportIataCode" => $airportIataCode,
                                        "orderId" => $orderId
                                    ]),
                                    "timestamp" => time()
                                )
                        )
                    );
                } catch (Exception $ex2) {

                    $response = json_decode($ex2->getMessage(), true);
                    json_error($response["error_code"], "",
                        "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                }

                json_error("AS_819", $invalidReasonUser,
                    "Coupon code (" . $couponCode . ") failed to apply for " . $orderId . " - " . $invalidReasonLog, 3);
            } else {
                if ($isReferralCode == true) {

                    $invalidReasonUser = 'This referral code can not be applied';
                    $invalidReasonLog = 'Referral code in cart.';

                    try {

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_cart_coupon_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "coupon" => $couponCode,
                                            "reason" => $invalidReasonLog,
                                            "actionForRetailerAirportIataCode" => $airportIataCode,
                                            "airportIataCode" => $airportIataCode,
                                            "orderId" => $orderId
                                        ]),
                                        "timestamp" => time()
                                    )
                            )
                        );
                    } catch (Exception $ex2) {

                        $response = json_decode($ex2->getMessage(), true);
                        json_error($response["error_code"], "",
                            "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                    }

                    json_error("AS_899", $invalidReasonUser,
                        "Coupon code (" . $couponCode . ") failed to apply for " . $orderId . " - " . $invalidReasonLog,
                        3);
                } /*
		// JMD
		// Is this coupon being applied at checkout while Referral credit is already applied to order
		else if(wasUserBeenAcquiredViaReferral($order->getUserObject())
 				&& getAvailableUserCreditsViaMap($order->getUserObject())[1] > 0
        		&& doesOrderHaveReferralSignupCreditApplied($order->getDBObj())) {

		    $invalidReasonUser = 'Sorry, coupon cannot be applied since you have promotional credit applied';
		    $invalidReasonLog = 'Coupon being applied while Promo credit in cart.';

	        try {

	            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

	            $workerQueue->sendMessage(
	                    array("action" => "log_user_action_cart_coupon_failed",
	                          "content" =>
	                            array(
	                                "objectId" => $GLOBALS['user']->getObjectId(),
	                                "data" => json_encode(["coupon" => $couponCode, "reason" => $invalidReasonLog, "actionForRetailerAirportIataCode" => $airportIataCode, "airportIataCode" => $airportIataCode, "orderId" => $orderId]),
	                                "timestamp" => time()
	                            )
	                        )
	                    );
	        }
	        catch (Exception $ex2) {

	            $response = json_decode($ex2->getMessage(), true);
	            json_error($response["error_code"], "", "Log user action queue message failed " . $response["error_message_log"], 1, 1);
	        }

			json_error("AS_819", $invalidReasonUser, "Coupon code (" . $couponCode . ") failed to apply for " . $orderId . " - " . $invalidReasonLog, 3);
		}
		*/
                else {

                    // Log user event
                    if ($GLOBALS['env_LogUserActions']) {
                        // JMD
                        try {

                            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                            $workerQueue->sendMessage(
                                array(
                                    "action" => "log_user_action_cart_coupon_applied",
                                    "content" =>
                                        array(
                                            "objectId" => $GLOBALS['user']->getObjectId(),
                                            "data" => json_encode([
                                                "coupon" => $couponCode,
                                                "actionForRetailerAirportIataCode" => $airportIataCode,
                                                "airportIataCode" => $airportIataCode,
                                                "orderId" => $orderId
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

                    // Else coupon found, apply to the Order
                    $order->applyCoupon($couponObj);

                    $responseArray = array("applied" => 1, "comments" => "Coupon applied");
                }
            }
        }

        // Drop Cart cache
        $namedCacheKey = 'cart' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        $namedCacheKey = 'cartv2' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getObjectId()));

        json_echo(
            json_encode($responseArray)
        );
    });

// Apply Airport Employee Discount
$app->get('/discount/airportEmployee/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/apply/:apply',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId, $apply) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available order by order id
        $order->fetchOrderByOrderId($orderId);

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            $rules = [
                "exists" => true,
                "statusInList" => listStatusesForCart()
            ];

            // Validate if we found the order
            $order->performChecks($rules);
        } catch (Exception $ex) {

            json_error("AS_816", "",
                "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
        }

        $apply = intval($apply);

        if ($apply == 1) {

            // Apply Discount
            $order->applyAirportEmployeeDiscount();

            // Remove Military Discount as both can't be given together
            $order->removeMilitaryDiscount();
        } else {

            // Remove Discount
            $order->removeAirportEmployeeDiscount();
        }

        $responseArray = array("applied" => $apply);

        // Drop Cart cache
        $namedCacheKey = 'cart' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        $namedCacheKey = 'cartv2' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getObjectId()));

        json_echo(
            json_encode($responseArray)
        );
    });

// Apply Military Discount
$app->get('/discount/military/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/apply/:apply',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId, $apply) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch available order by order id
        $order->fetchOrderByOrderId($orderId);

        ////////////////////////////////////////////////
        // Verify if an Open Order was found
        try {

            $rules = [
                "exists" => true,
                "statusInList" => listStatusesForCart()
            ];

            // Validate if we found the order
            $order->performChecks($rules);
        } catch (Exception $ex) {

            json_error("AS_816", "",
                "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
        }

        if ($apply == 1) {

            // Apply Discount
            $order->applyMilitaryDiscount();

            // Remove Airport Employee Discount as both can't be applied together
            $order->removeAirportEmployeeDiscount();
        } else {

            // Remove Discount
            $order->removeMilitaryDiscount();
        }

        $responseArray = array("applied" => $apply);

        // Drop Cart cache
        $namedCacheKey = 'cart' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        $namedCacheKey = 'cartv2' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));

        delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getObjectId()));

        json_echo(
            json_encode($responseArray)
        );
    });

$app->get('/activecount/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        // Finds In progress Orders
        $countActiveOrders = parseExecuteQuery(array(
            "user" => $GLOBALS['user'],
            "status" => listStatusesForPendingInProgress()
        ), "Order", "", "", [], 1, false, array(), 'count');

        $responseArray = array("count" => $countActiveOrders);

        json_echo(
            json_encode($responseArray)
        );
    });

// Order List
$app->get('/list/a/:apikey/e/:epoch/u/:sessionToken/type/:type',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken, $type) {

        // Type needs to be either c=completed, a=active
        if (strcasecmp($type, "c") != 0
            && strcasecmp($type, "a") != 0
        ) {

            json_error("AS_863", "", "Invalid type", 1);
        }

        $responseArray = array();

        // Finds Final Status orders Ordered or Paid or Confirmed
        $objParseQueryResults = parseExecuteQuery(array(
            "user" => $GLOBALS['user'],
            "status" => listStatusesForNonInternalNonCart()
        ), "Order", "", "submitTimestamp",
            array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user"));

        $fetchedRetailerArray = array();
        $fetchedAirportsArray = array();
        $currentTimeZone = date_default_timezone_get();
        foreach ($objParseQueryResults as $orderObjectResults) {

            $status = $orderObjectResults->get('status');
            $statusInterpreted = orderStatusType($orderObjectResults->get('status'));

            $typeOfOrder = isOrderActiveOrCompleted($orderObjectResults);
            if (strcasecmp($typeOfOrder, $type) != 0) {

                continue;
            }

            // Check if this retailer is no longer in the system
            if (empty($orderObjectResults->get('retailer'))) {

                continue;
            }

            $orderArray = array();

            $orderArray["orderId"] = $orderObjectResults->getObjectId();
            $orderArray["orderIdDisplay"] = $orderObjectResults->get('orderSequenceId');

            // Get Retailer Information
            if (isset($fetchedRetailerArray[$orderObjectResults->get('retailer')->get('uniqueId')])) {

                $retailerObjectResults = $fetchedRetailerArray[$orderObjectResults->get('retailer')->get('uniqueId')];
            } else {

                $retailerObjectResults = $orderObjectResults->get('retailer');
                $fetchedRetailerArray[$orderObjectResults->get('retailer')->get('uniqueId')] = $retailerObjectResults;
            }

            $orderArray["retailerId"] = $retailerObjectResults->get('uniqueId');
            $orderArray["retailerName"] = $retailerObjectResults->get('retailerName');
            $orderArray["retailerAirportIataCode"] = $retailerObjectResults->get('airportIataCode');
            $orderArray["retailerLocationId"] = $retailerObjectResults->get('location')->getObjectId();
            $orderArray["retailerImageLogo"] = preparePublicS3URL($retailerObjectResults->get('imageLogo'),
                getS3KeyPath_ImagesRetailerLogo($retailerObjectResults->get('airportIataCode')),
                $GLOBALS['env_S3Endpoint']);
            $airportIataCode = $retailerObjectResults->get('airportIataCode');

            //////////////////////////////////////////////////////////////////////////////////////////
            //////////////////////////////////// FETCH AIRPORT TTMEZONE //////////////////////////////
            if (isset($fetchedAirportsArray[$airportIataCode])) {

                list($airporTimeZone, $airporTimeZoneShort) = $fetchedRetailerArray[$airportIataCode];
            } else {

                $airporTimeZone = fetchAirportTimeZone($airportIataCode, $currentTimeZone);
                $airporTimeZoneShort = getTimezoneShort($airporTimeZone);
                $fetchedRetailerArray[$airportIataCode] = [$airporTimeZone, $airporTimeZoneShort];
            }
            //////////////////////////////////////////////////////////////////////////////////////////

            $orderArray["orderInternalStatus"] = orderStatusToPrint($orderObjectResults);
            $orderArray["orderInternalStatusCode"] = $status;

            // If active order
            // Get User ready status code
            if (strcasecmp($type, 'a') == 0) {

                $orderUserStatusList = orderStatusList($orderObjectResults);

                $orderArray["orderStatus"] = $orderUserStatusList[count_like_php5($orderUserStatusList) - 1]["status"];
                $orderArray["orderStatusCode"] = $orderUserStatusList[count_like_php5($orderUserStatusList) - 1]["statusCode"];
                $orderArray["orderStatusCategoryCode"] = orderStatusCategory($orderObjectResults,
                    $orderArray["orderStatusCode"]);
            } // Else it matches the completed status
            else {

                $orderArray["orderStatus"] = $orderArray["orderInternalStatus"];
                $orderArray["orderStatusCode"] = $orderArray["orderInternalStatusCode"];
                $orderArray["orderStatusCategoryCode"] = orderStatusCategory($orderObjectResults);
            }

            $orderArray["orderStatusDeliveryCode"] = $orderObjectResults->get('statusDelivery');

            $orderArray["fullfillmentETATimestamp"] = $orderObjectResults->get('etaTimestamp');
            $orderArray["fullfillmentETATimeDisplay"] = orderFormatDate($airporTimeZone,
                $orderObjectResults->get('etaTimestamp'), 'auto', 0, $currentTimeZone);
            $orderArray["fullfillmentETATimezoneShort"] = $airporTimeZoneShort;
            $orderArray["fullfillmentType"] = $orderObjectResults->get('fullfillmentType');

            $orderArray["orderSubmitTimestampUTC"] = $orderObjectResults->get('submitTimestamp');
            $orderArray["orderSubmitAirportTimeDisplay"] = orderFormatDate($airporTimeZone,
                $orderObjectResults->get('submitTimestamp'), 'auto', 0, $currentTimeZone);
            //$orderArray["etaRangeEstimateDisplay"] = getOrderFullfillmentTimeRangeEstimateDisplay($orderObjectResults->get('etaTimestamp') - $orderObjectResults->get('submitTimestamp'))[0];


            // time between now and eta timestamp

            $timeLeftToDelivery = $orderObjectResults->get('etaTimestamp') - (new DateTime('now'))->getTimestamp();

            $orderArray["etaRangeEstimateDisplay"] =
                \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                    $timeLeftToDelivery,
                    $orderObjectResults->get('isScheduled') === true? $GLOBALS['env_fullfillmentETALowInSecsForScheduled'] : $GLOBALS['env_fullfillmentETALowInSecs'],
                    $orderObjectResults->get('isScheduled') === true? $GLOBALS['env_fullfillmentETAHighInSecsForScheduled'] : $GLOBALS['env_fullfillmentETAHighInSecs'],
                    $airporTimeZone
                )[0];

            $responseArray[] = $orderArray;
        }


        foreach ($responseArray as $k=>$responseArraySingleOrder){
            if ($responseArraySingleOrder['fullfillmentType']=='p'){
                $responseArray[$k]=OrderHelper::updatePickupStagesFromOrderListReturnArray($responseArraySingleOrder);
            }
        }

        json_echo(
            json_encode($responseArray)
        );
    });

// Order Summary
$app->get('/summary/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId) {

        $order = parseExecuteQuery(array("objectId" => $orderId, "user" => $GLOBALS['user']), "Order", "", "", array(
            "retailer",
            "retailer.location",
            "retailer.retailerType",
            "deliveryLocation",
            "coupon",
            "coupon.applicableUser",
            "user"
        ), 1);

        // Log user event
        if ($GLOBALS['env_LogUserActions']) {
            // JMD
            try {

                $retailerName = $order->get('retailer')->get('retailerName') . ' (' . $order->get('retailer')->get('location')->get('locationDisplayName') . ')';
                $airportIataCode = $order->get('retailer')->get('airportIataCode');
                $pingArray = cartPingRetailer($order->get('retailer'));

                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "log_user_action_checkout_cart",
                        "content" =>
                            array(
                                "objectId" => $GLOBALS['user']->getObjectId(),
                                "data" => json_encode([
                                    "retailer" => $retailerName,
                                    "actionForRetailerAirportIataCode" => $airportIataCode,
                                    "airportIataCode" => $airportIataCode,
                                    "orderId" => $order->getObjectId(),
                                    "ping" => $pingArray
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

        $namedCacheKey = 'cart' . '__u__' . $GLOBALS['user']->getObjectId() . '__o__' . $order->getObjectId();

        // If status is of cart
        // Search for cache
        if (in_array($order->get('status'), listStatusesForCart())) {

            //getRouteCache($namedCacheKey);
            // @todo uncomment that
        }

        list($responseArray, $retailerTotals) = getOrderSummary($order, 1);

        json_echo(
            setRouteCache([
                "cacheSlimRouteNamedKey" => $namedCacheKey,
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => "EOD"
            ])
        );
    });


// Order Summary v2.0
$app->get('/summarize/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId) {

        $order = parseExecuteQuery(array("objectId" => $orderId, "user" => $GLOBALS['user']), "Order", "", "", array(
            "retailer",
            "retailer.location",
            "retailer.retailerType",
            "deliveryLocation",
            "coupon",
            "coupon.applicableUser",
            "user"
        ), 1);

        $namedCacheKey = 'cartv2' . '__u__' . $GLOBALS['user']->getObjectId() . '__o__' . $order->getObjectId();
        $namedCacheKeyRawSummary = 'cartv2raw' . '__u__' . $GLOBALS['user']->getObjectId() . '__o__' . $order->getObjectId();

        if (in_array($order->get('status'), listStatusesForAbandonedCart())) {

            json_error("AS_898", "", "Abandoned cart pull requested", 3);
        }

        // If status is of cart, search for cache
        if (in_array($order->get('status'), listStatusesForCart())) {

            // Log user event
            if ($GLOBALS['env_LogUserActions']) {
                // JMD
                try {
                    $order->get('retailer')->get('location')->fetch();
                    $retailerName = $order->get('retailer')->get('retailerName') . ' (' . $order->get('retailer')->get('location')->get('locationDisplayName') . ')';
                    $airportIataCode = $order->get('retailer')->get('airportIataCode');
                    $pingArray = cartPingRetailer($order->get('retailer'));

                    //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                    $workerQueue->sendMessage(
                        array(
                            "action" => "log_user_action_checkout_cart",
                            "content" =>
                                array(
                                    "objectId" => $GLOBALS['user']->getObjectId(),
                                    "data" => json_encode([
                                        "retailer" => $retailerName,
                                        "actionForRetailerAirportIataCode" => $airportIataCode,
                                        "airportIataCode" => $airportIataCode,
                                        "orderId" => $order->getObjectId(),
                                        "pingInfo" => $pingArray
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

            delCacheByKey($namedCacheKeyRawSummary);
            //getRouteCache($namedCacheKey);
        }

        list($responseArray, $retailerTotals) = getOrderSummary($order, 1);
        setCache($namedCacheKeyRawSummary, $responseArray, 1, "EOD");

        // Create new format of summary
        $responseArrayV2["internal"] = $responseArray["internal"];

        // add tips information
        $responseArrayV2["internal"]['TipsPCT'] = $responseArray['totals']['TipsPCT'];
        $responseArrayV2["internal"]['Tips'] = $responseArray['totals']['Tips'];
        $responseArrayV2["internal"]['TipsDisplay'] = $responseArray['totals']['TipsDisplay'];
        $responseArrayV2["internal"]['TipsAppliedAs'] = $responseArray['totals']['TipsAppliedAs'];


        $responseArrayV2["items"] = $responseArray["items"];
        $responseArrayV2["payment"] = $responseArray["payment"];
        $responseArrayV2["internal"]["couponCodeApplied"] = $responseArray["totals"]["CouponCodeApplied"];
        $responseArrayV2["internal"]["couponAppliedByDefault"] = $responseArray["totals"]["CouponAppliedByDefault"];

        // Set up v2 totals
        $negativeSign = "-";
        $sequence = 0;

        if (isset($responseArray["totals"]["AirEmployeeDiscount"])
            //&& $responseArray["totals"]["AirEmployeeDiscount"] > 0
            && isset($responseArray["totals"]["AirEmployeeDiscountApplied"])
            && $responseArray["totals"]["AirEmployeeDiscountApplied"] === true

        ) {

            $airEmployeeDiscountEligible = $GLOBALS['env_AirportEmployeeDiscountEnabled'];
            $airEmployeeDiscountApplied = ($GLOBALS['env_AirportEmployeeDiscountEnabled'] == true) ? true : false;

            if ($responseArray["totals"]["AirEmployeeDiscount"] > 0){
                $total[] = [
                    "textDisplay" => "Airport Employee discount",
                    "categoryDisplay" => "",
                    "valueDisplay" => $negativeSign . $responseArray["totals"]["AirEmployeeDiscountDisplay"],
                    "displaySequence" => ++$sequence,
                    "infoTitleDisplay" => "",
                    "infoDisplay" => [],
                    "fieldDisplayRules" => new ArrayObject([]),
                    "textDisplayHexColor" => "",
                    "categoryDisplayHexColor" => "",
                    "valueDisplayHexColor" => "#32CD32"
                ];
            }
        } else {

            $airEmployeeDiscountEligible = isAirportEmployeeDiscountEnabled($responseArray["internal"]["retailerUniqueId"],
                true);
            $airEmployeeDiscountApplied = false;
        }

        if (isset($responseArray["totals"]["MilitaryDiscount"])
            && $responseArray["totals"]["MilitaryDiscount"] > 0
        ) {

            $militaryDiscountEligible = $GLOBALS['env_MilitaryDiscountEnabled'];
            $militaryDiscountApplied = true;

            $total[] = [
                "textDisplay" => "Military discount",
                "categoryDisplay" => "",
                "valueDisplay" => $negativeSign . $responseArray["totals"]["MilitaryDiscountDisplay"],
                "displaySequence" => ++$sequence,
                "infoDisplay" => [],
                "fieldDisplayRules" => new ArrayObject([]),
                "textDisplayHexColor" => "",
                "categoryDisplayHexColor" => "",
                "valueDisplayHexColor" => "#32CD32"
            ];
        } else {

            $militaryDiscountEligible = isMilitaryDiscountEnabled($responseArray["internal"]["retailerUniqueId"]);
            $militaryDiscountApplied = false;
        }


        if (isset($responseArray["totals"]["serviceFeeExplicitlyShown"])
            && $responseArray["totals"]["serviceFeeExplicitlyShown"]
            && $responseArray["totals"]["ServiceFee"] > 0
        ) {

            $total[] = [
                "textDisplay" => "Taxes & charges",
                "categoryDisplay" => "",
                "valueDisplay" => $responseArray["totals"]["ServiceFeeAndTaxesDisplay"],
                "displaySequence" => ++$sequence,
                "infoTitleDisplay" => "Taxes & charges",
                "infoDisplay" => [
                    0 => ["infoTitleDisplay" => "Taxes - " . $responseArray["totals"]["TaxesDisplay"]],
                    1 => ["infoTitleDisplay" => "Service charges - " . $responseArray["totals"]["ServiceFeeDisplay"]],
                    2 => ["infoTitleDisplay" => "The service charges allow AtYourGate to operate with excellent service."],
                ],
                "fieldDisplayRules" => new ArrayObject([]),
                "textDisplayHexColor" => "",
                "categoryDisplayHexColor" => "",
                "valueDisplayHexColor" => "",
                "totalIndicator" => false
            ];
        } else {

            $total[] = [
                "textDisplay" => "Taxes",
                "categoryDisplay" => "",
                "valueDisplay" => $responseArray["totals"]["TaxesDisplay"],
                "displaySequence" => ++$sequence,
                "infoTitleDisplay" => "",
                "infoDisplay" => [],
                "fieldDisplayRules" => new ArrayObject([]),
                "textDisplayHexColor" => "",
                "categoryDisplayHexColor" => "",
                "valueDisplayHexColor" => "",
                "totalIndicator" => false
            ];
        }

        if (isset($responseArray["totals"]["CouponCodeApplied"])
            && !empty($responseArray["totals"]["CouponCodeApplied"])
        ) {

            // If coupon was for delivery but order is of pickup
            if (preg_match("/delivery/si", strtolower($responseArray["totals"]["CouponDisplay"]))
                && strcasecmp($order->get('fullfillmentType'), 'p') == 0
            ) {

                // skip
            } else {

                $negativeSignCoupon = '-';

                // Text already formatted
                if (strcasecmp(dollar_format($responseArray["totals"]["Coupon"]),
                        $responseArray["totals"]["CouponDisplay"]) != 0
                ) {

                    $negativeSignCoupon = '';
                }

                $total[] = [
                    "textDisplay" => "Coupon saving",
                    "categoryDisplay" => "",
                    "valueDisplay" => ($responseArray["totals"]["Coupon"] > 0) ? $negativeSignCoupon . $responseArray["totals"]["CouponDisplay"] : $responseArray["totals"]["CouponDisplay"],
                    "displaySequence" => ++$sequence,
                    "infoTitleDisplay" => "",
                    "infoDisplay" => [],
                    "fieldDisplayRules" => new ArrayObject([]),
                    "textDisplayHexColor" => "",
                    "categoryDisplayHexColor" => "",
                    "valueDisplayHexColor" => "#32CD32",
                    "totalIndicator" => false
                ];
            }
        }

        $totalTextDisplay = "Sub-Total";

        // If NOT a CART
        if (!in_array($order->get('status'), listStatusesForCart())) {

            $fullfillmentTypeName = $order->get('fullfillmentType') == 'd' ? "Delivery" : "Pickup";

            // Fee Credits
            $fullfillmentFeeCredits = 0;
            if ($order->get('fullfillmentType') == 'd'
                && $order->has('quotedFullfillmentDeliveryFeeCredits')
                && $order->get('quotedFullfillmentDeliveryFeeCredits') > 0
            ) {

                $fullfillmentFeeCredits = $order->get('quotedFullfillmentDeliveryFeeCredits');
            } else {
                if ($order->get('fullfillmentType') == 'p'
                    && $order->has('quotedFullfillmentPickupFeeCredits')
                    && $order->get('quotedFullfillmentPickupFeeCredits') > 0
                ) {

                    $fullfillmentFeeCredits = $order->get('quotedFullfillmentPickupFeeCredits');
                }
            }

            $valueDisplayHexColor = "";

            // If no fee was applied
            if ($responseArray["totals"]["AirportSherpaFee"] == 0) {

                // If this was due to the credits, then show the credits as the fee
                if ($fullfillmentFeeCredits > 0) {

                    $fullfillmentFee = dollar_format($fullfillmentFeeCredits) . " (-" . dollar_format($fullfillmentFeeCredits) . " credits)";
                } // Else this was FREE for non-credit reasons, coupon or promo rate
                else {

                    $valueDisplayHexColor = '#32CD32';
                    $fullfillmentFee = "FREE!";
                }
            } // If fee was paid
            else {

                // But we had some credits used
                if ($fullfillmentFeeCredits > 0) {

                    $fullfillmentFee = $responseArray["totals"]["AirportSherpaFeeDisplay"] . " (-" . dollar_format($fullfillmentFeeCredits) . " credits)";
                } // Else regular display
                else {

                    $fullfillmentFee = $responseArray["totals"]["AirportSherpaFeeDisplay"];
                }
            }

            $total[] = [
                "textDisplay" => $fullfillmentTypeName . " Charge",
                "categoryDisplay" => "",
                "valueDisplay" => $fullfillmentFee,
                "displaySequence" => ++$sequence,
                "infoTitleDisplay" => "",
                "infoDisplay" => [],
                "fieldDisplayRules" => new ArrayObject([]),
                "textDisplayHexColor" => "",
                "categoryDisplayHexColor" => "",
                "valueDisplayHexColor" => $valueDisplayHexColor,
                "totalIndicator" => false
            ];

            $totalTextDisplay = "Total";
        }

        if (isset($responseArray["totals"]["CreditsAppliedInCents"])
            && $responseArray["totals"]["CreditsAppliedInCents"] > 0
        ) {
            $total[] = [
                "textDisplay" => "Credits applied",
                "categoryDisplay" => "",
                "valueDisplay" => $negativeSign . $responseArray["totals"]["CreditsAppliedDisplay"],
                "displaySequence" => ++$sequence,
                "infoTitleDisplay" => "",
                "infoDisplay" => [],
                "fieldDisplayRules" => new ArrayObject([]),
                "textDisplayHexColor" => "",
                "categoryDisplayHexColor" => "",
                "valueDisplayHexColor" => "#32CD32",
                "totalIndicator" => false
            ];
        }

        $total[] = [
            "textDisplay" => $totalTextDisplay,
            "categoryDisplay" => "",
            "valueDisplay" => $responseArray["totals"]["TotalDisplay"],
            "displaySequence" => ++$sequence,
            "infoTitleDisplay" => "",
            "infoDisplay" => [],
            "fieldDisplayRules" => new ArrayObject([]),
            "textDisplayHexColor" => "",
            "categoryDisplayHexColor" => "",
            "valueDisplayHexColor" => "",
            "totalIndicator" => true
        ];

        $responseArrayV2["data"] = $total;

        $discounts = [];
        $sequence = 0;

        $airEmployeeDiscountEligible = isset($responseArray["totals"]["AirEmployeeEligable"]) ? $responseArray["totals"]["AirEmployeeEligable"] : false;
        $militaryDiscountEligible = isset($responseArray["totals"]["MilitaryDiscountEligable"]) ? $responseArray["totals"]["MilitaryDiscountEligable"] : false;

        $discounts[] = [
            "textDisplay" => "Apply Airport Employee Discount",
            "categoryDisplay" => "",
            "valueDisplay" => "",
            "displaySequence" => ++$sequence,
            "infoTitleDisplay" => "Discount Criteria",
            "infoDisplay" => [
                0 => ["infoTitleDisplay" => "To be eligible for this discount you must be a " . $order->get('retailer')->get('airportIataCode') . " airport employee with active credentials. Ineligible use of this discount would result in the discounted amount being billed separately."]
            ],
            "fieldDisplayRules" => [
                "eligible" => $airEmployeeDiscountEligible,
                "applied" => $airEmployeeDiscountApplied,
                "discountToggleEndpoint" => "airportEmployee"
            ],
            "textDisplayHexColor" => "",
            "categoryDisplayHexColor" => "",
            "valueDisplayHexColor" => "",
            "totalIndicator" => false
        ];

        $discounts[] = [
            "textDisplay" => "Apply Military Discount",
            "categoryDisplay" => "",
            "valueDisplay" => "",
            "displaySequence" => ++$sequence,
            "infoTitleDisplay" => "Discount Criteria",
            "infoDisplay" => [
                0 => ["infoTitleDisplay" => "We are proud to offer Military discount to all service members. Thank you for your service. Ineligible use of this discount would result in the discounted amount being billed separately."]
            ],
            "fieldDisplayRules" => [
                "eligible" => $militaryDiscountEligible,
                "applied" => $militaryDiscountApplied,
                "discountToggleEndpoint" => "military"
            ],
            "textDisplayHexColor" => "",
            "categoryDisplayHexColor" => "",
            "valueDisplayHexColor" => "",
            "totalIndicator" => false
        ];

        $responseArrayV2["discounts"] = $discounts;

        /*
	$responseArrayV2["discounts"] = [
		"airportEmployee" => ["eligible" => $airEmployeeDiscountEligible, "applied" => $airEmployeeDiscountApplied, "textDisplay" => "Apply Airport Employee Discount", "titleDisplay" => "Airport Employee Discount", "infoTextDisplay" => "To be eligible for this discount you must be a BWI airport employee with an active credentials.", "discountToggleEndpoint" => "airportEmployee"],
		"military" => ["eligible" => $militaryDiscountEligible, "applied" => $militaryDiscountApplied, "textDisplay" => "Apply Military Discount", "titleDisplay" => "Military Discount", "infoTextDisplay" => "We are proud to offer Military discount to all service members. Thank you for your service.", "discountToggleEndpoint" => "military"],
	];
	*/

        json_echo(
            setRouteCache([
                "cacheSlimRouteNamedKey" => $namedCacheKey,
                "jsonEncodedString" => json_encode($responseArrayV2),
                "expireInSeconds" => "EOD"
            ])
        );
    });

// Order Item Count
$app->get('/count/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId) {

        // Initialize Order under user
        $order = new Order($GLOBALS['user']);

        // Fetch count of items in the cart
        $orderItemCount = $order->fetchOrderModifiersCount($orderId);

        $responseArray = array("count" => $orderItemCount);

        json_echo(
            json_encode($responseArray)
        );
    });

$app->post('/checkout/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    \App\Consumer\Middleware\OrderCheckoutMiddleware::class . '::validate',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        $orderId = $app->request()->post('orderId');
        $deliveryLocation = $app->request()->post('deliveryLocation');
        $requestedFullFillmentTimestamp = $app->request()->post('requestedFullFillmentTimestamp');

        $applyTipAs = (string)$app->request()->post('applyTipAs');
        $applyTipValue = (int)$app->request()->post('applyTipValue');

        $tipsValuesInJson = ConfigHelper::get('env_TipsConfig');
        $tipsValues = InfoTipsValuesResponse::createFromJsonString($tipsValuesInJson);
        $orderService = OrderServiceFactory::create(CacheServiceFactory::create());


        if (in_array($applyTipAs, \App\Consumer\Entities\Order::TIP_APPLIED_AS_OPTIONS)) {
            if ($applyTipAs == \App\Consumer\Entities\Order::TIP_APPLIED_AS_DEFAULT) {
                // get default
                $default = $tipsValues->getDefault();
                if ($default !== null) {
                    $applyTipAs = $default->getType();
                    $applyTipValue = $default->getValue();
                } else {
                    $applyTipAs = null;
                    $applyTipValue = null;
                }
            }

            if ($applyTipAs !== null) {
                if ($applyTipAs == \App\Consumer\Entities\Order::TIP_APPLIED_AS_PERCENTAGE) {
                    $orderService->applyTipAsPercentage($orderId, $GLOBALS['user']->getObjectId(), $applyTipValue);
                } elseif ($applyTipAs == \App\Consumer\Entities\Order::TIP_APPLIED_AS_FIXED_VALUE) {
                    $orderService->applyTipAsFixedValue($orderId, $GLOBALS['user']->getObjectId(), $applyTipValue);
                }
            }
        }

        // apply tips if needed
        $responseArray = getFulfillmentQuote($orderId, $deliveryLocation, $requestedFullFillmentTimestamp);


        // add tips options to array
        $responseArray['tipsValues'] = $tipsValues;

        $responseArray['voucherOptions'] = $orderService->getVouchersOptions();
        $responseArray['d']['scheduledOptions'] = $orderService->getScheduledOrderOptions(
            $orderId,
            (int)$responseArray['d']['fullfillmentTimeRangeEstimateHighInSeconds']
        );


        // No cache
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray)
            ])
        );

    });


// Order fullfillment quote
$app->get('/getFullfillmentQuote/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/deliveryLocation/:deliveryLocation/requestedFullFillmentTimestamp/:requestedFullFillmentTimestamp',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId, $deliveryLocation, $requestedFullFillmentTimestamp) {

        $responseArray = getFulfillmentQuote($orderId, $deliveryLocation, $requestedFullFillmentTimestamp);

        // No cache
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray)
            ])
        );
    });


function getFulfillmentQuote($orderId, $deliveryLocation, $requestedFullFillmentTimestamp)
{
    // Also checks if the order belongs to the User
    $order = parseExecuteQuery(array(
        "objectId" => $orderId,
        "user" => $GLOBALS['user'],
        "status" => listStatusesForCart()
    ), "Order", "", "",
        array("retailer", "retailer.location", "retailer.retailerType", "deliveryLocation", "coupon", "user"), 1);

    if (count_like_php5($order) == 0) {

        json_error("AS_859", "", "Order not found! Order Id: " . $orderId);
    }

    // If deliveryLocation == 0, then allow it
    if (strcasecmp($deliveryLocation, "0") != 0) {

        // Check validity delivery location
        $objDeliveryLocation = getTerminalGateMapByLocationId($order->get('retailer')->get('airportIataCode'),
            $deliveryLocation);

        if (empty($objDeliveryLocation)) {

            json_error("AS_861", "", "Invalid delivery location: " . $deliveryLocation);
        }
    } // Set a default location for the Terminal
    else {

        $objDeliveryLocation = getTerminalGateMapDefaultLocation($order->get('retailer')->get('airportIataCode'));
    }

    // Get fullfillment info
    $retailer = $order->get('retailer');


    $responseArray = getFullfillmentInfoWithOrder($retailer, $objDeliveryLocation, $requestedFullFillmentTimestamp,
        $order);

    // Add instructions required
    $responseArray["d"]["requiresDeliveryInstructions"] = ($objDeliveryLocation->has('requiresDeliveryInstructions') && $objDeliveryLocation->get('requiresDeliveryInstructions') == true ? true : false);

    $deliveryFullfillmentFeesInCents = $pickupFullfillmentFeesInCents = 0;
    // Update quotes in the order object
    // If either of fullfillment type is available
    //if ($responseArray["d"]["isAvailable"]
    //    || $responseArray["p"]["isAvailable"]
    //) {

        $order->set("quotedFullfillmentFeeTimestamp", time());

        $deliveryFullfillmentFeesInCents = $responseArray["d"]["fullfillmentFeesInCents"];
        if ($responseArray["d"]["fullfillmentFeesInCents"] == -1) {

            $deliveryFullfillmentFeesInCents = 0;
        }

        $order->set("quotedFullfillmentDeliveryFee", $deliveryFullfillmentFeesInCents);

        $pickupFullfillmentFeesInCents = $responseArray["p"]["fullfillmentFeesInCents"];
        if ($responseArray["p"]["fullfillmentFeesInCents"] == -1) {

            $pickupFullfillmentFeesInCents = 0;
        }

        $order->set("quotedFullfillmentPickupFee", $pickupFullfillmentFeesInCents);

        $order->set("quotedFullfillmentDeliveryFeeCredits", $responseArray["quotedFullfillmentDeliveryFeeCredits"]);
        $order->set("quotedFullfillmentPickupFeeCredits", $responseArray["quotedFullfillmentPickupFeeCredits"]);

        $quotedEstimates = $responseArray;
        $quotedEstimates["d"]["deliveryLocation"] = $deliveryLocation;
        $order->set("quotedEstimates", json_encode($quotedEstimates));
        // JMD
        unset($responseArray["quotedFullfillmentDeliveryFeeCredits"]);
        unset($responseArray["quotedFullfillmentPickupFeeCredits"]);

        $order->save();
    //}

    // Log user event
    if ($GLOBALS['env_LogUserActions']) {
        // JMD
        try {

            $retailerName = $retailer->get('retailerName') . ' (' . $retailer->get('location')->get('locationDisplayName') . ')';
            $airportIataCode = $retailer->get('airportIataCode');

            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);


            $workerQueue->sendMessage(
                array(
                    "action" => "log_user_action_checkout_start",
                    "content" =>
                        array(
                            "objectId" => $GLOBALS['user']->getObjectId(),
                            "data" => json_encode([
                                "retailer" => $retailerName,
                                "actionForRetailerAirportIataCode" => $airportIataCode,
                                "airportIataCode" => $airportIataCode,
                                "orderId" => $order->getObjectId(),
                                "isDelivery" => convertBoolToString($responseArray["d"]["isAvailable"]),
                                "isPickup" => convertBoolToString($responseArray["p"]["isAvailable"]),
                                "deliveryFee" => $deliveryFullfillmentFeesInCents,
                                "pickupFee" => $pickupFullfillmentFeesInCents,
                                "deliveryEstimateInSecs" => $responseArray["d"]["fullfillmentTimeEstimateInSeconds"],
                                "pickupEstimateInSecs" => $responseArray["p"]["fullfillmentTimeEstimateInSeconds"]
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

    return $responseArray;
}

// JMD
// Order pre-submit validation
$app->post('/submit/validation/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // Fetch Post variables
        $postVars = array();

        $postVars['orderId'] = $orderId = urldecode($app->request()->post('orderId'));
        $postVars['fullfillmentType'] = $fullfillmentType = urldecode($app->request()->post('fullfillmentType'));
        $postVars['deliveryLocation'] = $deliveryLocationId = urldecode($app->request()->post('deliveryLocation'));
        $postVars['requestedFullFillmentTimestamp'] = $requestedFullFillmentTimestamp = floatval(urldecode($app->request()->post('requestedFullFillmentTimestamp')));



        if (empty($orderId)
            || empty($fullfillmentType)
            || empty_zero_allowed($deliveryLocationId)
            || empty_zero_allowed($requestedFullFillmentTimestamp)
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }


        //////////////////////////////////////////////////////////////////////////////////////////////////////
        // FETCH ORDER
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        $orderObject = parseExecuteQuery(array(
            "objectId" => $orderId,
            "user" => $GLOBALS['user'],
            "status" => listStatusesForCart()
        ), "Order", "", "", array(
            "retailer",
            "retailer.location",
            "retailer.retailerType",
            "deliveryLocation",
            "coupon",
            "coupon.applicableUser",
            "user"
        ), 1);

        if (count_like_php5($orderObject) == 0) {

            json_error("AS_821", "This order has already been placed. Please proceed to Track Orders.",
                "No open order found! Order Id: " . $orderId);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	






        // If for immediate fullfillment
        $immediateProcessing = false;
        if ($requestedFullFillmentTimestamp == 0) {

            $immediateProcessing = true;
            $requestedFullFillmentTimestamp = time();
        }

        // Call Order Summary function
        list($orderSummaryArray, $retailerTotals) = getOrderSummary($orderObject, 0, true,
            $requestedFullFillmentTimestamp, false, $fullfillmentType);

        if (!isset($orderSummaryArray["items"]) || count_like_php5($orderSummaryArray["items"]) == 0) {

            json_error("AS_824", "Please add at least one item to the order before submission.",
                "Order could not be submitted! Order Id: " . $orderId . " has no items!", 1);
        }

/*
        // new partners
        $retailerPartnerServiceFactory = new  \App\Consumer\Services\PartnerIntegrationServiceFactory(
            new \App\Consumer\Repositories\RetailerPartnerCacheRepository(
                new \App\Consumer\Repositories\RetailerPartnerParseRepository(),
                \App\Consumer\Services\CacheServiceFactory::create()
            )
        );
        $retailerPartnerService = $retailerPartnerServiceFactory->createByRetailerUniqueId($orderObject->get('retailer')->get('uniqueId'));
        $partnerRetailer = $retailerPartnerService->getPartnerIdByRetailerUniqueId($orderObject->get('retailer')->get('uniqueId'));
        if ($retailerPartnerService !== null) {
            $airportTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'));
            $cart = new \App\Consumer\Dto\PartnerIntegration\Cart(
                $partnerRetailer->getPartnerId(),
                CartItemList::createFromGetOrderSummaryItemListResult(
                    $orderSummaryArray["items"]
                ),
                new \DateTimeZone($airportTimeZone),
                $orderSummaryArray['totals']['subTotal']
            );

            $cartTotals = $retailerPartnerService->getCartTotals($cart);



            $saveOrderResultArray = $retailerPartnerService->submitOrder($cart, $cartTotals);
            die();
            $saveOrderResult = \App\Consumer\Entities\Partners\Grab\SaveOrderResult::createFromApiResult($saveOrderResultArray);

            if ($saveOrderResult->isSuccess()) {
                $orderObject->set('partnerName', $retailerPartnerService->getPartnerName());
                $orderObject->set('partnerOrderId', $saveOrderResult->getOrderID());
                $orderObject->save();
            }
        }
*/


        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        $preparedFlightInfo = [];
        $orderValidationErrorList = new \App\Consumer\Entities\OrderValidationErrorList();
        $departureGateInfo = "";
        $deliveryGateInfo = "";
        $directionsToDepartureGateFromDeliveryLocation = "";
        $directionsToDepartureGateFromRetailerLocation = "";
        $directionsToDeliveryLocationFromRetailerLocation = "";

        //if coupon is of different type then fullfillmentType show warning
        if (isset($orderSummaryArray["totals"]["CouponIsFullfillmentRestrictionApplied"]) && $orderSummaryArray["totals"]["CouponIsFullfillmentRestrictionApplied"] == true) {

            // @todo - change it to be more domain specific, now it is no_upcoming_flights only because the front-end buttons ("nevermind" and "place order") are fine

            $orderValidationErrorList->addItem(
                new \App\Consumer\Entities\OrderValidationError(
                    "incorrect_coupon_fullfillment_type",
                    "Your coupon code \"" . mb_strtoupper($orderSummaryArray["totals"]["couponCodeTitle"]) . "\" is not eligible for " . (strtolower($fullfillmentType) == 'd' ? 'delivery' : 'pickup') . " orders. The total payment amount for this order would be " . ($retailerTotals["TotalDisplay"]),
                    true,
                    "Not a " . (strtolower($fullfillmentType) == 'd' ? 'delivery' : 'pickup') . " coupon code!",
                    'Cancel',
                    'Proceed'
                ));
        }

        if ($immediateProcessing == false){
            $orderValidationErrorList->addItem(
                new \App\Consumer\Entities\OrderValidationError(
                    "scheduled_order",
                    "Your order will be confirmed by the retailer once it is closer to your scheduled delivery time.",
                    true,
                    "Scheduled Order",
                    'Cancel',
                    'Proceed'
                ));
        }

        // JMD
        // Existing flights within next 6 hours (in under 6 hrs left to midnight), or till midnight of requested timestamp
        list($flightTrip, $associatedFlightId) = getFlightIdForRequestedFullfillmentTime($GLOBALS['user'],
            $requestedFullFillmentTimestamp, $orderObject->get('retailer')->get('airportIataCode'));
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        ////////////////////////////////////////////////////
        // Check with external partner before adding to cart
        ////////////////////////////////////////////////////
        list($isExternalPartnerOrder, $tenderType, $dualPartnerConfig) = isExternalPartnerOrder($orderObject->get("retailer")->get("uniqueId"));

        if ($isExternalPartnerOrder == true) {

            $unavailableItemsFromPartner = getPartnerUnavailableItems($dualPartnerConfig, $orderSummaryArray,
                $orderObject);

            if (count_like_php5($unavailableItemsFromPartner) > 0) {

                $unallowedItemsForOrder = "\r\n\r\n" . implode("\r\n", $unavailableItemsFromPartner) . "\r\n\r\n";

                $orderValidationErrorList->addItem(
                    new \App\Consumer\Entities\OrderValidationError(
                        "items_not_available_at_this_time",
                        "Your order contains items that are not currently available. These items are: " . $unallowedItemsForOrder . "Please remove these items.",
                        false,
                        "Item(s) not available",
                        'Cancel',
                        'Proceed'
                    ));
            }
        }
        ////////////////////////////////////////////////////
        $orderObject->get('retailer')->get('location')->fetch();
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // ALERT: No flights in next 6 hours
        // Skip check for orders with employee discount
        if (!isset($orderSummaryArray["totals"]["AirEmployeeDiscount"]) || $orderSummaryArray["totals"]["AirEmployeeDiscount"] == 0) {
            // JMD
            if (empty($associatedFlightId)) {

                /*
			// Create error about no upcoming flights
			if($immediateProcessing == true) {

				$errorArray[] = ["alert_code" => "no_upcoming_flights",
									 "alert_message" => "You have no upcoming flights from " . $orderObject->get('retailer')->get('airportIataCode') . ". This order is for immediate processing. Are you sure?",
								 "allow_user_to_continue" => true,
								 "alert_title" => "No upcoming flights"];
			}
			else {

				$errorArray[] = ["alert_code" => "no_upcoming_flights",
									 "alert_message" => "You have no upcoming flights from " . $orderObject->get('retailer')->get('airportIataCode') . " around the time you have requested the order for. Are you sure?",
								 "allow_user_to_continue" => true,
								 "alert_title" => "No upcoming flights"];
			}
		*/
            } else {

                // Fetch Flight information
                try {

                    $flight = getFlightInfoFromCacheOrAPI($associatedFlightId);
                } catch (Exception $ex) {

                    $error_array = json_decode($ex->getMessage(), true);
                    json_error($error_array["error_code"], "",
                        $error_array["error_message_log"] . " Flight Status check failed during Order submit validation - ",
                        1);
                }

                $preparedFlightInfo = prepareFlightInfo($flight);

                // Fetch departure gate info
                if (!empty($preparedFlightInfo["departure"]["location"])) {

                    $departureGateInfo = getTerminalGateMapByLocationId($orderObject->get('retailer')->get('airportIataCode'),
                        $preparedFlightInfo["departure"]["location"]);
                }
            }
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Set up information
        if (strcasecmp($fullfillmentType, "d") == 0) {

            // Check validity delivery location
            $objDeliveryLocation = getTerminalGateMapByLocationId($orderObject->get('retailer')->get('airportIataCode'),
                $deliveryLocationId);

            if (empty($objDeliveryLocation)) {

                json_error("AS_864", "", "Invalid delivery Location: " . $deliveryLocationId, 1);
            }

            // Fetch Delivery Location info
            $deliveryGateInfo = getTerminalGateMapByLocationId($orderObject->get('retailer')->get('airportIataCode'),
                $deliveryLocationId);

            // Fetch Directions From Retailer Location to Delivery Location
            $directionsToDeliveryLocationFromRetailerLocation = getDirectionsSummarizedCache($orderObject->get('retailer')->get('airportIataCode'),
                $orderObject->get('retailer')->get('location')->getObjectId(), $deliveryLocationId);

            // Fetch Directions to Delivery location to Departure Gate, if the two are different
            if (!empty($preparedFlightInfo["departure"]["location"])
                && strcasecmp($deliveryLocationId, $preparedFlightInfo["departure"]["location"]) != 0
            ) {

                // Directions to the Departure Gate From Delivery Location
                $directionsToDepartureGateFromDeliveryLocation = getDirectionsSummarizedCache($orderObject->get('retailer')->get('airportIataCode'),
                    $deliveryLocationId, $preparedFlightInfo["departure"]["location"]);
            }

            // Get delivery estimates
            list($fullfillmentTimeInSeconds, $fullfillmentProcessTimeInSeconds, $fullfillmentTimeInSecondsOverriden, $requestedFullFillmentTimestampOverriden) = getDeliveryTimeInSeconds($orderObject->get('retailer'),
                $objDeliveryLocation, $orderObject);

            if ($requestedFullFillmentTimestampOverriden > 0) {
                $immediateProcessing = false;
                $requestedFullFillmentTimestamp = $requestedFullFillmentTimestampOverriden;
            }

            $deliveryTimestamp = $requestedFullFillmentTimestamp + $fullfillmentTimeInSeconds;
        } else {

            // Directions to the Departure Gate from Retailer Location
            if (!empty($preparedFlightInfo["departure"]["location"])) {

                $directionsToDepartureGateFromRetailerLocation = getDirectionsSummarizedCache($orderObject->get('retailer')->get('airportIataCode'),
                    $orderObject->get('retailer')->get('location')->getObjectId(),
                    $preparedFlightInfo["departure"]["location"]);
            }

            // Get Pickup estimates
            list($fullfillmentTimeInSeconds, $fullfillmentProcessTimeInSeconds) = getPickupTimeInSeconds($orderObject->get('retailer'),
                $orderObject, $requestedFullFillmentTimestamp);
            $pickupTimestamp = $requestedFullFillmentTimestamp + $fullfillmentTimeInSeconds;
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	


        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // If Delivery
        if (strcasecmp($fullfillmentType, "d") == 0) {

            // ALERT: Delivery gate != departure gate 
            // 	   && Delivery time + User to walk to departure gate > Boarding time
            if (count_like_php5($preparedFlightInfo) > 0
                && strcasecmp($deliveryLocationId, $preparedFlightInfo["departure"]["location"]) != 0
                && isset($directionsToDepartureGateFromDeliveryLocation["walkingTime"])
                && (($deliveryTimestamp + ($directionsToDepartureGateFromDeliveryLocation["walkingTime"]) * 60) > $preparedFlightInfo["departure"]["deliveryAlertTimestamp"])
            ) {
                $orderValidationErrorList->addItem(
                    new \App\Consumer\Entities\OrderValidationError(
                        "delivery_after_boarding",
                        "Your flight " . $preparedFlightInfo["info"]["airlineIataCode"] . " " . $preparedFlightInfo["info"]["flightNum"] . " to " . $preparedFlightInfo["arrival"]["airportIataCode"] . " will begin boarding before the estimated delivery time to " . $deliveryGateInfo->get('gateDisplayName') . " and the time it will take for you walk to your departure gate, " . $departureGateInfo->get('gateDisplayName') . ". Are you sure you want to continue?",
                        true,
                        "Delivery after Boarding",
                        'Cancel',
                        'Proceed'
                    ));


            } // ALERT: Delivery time > Boarding time
            else {
                if (count_like_php5($preparedFlightInfo) > 0
                    && ($requestedFullFillmentTimestamp + $fullfillmentTimeInSeconds) > $preparedFlightInfo["departure"]["deliveryAlertTimestamp"]
                ) {
                    $orderValidationErrorList->addItem(
                        new \App\Consumer\Entities\OrderValidationError(
                            "delivery_after_boarding",
                            "Your flight " . $preparedFlightInfo["info"]["airlineIataCode"] . " " . $preparedFlightInfo["info"]["flightNum"] . " to " . $preparedFlightInfo["arrival"]["airportIataCode"] . " will begin boarding before the estimated delivery time. Are you sure you want to continue?",
                            true,
                            "Delivery after Boarding",
                            'Cancel',
                            'Proceed'
                        ));
                }
            }
        } // Pickup
        else {

            // ALERT: Pickup time + Walk time to departure gate > Boarding time
            if (count_like_php5($preparedFlightInfo) > 0
                && isset($directionsToDepartureGateFromRetailerLocation["walkingTime"])
                && (($pickupTimestamp + $directionsToDepartureGateFromRetailerLocation["walkingTime"]) > $preparedFlightInfo["departure"]["deliveryAlertTimestamp"])
            ) {
                $orderValidationErrorList->addItem(
                    new \App\Consumer\Entities\OrderValidationError(
                        "pickup_after_boarding",
                        "Your flight " . $preparedFlightInfo["info"]["airlineIataCode"] . " " . $preparedFlightInfo["info"]["flightNum"] . " to " . $preparedFlightInfo["arrival"]["airportIataCode"] . " will begin boarding before the estimated pickup time from " . $orderObject->get('retailer')->get('retailerName') . " and the time it will take you to walk to your departure gate, " . $departureGateInfo->get('gateDisplayName') . ". Are you sure you want to continue?",
                        true,
                        "Pickup after Boarding",
                        'Cancel',
                        'Proceed'
                    ));
            }

            // ALERT: Pickup location requires resecurity checkin given Gate location
            if (count_like_php5($preparedFlightInfo) > 0
                && isset($directionsToDepartureGateFromRetailerLocation["reEnterSecurityFlag"])
                && strcasecmp($directionsToDepartureGateFromRetailerLocation["reEnterSecurityFlag"], "Y") == 0
            ) {
                // TODO: Change code to: pickup_thru_security
                $orderValidationErrorList->addItem(
                    new \App\Consumer\Entities\OrderValidationError(
                        "pickup_thru_security_with_unallowed_items",
                        "Your flight " . $preparedFlightInfo["info"]["airlineIataCode"] . " " . $preparedFlightInfo["info"]["flightNum"] . " to " . $preparedFlightInfo["arrival"]["airportIataCode"] . " will depart from " . $departureGateInfo->get('gateDisplayName') . ", but " . $orderObject->get('retailer')->get('retailerName') . " is located at " . $orderObject->get('retailer')->get('location')->get("gateDisplayName") . ". You will need to go through security again to reach your gate. Are you sure you want to continue?",
                        true,
                        "Pickup from different Terminal",
                        'Cancel',
                        'Proceed'
                    ));
            }

            // ALERT: Pickup when no delivery is on
            $quotedEstimates = json_decode($orderObject->get('quotedEstimates'), true);
            if ($quotedEstimates["d"]["isAvailable"] == false
                && $quotedEstimates["d"]["isAvailableAtDeliveryLocation"] == true
            ) {

                // TODO: Change code to: something specific for this: pickup_warning_with_no_delivery
                $orderValidationErrorList->addItem(
                    new \App\Consumer\Entities\OrderValidationError(
                        "walk_to_depgate_thru_security_with_unallowed_items",
                        "We would like to confirm that you will pick up this order from " . $orderObject->get('retailer')->get('retailerName') . " located at " . $orderObject->get('retailer')->get('location')->get("gateDisplayName") . "?",
                        true,
                        "Confirm Pick up selection",
                        'Cancel',
                        'Proceed'
                    ));
            }
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // ALERT: Items not allowed through security
        if ($orderSummaryArray["internal"]["orderNotAllowedThruSecurity"] == true) {

            $unallowedItemListThruSecurity = getItemsNotAllowedThruSecurity($orderSummaryArray);

            /*
		$maxItemsToShowInAlert = 3;

		// If more than 3 items in the list, then just show first 3
		if(count_like_php5($unallowedItemsListThruSecurity) > $maxItemsToShowInAlert) {

			$unallowedItemsThruSecurity = implode(", ", array_slice($unallowedItemsListThruSecurity, 0, $maxItemsToShowInAlert, true)) . ", and " . (count_like_php5($unallowedItemsListThruSecurity)-$maxItemsToShowInAlert) . " more. ";
		}
		// Else show all
		else {

			$unallowedItemsThruSecurity = implode(", ", $unallowedItemsListThruSecurity) . ". ";
		}
		*/

            $unallowedItemsThruSecurity = "\r\n\r\n" . implode("\r\n", $unallowedItemListThruSecurity) . "\r\n\r\n";

            // If Delivery
            if (strcasecmp($fullfillmentType, "d") == 0) {

                // ALERT: Retailer to Delivery Gate requires Security check
                if (isset($directionsToDeliveryLocationFromRetailerLocation["reEnterSecurityFlag"])
                    && $directionsToDeliveryLocationFromRetailerLocation["reEnterSecurityFlag"] == "Y"
                ) {
                    $orderValidationErrorList->addItem(
                        new \App\Consumer\Entities\OrderValidationError(
                            "delivery_thru_security_with_unallowed_items",
                            "Your order contains items that can't be carried through security, which will be required for the Delivery person coming from " . $orderObject->get('retailer')->get('retailerName') . " to your delivery location, " . $deliveryGateInfo->get('gateDisplayName') . ". These items are: " . $unallowedItemsThruSecurity . "Please remove the following items before continuing.",
                            false,
                            "Security Restrictions",
                            'Cancel',
                            'Proceed'
                        ));
                }

                // ALERT: Delivery gate != departure gate 
                // 	   && Delivery Gate to Departure Gate requires Security check
                if (count_like_php5($preparedFlightInfo) > 0
                    && strcasecmp($deliveryLocationId, $preparedFlightInfo["departure"]["location"]) != 0
                    && isset($directionsToDepartureGateFromDeliveryLocation["reEnterSecurityFlag"])
                    && $directionsToDepartureGateFromDeliveryLocation["reEnterSecurityFlag"] == "Y"
                ) {
                    $orderValidationErrorList->addItem(
                        new \App\Consumer\Entities\OrderValidationError(
                            "walk_to_depgate_thru_security_with_unallowed_items",
                            "Your order contains items that can't be carried through security, which will be required for you going from your delivery location, " . $deliveryGateInfo->get('gateDisplayName') . " to your departure gate, " . $departureGateInfo->get('gateDisplayName') . ". These items are: " . $unallowedItemsThruSecurity . "Are you sure you want to continue?",
                            true,
                            "Security Restrictions",
                            'Cancel',
                            'Proceed'
                        ));
                }
            } // If Pickup
            else {

                // ALERT: Departure Gate is populated (directions variable not listed otherwise)
                // 	   && Retailer to Departure Gate requires Security check
                if (isset($directionsToDepartureGateFromRetailerLocation["reEnterSecurityFlag"])
                    && $directionsToDepartureGateFromRetailerLocation["reEnterSecurityFlag"] == "Y"
                ) {
                    $orderValidationErrorList->addItem(
                        new \App\Consumer\Entities\OrderValidationError(
                            "pickup_thru_security_with_unallowed_items",
                            "Your order contains items that can't be carried through security, which will be required for you going from " . $orderObject->get('retailer')->get('retailerName') . " to your departure gate, " . $departureGateInfo->get('gateDisplayName') . ". These items are: " . $unallowedItemsThruSecurity . "Are you sure you want to continue?",
                            true,
                            "Security Restrictions",
                            'Cancel',
                            'Proceed'
                        ));
                } // ALERT: No Departure Gate info available, so just alert about the security pass through
                else {
                    $orderValidationErrorList->addItem(
                        new \App\Consumer\Entities\OrderValidationError(
                            "pickup_thru_security_with_unallowed_items",
                            "Your order contains items that can't be carried through security, which may be required when going to your departure gate. These items are: " . $unallowedItemsThruSecurity . "Are you sure you want to continue?",
                            true,
                            "Security Restrictions",
                            'Cancel',
                            'Proceed'
                        ));
                }
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////
        // Order Time check; is this item allowed to be ordered right now (Order Time, i.e. Breakfast)
        ////////////////////////////////////////////////////////////////////////////////////////////////
        $unallowedItemListForOrderTime = getItemsNotAvailableAtOrderTime($orderObject, $requestedFullFillmentTimestamp);

        // Check if there are Order time alerts
        if (count_like_php5($unallowedItemListForOrderTime) > 0) {

            /*
		$maxItemsToShowInAlert = 3;

		// If more than 3 items in the list, then just show first 3
		if(count_like_php5($unallowedItemListForOrderTime) > $maxItemsToShowInAlert) {

			$unallowedItemsForOrder = implode(", ", array_slice($unallowedItemListForOrderTime, 0, $maxItemsToShowInAlert, true)) . ", and " . (count_like_php5($unallowedItemListForOrderTime)-$maxItemsToShowInAlert) . " more. ";
		}
		// Else show all
		else {

			$unallowedItemsForOrder = implode(", ", $unallowedItemListForOrderTime) . ". ";
		}
		*/

            $unallowedItemsForOrder = "\r\n\r\n" . implode("\r\n", $unallowedItemListForOrderTime) . "\r\n\r\n";

            if ($immediateProcessing == true) {

                // ALERT: Certain items not available at the time of fullfillment
                $orderValidationErrorList->addItem(
                    new \App\Consumer\Entities\OrderValidationError(
                        "items_not_available_at_this_time",
                        "Your order contains items that are not currently available. These items are: " . $unallowedItemsForOrder . "Please remove these items.",
                        false,
                        "Item(s) not available",
                        'Cancel',
                        'Proceed'
                    ));
            } else {
                $orderValidationErrorList->addItem(
                    new \App\Consumer\Entities\OrderValidationError(
                        "items_not_available_at_this_time",
                        "Your order contains items that are not available for the scheduled time. These items are: " . $unallowedItemsForOrder . "Please remove these items.",
                        false,
                        "Item(s) not available",
                        'Cancel',
                        'Proceed'
                    ));
            }
        }

        //if (count_like_php5($orderValidationErrorList) > 0) {
        if (count($orderValidationErrorList) > 0) {

            $errorArray = $orderValidationErrorList->returnAsArray();

            $responseArray = ["validation" => false, "alerts" => $errorArray];
            $alert_codes = array_column($errorArray, 'alert_code');

            // Log user event
            if ($GLOBALS['env_LogUserActions']) {

                try {
                    $orderObject->get('retailer')->get('location')->fetch();
                    $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                    $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                    //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                    $workerQueue->sendMessage(
                        array(
                            "action" => "log_user_action_checkout_warning",
                            "content" =>
                                array(
                                    "objectId" => $GLOBALS['user']->getObjectId(),
                                    "data" => json_encode([
                                        "retailer" => $retailerName,
                                        "actionForRetailerAirportIataCode" => $airportIataCode,
                                        "airportIataCode" => $airportIataCode,
                                        "alert_codes" => $alert_codes,
                                        "orderId" => $orderObject->getObjectId(),
                                        "fullfillmentType" => $fullfillmentType
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
        } else {

            $responseArray = ["validation" => true, "alerts" => ""];
        }


        // No cache
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                // "expireInSeconds" => 2*60
            ])
        );
    });

// Order Submit
$app->post('/submit/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuthWithFullAccessSessionOnly',
    function ($apikey, $epoch, $sessionToken) use ($app) {

        // list($orderObject, $postVars) = orderSubmissionValidation($app);	

        // $orderId = $postVars['orderId'];
        // $fullfillmentType = $postVars['fullfillmentType'];
        // $deliveryLocationId = $postVars['deliveryLocation'];
        // $deliveryInstructions = $postVars['deliveryInstructions'];
        // $requestedFullFillmentTimestamp = $postVars['requestedFullFillmentTimestamp'];
        // $paymentToken = $postVars['paymentToken'];

        // Fetch Post variables
        $postVars = array();


        $postVars['orderId'] = $orderId = urldecode($app->request()->post('orderId'));
        $postVars['fullfillmentType'] = $fullfillmentType = urldecode($app->request()->post('fullfillmentType'));
        $postVars['deliveryLocation'] = $deliveryLocationId = urldecode($app->request()->post('deliveryLocation'));
        $postVars['deliveryInstructions'] = $deliveryInstructions = urldecode($app->request()->post('deliveryInstructions'));
        $postVars['requestedFullFillmentTimestamp'] = $requestedFullFillmentTimestamp = floatval(urldecode($app->request()->post('requestedFullFillmentTimestamp')));
        $postVars['paymentToken'] = $paymentToken = (($app->request()->post('paymentToken') == null || empty($app->request()->post('paymentToken'))) ? '' : urldecode($app->request()->post('paymentToken')));

//json_error(json_encode($postVars),json_encode($postVars));



        if (empty($orderId)
            || empty($fullfillmentType)
            || empty_zero_allowed($deliveryLocationId)
            || empty_zero_allowed($deliveryInstructions)
            || empty_zero_allowed($requestedFullFillmentTimestamp)
            /*|| empty($paymentToken)*/
        ) {

            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // FETCH ORDER
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        $orderObject = parseExecuteQuery(array(
            "objectId" => $orderId,
            "user" => $GLOBALS['user'],
            "status" => listStatusesForCart()
        ), "Order", "", "", array(
            "retailer",
            "retailer.location",
            "retailer.retailerType",
            "deliveryLocation",
            "coupon",
            "coupon.applicableUser",
            "user"
        ), 1);

        if (count_like_php5($orderObject) == 0) {

            json_error("AS_821", "This order has already been placed. Please proceed to Track Orders.",
                "No open order found! Order Id: " . $orderId);
        }

        $fullfillmentInfoArray[strtolower($fullfillmentType)]['allowWithoutCreditCard'] = false;
        if ($paymentToken == null || empty($paymentToken)) {
            // If deliveryLocation == 0, then allow it
            if (strcasecmp($deliveryLocationId, "0") != 0) {

                // Check validity delivery location
                $objDeliveryLocation = getTerminalGateMapByLocationId($orderObject->get('retailer')->get('airportIataCode'),
                    $deliveryLocationId);

                if (empty($objDeliveryLocation)) {

                    json_error("AS_861", "", "Invalid delivery location: " . $deliveryLocationId);
                }
            } // Set a default location for the Terminal
            else {

                $objDeliveryLocation = getTerminalGateMapDefaultLocation($orderObject->get('retailer')->get('airportIataCode'));
            }

            // Get fullfillment info
            $retailer = $orderObject->get('retailer');

            $fullfillmentInfoArray = getFullfillmentInfoWithOrder($retailer, $objDeliveryLocation,
                $requestedFullFillmentTimestamp,
                $orderObject);

        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Initial checks
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Check input => order type
        if (strcasecmp($fullfillmentType, "p") != 0
            && strcasecmp($fullfillmentType, "d") != 0
        ) {

            json_error("AS_861", "", "Invalid order type: " . $fullfillmentType, 1);
        } // Check if the order has quoted fullfillmet amount
        else {
            if (strcasecmp($fullfillmentType, "p") != 0
                && strcasecmp($fullfillmentType, "d") != 0
            ) {

                json_error("AS_861", "", "Invalid order type: " . $fullfillmentType, 1);
            }
        }

        // If value is 0, then set for immediate processing
        $immediateProcessing = false;
        $isScheduledOrder = false;
        if ($requestedFullFillmentTimestamp == 0) {
            // Verify if this is needed; we should not use time for that
            $requestedFullFillmentTimestamp = time();
            $immediateProcessing = true;
        }else{
            $isScheduledOrder = true;
        }


        // Check if a fullfillment quote was provided
        if ($immediateProcessing == true && empty($orderObject->get("quotedFullfillmentFeeTimestamp"))) {

            json_error("AS_865", "", "No fullfillment quote in db for order", 1);
        }

        // Ensure requestedFullFillmentTimestamp value
        $requestedFullFillmentTimestamp = intval($requestedFullFillmentTimestamp);

        if (strcasecmp($fullfillmentType, "d") == 0) {

            // Check validity delivery location
            $objDeliveryLocation = getTerminalGateMapByLocationId($orderObject->get('retailer')->get('airportIataCode'),
                $deliveryLocationId);

            if (empty($objDeliveryLocation)) {

                json_error("AS_864", "", "Invalid delivery Location: " . $deliveryLocationId, 1);
            }

            // fullfillmentTimeInSeconds = Total time taken for prep and fullfillment (i.e. delivery)
            // fullfillmentProcessTimeInSeconds = Time taken for delivery after pickup by delivery person; for pickup orders, this will be 0
            list($fullfillmentTimeInSeconds, $fullfillmentProcessTimeInSeconds, $fullfillmentTimeInSecondsOverriden, $requestedFullFillmentTimestampOverriden) = getDeliveryTimeInSeconds($orderObject->get('retailer'),
                $objDeliveryLocation, $orderObject);

            if ($fullfillmentTimeInSecondsOverriden > 0) {
                //$fullfillmentTimeInSeconds = $fullfillmentTimeInSecondsOverriden;
            }


            // If forced by Delivery location, change request timestamp
            if ($requestedFullFillmentTimestampOverriden > 0) {

                $immediateProcessing = false;
                $requestedFullFillmentTimestamp = $requestedFullFillmentTimestampOverriden;
            }

            // Check retailer's delivery availability
            list($isAvailable, $errorMessage,) = isDeliveryAvailableForRetailer($orderObject->get('retailer'),
                $fullfillmentTimeInSeconds, $objDeliveryLocation, $requestedFullFillmentTimestamp);
            if ($immediateProcessing && !$isAvailable) {

                json_error("AS_880", $errorMessage,
                    "fullfillmentTimeInSeconds = " . $fullfillmentTimeInSeconds . ", requestedFullFillmentTimestamp = " . $requestedFullFillmentTimestamp,
                    1);
            }

            // Check delivery availability
            list($isAvailable, $errorMessage) = isDeliveryAvailableAt($orderObject->get('retailer'),
                $objDeliveryLocation, $requestedFullFillmentTimestamp);
            /** @todo think about get information from delivery plan inside isDeliveryAvailableAt() **/
            if ($immediateProcessing && !$isAvailable) {

                json_error("AS_866", $errorMessage,
                    "No Delivery Persons available: " . $deliveryLocationId . " for user " . $orderObject->get('user')->get('firstName') . " " . $orderObject->get('user')->get('lastName'),
                    1);
            }
        } else {

            // Fullfillment value
            list($fullfillmentTimeInSeconds, $fullfillmentProcessTimeInSeconds) = getPickupTimeInSeconds($orderObject->get('retailer'),
                $orderObject, $requestedFullFillmentTimestamp);

        }

        // Order time check
        // CHANGE TO DO: Deduct retailerPrepTimeInSecs so the time of order placement is used for calculation of available time
        $unallowedItemListForOrderTime = getItemsNotAvailableAtOrderTime($orderObject,
            $requestedFullFillmentTimestamp - $fullfillmentProcessTimeInSeconds);
        if (count_like_php5($unallowedItemListForOrderTime) > 0) {

            json_error("AS_879",
                "Some of the items in your cart are not available at this time. Please remove them to continue.",
                "Order time validation indicated items that cannot be ordered were being processed: " . json_encode($unallowedItemListForOrderTime),
                1);
        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Submission Attempt verification
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        $orderObject->increment('submissionAttempt');
        $orderObject->save();
        $submissionAttempt = $orderObject->get('submissionAttempt');

        if ($submissionAttempt != 1) {

            decrementSubmissionAttempt($orderObject);
            json_error("AS_822", "",
                "Order could not be processed! Order Id: " . $orderId . ", Duplicate Order Process run caught! submissionAttempt = " . $submissionAttempt . ", expected 1",
                1);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        // Set Subbmission time
        $submitTimestamp = time();
        $orderObject->set("submitTimestamp", $submitTimestamp);
        $orderObject->set("interimOrderStatus", 0);
        //////////////////////////////////////////////////////////////////////////////////////////////////////

        // Set input order parameters
        $orderObject->set('fullfillmentType', $fullfillmentType);

        // If Delivery order
        // Check Delivery Location validity
        // Fetch Fullfillmet time
        if (strcasecmp($fullfillmentType, "d") == 0) {

            $orderObject->set('fullfillmentFee', $orderObject->get('quotedFullfillmentDeliveryFee'));
            $orderObject->set('fullfillmentProcessTimeInSeconds', $fullfillmentProcessTimeInSeconds);
            $orderObject->set('deliveryInstructions', $deliveryInstructions);
            $orderObject->set('deliveryLocation', $objDeliveryLocation);
        }

        // Pickup order
        // Fetch Fullfillment time
        else {

            $orderObject->set('fullfillmentFee', $orderObject->get('quotedFullfillmentPickupFee'));
            $orderObject->set('fullfillmentProcessTimeInSeconds', $fullfillmentProcessTimeInSeconds);

            // For pickup orders blank it out
            $deliveryLocationId = "";
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        $deliveryAtScheduledGate = false;

        // Existing flights within next 6 hours of requested timestamp
        list($flightTrip, $associatedFlightId) = getFlightIdForRequestedFullfillmentTime($GLOBALS['user'],
            $requestedFullFillmentTimestamp, $orderObject->get('retailer')->get('airportIataCode'));

        if (!empty($associatedFlightId)) {

            $departureGateLocationId = '';

            // Fetch Flight information
            try {

                $flight = getFlightInfoFromCacheOrAPI($associatedFlightId);
            } catch (Exception $ex) {

                $error_array = json_decode($ex->getMessage(), true);
                json_error($error_array["error_code"], "",
                    $error_array["error_message_log"] . " Flight Status check failed during Order submit validation - ",
                    1);
            }

            $preparedFlightInfo = prepareFlightInfo($flight);

            // Fetch departure gate info
            if (!empty($preparedFlightInfo["departure"]["location"])) {

                $departureGateLocationId = $preparedFlightInfo["departure"]["location"];
            }

            // Departure info is available
            // Order is Delivery type
            // Departure Gate matches the Delivery Gate
            // This flag means, we need to keep track of Departure Gate changes
            if (!empty($departureGateLocationId)
                && isset($objDeliveryLocation)
                && strcasecmp($fullfillmentType, "d") == 0
                && strcasecmp($departureGateLocationId, $objDeliveryLocation->getObjectId()) == 0
            ) {

                $deliveryAtScheduledGate = true;
            }

            $orderObject->set('flightTrip', $flightTrip);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        $orderObject->set('deliveryAtScheduledGate', $deliveryAtScheduledGate);

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Initialize Queue
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Connect to Queue
        // $GLOBALS['sqs_client'] = getSQSClientObject();


        try {

            // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
            newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
        } catch (Exception $ex) {
            $response = json_decode($ex->getMessage(), true);
            json_error($response["error_code"], "", "Order submission failed! " . $response["error_message_log"], 1);
        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Set Order updated values
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        $orderObject->set('requestedFullFillmentTimestamp', $requestedFullFillmentTimestamp);
        $orderObject->set('statusDelivery', 0);
        $orderObject->set('sessionDevice', getCurrentSessionDevice());
        //////////////////////////////////////////////////////////////////////////////////////////////////////	


        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Ping Retailer
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Check if the POS system is up for the Retailer
        list($ping, $isClosed, $error, $pingStatusDescription) = pingRetailer($orderObject->get('retailer'),
            $orderObject);
        if ($immediateProcessing && $ping == false) {

            decrementSubmissionAttempt($orderObject);
            json_error("AS_823", "The retailer is currently not accepting orders. Please try again in a few minutes.",
                "Retailer POS is DOWN. Orders not being accepted right now. " . $error, 3);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Verify Pending early close
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Check if an early close request is pending
        $isRetailerEarlyClosed = getRetailerCloseEarlyForNewOrders($orderObject->get('retailer')->get('uniqueId'));
        if ($immediateProcessing && is_array($isRetailerEarlyClosed)) {

            decrementSubmissionAttempt($orderObject);
            json_error("AS_889",
                "The retailer has closed early for the day and is no longer accepting orders. Please try another retailer.",
                "Retailer POS Pending early close idenified. No new orders being acceped.", 2);
        }
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Get Updated Order Totals
        //// TODO: Build a smaller method to get items count and just the total
        //////////////////////////////////////////////////////////////////////////////////////////////////////	

        // Call Order Summary function
        list($orderSummaryArray, $retailerTotals) = getOrderSummary($orderObject, 0, true, 0, true, $fullfillmentType);

        if (!isset($orderSummaryArray["items"]) || count_like_php5($orderSummaryArray["items"]) == 0) {

            decrementSubmissionAttempt($orderObject);
            json_error("AS_824", "Please add at least one item to the order before submission.",
                "Order could not be submitted! Order Id: " . $orderId . " has no items!", 1);
        }

        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        // Verify totals from external partner if retailer is set up as such
        //////////////////////////////////////////////////////////////////////////////////////////////////////	
        list($isExternalPartnerOrder, $tenderType, $dualPartnerConfig) = isExternalPartnerOrder($orderObject->get('retailer'));

        if ($isExternalPartnerOrder == true) {

            if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

                try {

                    $hmshost = new HMSHost($dualPartnerConfig->get('airportId'), $dualPartnerConfig->get('retailerId'),
                        $orderObject->get('retailer')->get('uniqueId'), 'order');
                } catch (Exception $ex) {

                    decrementSubmissionAttempt($orderObject);
                    json_error("AS_895", "We are sorry, but the retailer is currently not accepting orders.",
                        "Failed to connect to HMSHost for cart totals, Order Id: " . $orderId, 1);
                }

                $cartFormatted = $hmshost->format_cart($orderSummaryArray, $retailerTotals, $tenderType, 1);

                // Cart Id 
                try {

                    $statusForSubmissionToHost = $hmshost->push_cart_for_totals($orderSummaryArray["internal"]["orderIdDisplay"],
                        $retailerTotals["Total"], $cartFormatted);

                    if ($statusForSubmissionToHost == true) {

                        throw new Exception("Card submission failed.");
                    }
                } catch (Exception $ex) {

                    decrementSubmissionAttempt($orderObject);
                    json_error("AS_896", "We are sorry, but the retailer is currently not accepting orders.",
                        "Failed to get totals from HMSHost, Order Id: " . $orderId . " - " . $ex->getMessage(), 1);
                }

                $orderObject->set("orderPOSId", $orderPOSId);
            }
        }





        ////////////////////////////////////////////////////////////////////////////////////
        // Order Save - Update Totals & Service Fee
        ////////////////////////////////////////////////////////////////////////////////////

        $orderObject->set("totalsForRetailer", json_encode($retailerTotals));
        $orderObject->set("totalsWithFees", json_encode($orderSummaryArray["totals"]));
        $orderObject->set("serviceFee", $orderSummaryArray["totals"]["ServiceFee"]);

        /*
        if ($orderObject->get('fullfillmentType')=='d')
        {
            $orderObject->set('tipAppliedAs',$orderSummaryArray["totals"]['TipsAppliedAs']);
            $orderObject->set('tipCents',$orderSummaryArray["totals"]['Tips']);
            $orderObject->set('tipPct',trim($orderSummaryArray["totals"]['TipsPCT'],'%'));
        }else{
            $orderObject->set('tipAppliedAs',null);
            $orderObject->set('tipCents',0);
            $orderObject->set('tipPct',0);
        }
        */

        //////////////////////////////////////////////////////////////////////////////////////////////////////

        // Fetch order total

        $airporTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'),
            date_default_timezone_get());

        $orderTotalForPayment = $orderSummaryArray["totals"]["Total"];

        // If order total is 0, then authorize $1
        if ($orderTotalForPayment == 0) {

            $orderTotalForPayment = 100;
        }


        ////////////////////////////////////////////////////////////////////////////////////
        // Find Payment method and Generate Nonce
        ////////////////////////////////////////////////////////////////////////////////////
        // Find Customer Id
        $paymentsCustomerId = parseExecuteQuery(array("user" => $GLOBALS['user']), "Payments", "", "createdAt");

        // Records found, then return
        if (count_like_php5($paymentsCustomerId) > 0) {

            $customerId = $paymentsCustomerId[0]->get('externalCustomerId');
            unset($paymentsCustomerId);
        } else {

            // JMD
            // Log user event
            if ($GLOBALS['env_LogUserActions']) {

                try {
                    $orderObject->get('retailer')->get('location')->fetch();
                    $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                    $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                    //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                    $workerQueue->sendMessage(
                        array(
                            "action" => "log_user_action_checkout_payment_failed",
                            "content" =>
                                array(
                                    "objectId" => $GLOBALS['user']->getObjectId(),
                                    "data" => json_encode([
                                        "retailer" => $retailerName,
                                        "actionForRetailerAirportIataCode" => $airportIataCode,
                                        "airportIataCode" => $airportIataCode,
                                        "orderId" => $orderObject->getObjectId()
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

            decrementSubmissionAttempt($orderObject);
            json_error("AS_849", "Payment processing failed. Please check your payment method details and try again.",
                "Braintree Customer Id not found", 1);
        }

        /*
	//// TODO: This check should happen via Queue before processing the payment
	// Find Payment method to ensure it belongs to user
	try {
		
		$findClient = Braintree_Customer::find(
			$customerId
		);
	}
	catch (Exception $ex) {
		
		decrementSubmissionAttempt($orderObject);
		json_error("AS_855", "Payment processing failed. Please check your payment method details and try again.", "Payment Method list fetch failed for customerId: " . $customerId . " :: " . $ex->getMessage(), 1);
	}
	
	// Check if the provided token is a credit card
	if(count_like_php5($findClient->creditCards) > 0) {
		
		$foundToken = "N";
		foreach($findClient->creditCards as $creditCard) {
			
			if(strcasecmp($creditCard->token, $paymentToken) == 0) {
				
				$foundToken = "Y";
				break;
			}
		}
		
		if($foundToken == "Y") {
			
			// If Card is expired; the value is responded
			if(!empty(strlen(trim($creditCard->expired)))) {
				
				decrementSubmissionAttempt($orderObject);
				json_error("AS_820", "Payment processing failed. Please check your payment method details and try again.", "Payment Token / Method has expired.", 1);
			}
		}
		else {
			
			decrementSubmissionAttempt($orderObject);
			json_error("AS_826", "Payment processing failed. Please check your payment method details and try again.", "Braintree Customer doesn't own the provided token.", 1);
		}
		
		unset($creditCard);
		unset($findClient);
	}
	else {
		
		if(!$findClient->success && count_like_php5($findClient->errors->deepAll()) > 0) {
			
			decrementSubmissionAttempt($orderObject);
			json_error("AS_827", "Payment processing failed. Please check your payment method details and try again.", braintreeErrorCollect($findClient), 1);
		}
	}
	*/

        if (($paymentToken == null || empty(trim($paymentToken))) && $fullfillmentInfoArray[strtolower($fullfillmentType)]['allowWithoutCreditCard'] == false) {
            json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars));
        }

        //Create zero payment order =$0
        if (!empty(trim($paymentToken)) && !is_null($paymentToken)) {
            // Generate Payment nonce
            try {

                $paymentNonceObject = Braintree_PaymentMethodNonce::create($paymentToken);
            } catch (Exception $ex) {

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {
                        $orderObject->get('retailer')->get('location')->fetch();
                        $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                        $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_checkout_payment_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "retailer" => $retailerName,
                                            "actionForRetailerAirportIataCode" => $airportIataCode,
                                            "airportIataCode" => $airportIataCode,
                                            "orderId" => $orderObject->getObjectId()
                                        ]),
                                        "timestamp" => time()
                                    )
                            )
                        );
                    } catch (Exception $ex2) {

                        $response = json_decode($ex2->getMessage(), true);
                        json_error($response["error_code"], "",
                            "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                    }
                }

                decrementSubmissionAttempt($orderObject);
                json_error("AS_829",
                    "Payment processing failed. Please check your payment method details and try again.",
                    "Payment Processing failed. BT Message: " . $ex->getMessage() . ", BT Error Code: " . $ex->getCode(),
                    1);
            }

            // If Nonce creation failed
            if (!$paymentNonceObject->success && count_like_php5($paymentNonceObject->errors->deepAll()) > 0) {

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {
                        $orderObject->get('retailer')->get('location')->fetch();
                        $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                        $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_checkout_payment_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "retailer" => $retailerName,
                                            "actionForRetailerAirportIataCode" => $airportIataCode,
                                            "airportIataCode" => $airportIataCode,
                                            "orderId" => $orderObject->getObjectId()
                                        ]),
                                        "timestamp" => time()
                                    )
                            )
                        );
                    } catch (Exception $ex2) {

                        $response = json_decode($ex2->getMessage(), true);
                        json_error($response["error_code"], "",
                            "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                    }
                }

                decrementSubmissionAttempt($orderObject);
                json_error("AS_828",
                    "Payment processing failed. Please check your payment method details and try again.",
                    "Payment Processing failed. " . braintreeErrorCollect($paymentNonceObject), 1);
            }

            $paymentNonce = $paymentNonceObject->paymentMethodNonce->nonce;

            //////////////////////////////////////////////////////////////////////////////////////////////////////


            ////////////////////////////////////////////////////////////////////////////////////
            // Authorize Payment
            ////////////////////////////////////////////////////////////////////////////////////
            $paymentObject = Braintree_Transaction::sale([
                'amount' => round($orderTotalForPayment / 100, 2),
                'paymentMethodNonce' => $paymentNonce,
                // 'serviceFeeAmount' => round($orderSummaryArray["totals"]["AirportSherpaFee"] / 100, 2),
                'taxAmount' => round($orderSummaryArray["totals"]["Taxes"] / 100, 2),
                'orderId' => $orderObject->getObjectId(),
                'customerId' => $customerId,

                'descriptor' => [
                    'name' => "AYG*" . $orderObject->get('retailer')->get('airportIataCode') . '-' . substr(replaceSpecialCharsAllowNumsAndLettersOnly($orderObject->get('retailer')->get('retailerName')),
                            0, 10),
                    // Needs to be <= 13 characters
                    // 'url' => "airportsherpa.io",
                    'phone' => "844-266-4283"
                ],

                'customFields' => [
                    'retailer_id' => $orderObject->get('retailer')->get('uniqueId'),
                    'retailer_name' => $orderObject->get('retailer')->get('retailerName'),
                    'airport_iata_code' => $orderObject->get('retailer')->get('airportIataCode'),
                    'order_date_time_formatted' => $orderObject->get('retailer')->get('airportIataCode'),
                    'order_timestamp' => orderFormatDate($airporTimeZone,
                        $orderObject->get('retailer')->get('submitTimesTamp'))
                ],

                'purchaseOrderNumber' => $orderObject->get('retailer')->getObjectId(),
                'options' => [
                    'storeInVaultOnSuccess' => true,
                ]
            ]);

            if (!$paymentObject->success && count_like_php5($paymentObject->errors->deepAll()) > 0) {

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {
                        $orderObject->get('retailer')->get('location')->fetch();
                        $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                        $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_checkout_payment_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "retailer" => $retailerName,
                                            "actionForRetailerAirportIataCode" => $airportIataCode,
                                            "airportIataCode" => $airportIataCode,
                                            "orderId" => $orderObject->getObjectId()
                                        ]),
                                        "timestamp" => time()
                                    )
                            )
                        );
                    } catch (Exception $ex2) {

                        $response = json_decode($ex2->getMessage(), true);
                        json_error($response["error_code"], "",
                            "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                    }
                }

                decrementSubmissionAttempt($orderObject);
                json_error("AS_850",
                    "Payment processing failed. Please check your payment method details and try again.",
                    "Payment Processing failed. " . braintreeErrorCollect($paymentObject), 1);
            }

            // Check if the payment was NOT authorized
            if (strcasecmp($paymentObject->transaction->status, "authorized") != 0) {

                $message = "";
                if (isset($paymentObject->message)) {

                    $message = $paymentObject->message;
                }

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {
                        $orderObject->get('retailer')->get('location')->fetch();
                        $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                        $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_checkout_payment_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "retailer" => $retailerName,
                                            "actionForRetailerAirportIataCode" => $airportIataCode,
                                            "airportIataCode" => $airportIataCode,
                                            "orderId" => $orderObject->getObjectId()
                                        ]),
                                        "timestamp" => time()
                                    )
                            )
                        );
                    } catch (Exception $ex2) {

                        $response = json_decode($ex2->getMessage(), true);
                        json_error($response["error_code"], "",
                            "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                    }
                }

                decrementSubmissionAttempt($orderObject);
                json_error("AS_851",
                    "Payment processing failed. Please check your payment method details and try again.",
                    "Payment Processing failed. Trans_id: " . $paymentObject->transaction->id . ", Status: " . $paymentObject->transaction->status . ", message: " . $message . ", " . braintreeErrorCollect($paymentObject),
                    1);
            }
            ////////////////////////////////////////////////////////////////////////////////////

            ////////////////////////////////////////////////////////////////////////////////////
            // Set Order updates - Save Payment Method information
            ////////////////////////////////////////////////////////////////////////////////////

            $paymentTypeName = "";
            $paymentTypeId = "";

            // If Type used was CreditCard
            if (strcasecmp($paymentObject->transaction->paymentInstrumentType, "credit_card") == 0) {

                $paymentTypeName = $paymentObject->transaction->creditCard["cardType"];

                // Save its last 4
                if (isset($paymentObject->transaction->creditCard["last4"])
                    && !empty(trim($paymentObject->transaction->creditCard["last4"]))
                ) {

                    $paymentTypeId = encryptPaymentInfo(trim($paymentObject->transaction->creditCard["last4"]));
                }
            } // If it is Paypal
            else {
                if (strcasecmp($paymentObject->transaction->paymentInstrumentType, "paypal_account") == 0) {

                    $paymentTypeName = "Paypal";

                    // Save its Payor email address
                    if (isset($paymentObject->transaction->paypal["payerEmail"])
                        && !empty(trim($paymentObject->transaction->paypal["payerEmail"]))
                    ) {

                        $paymentTypeId = encryptPaymentInfo(trim($paymentObject->transaction->paypal["payerEmail"]));
                    }
                } // Else raise a default error as the payment method type was not recognized
                else {

                    // Log user event
                    if ($GLOBALS['env_LogUserActions']) {

                        try {
                            $orderObject->get('retailer')->get('location')->fetch();
                            $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                            $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                            $workerQueue->sendMessage(
                                array(
                                    "action" => "log_user_action_checkout_payment_failed",
                                    "content" =>
                                        array(
                                            "objectId" => $GLOBALS['user']->getObjectId(),
                                            "data" => json_encode([
                                                "retailer" => $retailerName,
                                                "actionForRetailerAirportIataCode" => $airportIataCode,
                                                "airportIataCode" => $airportIataCode,
                                                "orderId" => $orderObject->getObjectId()
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

                    decrementSubmissionAttempt($orderObject);
                    json_error("AS_830",
                        "Payment processing failed. Please check your payment method details and try again.",
                        "Payment Method Type not recognized; paymentTypeId not saved. paymentInstrumentType = " . $paymentObject->transaction->paymentInstrumentType,
                        1);
                }
            }

            //// TODO: Check if all this needs to be truly saved here or should be done via Queue
            // Save Payment Nonce
            $braintreeTransactionId = $paymentObject->transaction->id;

            // Verify if an non-empty Payment Id was found
            if (empty($braintreeTransactionId)
                || empty($paymentTypeName)
                || empty($paymentTypeId)
            ) {

                // Log user event
                if ($GLOBALS['env_LogUserActions']) {

                    try {
                        $orderObject->get('retailer')->get('location')->fetch();
                        $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                        $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                        $workerQueue->sendMessage(
                            array(
                                "action" => "log_user_action_checkout_payment_failed",
                                "content" =>
                                    array(
                                        "objectId" => $GLOBALS['user']->getObjectId(),
                                        "data" => json_encode([
                                            "retailer" => $retailerName,
                                            "actionForRetailerAirportIataCode" => $airportIataCode,
                                            "airportIataCode" => $airportIataCode,
                                            "orderId" => $orderObject->getObjectId()
                                        ]),
                                        "timestamp" => time()
                                    )
                            )
                        );
                    } catch (Exception $ex2) {

                        $response = json_decode($ex2->getMessage(), true);
                        json_error($response["error_code"], "",
                            "Log user action queue message failed " . $response["error_message_log"], 1, 1);
                    }
                }

                decrementSubmissionAttempt($orderObject);
                json_error("AS_869",
                    "Payment processing failed. Please check your payment method details and try again.",
                    "One of these were empty, paymentId = $braintreeTransactionId , paymentTypeName = $paymentTypeName , paymentTypeId = $paymentTypeName",
                    1);
            }

            // $orderObject->set("interimOrderStatus", 1);
            $orderObject->set("paymentId", $braintreeTransactionId);
            $orderObject->set("paymentTypeName", $paymentTypeName);
            $orderObject->set("paymentTypeId", $paymentTypeId);
            $orderObject->set("paymentType", $paymentObject->transaction->paymentInstrumentType);
            ////////////////////////////////////////////////////////////////////////////////////

        }





// new partners
        $retailerPartnerServiceFactory = new  \App\Consumer\Services\PartnerIntegrationServiceFactory(
            new \App\Consumer\Repositories\RetailerPartnerCacheRepository(
                new \App\Consumer\Repositories\RetailerPartnerParseRepository(),
                \App\Consumer\Services\CacheServiceFactory::create()
            )
        );
        $retailerPartnerService = $retailerPartnerServiceFactory->createByRetailerUniqueId($orderObject->get('retailer')->get('uniqueId'));
        if ($retailerPartnerService !== null) {



            // on test we have prod credentials, and we dont want to send it to grab, so we need a blocker
            if ($GLOBALS['env_EnvironmentDisplayCode']=='TEST'){
                //decrementSubmissionAttempt($orderObject);
                //json_error("AS_896", "We are sorry, but the retailer is currently not accepting orders.",
                //    "GRAB Order can not be saved on test env, Order Id: " . $orderId, 1);
            }


            $partnerRetailer = $retailerPartnerService->getPartnerIdByRetailerUniqueId($orderObject->get('retailer')->get('uniqueId'));
            $airportTimeZone = fetchAirportTimeZone($orderObject->get('retailer')->get('airportIataCode'));
            $cart = new \App\Consumer\Dto\PartnerIntegration\Cart(
                new \App\Consumer\Dto\PartnerIntegration\CartUserDetails(
                    (string)$orderObject->get('user')->get('firstName'),
                    (string)$orderObject->get('user')->get('lastName')
                ),
                $partnerRetailer->getPartnerId(),
                CartItemList::createFromGetOrderSummaryItemListResult(
                    $orderSummaryArray["items"]
                ),
                new \DateTimeZone($airportTimeZone),
                $orderSummaryArray['totals']['subTotal'],
                strtolower($fullfillmentType) == 'p' ? true : false,
                strtolower($fullfillmentType) == 'd' ? true : false
            );

            // check for employee discount
            // if employee discount is selected, we need to get details and inject it to submit order endpoint


            $employeeDiscount = null;
            if ((bool)$orderObject->get('airportEmployeeDiscount')==true){
                $employeeDiscount = $retailerPartnerService->getEmployeeDiscount($cart);
            }

            $cartTotals = $retailerPartnerService->getCartTotals($cart, $employeeDiscount);
            //$saveOrderResultArray = $retailerPartnerService->submitOrder($cart, $cartTotals);

            try {

                $saveOrderResultArray = $retailerPartnerService->submitOrderAsGuest($cart, $cartTotals, $employeeDiscount);
                $saveOrderResult = \App\Consumer\Entities\Partners\Grab\SaveOrderResult::createFromApiResult($saveOrderResultArray);
                $partnerId = $saveOrderResult->getOrderID();
            }catch (\App\Consumer\Exceptions\Partners\OrderCanNotBeSaved $exception){
                $partnerId='';

                $slack = createOrderNotificationSlackMessage($orderObject->getObjectId());
                $slack->setText('Partner ('.$partnerRetailer->getPartnerName().') Order can not be saved, in order to get details, please contact Tech support');
                $slack->send();

                $slack = createGrabPartnerErrorSlackMessage();
                $slack->setText(
                    $exception->getMessage().PHP_EOL.
                    'REQUEST: ' .$exception->getInputPayload().PHP_EOL.
                    'RESPONSE: ' .$exception->getOutputJson().PHP_EOL.
                    'URL: ' .$exception->getUrl().PHP_EOL
                );
                $slack->send();


                decrementSubmissionAttempt($orderObject);
                json_error("AS_896", "We are sorry, but the retailer is currently not accepting orders.",
                    "Failed to get totals from Grab, Order Id: " . $orderId . " - " . $exception->getMessage(), 1);

                $slack = createOrderNotificationSlackMessage($orderObject->getObjectId());
                $slack->setText($retailerPartnerService->getPartnerName().' order failed, payment has been done.');
                $slack->send();

            }
            $orderObject->set('partnerName', $retailerPartnerService->getPartnerName());
            $orderObject->set('partnerOrderId', $partnerId);
            $orderObject->save();
        }



        ////////////////////////////////////////////////////////////////////////////////////
        // Calculate ETA Timestamp for completion
        ////////////////////////////////////////////////////////////////////////////////////
        // If fullfillmentTimeInSeconds (SLA calculated to fullfill this order) + Order Submit timestamp < requestedFullFillmentTimestamp (requested time by user)
        // Then, use the requested time
        // Else use this the former
        // RequestedFullFillmentTimestamp = Time restaurant needs to prepare the food by
        // EtaTimestamp = Order must be delivered or picked up by (for immediate pickup = requestedFullFillmentTimestamp)

        // Scheduled order
        if ($immediateProcessing == false) {

            $etaTimestamp = $requestedFullFillmentTimestamp;
            $retailerETATimestamp = $etaTimestamp - $fullfillmentProcessTimeInSeconds;
        } // Immediate processing
        else {

            $etaTimestamp = $fullfillmentTimeInSeconds + $submitTimestamp;
            $retailerETATimestamp = $etaTimestamp - $fullfillmentProcessTimeInSeconds;
        }

        $orderObject->set("etaTimestamp", $etaTimestamp);
        $orderObject->set("retailerETATimestamp", $retailerETATimestamp);
        ////////////////////////////////////////////////////////////////////////////////////

        if ($immediateProcessing == true) {

            $processAfterTimestamp = time() + 10; // min 10 second delay before processing
            $waitTimeForDelayTimestamp = $processAfterTimestamp;
        } else {

            $processAfterTimestamp = ($requestedFullFillmentTimestamp - $fullfillmentTimeInSeconds);
            $waitTimeForDelayTimestamp = $processAfterTimestamp;
        }

        // Log user event
        if ($GLOBALS['env_LogUserActions']) {

            try {
                $orderObject->get('retailer')->get('location')->fetch();
                $retailerName = $orderObject->get('retailer')->get('retailerName') . ' (' . $orderObject->get('retailer')->get('location')->get('locationDisplayName') . ')';
                $airportIataCode = $orderObject->get('retailer')->get('airportIataCode');

                //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "log_user_action_checkout_complete",
                        "content" =>
                            array(
                                "objectId" => $GLOBALS['user']->getObjectId(),
                                "data" => json_encode([
                                    "retailer" => $retailerName,
                                    "actionForRetailerAirportIataCode" => $airportIataCode,
                                    "airportIataCode" => $airportIataCode,
                                    "orderId" => $orderObject->getObjectId(),
                                    "fullfillmentType" => $fullfillmentType
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
        //////////////////////////////////////////////////////////////////////////////////////////

        // apply credits (add entry)
        if (isset($orderSummaryArray["totals"]["CreditsAppliedInCents"])
            && $orderSummaryArray["totals"]["CreditsAppliedInCents"] > 0
        ) {

            // applyCreditsToOrder($orderObject, $orderObject->get('user'), ($orderSummaryArray["totals"]["CreditsAppliedInCents"]));
            // JMD
            applyCreditsToOrderViaMap($orderObject, $orderObject->get('user'),
                $orderSummaryArray["internal"]["creditAppliedMap"][$fullfillmentType]["map"]);
        }

        // for fullfilment info, use correct one,
        // for displaying for the user, lets use range from overwritten one when set
        //list($fullfillmentTimeRangeEstimateDisplay, $fullfillmentTimeRangeEstimateLowInSeconds, $fullfillmentTimeRangeEstimateHighInSeconds) = getOrderFullfillmentTimeRangeEstimateDisplay($fullfillmentTimeInSeconds);


        if ($isScheduledOrder) {
            list($fullfillmentTimeRangeEstimateDisplay, $fullfillmentTimeRangeEstimateLowInSeconds, $fullfillmentTimeRangeEstimateHighInSeconds) =
                \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                    $fullfillmentTimeInSeconds,
                    $GLOBALS['env_fullfillmentETALowInSecs'],
                    $GLOBALS['env_fullfillmentETAHighInSecs'],
                    $airporTimeZone
                );
        }else{
            list($fullfillmentTimeRangeEstimateDisplay, $fullfillmentTimeRangeEstimateLowInSeconds, $fullfillmentTimeRangeEstimateHighInSeconds) = \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                $fullfillmentTimeInSeconds,
                $GLOBALS['env_fullfillmentETALowInSecsForScheduled'],
                $GLOBALS['env_fullfillmentETAHighInSecsForScheduled'],
                $airporTimeZone
            );
        }

        $orderObject->set("fullfillmentTimeRangeEstimateLowInSeconds", $fullfillmentTimeRangeEstimateLowInSeconds);
        $orderObject->set("fullfillmentTimeRangeEstimateHighInSeconds", $fullfillmentTimeRangeEstimateHighInSeconds);
        $orderObject->set("fullfillmentTimeInSeconds", $fullfillmentTimeInSeconds);
        $orderObject->set("isScheduled", $isScheduledOrder);



/*
        //list($fullfillmentTimeRangeEstimateDisplay, ,) = getOrderFullfillmentTimeRangeEstimateDisplay(isset($fullfillmentTimeInSecondsOverriden) ? $fullfillmentTimeInSecondsOverriden : $fullfillmentTimeInSeconds);
        list($fullfillmentTimeRangeEstimateDisplay, ,) = \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
            (isset($fullfillmentTimeInSecondsOverriden)&&!empty($fullfillmentTimeInSecondsOverriden))? $fullfillmentTimeInSecondsOverriden : $fullfillmentTimeInSeconds,
            $GLOBALS['env_fullfillmentETALowInSecs'],
            $GLOBALS['env_fullfillmentETAHighInSecs'],
            $airporTimeZone
        );
*/
        ////////////////////////////////////////////////////////////////////////////////////
        // Order Save - Update Status after Queue submission goes through
        ////////////////////////////////////////////////////////////////////////////////////

        if ($immediateProcessing == false) {

            orderStatusChange_Scheduled($orderObject);

            ////////////////////////////////////////////////////////////////////////////////////
            // Put on queue for processing
            ////////////////////////////////////////////////////////////////////////////////////
            try {

                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "order_scheduled_process",
                        "content" =>
                            array(
                                "orderId" => $orderId,
                                "processAfterTimestamp" => $processAfterTimestamp,
                                // Indicate this has been put back on queue
                                "backOnQueue" => false,
                            )
                    ),
                    // This states by when we need to place the order
                    // Until then the order stays in scheduled state
                    $workerQueue->getWaitTimeForDelay($waitTimeForDelayTimestamp)
                );
            } catch (Exception $ex) {

                $response = json_decode($ex->getMessage(), true);
                json_error($response["error_code"], "",
                    $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), 1);
            }
        } else {

            orderStatusChange_Submitted($orderObject);

            ////////////////////////////////////////////////////////////////////////////////////
            // Put on queue for processing
            ////////////////////////////////////////////////////////////////////////////////////
            try {

                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);

                $workerQueue->sendMessage(
                    array(
                        "action" => "order_submission_process",
                        "content" =>
                            array(
                                "orderId" => $orderId,
                                "processAfterTimestamp" => $processAfterTimestamp,
                                // Indicate this has been put back on queue
                                "backOnQueue" => false,
                            )
                    ),
                    // This states by when we need to place the order
                    // Until then the order stays in scheduled state
                    $workerQueue->getWaitTimeForDelay($waitTimeForDelayTimestamp)
                );
            } catch (Exception $ex) {

                $response = json_decode($ex->getMessage(), true);
                json_error($response["error_code"], "",
                    $response["error_message_log"] . " OrderId - " . $orderObject->getObjectId(), 1);
            }
        }

        $orderObject->save();

        $totalsWithFeesForTipsRecalculation = $orderObject->get('totalsWithFees');
        $totalsWithFeesForTipsRecalculation = json_decode($totalsWithFeesForTipsRecalculation, true);


        if ($orderObject->get('fullfillmentType') == 'd') {
            $orderObject->set('tipAppliedAs', $totalsWithFeesForTipsRecalculation['TipsAppliedAs']);
            $orderObject->set('tipCents', intval($totalsWithFeesForTipsRecalculation['Tips']));
            $orderObject->set('tipPct', floatval(trim($totalsWithFeesForTipsRecalculation['TipsPCT'], '%')));
        } else {
            $orderObject->set('tipAppliedAs', null);
            $orderObject->set('tipCents', 0);
            $orderObject->set('tipPct', 0);
        }
        $orderObject->save();


        ////////////////////////////////////////////////////////////////////////////////////

        if ($orderObject->has("coupon")
            && couponAddedToOrderViaCart($orderObject)
        ) {

            // check if order and coupon fullfilment matches, if not, detach coupon from order
            $couponFullfilmentType = strtolower((string)$orderObject->get("coupon")->get('fullfillmentTypeRestrict'));
            $orderFullfilmentType = strtolower((string)$orderObject->get("fullfillmentType"));
            if (($couponFullfilmentType == 'p' && $orderFullfilmentType == 'd') || ($couponFullfilmentType == 'd' && $orderFullfilmentType == 'p')) {
                json_error('AS_000',
                    'order coupon ' . $orderObject->get("coupon")->get('couponCode') . ' has been deatached from order ' . $orderObject->getObjectId() . ' due to wrong type',
                    'order coupon ' . $orderObject->get("coupon")->get('couponCode') . ' has been deatached from order ' . $orderObject->getObjectId() . ' due to wrong type',
                    2, 1
                );
                $orderObject->set('coupon', null);
            } else {
                // If order had a coupon usage and was added in cart (not pre-applied via UserCoupons), let's update local usage cache
                addCouponUsageByUser($orderObject->get("user")->getObjectId(),
                    $orderObject->get("coupon")->get("couponCode"), true);
                addCouponUsageByCode($orderObject->get("coupon")->get("couponCode"), true);
            }
        }

        //////////////////////////////////////////////////////////////////////////////////////////
        // Generate response array
        //////////////////////////////////////////////////////////////////////////////////////////

        //$fullfillmentETATimeDisplay = orderFormatDate($airporTimeZone, $etaTimestamp, 'auto', 1);

        if ($isScheduledOrder){
            $fullfillmentETATimeDisplay = \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                $etaTimestamp - (new DateTime('now', new DateTimeZone($airporTimeZone)))->getTimestamp(),
                $GLOBALS['env_fullfillmentETALowInSecsForScheduled'],
                $GLOBALS['env_fullfillmentETAHighInSecsForScheduled'],
                $airporTimeZone
            )[0];
        }else{
            $fullfillmentETATimeDisplay = \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
                $etaTimestamp - (new DateTime('now', new DateTimeZone($airporTimeZone)))->getTimestamp(),
                $GLOBALS['env_fullfillmentETALowInSecs'],
                $GLOBALS['env_fullfillmentETAHighInSecs'],
                $airporTimeZone
            )[0];
        }
        $fullfillmentTimeRangeEstimateDisplay = $fullfillmentETATimeDisplay;

        if (!empty($orderObject->get('deliveryLocation'))) {

            $fullfillmentLocation = $orderObject->get('deliveryLocation')->getObjectId();
        } else {

            $fullfillmentLocation = $orderObject->get('retailer')->get('location')->getObjectId();
        }

        // ***** slack order if not of type immediate ****** //
        if ($immediateProcessing == false && strcasecmp($fullfillmentType, "d") == 0) {

            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueSlackNotificationConsumerName']);


            $workerQueue->sendMessage(
                array(
                    "action" => "slackOffsetOrderDelivery_PreNotification",
                    "content" =>
                        array(
                            "orderId" => $orderId,
                            "fullfillmentTimeInSeconds" => $fullfillmentTimeInSeconds,
                            "fullfillmentTimeInSecondsOverriden" => $fullfillmentTimeInSecondsOverriden,
                        )
                ),
                0
            );
        }


        $responseArray = array(
            "ordered" => 1,
            "orderId" => $orderId,
            "fullfillmentTypeDisplay" => $fullfillmentType,
            "fullfillmentETATimestamp" => $etaTimestamp,
            "fullfillmentETATimeDisplay" => $fullfillmentETATimeDisplay,
            "fullfillmentLocation" => $fullfillmentLocation,
            "fullfillmentTimeRangeEstimateDisplay" => $fullfillmentTimeRangeEstimateDisplay
        );

        //////////////////////////////////////////////////////////////////////////////////////////


        logResponse(json_encode('FAST PROCESSING3'), false);

        logResponse(json_encode($immediateProcessing), false);
        logResponse(json_encode('FAST PROCESSING4'), false);


        json_echo(
            json_encode($responseArray)
        );
    });

// Order help
$app->get('/help/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/comments/:comments',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $orderId, $comments) {

        try {

            orderHelpContactCustomerService($orderId, $GLOBALS['user'], $comments);
        } catch (Exception $ex) {

            $error_array = json_decode($ex->getMessage(), true);

            if (is_array($error_array)) {

                json_error($error_array["error_code"], "", $error_array["error_message_log"], 1);
            } else {

                json_error("AS_1000", "", $ex->getMessage(), 1);
            }
        }

        // Return its object id
        $responseArray = array("saved" => "1");

        json_echo(
            json_encode($responseArray)
        );
    });

// Order Confirmation via slack
$app->post('/confirm/slack',
    function () use ($app) {

        logCal();

        // Fetch Post variables
        $postVars = array();

        // $postVars['payload'] = $payload = json_decode(unserialize(base64_decode($app->request()->post('payload')))['payload'], true);
        $postVars['payload'] = $payload = json_decode(html_entity_decode($app->request()->post('payload')), true);

        // $postVars['payload'] = $payload = json_decode(html_entity_decode($app->request()->post('payload')), true);

        // setcache('slack', ($app->request()->post('payload')), 0);
        // setcache('slack2', html_entity_decode($app->request()->post('payload')), 0);

        // Validate token
        if (strcasecmp($payload["token"], $GLOBALS["env_Slack_tokenPOSApp"]) != 0) {

            // log error in cache
            $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

            // non display error
            json_error("AS_870", "",
                "Slack confirmation, Invalid token! - Refer to cache named __SLACKFAILURE__" . $cacheId, 1, 1);
            gracefulExit();
        }

        // If no action button was pressed
        // callback id ends in ___noaction
        if (preg_match("/\_\_\_noaction$/s", $payload['callback_id'])) {

            // log error in cache
            // $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));
            // json_error("AS_870", "", "BEFORE VERIFY - Refer to cache named __SLACKFAILURE__" . $cacheId, 1, 1);

            // non display error
            // json_error("AS_874", "", "Slack confirmation, No known action button pressed! - " . $app->request()->post('payload'), 3, 1);
            header("HTTP/1.1 200 OK");
            gracefulExit();
        }


        // Parse order id
        try {

            list($action, $orderId) = explode("__", $payload['callback_id']);
        } catch (Exception $ex) {

            // log error in cache
            $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

            // non display error
            json_error("AS_872", "",
                "Slack confirmation failed, orderId couldn't be parsed! " . $ex->getMessage() . " - Refer to cache named __SLACKFAILURE__" . $cacheId,
                1, 1);
            header("HTTP/1.1 200 OK");
            gracefulExit();
            // echo("Something went wrong! Please contact customer service.");gracefulExit();
        }


        // Find order
        $order = parseExecuteQuery(["objectId" => $orderId], "Order", "", "", [
            "retailer",
            "retailer.location",
            "retailer.retailerType",
            "deliveryLocation",
            "coupon",
            "user",
            "sessionDevice",
            "sessionDevice.userDevice"
        ], 1);

        if (count_like_php5($order) == 0) {

            // log error in cache
            $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

            // non display error
            json_error("AS_859", "",
                "Slack confirmation failed, orderId (" . $orderId . ") couldn't be found! - Refer to cache named __SLACKFAILURE__" . $cacheId,
                1, 1);
            header("HTTP/1.1 200 OK");
            gracefulExit();
        }

        // Calculate Order Progress Threshold
        // i.e. Time after which Delivery Person is not allowed to progress the order
        // This is based on when the retailer accepted the order
        // 60 seconds is the one minute we give for orders to be accepted after submission
        $orderDelayTimestampThresholdStarting = 0;

        // Find the Order Acceptance Timestamp
        $orderStatus = parseExecuteQuery(["order" => $order, "status" => listStatusesForAcceptedByRetailer()],
            "OrderStatus", "createdAt", "", [], 1);

        if (count_like_php5($orderStatus) > 0) {

            $orderAcceptanceTimestamp = $orderStatus->getCreatedAt()->getTimestamp();

            $orderDelayTimestampThresholdStarting = $order->get("etaTimestamp") + ($orderAcceptanceTimestamp - $order->get("submitTimestamp") - 60);
        }

        // If this is less than ETA, then use ETA
        if ($orderDelayTimestampThresholdStarting < $order->get("etaTimestamp")) {

            $orderDelayTimestampThresholdStarting = $order->get("etaTimestamp");
        }

        // Is this delivery call
        // callback id starts with confirm_delivery
        if (preg_match("/^confirm_delivery\_\_/s", $payload['callback_id'])) {

            // Get Delivery user id
            $deliveryObjectId = $payload["actions"][0]["value"];

            // Find Delivery user
            $deliverySlackUser = parseExecuteQuery(["objectId" => $deliveryObjectId], "zDeliverySlackUser", "", "", [],
                1);

            if (count_like_php5($deliverySlackUser) == 0) {

                // log error in cache
                $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

                // non display error
                json_error("AS_881", "",
                    "Slack confirmation failed, Delivery User not found (" . $deliveryObjectId . ") - Refer to cache named __SLACKFAILURE__" . $cacheId,
                    1, 1);
                header("HTTP/1.1 200 OK");
                gracefulExit();
            }

            // Order no longer in progress
            if (!in_array($order->get("status"), listStatusesForInProgress())) {

                // Get Delivery user id
                $deliveryObjectId = $payload["actions"][0]["value"];

                // If Cancelled
                if (in_array($order->get("status"), listStatusesForCancelled())) {

                    // Set next button
                    $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload, $deliveryObjectId,
                        "", "ORDER CANCELLED", "ORDER CANCELLED");
                } // If fullfilled / delivered
                else {

                    // Set next button
                    $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload, "", "", "",
                        "DELIVERY COMPLETE");
                }
            } // The delivery update is coming after 30 mins of ETA timestamp
            else {
                if (time() > ($orderDelayTimestampThresholdStarting + 0.5 * 60 * 60)) {

                    // Get Delivery user id
                    $deliveryObjectId = $payload["actions"][0]["value"];

                    // Set next button
                    $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload, $deliveryObjectId,
                        "", "STATUS UPDATE DELAYED", "STATUS UPDATE DELAYED");
                } else {

                    // If an assignment has already been made, then find the assignment row
                    // Check Order Status is equal or greater than Assigned Delivery status
                    if ($order->get('statusDelivery') >= getOrderStatusDeliveryAssignedDelivery()) {

                        // Find Delivery Assignment for the order
                        $deliveryUserAssignment = parseExecuteQuery(array(
                            "order" => $order,
                            "deliveryUser" => $deliverySlackUser
                        ), "zDeliverySlackOrderAssignments", "", "", [], 1);

                        // Check delivery user was found for the order
                        if (count_like_php5($deliveryUserAssignment) == 0) {

                            // log error in cache
                            $cacheId = setSlackFailureLogCache($payload['callback_id'],
                                $app->request()->post('payload'));

                            // non display error
                            json_error("AS_882", "",
                                "Slack confirmation failed, Delivery Assignment not found - Refer to cache named __SLACKFAILURE__" . $cacheId,
                                1, 1);
                            header("HTTP/1.1 200 OK");
                            gracefulExit();
                        }
                    }

                    // Verify if the last delivery status came less 20 secs ago
                    // and the last status was not of assigned
                    // If so, then don't except the update
                    if ($deliveryUserAssignment->get('lastStatusUpdateTimestamp') > (time() - $GLOBALS['env_DeliveryStatusUpdateMinIntervalInSecs'])
                        && $deliveryUserAssignment->get('lastStatusDelivery') > getOrderStatusDeliveryAssignedDelivery()
                    ) {

                        // log error in cache
                        $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

                        // non display error
                        json_error("AS_888", "",
                            "Slack confirmation failed, last response was " . (time() - $deliveryUserAssignment->get('lastStatusUpdateTimestamp')) . " secs ago; you must wait " . $GLOBALS['env_DeliveryStatusUpdateMinIntervalInSecs'] . " secs - Refer to cache named __SLACKFAILURE__" . $cacheId,
                            1, 1);
                        header("HTTP/1.1 200 OK");
                        gracefulExit();
                    }

                    // Override payload action with the expected action for this order
                    if ($order->get('statusDelivery') < getOrderStatusDeliveryAcceptedDelivery()) {

                        $payload["actions"][0]["name"] = 'confirm_delivery_acceptance';
                    } else {
                        if ($order->get('statusDelivery') < getOrderStatusDeliveryArrivedDelivery()) {

                            $payload["actions"][0]["name"] = 'confirm_delivery_arrived';
                        } else {
                            if ($order->get('statusDelivery') < getOrderStatusDeliveryPickedupByDelivery()) {

                                $payload["actions"][0]["name"] = 'confirm_delivery_pickedup';
                            } else {
                                if ($order->get('statusDelivery') < getOrderStatusDeliveryAtDeliveryLocationDelivery()) {

                                    $payload["actions"][0]["name"] = 'confirm_delivery_atdeliveryloc';
                                } else {
                                    if ($order->get('statusDelivery') < getOrderStatusDeliveryDelivered()) {

                                        $payload["actions"][0]["name"] = 'confirm_delivery_delivered';
                                    } else {

                                        $payload["actions"][0]["name"] = 'confirm_delivery_delivered_post';
                                    }
                                }
                            }
                        }
                    }

                    // If Delivery has accepted assignment
                    if (strcasecmp($payload["actions"][0]["name"], 'confirm_delivery_acceptance') == 0) {

                        // Check Order Status is equal or greater than Assigned Delivery status
                        if ($order->get('statusDelivery') >= getOrderStatusDeliveryAcceptedDelivery()) {

                            // log error in cache
                            $cacheId = setSlackFailureLogCache($payload['callback_id'],
                                $app->request()->post('payload'));

                            // non display error
                            json_error("AS_883", "",
                                "Slack confirmation failed, Delivery Status already ahead of Assigned Delivery state! - Refer to cache named __SLACKFAILURE__" . $cacheId,
                                1, 1);
                            header("HTTP/1.1 200 OK");
                            gracefulExit();
                        }

                        // Update status time in the assignment table
                        $deliveryUserAssignment->set('lastStatusUpdateTimestamp', time());
                        $deliveryUserAssignment->set('lastStatusDelivery', getOrderStatusDeliveryAcceptedDelivery());
                        $deliveryUserAssignment->save();

                        ///////////////////////////////////////////////////////////////////////////////////////
                        ///////////////////// DELIVERY STATUS :: Accepted Delivery //////////////////////////////
                        ///////////////////////////////////////////////////////////////////////////////////////

                        $response = deliveryStatusChange_AcceptedDelivery($order);

                        if (is_array($response)) {

                            $error_array = order_processing_error($order, $response["error_code"],
                                $response["error_message_user"],
                                $response["error_message_log"] . " OrderId - " . $order->getObjectId(),
                                $response["error_severity"], 1);
                            json_error($error_array["error_code"], "",
                                $error_array["error_message_log"] . " Delivery Acceptance failed - ",
                                $error_array["error_severity"]);
                        }
                        ///////////////////////////////////////////////////////////////////////////////////////

                        $order->save();

                        // Set next button
                        $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload,
                            $deliveryObjectId, "confirm_delivery_arrived", "Arrived @ Retailer");

                        //Log delivery order Status
                        $queueService = QueueServiceFactory::create();
                        $logInActiveDelivery = QueueMessageHelper::getLogOrderDeliveryStatuses($order->get('retailer')->get('location')->get('airportIataCode'), 'arrived_at_retailer', time(), $order->get('orderSequenceId'));
                        $queueService->sendMessage($logInActiveDelivery, 0);

                        ////////////////////////////////////////////////////////////////////////////////////
                        // Assigned Delivery actions -- Put on Queue
                        ////////////////////////////////////////////////////////////////////////////////////
                        try {

                            // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueSlackNotificationConsumerName']);
                            $workerQueue->sendMessage(
                                array(
                                    "action" => "slackDelivery_AssignedDelivery",
                                    "content" =>
                                        array(
                                            "orderId" => $orderId,
                                        )
                                ), 1
                            );
                        } catch (Exception $ex) {

                            $response = json_decode($ex->getMessage(), true);
                            json_error($response["error_code"], $response["error_message_user"],
                                $response["error_message_log"] . " OrderId - " . $orderId, $response["error_severity"],
                                1);
                        }
                        //////////////////////////////////////////////////////////////////////////////////////////
                    } // If Delivery has arrived
                    else {
                        if (strcasecmp($payload["actions"][0]["name"], 'confirm_delivery_arrived') == 0) {

                            // Check Order Status is equal or greater than Assigned Delivery status
                            if ($order->get('statusDelivery') >= getOrderStatusDeliveryArrivedDelivery()) {

                                // log error in cache
                                $cacheId = setSlackFailureLogCache($payload['callback_id'],
                                    $app->request()->post('payload'));

                                // non display error
                                json_error("AS_884", "",
                                    "Slack confirmation failed, Delivery Status already ahead of Arrived Delivery state! - Refer to cache named __SLACKFAILURE__" . $cacheId,
                                    1, 1);
                                header("HTTP/1.1 200 OK");
                                gracefulExit();
                            }

                            // Update status time in the assignment table
                            $deliveryUserAssignment->set('lastStatusUpdateTimestamp', time());
                            $deliveryUserAssignment->set('lastStatusDelivery', getOrderStatusDeliveryArrivedDelivery());
                            $deliveryUserAssignment->save();

                            ///////////////////////////////////////////////////////////////////////////////////////
                            ///////////////////// DELIVERY STATUS :: Arrived Delivery ///////////////////////////////
                            ///////////////////////////////////////////////////////////////////////////////////////

                            $response = deliveryStatusChange_ArrivedDelivery($order);

                            if (is_array($response)) {

                                $error_array = order_processing_error($order, $response["error_code"],
                                    $response["error_message_user"],
                                    $response["error_message_log"] . " OrderId - " . $order->getObjectId(),
                                    $response["error_severity"], 1);
                                json_error($error_array["error_code"], "",
                                    $error_array["error_message_log"] . " Delivery Arrival failed - ",
                                    $error_array["error_severity"]);
                            }
                            ///////////////////////////////////////////////////////////////////////////////////////

                            $order->save();

                            // Get order quantity
                            $itemCount = 0;
                            $orderModifiers = parseExecuteQuery(["order" => $order], "OrderModifiers");
                            foreach ($orderModifiers as $modifier) {

                                $itemCount += $modifier->get('itemQuantity');
                            }

                            // Set next button
                            // $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload, $deliveryObjectId, "confirm_delivery_pickedup", "Picked up Order");

                            $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload,
                                $deliveryObjectId, "confirm_delivery_pickedup",
                                "I confirm I have picked up " . $itemCount . " items");

                            //Log delivery order Status
                            $queueService = QueueServiceFactory::create();
                            $logInActiveDelivery = QueueMessageHelper::getLogOrderDeliveryStatuses($order->get('retailer')->get('location')->get('airportIataCode'), 'order_picked_up', time(), $order->get('orderSequenceId'));
                            $queueService->sendMessage($logInActiveDelivery, 0);
                            ////////////////////////////////////////////////////////////////////////////////////
                            // Arrived Delivery actions -- Put on Queue
                            ////////////////////////////////////////////////////////////////////////////////////
                            try {

                                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueSlackNotificationConsumerName']);
                                $workerQueue->sendMessage(
                                    array(
                                        "action" => "slackDelivery_ArrivedDelivery",
                                        "content" =>
                                            array(
                                                "orderId" => $orderId,
                                            )
                                    ), 1
                                );
                            } catch (Exception $ex) {

                                $response = json_decode($ex->getMessage(), true);
                                json_error($response["error_code"], $response["error_message_user"],
                                    $response["error_message_log"] . " OrderId - " . $orderId,
                                    $response["error_severity"], 1);
                            }
                            //////////////////////////////////////////////////////////////////////////////////////////
                        } // If Delivery has picked up
                        else {
                            if (strcasecmp($payload["actions"][0]["name"], 'confirm_delivery_pickedup') == 0) {

                                // Check Order Status is equal or greater than Assigned Delivery status
                                if ($order->get('statusDelivery') >= getOrderStatusDeliveryPickedupByDelivery()) {

                                    // log error in cache
                                    $cacheId = setSlackFailureLogCache($payload['callback_id'],
                                        $app->request()->post('payload'));

                                    // non display error
                                    json_error("AS_885", "",
                                        "Slack confirmation failed, Delivery Status already ahead of Picked up Delivery state! - Refer to cache named __SLACKFAILURE__" . $cacheId,
                                        1, 1);
                                    header("HTTP/1.1 200 OK");
                                    gracefulExit();
                                }

                                // Update status time in the assignment table
                                $deliveryUserAssignment->set('lastStatusUpdateTimestamp', time());
                                $deliveryUserAssignment->set('lastStatusDelivery',
                                    getOrderStatusDeliveryPickedupByDelivery());
                                $deliveryUserAssignment->save();

                                ///////////////////////////////////////////////////////////////////////////////////////
                                //////////////////// DELIVERY STATUS :: Picked up Delivery //////////////////////////////
                                ///////////////////////////////////////////////////////////////////////////////////////

                                $response = deliveryStatusChange_PickedupByDelivery($order);

                                if (is_array($response)) {

                                    $error_array = order_processing_error($order, $response["error_code"],
                                        $response["error_message_user"],
                                        $response["error_message_log"] . " OrderId - " . $order->getObjectId(),
                                        $response["error_severity"], 1);
                                    json_error($error_array["error_code"], "",
                                        $error_array["error_message_log"] . " Delivery Pickup failed - ",
                                        $error_array["error_severity"]);
                                }
                                ///////////////////////////////////////////////////////////////////////////////////////

                                $order->save();

                                // Set next button
                                $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload,
                                    $deliveryObjectId, "confirm_delivery_atdeliveryloc",
                                    "Approaching " . $order->get('deliveryLocation')->get('gateDisplayName'));

                                //Log delivery order Status
                                $queueService = QueueServiceFactory::create();
                                $logInActiveDelivery = QueueMessageHelper::getLogOrderDeliveryStatuses($order->get('retailer')->get('location')->get('airportIataCode'), 'arrived_at_location', time(), $order->get('orderSequenceId'));
                                $queueService->sendMessage($logInActiveDelivery, 0);
                                ////////////////////////////////////////////////////////////////////////////////////
                                // Pickedup Delivery actions -- Put on Queue
                                ////////////////////////////////////////////////////////////////////////////////////
                                try {

                                    // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                    $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueSlackNotificationConsumerName']);
                                    $workerQueue->sendMessage(
                                        array(
                                            "action" => "slackDelivery_PickedupByDelivery",
                                            "content" =>
                                                array(
                                                    "orderId" => $orderId,
                                                )
                                        ), 1
                                    );
                                } catch (Exception $ex) {

                                    $response = json_decode($ex->getMessage(), true);
                                    json_error($response["error_code"], $response["error_message_user"],
                                        $response["error_message_log"] . " OrderId - " . $orderId,
                                        $response["error_severity"], 1);
                                }
                                //////////////////////////////////////////////////////////////////////////////////////////
                            } // If Delivery has arrived at delivery location
                            else {
                                if (strcasecmp($payload["actions"][0]["name"], 'confirm_delivery_atdeliveryloc') == 0) {

                                    // Check Order Status is equal or greater than Assigned Delivery status
                                    if ($order->get('statusDelivery') >= getOrderStatusDeliveryAtDeliveryLocationDelivery()) {

                                        // log error in cache
                                        $cacheId = setSlackFailureLogCache($payload['callback_id'],
                                            $app->request()->post('payload'));

                                        // non display error
                                        json_error("AS_886", "",
                                            "Slack confirmation failed, Delivery Status already ahead of Arrived at Delivery Location Delivery state! - Refer to cache named __SLACKFAILURE__" . $cacheId,
                                            1, 1);
                                        header("HTTP/1.1 200 OK");
                                        gracefulExit();
                                    }

                                    // Update status time in the assignment table
                                    $deliveryUserAssignment->set('lastStatusUpdateTimestamp', time());
                                    $deliveryUserAssignment->set('lastStatusDelivery',
                                        getOrderStatusDeliveryAtDeliveryLocationDelivery());
                                    $deliveryUserAssignment->save();

                                    ///////////////////////////////////////////////////////////////////////////////////////
                                    ////////////////// DELIVERY STATUS :: At Delivery Location ////////////////////////////
                                    ///////////////////////////////////////////////////////////////////////////////////////

                                    $response = deliveryStatusChange_AtDeliveryLocationDelivery($order);

                                    if (is_array($response)) {

                                        $error_array = order_processing_error($order, $response["error_code"],
                                            $response["error_message_user"],
                                            $response["error_message_log"] . " OrderId - " . $order->getObjectId(),
                                            $response["error_severity"], 1);
                                        json_error($error_array["error_code"], "",
                                            $error_array["error_message_log"] . " Delivery At Delivery failed - ",
                                            $error_array["error_severity"]);
                                    }
                                    ///////////////////////////////////////////////////////////////////////////////////////

                                    $order->save();

                                    // Set next button
                                    $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload,
                                        $deliveryObjectId, "confirm_delivery_delivered", "Delivered Order");


                                    //Log delivery order Status
                                    $queueService = QueueServiceFactory::create();
                                    $logInActiveDelivery = QueueMessageHelper::getLogOrderDeliveryStatuses($order->get('retailer')->get('location')->get('airportIataCode'), 'order_delivered', time(), $order->get('orderSequenceId'));
                                    $queueService->sendMessage($logInActiveDelivery, 0);
                                    ////////////////////////////////////////////////////////////////////////////////////
                                    // At Delivery Location Delivery actions -- Put on Queue
                                    ////////////////////////////////////////////////////////////////////////////////////
                                    try {

                                        // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                        $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueSlackNotificationConsumerName']);
                                        $workerQueue->sendMessage(
                                            array(
                                                "action" => "slackDelivery_AtDeliveryLocationByDelivery",
                                                "content" =>
                                                    array(
                                                        "orderId" => $orderId,
                                                    )
                                            ), 1
                                        );
                                    } catch (Exception $ex) {

                                        $response = json_decode($ex->getMessage(), true);
                                        json_error($response["error_code"], $response["error_message_user"],
                                            $response["error_message_log"] . " OrderId - " . $orderId,
                                            $response["error_severity"], 1);
                                    }
                                    //////////////////////////////////////////////////////////////////////////////////////////
                                } // If Delivery has delivered
                                else {
                                    if (strcasecmp($payload["actions"][0]["name"], 'confirm_delivery_delivered') == 0
                                        || strcasecmp($payload["actions"][0]["name"],
                                            'confirm_delivery_delivered_post') == 0
                                    ) {

                                        // Check Order Status is equal or greater than Assigned Delivery status
                                        /*
				if($order->get('statusDelivery') >= getOrderStatusDeliveryDelivered()) {

					// log error in cache
					$cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

					// non display error
					json_error("AS_887", "", "Slack confirmation failed, Delivery Status already ahead of Picked up Delivery state! - Refer to cache named __SLACKFAILURE__" . $cacheId, 1, 1);
					header("HTTP/1.1 200 OK");gracefulExit();
				}
				*/

                                        // If Order not already in delivered status		
                                        if (strcasecmp($payload["actions"][0]["name"],
                                                'confirm_delivery_delivered_post') != 0
                                        ) {

                                            // Update status time in the assignment table
                                            $deliveryUserAssignment->set('lastStatusUpdateTimestamp', time());
                                            $deliveryUserAssignment->set('lastStatusDelivery',
                                                getOrderStatusDeliveryDelivered());
                                            $deliveryUserAssignment->save();

                                            ///////////////////////////////////////////////////////////////////////////////////////
                                            //////////////////// DELIVERY STATUS :: Delivered Delivery //////////////////////////////
                                            ///////////////////////////////////////////////////////////////////////////////////////

                                            $response = deliveryStatusChange_DeliveredyByDelivery($order);

                                            if (is_array($response)) {

                                                $error_array = order_processing_error($order, $response["error_code"],
                                                    $response["error_message_user"],
                                                    $response["error_message_log"] . " OrderId - " . $order->getObjectId(),
                                                    $response["error_severity"], 1);
                                                json_error($error_array["error_code"], "",
                                                    $error_array["error_message_log"] . " Delivery Delivered failed - ",
                                                    $error_array["error_severity"]);
                                            }
                                            ///////////////////////////////////////////////////////////////////////////////////////

                                            ///////////////////////////////////////////////////////////////////////////////////////
                                            /////////////////////////// ORDER STATUS :: Completed /////////////////////////////////
                                            ///////////////////////////////////////////////////////////////////////////////////////

                                            $response = orderStatusChange_Completed($order);

                                            if (is_array($response)) {

                                                $error_array = order_processing_error($order, $response["error_code"],
                                                    $response["error_message_user"],
                                                    $response["error_message_log"] . " OrderId - " . $order->getObjectId(),
                                                    $response["error_severity"], 1);
                                                json_error($error_array["error_code"], "",
                                                    $error_array["error_message_log"] . " Order Completeion Status failed - ",
                                                    $error_array["error_severity"]);
                                            }
                                            ///////////////////////////////////////////////////////////////////////////////////////

                                            $order->save();

                                            ////////////////////////////////////////////////////////////////////////////////////
                                            // Pickedup Delivery actions -- Put on Queue
                                            ////////////////////////////////////////////////////////////////////////////////////
                                            try {

                                                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueSlackNotificationConsumerName']);
                                                $workerQueue->sendMessage(
                                                    array(
                                                        "action" => "slackDelivery_DeliveredByDelivery",
                                                        "content" =>
                                                            array(
                                                                "orderId" => $orderId,
                                                            )
                                                    ), 1
                                                );
                                            } catch (Exception $ex) {

                                                $response = json_decode($ex->getMessage(), true);
                                                json_error($response["error_code"], $response["error_message_user"],
                                                    $response["error_message_log"] . " OrderId - " . $orderId,
                                                    $response["error_severity"], 1);
                                            }
                                            //////////////////////////////////////////////////////////////////////////////////////////
                                        }

                                        // Set delivery status response in message
                                        $responseArray["attachments"][] = slackDeliveryUpdateResponseMessage($payload,
                                            "", "", "", "DELIVERY COMPLETE");
                                    } // Invalid button pressed
                                    else {

                                        // log error in cache
                                        // $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));
                                        // json_error("AS_870", "", "VERIFY - Refer to cache named __SLACKFAILURE__" . $cacheId, 1, 1);

                                        // No response required
                                        gracefulExit();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } // This Slack tablet call
        else {
            if (preg_match("/^confirm\_order\_\_/s", $payload['callback_id'])) {

                // Order no longer in progress
                if (!in_array($order->get("status"), listStatusesForInProgress())) {

                    // log error in cache
                    $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

                    // non display error
                    json_error("AS_859", "",
                        "Slack confirmation failed, orderId (" . $orderId . ") couldn't be found! - Refer to cache named __SLACKFAILURE__" . $cacheId,
                        1, 1);
                    header("HTTP/1.1 200 OK");
                    gracefulExit();
                } else {

                    // If confirmation requested
                    if (intval($payload["actions"][0]["value"]) == 1) {

                        // Confirm order
                        $response = orderStatusChange_ConfirmedByRetailer($order);

                        if (is_array($response)) {

                            // log error in cache
                            $cacheId = setSlackFailureLogCache($payload['callback_id'],
                                $app->request()->post('payload'));

                            // non display error
                            json_error($response["error_code"], "",
                                "Slack confirmation failed, status update failed! " . $response["error_message_user"] . " - Refer to cache named __SLACKFAILURE__" . $cacheId,
                                1, 1);
                            header("HTTP/1.1 200 OK");
                            gracefulExit();
                            // echo("Something went wrong! Please contact customer service.");gracefulExit();
                        }

                        // Save order
                        $order->save();


                        ////////////////////////////////////////////////////////////////////////////////////
                        // SENDGRID Receipt -- Put on Queue to mark completion after pickup
                        ////////////////////////////////////////////////////////////////////////////////////
                        try {

                            // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueEmailConsumerName']);
                            $workerQueue->sendMessage(
                                array(
                                    "action" => "order_email_receipt",
                                    "content" =>
                                        array(
                                            "orderId" => $orderId,
                                        )
                                )
                            );
                        } catch (Exception $ex) {

                            $response = json_decode($ex->getMessage(), true);
                            json_error($response["error_code"], $response["error_message_user"],
                                $response["error_message_log"] . " OrderId - " . $orderId, $response["error_severity"],
                                1);
                        }
                        //////////////////////////////////////////////////////////////////////////////////////////


                        ///////////////////////////////////////////////////////////////////////////////////////
                        // If Pickup Order then put on Queue for auto completion after etaTimestamp
                        ///////////////////////////////////////////////////////////////////////////////////////

                        if (strcasecmp($order->get('fullfillmentType'), "p") == 0) {

                            ////////////////////////////////////////////////////////////////////////////////////
                            // Put on queue to mark completion after pickup
                            ////////////////////////////////////////////////////////////////////////////////////
                            try {

                                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue->sendMessage(
                                    array(
                                        "action" => "order_pickup_mark_complete",
                                        "processAfter" => ["timestamp" => $order->get('etaTimestamp')],
                                        "content" =>
                                            array(
                                                "orderId" => $orderId,
                                                "etaTimestamp" => $order->get('etaTimestamp')
                                            )
                                    ),
                                    // DelaySeconds after ETA timestamp
                                    $workerQueue->getWaitTimeForDelay($order->get('etaTimestamp'))
                                );
                            } catch (Exception $ex) {

                                $response = json_decode($ex->getMessage(), true);
                                json_error($response["error_code"], $response["error_message_user"],
                                    $response["error_message_log"] . " OrderId - " . $orderId,
                                    $response["error_severity"], 1);
                            }
                            //////////////////////////////////////////////////////////////////////////////////////////
                        }

                        ///////////////////////////////////////////////////////////////////////////////////////
                        // If Delivery Order then put on Queue for Delivery process to take over
                        ///////////////////////////////////////////////////////////////////////////////////////

                        else {

                            ////////////////////////////////////////////////////////////////////////////////////
                            // Put order on the queue for processing
                            ////////////////////////////////////////////////////////////////////////////////////
                            try {

                                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue->sendMessage(
                                    array(
                                        "action" => "order_delivery_assign_delivery",
                                        "content" =>
                                            array(
                                                "orderId" => $orderId,
                                            )
                                    )
                                );
                            } catch (Exception $ex) {

                                $response = json_decode($ex->getMessage(), true);
                                json_error($response["error_code"], $response["error_message_user"],
                                    $response["error_message_log"] . " OrderId - " . $orderId,
                                    $response["error_severity"], 1);
                            }
                            //////////////////////////////////////////////////////////////////////////////////////////
                        }
                        ///////////////////////////////////////////////////////////////////////////////////////


                        // Update response message

                        // Create Slack attachment object from the first attachment
                        $attachment = new SlackAttachment($payload["original_message"]["attachments"][0]);

                        // Accepted Color
                        $attachment->setColorAccepted();

                        // Update title text
                        // $responseArray["attachments"][0]["text"] = "`Order Confirmed`";
                        $attachment->setAttribute("text", "`Order " . $order->get('orderSequenceId') . " Confirmed`");

                        // Change callback id so if the buttons are pressed no action is taken
                        // $responseArray["attachments"][0]["callback_id"] .= "___noaction";
                        $attachment->setAttribute("callback_id",
                            $attachment->getAttribute("callback_id") . "___noaction");

                        // Remove all buttons
                        // unset($responseArray["attachments"][0]["actions"]);
                        $attachment->removeAllButtons();

                        // Add a non action button listing Confirmed status
                        // $responseArray["attachments"][0]["actions"][] = ["name" => "noaction", "text" => "ORDER CONFIRMED", "type" => "button", "value" => 0];
                        $attachment->addButtonDefault("noaction", "ORDER CONFIRMED", 0);

                        // Update timestamp
                        // $responseArray["attachments"][0]["ts"] = time();
                        $attachment->addTimestamp();

                        $responseArray["attachments"][] = $attachment->getAttachment();
                    } // Reject or Customer service requested
                    else {
                        if (intval($payload["actions"][0]["value"]) == -1) {

                            // Put message on queue
                            try {

                                // $workerQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                                $workerQueue->sendMessage(
                                    array(
                                        "action" => "order_retailer_help_request",
                                        "content" =>
                                            array(
                                                "orderId" => $orderId
                                            )
                                    )
                                );
                            } catch (Exception $ex) {

                                $response = json_decode($ex->getMessage(), true);

                                // non display error
                                json_error($response["error_code"], "",
                                    $response["error_message_log"] . " OrderId - " . $order->getObjectId(), 1, 1);
                                header("HTTP/1.1 200 OK");
                                gracefulExit();
                                // echo("Something went wrong! Please contact customer service.");gracefulExit();
                            }

                            // Respond
                            // Fetch attachments
                            // $responseArray["attachments"] = $payload["original_message"]["attachments"];
                            $attachment = new SlackAttachment($payload["original_message"]["attachments"][0]);

                            // Accepted Color
                            $attachment->setColorRejected();

                            // Update title text
                            // $responseArray["attachments"][0]["text"] = "`Requires Customer Service Support`";
                            $attachment->setAttribute("text", "`Requires Customer Service Support`");

                            // Update timestamp
                            // $responseArray["attachments"][0]["ts"] = time();
                            $attachment->addTimestamp();

                            // Find the index of the action with value = -1 (Need help button), and then replace it
                            $buttonIndexToFind = $attachment->getButtonIndexByValue(-1);

                            // Replace button value so no action is taken when pressed
                            // $responseArray["attachments"][0]["actions"][$i] = ["name" => "noaction", "text" => "Help requested", "type" => "button", "value" => 0, "style" => "danger"];
                            $buttonIndex = $attachment->addButtonDanger("noaction", "Help requested", 0,
                                $buttonIndexToFind);

                            $responseArray["attachments"][] = $attachment->getAttachment();
                        } // Invalid button pressed
                        else {

                            // log error in cache
                            // $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));
                            // json_error("AS_870", "", "VERIFY - Refer to cache named __SLACKFAILURE__" . $cacheId, 1, 1);
                            // No response required
                            gracefulExit();
                        }
                    }
                }
            } else {

                // log error in cache
                $cacheId = setSlackFailureLogCache($payload['callback_id'], $app->request()->post('payload'));

                // non display error
                json_error("AS_871", "",
                    "Slack confirmation failed, callback_id didn't match! - Refer to cache named __SLACKFAILURE__" . $cacheId,
                    1, 1);
                header("HTTP/1.1 200 OK");
                gracefulExit();
                // echo("Something went wrong! Please contact customer service.");gracefulExit();
            }
        }

        json_echo(
            json_encode($responseArray)
        );
    });

$app->post('/rate/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\ApiMiddleware::class . '::apiAuth',
    \App\Consumer\Middleware\OrderRatingMiddleware::class . '::validate',
    OrderController::class . ':orderRating'
);

$app->get('/getLastRating/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId',
    \App\Consumer\Middleware\ApiMiddleware::class . '::apiAuth',
    OrderController::class . ':getLastOrderRating'
);


$app->post('/:orderId/applyTipAsPercentage/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::addCurrentUserAsFirstAndRemoveApiKeysFromParams',
    \App\Consumer\Controllers\OrderController::class . ':applyTipAsPercentage'
);


$app->post('/:orderId/applyTipAsFixedValue/a/:apikey/e/:epoch/u/:sessionToken',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    \App\Consumer\Middleware\UserAuthMiddleware::class . '::addCurrentUserAsFirstAndRemoveApiKeysFromParams',
    \App\Consumer\Controllers\OrderController::class . ':applyTipAsFixedValue'
);


$app->notFound(function () {

    json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();


function getPrintableOrderStatusList($orderId, $getFullList = false)
{

    // Initialize Order under user
    $order = new Order($GLOBALS['user']);

    // Fetch available order by order id
    $order->fetchOrderByOrderId($orderId);

    ////////////////////////////////////////////////
    // Verify if an Open Order was found
    try {

        $rules = [
            "exists" => true,
            "statusInList" => listStatusesForNonInternalNonCart()
        ];

        // Validate if we found the order
        $order->performChecks($rules);
    } catch (Exception $ex) {

        json_error("AS_805", "",
            "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
    }

    $responseArray["internal"]["orderInternalStatus"] = $order->getStatusForPrint();
    $responseArray["internal"]["orderInternalStatusCode"] = $order->getStatus();

    $orderUserStatusList = $order->getStatusList();

    if (count_like_php5($orderUserStatusList) > 0) {

        // If active order
        // Get User ready status code
        if (strcasecmp($order->getActiveOrCompletedCode(), 'a') == 0) {

            $responseArray["internal"]["orderStatus"] = $orderUserStatusList[count_like_php5($orderUserStatusList) - 1]["status"];
            $responseArray["internal"]["orderStatusCode"] = $orderUserStatusList[count_like_php5($orderUserStatusList) - 1]["statusCode"];
            $responseArray["internal"]["orderStatusCategoryCode"] = $order->getStatusCategory($responseArray["internal"]["orderStatusCode"]);
        } // Else it matches the completed status
        else {

            $responseArray["internal"]["orderStatus"] = $responseArray["internal"]["orderInternalStatus"];
            $responseArray["internal"]["orderStatusCode"] = $responseArray["internal"]["orderInternalStatusCode"];
            $responseArray["internal"]["orderStatusCategoryCode"] = $order->getStatusCategory();
        }

        if (strcasecmp($order->getFullfillmentType(), 'd') == 0) {

            $responseArray["internal"]["orderDeliveryLocationId"] = $order->getDeliveryLocation();
        } else {

            $responseArray["internal"]["orderDeliveryLocationId"] = '';
        }

        $responseArray["internal"]["orderStatusDeliveryCode"] = $order->getStatusDelivery();

        $responseArray["internal"]["etaTimestamp"] = $order->getETATimestamp();

        //$responseArray["internal"]["etaRangeEstimateDisplay"] = getOrderFullfillmentTimeRangeEstimateDisplay($order->getETATimestamp() - $order->getsubmitTimestamp())[0];

        $responseArray["internal"]["etaRangeEstimateDisplay"] = \App\Consumer\Helpers\DateTimeHelper::getOrderFullfillmentTimeRangeEstimateDisplay(
            $order->getETATimestamp() - (new DateTime('now'))->getTimestamp(),
            ($order->getFromDB('isScheduled') === true ? $GLOBALS['env_fullfillmentETALowInSecsForScheduled'] : $GLOBALS['env_fullfillmentETALowInSecs']),
            ($order->getFromDB('isScheduled') === true ? $GLOBALS['env_fullfillmentETAHighInSecsForScheduled'] : $GLOBALS['env_fullfillmentETAHighInSecs']),
            fetchAirportTimeZone($order->getAirportIataCode())

        )[0];

        $responseArray["internal"]["etaTimestampFormatted"] = orderFormatDate(fetchAirportTimeZone($order->getAirportIataCode(),
            date_default_timezone_get()), $order->getETATimestamp());
        $responseArray["internal"]["etaTimezoneShort"] = getTimezoneShort(fetchAirportTimeZone($order->getAirportIataCode()));

        // Associated flight info if available
        if (!empty($order->getFlightTrip())) {

            $responseArray["internal"]["fromAirportIataCode"] = $order->getFlightTripDepartureAirportIataCode();
            $responseArray["internal"]["toAirportIataCode"] = $order->getFlightTripArrivalAirportIataCode();
            $responseArray["internal"]["airlineIataCode"] = $order->getFlightTripAirlineIataCode();
            $responseArray["internal"]["flightNum"] = $order->getFlightTripAirlineFlightNum();
            $responseArray["internal"]["lastknownDepartureTimestamp"] = $order->getFlightTripLastKnownDepartureTimestamp();
            $responseArray["internal"]["lastknownDepartureTimestampDisplay"] = formatDateTimeRelative($order->getFlightTripLastKnownDepartureTimestamp());
        } else {

            $responseArray["internal"]["fromAirportIataCode"] = "";
            $responseArray["internal"]["toAirportIataCode"] = "";
            $responseArray["internal"]["airlineIataCode"] = "";
            $responseArray["internal"]["flightNum"] = "";
            $responseArray["internal"]["lastknownDepartureTimestamp"] = "";
            $responseArray["internal"]["lastknownDepartureTimestampDisplay"] = "";
        }

        $responseArray["status"] = $orderUserStatusList;

        ///////////////////////////////////////////////////////////////////////////
        // Add remaining statuses
        if (strcasecmp($order->getActiveOrCompletedCode(), 'a') == 0
            && $getFullList == true
        ) {

            $alreadyAddedStatus = [];
            foreach ($orderUserStatusList as $status) {

                $alreadyAddedStatus[] = $status["statusCode"] . '-' . $status["statusDeliveryCode"];
            }

            foreach ($GLOBALS['statusIndexexForPrint'][$order->getFullfillmentType()] as $statusPairing) {

                if (!in_array($statusPairing, $alreadyAddedStatus)) {

                    list($statusCode, $statusDeliveryCode) = explode("-", $statusPairing);

                    if (strcasecmp($order->getFullfillmentType(), 'd') == 0
                        && $statusDeliveryCode > 0
                    ) {

                        $responseArray["status"][] = $order->getStatusEmpty($statusCode, $statusDeliveryCode);
                    } else {

                        $responseArray["status"][] = $order->getStatusEmpty($statusCode, $statusDeliveryCode);
                    }
                }
            }
        }
        ///////////////////////////////////////////////////////////////////////////
    }

    if ($order->getFullfillmentType()=='p'){
        $responseArray = OrderHelper::updatePickupStagesFromOrderStatusReturnArray($responseArray);
    }

    return $responseArray;
}

function add2Cart($postVars)
{

    $orderId = urldecode($postVars['orderId']);
    $orderItemId = urldecode($postVars['orderItemId']);
    $uniqueRetailerItemId = urldecode($postVars['uniqueRetailerItemId']);
    $itemQuantity = intval(urldecode($postVars['itemQuantity']));
    $itemComment = sanitize(urldecode($postVars['itemComment']));
    $options = $postVars['options']; // URL Decoded from Slim Library

    if (empty($itemComment)){
        // a hack, next part is checking if it is a "0" string
        $itemComment = '0';
    }

    if (empty($orderId)
        || empty_zero_allowed($orderItemId)
        || empty($uniqueRetailerItemId)
        || empty($itemQuantity)
        || empty_zero_allowed($itemComment)
        || empty_zero_allowed($options)
    ) {

        json_error("AS_005", "", "Incorrect API Call. PostVars = " . json_encode($postVars));
    }

    if (isItem86isedFortheDay($uniqueRetailerItemId)) {

        json_error("AS_895", "We are sorry, but this item is currently not available.",
            "Item 86 found, Unique Id: " . $uniqueRetailerItemId . ", Order Id: " . $orderId, 1);
    }

    /////////////////////////////////////////////////////////////////
    // Initialize Retailer under user
    $retailerItem = new RetailerItem($uniqueRetailerItemId);

    // Fetch Retailer Item
    $retailerItem->fetchRetailerItem();

    // Fetch Retailer Item Modifiers
    $retailerItem->fetchRetailerItemModifiers();

    // Fetch Retailer Item Modifier Options
    $retailerItem->fetchRetailerItemModifierOptions();

    if (empty($retailerItem->getObjectId())) {

        json_error("AS_868", "", "Item not found = " . $uniqueRetailerItemId, 1);
    }
    /////////////////////////////////////////////////////////////////

    // Initialize Order under user
    $order = new Order($GLOBALS['user']);

    // Fetch available order by order id
    $order->fetchOrderByOrderId($orderId);

    ////////////////////////////////////////////////
    // Verify if an Open Order was found
    try {

        $rules = [
            "exists" => true,
            "statusInList" => listStatusesForCart(),
            "matchesRetailerId" => $retailerItem->getUniqueRetailerId()
        ];

        // Validate if we found the order
        $order->performChecks($rules);
    } catch (Exception $ex) {

        json_error("AS_805", "",
            "Order not found or not yet submitted! Order Id = " . $orderId . " - " . $ex->getMessage());
    }

    $itemComment = $itemComment;
    if (strcasecmp(strval($itemComment), "0") == 0) {

        $itemComment = "";
    }

    $modifierOptionsInJSONForSaving = array();
    if ($retailerItem->hasModifiers()) {

        // JSON decode the Options array
        try {

            $options = json_decode($options, true);

            if (is_array($options)) {

                $options = sanitize_array($options);
            } else {

                $options = [];
            }
        } catch (Exception $ex) {
        }

        if (!is_array($options)) {

            json_error("AS_807", "You must select the required options.",
                "Modifier details not provided. Options array was not well-formed." . " PostVars = " . json_encode($postVars),
                1);
        }

        // Process Options
        $modifierGroupsForOptionsSelected = array();
        $optionsProcessed = array();

        foreach ($options as $index => $optionSelected) {

            // If missing indexes			
            if (!isset($optionSelected["id"]) || empty(trim($optionSelected["id"]))
                || !isset($optionSelected["quantity"]) || empty(intval($optionSelected["quantity"]))
            ) {

                json_error("AS_808", "You must select the required options.",
                    "Submitted modifier details (Id or Quantity) are not valid." . " PostVars = " . json_encode($postVars));
            }

            $optionSelected["id"] = trim($optionSelected["id"]);
            $optionSelected["quantity"] = intval($optionSelected["quantity"]);

            // Modifier Quantity is set to 0, so considered as a modifier deletion
            if ($optionSelected["quantity"] == 0) {

                continue;
            }

            // Add to Options Processed
            $optionsProcessed[$optionSelected["id"]] = $optionSelected;
        }

        // List all modifiers for the item in the DB
        $requiredModifiersButNotSelected = $requiredModifiers = $retailerItem->getRequiredModifiers();

        // List all Modifier options for the item in the DB
        $objModifiersOptions = $retailerItem->getModifierOptions();

        // Iterate thru DB Modifiers to verify options rules and missing required options
        $optionsProcessedFromDBCount = 0;
        foreach ($objModifiersOptions as $obj) {

            $uniqueOptionId = $obj->getUniqueId();

            // If this option was selected
            if (isset($optionsProcessed[$uniqueOptionId])) {

                $optionsProcessedFromDBCount++;

                $uniqueRetailerItemModifierId = $obj->getUniqueRetailerItemModifierId();

                // Add Quantities for the Modifier Group at level so we can confirm the min / max logic later
                // If index was not yet initialized, set to 0
                if (!isset($modifierGroupsForOptionsSelected[$uniqueRetailerItemModifierId])) {

                    $modifierGroupsForOptionsSelected[$uniqueRetailerItemModifierId] = 0;
                }

                // Add quantity
                $modifierGroupsForOptionsSelected[$uniqueRetailerItemModifierId] += $optionsProcessed[$uniqueOptionId]["quantity"];

                // Save the options in JSON so to be stored in DB
                $modifierOptionsInJSONForSaving[] = array(
                    "objectId" => $obj->getObjectId(),
                    "optionId" => $obj->getOptionId(),
                    "id" => $optionsProcessed[$uniqueOptionId]["id"],
                    "quantity" => $optionsProcessed[$uniqueOptionId]["quantity"],
                    "price" => empty($obj->getPricePerUnit()) ? 0 : $obj->getPricePerUnit()
                );
            }
        }

        // If the count of options found in RetailerItemModifierOptions < options sent
        // Then some invalid options were provided
        if ($optionsProcessedFromDBCount < count_like_php5($optionsProcessed)) {

            json_error("AS_811", "", "Some invalid options were provided" . " PostVars = " . json_encode($postVars), 2);
        }

        // Check if the required minimum quantity and max quantity are in place for those Options that are selected
        foreach ($modifierGroupsForOptionsSelected as $uniqueRetailerItemModifierId => $quantitySelected) {

            $modifierIsRequired = $retailerItem->getModifier($uniqueRetailerItemModifierId)->getIsRequired();
            $modifierMaxQuantity = $retailerItem->getModifier($uniqueRetailerItemModifierId)->getMaxQuantity();
            $modifierMinQuantity = $retailerItem->getModifier($uniqueRetailerItemModifierId)->getMinQuantity();

            if ($modifierIsRequired) {

                // Mark that this required modifier was indeed selected
                unset($requiredModifiersButNotSelected[$uniqueRetailerItemModifierId]);

                // If Quantity is < min required
                if ($quantitySelected < $modifierMinQuantity) {

                    json_error("AS_809", "You must select options before adding to item.",
                        "Modifier Options selected are less than minimum required. Required min quantity rule violated for Modifier Group $uniqueRetailerItemModifierId Quantity selected $quantitySelected but min required $modifierMinQuantity" . " PostVars = " . json_encode($postVars),
                        2);
                }
            }

            // If Quantity is > max allowed
            if ($quantitySelected > $modifierMaxQuantity && $modifierMaxQuantity != 0) {

                json_error("AS_810",
                    "Quantity selected for the option is higher than supported by the Retailer. Please select a lower value.",
                    "Modifier Options selected are greater than max allowed. Max quantity rule violated for Modifier Group $uniqueRetailerItemModifierId Quantity selected $quantitySelected but max allowed $modifierMaxQuantity" . " PostVars = " . json_encode($postVars),
                    2);
            }
        }

        // Check all required modifiers were selection
        if (count_like_php5($requiredModifiersButNotSelected) > 0) {

            json_error("AS_811", "You must select the appropriate options before adding to item.",
                "Required Modifiers were not selected." . " PostVars = " . json_encode($postVars), 2);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // Check if we have reach max for the Order
    $order->fetchOrderModifiers();

    // Start with requested quantity
    //$orderQuantity = $order->getQuantity() + $itemQuantity;

    // If item being updated, then remove the quantity of the item
    //if (!empty($orderItemId)) {

    //    $orderQuantity = $orderQuantity - $order->getModifier($orderItemId)->getItemQuantity();
    //}

    // Max 10 items
    //if ($orderQuantity > 10) {

    //json_error("AS_876", "You may order only up to 10 items per order. Please adjust quantities.", "Max order quantity reached ($orderQuantity)." . " PostVars = " . json_encode($postVars));
    //}
    ////////////////////////////////////////////////////////////////////////////////////////////////

    // Deleting Item
    // Delete entry if the ItemQuantity or ModifierQuantity is set to 0
    if ($itemQuantity == 0
        && !empty($orderItemId) && $orderItemId != "0"
    ) {

        if (empty($order->getModifier($orderItemId))) {

            json_error("AS_893", "",
                "Cart Operation Failed! - ModifierId not found. PostVars = " . json_encode($postVars), 1);
        }

        try {

            $order->deleteFromCart($orderItemId);
            $orderItemObjectId = $orderItemId;
            $logForAction = 'delete';
        } catch (Exception $ex) {

            json_error("AS_875", "",
                "Cart Operation Failed!" . $ex->getMessage() . " PostVars = " . json_encode($postVars), 1);
        }
    }
    // Updating Item	
    if (!empty($orderItemId) && $orderItemId != "0") {

        if (empty($order->getModifier($orderItemId))) {

            json_error("AS_893", "",
                "Cart Operation Failed! - ModifierId not found. PostVars = " . json_encode($postVars), 1);
        }

        $itemTax = 0;

        ////////////////////////////////////////////////////
        // Check with external partner before adding to cart
        ////////////////////////////////////////////////////
        list($isExternalPartnerOrder, $tenderType, $dualPartnerConfig) = isExternalPartnerOrder($order->getRetailer()->get("uniqueId"));

        if ($isExternalPartnerOrder == true) {

            // Prepare Item Array
            if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

                $item = [$retailerItem->getDBObj(), $itemQuantity, $modifierOptionsInJSONForSaving];
                $cartFormatted = prepareHMSHostItemArray([$item]);
            }

            $itemTax = getPartnerTaxes($dualPartnerConfig, $cartFormatted, $order->getDBObj(),
                $retailerItem->getDBObj());
        }
        ////////////////////////////////////////////////////

        try {

            $orderItemObjectId = $order->updateCart($orderItemId, $itemQuantity, $itemComment,
                $modifierOptionsInJSONForSaving, json_encode(["itemTax" => $itemTax]));
            $logForAction = 'update';
        } catch (Exception $ex) {

            json_error("AS_875", "",
                "Cart Operation Failed!" . $ex->getMessage() . " PostVars = " . json_encode($postVars), 1);
        }
    } // Adding Item
    else {

        $itemTax = 0;

        ////////////////////////////////////////////////////
        // Check with external partner before adding to cart
        ////////////////////////////////////////////////////
        list($isExternalPartnerOrder, $tenderType, $dualPartnerConfig) = isExternalPartnerOrder($order->getRetailer()->get("uniqueId"));

        if ($isExternalPartnerOrder == true) {

            // Prepare Item Array
            if (strcasecmp($dualPartnerConfig->get('partner'), 'hmshost') == 0) {

                $item = [$retailerItem->getDBObj(), $itemQuantity, $modifierOptionsInJSONForSaving];
                $cartFormatted = prepareHMSHostItemArray([$item]);
            }

            $itemTax = getPartnerTaxes($dualPartnerConfig, $cartFormatted, $order->getDBObj(),
                $retailerItem->getDBObj());
        }
        ////////////////////////////////////////////////////

        try {

            $orderItemObjectId = $order->addToCart($retailerItem, $itemQuantity, $itemComment,
                $modifierOptionsInJSONForSaving, json_encode(["itemTax" => $itemTax]));
            $logForAction = 'add';
        } catch (Exception $ex) {

            json_error("AS_875", "",
                "Cart Operation Failed!" . $ex->getMessage() . " PostVars = " . json_encode($postVars), 1);
        }
    }

    // Log user event
    if ($GLOBALS['env_LogUserActions']) {

        try {

            $retailer = parseExecuteQuery(["uniqueId" => $retailerItem->getUniqueRetailerId()], "Retailers", "", "",
                ["location"], 1);
            $retailerName = $retailer->get('retailerName') . ' (' . $retailer->get('location')->get('locationDisplayName') . ')';
            $airportIataCode = $retailer->get('airportIataCode');

            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

            $workerQueue->sendMessage(
                array(
                    "action" => "log_user_action_add_cart",
                    "content" =>
                        array(
                            "objectId" => $GLOBALS['user']->getObjectId(),
                            "data" => json_encode([
                                "retailer" => $retailerName,
                                "actionForRetailerAirportIataCode" => $airportIataCode,
                                "airportIataCode" => $airportIataCode,
                                "orderId" => $order->getObjectId(),
                                "retailerUniqueId" => $retailer->get('retailerUniqueId'),
                                "uniquetailerItemId" => $uniqueRetailerItemId,
                                "actionType" => $logForAction
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

    // If the customer was acquired by referral and has available credits
    if (wasUserBeenAcquiredViaReferral($order->getUserObject())
        && getAvailableUserCreditsViaMap($order->getUserObject())[1] > 0
    ) {

        // build the cart again
        // check if there is a coupon and if the cart now has referral credits
        // If so, remove any coupons that might been added before the item was added or removed if the referral credit is on the order, this is stop double dipping
        if (doesOrderHaveReferralSignupCreditApplied($order->getDBObj())) {

            $order->removeCoupon();
        }
    }

    // Drop Cart cache
    $namedCacheKey = 'cart' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
    delCacheByKey(getNamedRouteCacheName($namedCacheKey));

    $namedCacheKey = 'cartv2' . '__u__' . $order->getUserObjectId() . '__o__' . $order->getObjectId();
    delCacheByKey(getNamedRouteCacheName($namedCacheKey));

    delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getObjectId()));

    $responseArray = array("orderItemObjectId" => $orderItemObjectId);

    return $responseArray;
}

?>
