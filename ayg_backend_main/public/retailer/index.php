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
use Httpful\Request;

// Get Trending Restaurant List with retailerType and Limit
$app->get('/trending/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/retailerType/:retailerType/limit/:limit', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $retailerType, $limit) {

	// Check if already have cache for this
	getRouteCache();

	// Find Trending Retailers
	// $trending = getTrendingRetailersForAirport($airportIataCode);

	$limit = intval($limit);

	if($retailerType != "0") {
		
		$trendingRetailers = trendingRetailerTop($airportIataCode, $retailerType, $limit);	
	}
	else {

		$trendingRetailers = trendingRetailerTop($airportIataCode, "", $limit);	
	}

	$responseArray = array();
	foreach($trendingRetailers as $retailerUniqueId) {
		
		$responseArray[] = getRetailerInfo($retailerUniqueId);
	}
	
	// Set cache with default expiry
	json_echo(
		setRouteCache([
			"jsonEncodedString" => json_encode($responseArray)
		])
	);
});

// Retailer Types
$app->get('/type/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {

	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = array();
	
	$objParseQueryRetailerType = parseExecuteQuery(array(), "RetailerType");
	
	$i = 0;
	foreach($objParseQueryRetailerType as $type) {
		
		$responseArray[$i] = $type->getAllKeys();
		$i++;
	}
	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});

// Retailer Category
$app->get('/category/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {

	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = array();

	$objParseQueryRetailerCategory = parseExecuteQuery(array(), "RetailerCategory", "", "", array("retailerType"));
	
	$i = 0;
	foreach($objParseQueryRetailerCategory as $category) {
		
		$responseArray[$i] = $category->getAllKeys();
		$responseArray[$i]["retailerType"] = $category->get("retailerType")->getAllKeys();
		
		unset($responseArray[$i]["retailerType"]["uniqueId"]);
		unset($responseArray[$i]["retailerType"]["__type"]);
		unset($responseArray[$i]["retailerType"]["className"]);
		$i++;
	}
	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});

// Retailer Price Category
$app->get('/priceCategory/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {

	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = array();

	$objParseQueryRetailerPriceCategory = parseExecuteQuery(array(), "RetailerPriceCategory");
	
	$i = 0;
	foreach($objParseQueryRetailerPriceCategory as $priceCategory) {
		
		$responseArray[$i] = $priceCategory->getAllKeys();
	}
	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});

// Retailer Food Seating Type
$app->get('/foodSeatingType/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {

	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = array();

	$objParseQueryFoodSeatingType = parseExecuteQuery(array(), "RetailerFoodSeatingType");
	
	$i = 0;
	foreach($objParseQueryFoodSeatingType as $foodSeatingType) {
		
		$responseArray[$i] = $foodSeatingType->getAllKeys();
		$i++;
	}
	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});

// Get Restaurant List near a gate and limit WITHOUT retailerType
$app->get('/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/limit/:limit', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $locationId, $limit) {
				
	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = byDistance($airportIataCode, $locationId, "", "", "", "", $limit);

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => (intval($GLOBALS['env_PingSlackGraceMultiplier']) * intval($GLOBALS['env_TabletAppDefaultPingIntervalInSecs']))
		])
	);
});

// Get Restaurant List near a gate with retailerType and Limit
$app->get('/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/retailerType/:retailerType/limit/:limit', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $locationId, $retailerType, $limit) {
				
	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = byDistance($airportIataCode, $locationId, "", "", "", $retailerType, $limit);
		
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => (intval($GLOBALS['env_PingSlackGraceMultiplier']) * intval($GLOBALS['env_TabletAppDefaultPingIntervalInSecs']))
		])
	);
});

// Get Restaurant List near a gate with retailerType and Limit BUT with different Sort Order Terminal & Gate
$app->get('/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/terminalSort/:terminalSort/concourseSort/:concourseSort/gateSort/:gateSort/retailerType/:retailerType/limit/:limit', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $locationId, $terminalSort, $concourseSort, $gateSort, $retailerType, $limit) {
				
	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = byDistance($airportIataCode, $locationId, $terminalSort, $concourseSort, $gateSort, $retailerType, $limit);
		
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => (intval($GLOBALS['env_PingSlackGraceMultiplier']) * intval($GLOBALS['env_TabletAppDefaultPingIntervalInSecs']))
		])
	);
});

// Get Fullfillment info for all retailers
$app->get('/fullfillmentInfo/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId',
	\App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $locationId) {
				
    $namedCacheKey = '__FULLFILLMENTINFO__' . $airportIataCode . '__' . $locationId;

	// Check if already have cache for this
	$cachedResponse = getRouteCache($namedCacheKey, false, true);

	$cachedResponseArray = json_decode($cachedResponse, true);
        //$cachedResponseArray= [];
	// JM

	if(count_like_php5($cachedResponseArray) > 0) {

	    // Log user event
	    if($GLOBALS['env_LogUserActions']) {

	        try {

	            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

	            $workerQueue->sendMessage(
	                    array("action" => "log_user_action_retailer_list",
	                          "content" =>
	                            array(
	                                "objectId" => $GLOBALS['user']->getObjectId(),
	                                "data" => json_encode(["numberOfAvailableRetailers" => countActiveRetailersInFullfillment($cachedResponseArray)["total"], "numberOfAvailableRetailersWithDelivery" => countActiveRetailersInFullfillment($cachedResponseArray)["delivery"], "type" => "cached_fullfillment", "actionForRetailerAirportIataCode" => $airportIataCode, "airportIataCode" => $airportIataCode]),
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

		json_echo($cachedResponse);
	}


	$responseArray = fetchFullfillmentTimes($airportIataCode, $locationId);


	// Log user event
	if($GLOBALS['env_LogUserActions']) {

	    try {

	        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

	        $workerQueue->sendMessage(
	                array("action" => "log_user_action_retailer_list",
	                      "content" =>
	                        array(
	                            "objectId" => $GLOBALS['user']->getObjectId(),
	                            "data" => json_encode(["numberOfAvailableRetailers" => countActiveRetailersInFullfillment($responseArray)["total"], "numberOfAvailableRetailersWithDelivery" => countActiveRetailersInFullfillment($responseArray)["delivery"], "type" => "uncached_fullfillment", "actionForRetailerAirportIataCode" => $airportIataCode, "airportIataCode" => $airportIataCode]),
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

	// JMD
	// Cache for 3 times env_PingRetailerIntervalInSecs
	json_echo(
		setRouteCache([
				"cacheSlimRouteNamedKey" => $namedCacheKey,
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => intval($GLOBALS['env_PingRetailerIntervalInSecs']) * 3
		])
	);
});

// Get Fullfillment info for a specific retailer
$app->get('/fullfillmentInfo/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/retailerId/:retailerId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	\App\Consumer\Controllers\RetailerController::class . ':getFullfillmentInfo'
);
/*$app->get('/fullfillmentInfo/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/retailerId/:retailerId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $locationId, $retailerId) {
				
	// Check if already have cache for this
	getRouteCache();

	$responseArray = fetchFullfillmentTimes($airportIataCode, $locationId, $retailerId);

	// Cache for 5 mins
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5*60
		])
	);
});*/

// Get Distance between Current Location and give Retailer object id
$app->get('/distance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/fromLocationId/:fromLocationId/toRetailerLocationId/:toRetailerLocationId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $fromLocationId, $toRetailerLocationId) {

	// Check if already have cache for this
	getRouteCache();

	// Verify Airport Iata Code
	if(count_like_php5(getAirportByIataCode($airportIataCode)) == 0) {
		
		json_error("AS_511", "", "Invalid Airport Code provided for Airport - " . $airportIataCode);
	}

	// Get To Terminal and To Gate of the Retailer from Parse
	list($airportIataCode, $toTerminal, $toConcourse, $toGate) = getGateLocationDetails($airportIataCode, $toRetailerLocationId);

	// Check if From Terminal and From Gate values are valid
	list($airportIataCode, $fromTerminal, $fromConcourse, $fromGate) = getGateLocationDetails($airportIataCode, $fromLocationId);

	if(empty($airportIataCode)
		|| empty($toTerminal)
		|| empty_zero_allowed($toGate)
		|| empty($fromTerminal)
		|| empty_zero_allowed($fromGate)) {

		json_error("AS_514", "", "Provided location Ids are invalid");
	}
	
	// If FROM Terminal is different from TO Terminal, split the distance calculations
	$distanceMetrics = getDistanceMetrics($toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse, $fromGate, true, $airportIataCode);
	
	$responseArray = $distanceMetrics;
	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOW"
		])
	);
});

// Get Directions between Current Location and give Retailer object id
$app->get('/directions/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/fromLocationId/:fromLocationId/toRetailerLocationId/:toRetailerLocationId/referenceRetailerId/:referenceRetailerId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $airportIataCode, $fromLocationId, $toRetailerLocationId, $referenceRetailerId) {

	// $fromTerminal = base64decodeIfNeeded($fromTerminal);
	// $fromGate = base64decodeIfNeeded($fromGate);
	// $toRetailerId = base64decodeIfNeeded($toRetailerId);
	
	// Check if already have cache for this
	getRouteCache();

	$responseArray = getDirections($airportIataCode, $fromLocationId, $toRetailerLocationId, $referenceRetailerId);
	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOW"
		])
	);
});

// Get first level Menu for the Retailer
$app->get('/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken, $retailerId) {

	// Check if already have cache for this
	$namedCacheKey = 'menu' . '__ri__' . $retailerId;

	if($GLOBALS['env_LogUserActions']) {

	    try {

		    $retailerInfo = getRetailerInfo($retailerId);
			$retailerName = $retailerInfo["retailerName"] . ' (' . $retailerInfo["location"]["locationDisplayName"] . ')';
			$airportIataCode = $retailerInfo["airportIataCode"];

	        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

	        $workerQueue->sendMessage(
	                array("action" => "log_user_action_retailer_menu",
	                      "content" =>
	                        array(
	                            "objectId" => $GLOBALS['user']->getObjectId(),
	                            "data" => json_encode(["retailer" => $retailerName, "retailerUniqueId" => $retailerId, "actionForRetailerAirportIataCode" => $airportIataCode, "airportIataCode" => $airportIataCode]),
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

	getRouteCache($namedCacheKey);

	$responseArray = getRetailerMenu($retailerId, time());

	// Cache for EOD
	json_echo(
		setRouteCache([
				"cacheSlimRouteNamedKey" => $namedCacheKey,
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});


// Get first level Menu for the Retailer for a given time (for order schedulling)
$app->get('/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId/timestamp/:timestamp', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken, $retailerId, $timestamp) {

        // Check if already have cache for this for the given day
        $namedCacheKey = 'menu' . '__ri__sch_' . $retailerId . '__' . date("w", $timestamp);

        getRouteCache($namedCacheKey);

        $responseArray = getRetailerMenu($retailerId, intval($timestamp));

        // Cache for EOW
        json_echo(
            setRouteCache([
                "cacheSlimRouteNamedKey" => $namedCacheKey,
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => "EOW"
            ])
        );
    });


// Get Second level (Modifiers) Menu for the Retailer
$app->get('/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId/itemId/:uniqueItemId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $retailerId, $uniqueItemId) {

	// Check if already have cache for this
	$namedCacheKey = 'menu' . '__ri__' . $retailerId . '__ii__' . $uniqueItemId;
	getRouteCache($namedCacheKey);

	$responseArray = getRetailerMenuItem($retailerId, $uniqueItemId);	

	// Cache for EOD
	json_echo(
		setRouteCache([
				"cacheSlimRouteNamedKey" => $namedCacheKey,
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});

// QA - Get first level Menu for the Retailer
$app->get('/qa/identify/a/:apikey/e/:epoch/u/:sessionToken/id/:id/passcode/:passcode', 'apiAuthForWebAPI', 
	function ($apikey, $epoch, $sessionToken, $id, $passcode) {

	// Check if already have cache for this
	getRouteCache();

	$retailerLookup = parseExecuteQuery(["objectId" => $id, "passcode" => $passcode], "zQAappRetailerLookup", "", "", ["retailer"], 1);

	if(count_like_php5($retailerLookup) == 0) {

		json_error("AS_518", "Your credentials couldn't be confirmed. Please verify information provided.", "Retailer credential failed (" . $id . "-" . $passcode . ")", 3);	
	}

	$responseArray = ["retailerId" => $retailerLookup->get("retailer")->get("uniqueId")];

	// Cache for EOD
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});

// QA - Get Second level (Modifiers) Menu for the Retailer
$app->get('/qa/info/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId', 'apiAuthForWebAPI', 
	function ($apikey, $epoch, $sessionToken, $retailerId) {

	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = getRetailerInfoForMenu($retailerId);

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});

// Get first level Menu for the Retailer
$app->get('/qa/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId', 'apiAuthForWebAPI', 
	function ($apikey, $epoch, $sessionToken, $retailerId) {

	// Check if already have cache for this
	getRouteCache();

	$responseArray = getRetailerMenu($retailerId, time());

	// Cache for 5 mins
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5*60
		])
	);
});

// Get Second level (Modifiers) Menu for the Retailer
$app->get('/qa/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId/itemId/:uniqueItemId', 'apiAuthForWebAPI', 
	function ($apikey, $epoch, $sessionToken, $retailerId, $uniqueItemId) {

	// Check if already have cache for this
	getRouteCache();
	$responseArray = getRetailerMenuItem($retailerId, $uniqueItemId);	

	// Cache for 6 mins	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 5*60
		])
	);
});

// Ping to check if the Retailer POS is up
$app->get('/ping/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $retailerId) {


	$objectParseQueryRetailer = parseExecuteQuery(array("uniqueId" => $retailerId, "isActive" => true), "Retailers", "", "", array("location", "retailerType", "retailerPriceCategory", "retailerPriceCategory", "retailerFoodSeatingType"), 1);

	if(count_like_php5($objectParseQueryRetailer) < 1
		|| ($objectParseQueryRetailer->get("hasPickup")==false && $objectParseQueryRetailer->get("hasDelivery")==false)) {

		json_error("AS_508", "", "Retailer not found for uniqueRetailerId (" . $retailerId . ")", 1);
	}

	$responseArray = cartPingRetailer($objectParseQueryRetailer);

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray)
		])
	);
});


// Ping all retailers at once
// Jira Ticket https://airportsherpa.atlassian.net/browse/CON-240
$app->get('/ping/all/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

        getRouteCache();

        $responseArray = [];

        // prepare list by retailers
        // this is in case when retailer data is there, but retailerPosConfig data is not there
        $objRetailers = parseExecuteQuery([], "Retailers", "", "");
        if(count_like_php5($objRetailers) > 0) {
            foreach ($objRetailers as $obj) {
                $responseArray[$obj->get('uniqueId')] = [
                    "isAccepting" => false,
                    "isClosed" => true,
                    "pingStatusDescription" => "This retailer is currently not accepting orders."
                ];
            }
        }


        // overwrite it by the data from pingRetailer - for the retailers with RetailerPOSConfig
        $objRetailer = parseExecuteQuery([], "RetailerPOSConfig", "", "", ["retailer", "retailer.location"]);
        if(count_like_php5($objRetailer) > 0) {
            foreach($objRetailer as $obj) {
                if (!empty($obj->get('retailer')->get('uniqueId'))){
                    // Pull details if the retailer is accepting orders
                    list($isAcceptingOrders, $isClosed, $error, $notAcceptingOrderDesc) = pingRetailer($obj->get("retailer"));
		
					if(!$isClosed) {

						// If closed early
						if(isRetailerCloseEarlyForNewOrders($obj->get("retailer")->get('uniqueId'))) {

							$isAcceptingOrders = false;
							$isClosed = true;
							$notAcceptingOrderDesc = "The retailer has closed for the day.";
						}
					}

                    $responseArray[$obj->get('retailer')->get('uniqueId')] = 
						["isAccepting" => $isAcceptingOrders,
							"isClosed" => $isClosed,
							"pingStatusDescription" => $notAcceptingOrderDesc];
				}
			}
		}

        // Cache for 30 secs
        json_echo(
            setRouteCache([
                "jsonEncodedString" => json_encode($responseArray),
                "expireInSeconds" => 30
            ])
        );
    });

// Retailer Information
$app->get('/info/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 
	function ($apikey, $epoch, $sessionToken, $retailerId) {

	// Check if already have cache for this
	getRouteCache();
	
	$responseArray = getRetailerInfoForMenu($retailerId);

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOW"
		])
	);
});

// Check if Tip is allowed for this Retailer
$app->get('/tipCheck/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 

	function ($apikey, $epoch, $sessionToken, $retailerId) {

	// Check if already have cache for this
	getRouteCache();
	
	// Check if Retailer exists
	//$uniqueRetailerId = isCorrectObject(array("uniqueId" => $retailerId, "isActive" => true), "Retailers", "Retailer", "uniqueId", 0);
	
	$objectParseQueryRetailer = parseExecuteQuery(array("uniqueId" => $retailerId), "Retailers", "", "", array("location", "retailerType", "retailerPriceCategory", "retailerPriceCategory", "retailerFoodSeatingType"));
	if(count_like_php5($objectParseQueryRetailer) < 1) {
		
		json_error("AS_507", "", "Retailer not found for uniqueRetailerId (" . $retailerId . ")", 1);
	}
	
	$objectParseQueryPOSConfig = parseExecuteQuery(array("retailer" => $objectParseQueryRetailer[0]), "RetailerPOSConfig", "", "", array("retailer"));
	
	// If Config is NOT found, let caller know
	if(count_like_php5($objectParseQueryPOSConfig) == 0) {
		
		json_error("AS_503", "", "Retailer not found! POS Config not found for the uniqueRetailerId (" . $objectParseQueryPOSConfig[0]->get('retailer')->get('uniqueId') . ")", 1);
	}
	// Get the Config
	$areTipsAllowed = $objectParseQueryPOSConfig[0]->get('areTipsAllowed');
	
	if(!$areTipsAllowed) {
		
		$responseArray[] = array("allowed" => "0");
	}
	else {
		
		$responseArray[] = array("allowed" => "1");
	}
	
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOD"
		])
	);
});


// Retailers List by Airport with Retailer Type filter
$app->get('/list/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/retailerType/:retailerType', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 

	function ($apikey, $epoch, $sessionToken, $airportIataCode, $retailerType) {

	// Check if already have cache for this
	$cachedResponse = getRouteCache('', false, true);

	$cachedResponseArray = json_decode($cachedResponse, true);


	if(count_like_php5($cachedResponseArray) > 0) {

	    // Log user event
	    if($GLOBALS['env_LogUserActions']) {

	        try {

	            //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
                $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

	            $workerQueue->sendMessage(
	                    array("action" => "log_user_action_retailer_list",
	                          "content" =>
	                            array(
	                                "objectId" => $GLOBALS['user']->getObjectId(),
	                                "data" => json_encode(["numberOfRetailersShown" => count_like_php5($cachedResponseArray), "type" => "cached", "actionForRetailerAirportIataCode" => $airportIataCode, "airportIataCode" => $airportIataCode]),
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

		json_echo($cachedResponse);
	}

	// Find Retailers by airport code
	$retailerSearchQueryArray = array("airportIataCode" => $airportIataCode, "isActive" => true);
	
	$retailerType = trim($retailerType);
	if(!empty($retailerType)) {
		
		$objParseQueryRetailerTypeInner = parseExecuteQuery(array("retailerType" => $retailerType), "RetailerType");
		$retailerSearchQueryArray["retailerType"] = $objParseQueryRetailerTypeInner[0];
	}

	$objParseQueryRetailersResults = parseExecuteQuery($retailerSearchQueryArray, "Retailers", "", "", array("location", "retailerType", "retailerPriceCategory", "retailerPriceCategory", "retailerFoodSeatingType"));

	// If Airport code is not found
	if(count_like_php5($objParseQueryRetailersResults) < 1) {
		
		json_error("AS_509", "", "No Retailers found! " . "No records matching airport code = " . $airportIataCode . " RetailerType = " . $retailerType . " were not found on Parse in Retailers Class", 1);
	}

	$responseArray = array();
	foreach($objParseQueryRetailersResults as $objParseQueryRetailersResultOne) {
	
		$responseArray[] = getRetailerInfo($objParseQueryRetailersResultOne->get('uniqueId'), $objParseQueryRetailersResultOne);
	}
	
	// Log user event
	if($GLOBALS['env_LogUserActions']) {

	    try {

	        //$workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueConsumerName']);
            $workerQueue = newWorkerQueueConnection($GLOBALS['env_workerQueueMidPriorityAsynchConsumerName']);

	        $workerQueue->sendMessage(
	                array("action" => "log_user_action_retailer_list",
	                      "content" =>
	                        array(
	                            "objectId" => $GLOBALS['user']->getObjectId(),
	                            "data" => json_encode(["numberOfRetailersShown" => count_like_php5($responseArray), "type" => "uncached", "actionForRetailerAirportIataCode" => $airportIataCode, "airportIataCode" => $airportIataCode]),
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
        foreach ($responseArray as $i=>$retailer){
            if (getShouldRetailerBeShownDueItemCount($retailer['uniqueId'])===false) {
                unset($responseArray[$i]);
                continue;
            }
        }
        $responseArray=array_values($responseArray);




	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => "EOW"
		])
	);
});



// Curated Lists
$app->get('/curatedLists/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/deliveryLocationId/:deliveryLocationId/flightId/:flightId/requestedFullFillmentTimestamp/:requestedFullFillmentTimestamp', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 

	function ($apikey, $epoch, $sessionToken, $airportIataCode, $deliveryLocationId, $flightId, $requestedFullFillmentTimestamp) {

	$airportArray = getAirportByIataCode($airportIataCode);
	if(empty($airportArray)
		|| $airportArray->get("isReady") == false) {

		json_error("AS_521", "", "Curated list requested for an airport that is not deployed yet", 1);
	}

        try {
            $responseArray = generateCuratedList($airportIataCode, $deliveryLocationId, $flightId, $requestedFullFillmentTimestamp);
        }catch (Exception $exception){
            if ($exception->getMessage()!='AS_520'){
                throw $exception;
            }
            $responseArray['curatedList']=[];
        }

	///////////////////////////////////////////////////////////////
	// Add full retailer list
	///////////////////////////////////////////////////////////////
   	$responseArray["retailers"] = fetchRetailerListSequenced($airportIataCode, $deliveryLocationId, $requestedFullFillmentTimestamp);


   	// Temp fix till CON-1098 is implemented
   	// Removes Pickup estimates if delivery is available so the app shows Delivery estimate
   	foreach($responseArray["retailers"] as $i => $retailer) {

   		if (getShouldRetailerBeShownDueItemCount($retailer['uniqueId'])===false) {
            unset($responseArray["retailers"][$i]);
            continue;
        }

            if($retailer["fulfillmentData"]["d"]["isAvailable"]) {

                $responseArray["retailers"][$i]["fulfillmentData"]["p"]["isAvailable"] = false;
                $responseArray["retailers"][$i]["fulfillmentData"]["p"]["fullfillmentTimeEstimateInSeconds"] = 0;
            }
   	}
        $responseArray["retailers"] = array_values($responseArray["retailers"]);

	json_echo(
		json_encode($responseArray)
	);
});

// Curated list by id
$app->get('/curatedLists/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/deliveryLocationId/:deliveryLocationId/flightId/:flightId/requestedFullFillmentTimestamp/:requestedFullFillmentTimestamp/listId/:listId', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth', 

	function ($apikey, $epoch, $sessionToken, $airportIataCode, $deliveryLocationId, $flightId, $requestedFullFillmentTimestamp, $listId) {

	$airportArray = getAirportByIataCode($airportIataCode);
	if(empty($airportArray)
		|| $airportArray->get("isReady") == false) {

		json_error("AS_521", "", "Curated list requested for an airport that is not deployed yet", 1);
	}

	$responseArray = [];

        try {
            $response = generateCuratedList($airportIataCode, $deliveryLocationId, $flightId, $requestedFullFillmentTimestamp, $listId);
        }catch (Exception $exception){
            if ($exception->getMessage()!='AS_520'){
                throw $exception;
            }
            //$responseArray['retailers']=[];
            $responseArray['curatedList']=[];
            json_echo(
                json_encode($responseArray)
            );
        }

	if(count_like_php5($response) > 0) {

		$responseArray = $response["curatedList"][0];
	}
	
	json_echo(
		json_encode($responseArray)
	);
});

$app->notFound(function () {
	
	json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

?>
