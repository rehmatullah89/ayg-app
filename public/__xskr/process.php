<?php

require 'dirpath.php';
require $dirpath . 'vendor/autoload.php';
require $dirpath . 'core/token.php';

// Action
if(!isset($_GET['action'])) {

	$_GET['action'] = '';
}

// JMD
$_GET['action'] = sanitize($_GET['action']);

// default is false
$metadataCall = false;

if(in_array($_GET['action'], ['mt_airports', 'mt_terminalconcourses', 'mt_retailers'])) {

	$metadataCall = true;
}


if(!isset($_GET['token']) 
	|| empty(sanitize($_GET['token'])) 
	|| empty(sanitize($_GET['tokenEpoch'])) 
		|| !checkToken(sanitize($_GET['token']), sanitize($_GET['tokenEpoch']), $metadataCall)
		) {
	
	echo json_encode(array("json_resp_status" => 0, "json_resp_message" => "Please reload the page and try again."));

	exit;
}

// array to hold validation errors
$errors         = array();

// array to pass back data
$data           = array();

if(isset($_GET['action']) && $_GET['action'] == 'activate') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['id'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Beta Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/user/beta/action'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/userObjectId/' . $_GET['id']
				. '/activate/' . '1'
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'inactivate') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['id'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Beta Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/user/beta/action'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/userObjectId/' . $_GET['id']
				. '/activate/' . '-1'
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'posTestMsg') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['uniqueId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Retailer Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/testMsg/pos'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/uniqueId/' . $_GET['uniqueId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'closeEarlyPOS') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['uniqueId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Retailer Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/close/pos'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/uniqueId/' . $_GET['uniqueId']
				. '/closeUntilDate/' . $_GET['closeUntilDate']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'reopenPOS') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['uniqueId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Retailer Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/open/pos'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/uniqueId/' . $_GET['uniqueId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'mt_airports') {
	
    // show a message of success and provide a true success variable
	ini_set('precision', 14); 
	$epoch = microtime(true)*1000;
	$response = getpage($env_BaseURL . '/metadata/airports'
			. '/a/' . generateAPIToken($epoch)
			. '/e/' . $epoch
			. '/u/' . '0'
			);
	
	// Already in JSON format
	echo $response;
	exit;
	// JMD
}

else if(isset($_GET['action']) && $_GET['action'] == 'mt_terminalconcourses') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['airportIataCode'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'No airport selected';

	} else {

	    // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/metadata/terminalConcourses'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch

				. '/u/' . '0'
				. '/airportIataCode/' . $_GET['airportIataCode']
				);

		// Already in JSON format
		echo $response;
		exit;
	}
}

else if(isset($_GET['action']) && $_GET['action'] == 'mt_retailers') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['airportIataCode'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Please select Airport Code';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/metadata/retailers'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/airportIataCode/' . $_GET['airportIataCode']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'mt_terminalconcourses') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['airportIataCode'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Please select Airport Code';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/metadata/terminalConcourses'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/airportIataCode/' . $_GET['airportIataCode']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'deliveryTestMsg') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['deliveryId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Delivery Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/testMsg/delivery'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/deliveryId/' . $_GET['deliveryId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'appRatingRequestSend') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if(empty($_GET['orderId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Order Id! Reload and try again.';
	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;

		$response = getpage($env_BaseURL . '/sendAppRatingRequest'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/orderId/' . $_GET['orderId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'appRatingRequestSkip') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if(empty($_GET['orderId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Order Id! Reload and try again.';
	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;

		$response = getpage($env_BaseURL . '/skipAppRatingRequest'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/orderId/' . $_GET['orderId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'orderCancel') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if(empty($_GET['orderId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Order Id! Reload and try again.';
	} else if(!isset($_GET['cancelReasonCode']) || empty($_GET['cancelReasonCode'])) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Cancel Reason Code. Reload and try again.';
	} else if(!isset($_GET['cancelReason'])
				|| empty($_GET['cancelReason'])
					|| strlen($_GET['cancelReason']) < 10) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Cancel Specifics. Must be at least 10 chars. Reload and try again.';
	} else if(!isset($_GET['refundType']) || empty($_GET['refundType'])) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Refund Type';
	} else if(isset($_GET['partialRefundAmount']) && !empty($_GET['partialRefundAmount'])
				&& (strval(intval($_GET['partialRefundAmount'])) != $_GET['partialRefundAmount']
						|| intval($_GET['partialRefundAmount']) <= 0)) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Partial Refund Amount must be a number greater than 0';
	} else if(!isset($_GET['refundRetailer']) || empty_zero_allowed($_GET['refundRetailer'])) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Please select Refund Retailer field';
	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/order/cancel'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/orderId/' . intval($_GET['orderId'])
				. '/cancelReasonCode/' . intval($_GET['cancelReasonCode'])
				. '/cancelReason/' . urlencode($_GET['cancelReason'])
				. '/partialRefundAmount/' . intval($_GET['partialRefundAmount'])
				. '/refundType/' . urlencode($_GET['refundType'])
				. '/refundRetailer/' . urlencode($_GET['refundRetailer'])
				);

		// // // Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'orderCancelAdmin') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if(empty($_GET['orderId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Order Id! Reload and try again.';
	} else if(!isset($_GET['cancelReasonCode']) || empty($_GET['cancelReasonCode'])) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Cancel Reason Code. Reload and try again.';
	} else if(!isset($_GET['cancelReason'])
				|| empty($_GET['cancelReason'])
					|| strlen($_GET['cancelReason']) < 10) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Cancel Specifics. Must be at least 10 chars. Reload and try again.';
	} else if(!isset($_GET['refundType']) || empty($_GET['refundType'])) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Refund Type';
	} else if(isset($_GET['partialRefundAmount']) && !empty($_GET['partialRefundAmount'])
				&& (strval(intval($_GET['partialRefundAmount'])) != $_GET['partialRefundAmount']
						|| intval($_GET['partialRefundAmount']) <= 0)) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Partial Refund Amount must be a number greater than 0';
	} else if(!isset($_GET['refundRetailer']) || empty_zero_allowed($_GET['refundRetailer'])) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Please select Refund Retailer?';
	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/order/cancelWithAdmin'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/orderId/' . intval($_GET['orderId'])
				. '/cancelReasonCode/' . intval($_GET['cancelReasonCode'])
				. '/cancelReason/' . urlencode($_GET['cancelReason'])
				. '/partialRefundAmount/' . intval($_GET['partialRefundAmount'])
				. '/refundType/' . urlencode($_GET['refundType'])
				. '/refundRetailer/' . urlencode($_GET['refundRetailer'])
				);

		// // // Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'orderPartialRefund') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if(empty($_GET['orderId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Order Id! Reload and try again.';
	} else if(!isset($_GET['reason'])
				|| empty($_GET['reason'])
					|| strlen($_GET['reason']) < 10) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Reason for refund, e.g. Brownie was out of stock. Reason must be at least 10 chars. Reload and try again.';
	} else if(!isset($_GET['refundType']) || empty($_GET['refundType'])) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Missing: Refund Type';
	} else if(!isset($_GET['inCents']) || empty($_GET['inCents'])
				|| intval($_GET['inCents']) <= 0) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Partial Refund Amount must be a number greater than 0';
	} else if(strcasecmp(strval(intval($_GET['inCents'])), $_GET['inCents']) != 0) {

        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Partial Refund Amount must be a whole number';
	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/order/partialRefund'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/orderId/' . intval($_GET['orderId'])
				. '/refundType/' . urlencode($_GET['refundType'])
				. '/inCents/' . intval($_GET['inCents'])
				. '/reason/' . urlencode($_GET['reason'])
				);

		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'orderPush') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['orderId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Order Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/order/push'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/objectId/' . $_GET['orderId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'getDeliverySlackUrl') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['code'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. Missing code.';

	} else {

        // Get URL
		$responseArrayURL = getSlackOAuthResponse($_GET['code']);

		if(isset($responseArrayURL["ok"])
			&& $responseArrayURL["ok"] != true) {

	        $data['json_resp_status'] = 0;
	        $data['json_resp_message'] = 'Failed. ' . $responseArrayURL['error'];
		}
		else {

	        // Get User Id
			$responseArrayUserId = getSlackUserList();

			// If response was ok, then search for user id
			if(isset($responseArrayUserId["ok"])
				&& $responseArrayUserId["ok"] == true) {

				$userIdSearch = str_replace('#', '', $responseArrayURL['incoming_webhook']['channel']);
				$userIdSearch = str_replace(strtolower($GLOBALS['env_EnvironmentDisplayCode']) . '-', '', $userIdSearch);
			}

			$userId = searchForUserId($responseArrayUserId, $userIdSearch);

			if(empty($userId)) {

		        $data['json_resp_status'] = 0;
		        $data['json_resp_message'] = 'Failed. User id couldnt be located, searching for: ' . $userIdSearch . ' from channel ' . $responseArrayURL['incoming_webhook']['channel'];
			}
			else {

		        $data['json_resp_status'] = 1;
		        $data['json_resp_message'] = "For slackUserId: " . $userId . ", the URL for the associated channel: " . $responseArrayURL['incoming_webhook']['channel'] . " is, " . $responseArrayURL['incoming_webhook']['url'];
			}
		}
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'getPOSSlackUrl') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['code'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. Missing code.';

	} else {

        // Get URL
		$responseArrayURL = getSlackOAuthResponse($_GET['code']);

		if(isset($responseArrayURL["ok"])
			&& $responseArrayURL["ok"] != true) {

	        $data['json_resp_status'] = 0;
	        $data['json_resp_message'] = 'Failed. ' . $responseArrayURL['error'];
		}
		else {

	        // Get User Id
			$responseArrayUserId = getSlackUserList();

			// If response was ok, then search for user id
			if(isset($responseArrayUserId["ok"])
				&& $responseArrayUserId["ok"] == true) {

				$userIdSearch = str_replace('#', '', $responseArrayURL['incoming_webhook']['channel']);
				$userIdSearch = str_replace('__', '', $userIdSearch);
			}

			$userId = searchForUserId($responseArrayUserId, $userIdSearch);

			if(empty($userId)) {

		        $data['json_resp_status'] = 0;
		        $data['json_resp_message'] = 'Failed. Tablet id couldnt be located, searching for: ' . $userIdSearch . ' from channel ' . $responseArrayURL['incoming_webhook']['channel'];
			}
			else {

		        $data['json_resp_status'] = 1;
		        $data['json_resp_message'] = "For tabletId: " . $userId . ", the URL for the associated channel: " . $responseArrayURL['incoming_webhook']['channel'] . " is, " . $responseArrayURL['incoming_webhook']['url'];
			}
		}
    }
}

else if(isset($_GET['action']) && $_GET['action'] == '86item') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['uniqueRetailerItemId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Unique Retailer Id';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/retailer/set86'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/uniqueRetailerItemId/' . $_GET['uniqueRetailerItemId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'fetchInvoice') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['orderId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Order Id.';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/order/invoice'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/orderId/' . $_GET['orderId']
				);
		
		$response = json_decode($response, true);

		if($response[0]) {

			// Already in JSON format
			header('Content-type: application/pdf');
			header('Content-Disposition: inline; filename="invoice-"' . $_GET['orderId']);
			header('Content-Transfer-Encoding: binary');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			header('Accept-Ranges: bytes');
			print_r(getpage($response[1]));
			exit;
		}
		else {

			echo $response[1];exit;
		}
    }
}

else if(isset($_GET['action']) && $_GET['action'] == '86itemRemove') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['uniqueRetailerItemId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Unique Retailer Id';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/retailer/remove86'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/uniqueRetailerItemId/' . $_GET['uniqueRetailerItemId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}
else if(isset($_GET['action']) && $_GET['action'] == 'getMobilockDevices') {

	$response = \Httpful\Request::get('https://mobilock.in/api/v1/devices.json')
		->addHeader('Authorization', 'Token ' . $GLOBALS['env_MobiLock_APIKey'])
	    ->send();

	echo(json_encode($response->body));exit;
}

else if(isset($_GET['action']) && $_GET['action'] == 'deliveryActivate') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['deliveryId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Delivery Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/status/delivery/activate'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/deliveryId/' . $_GET['deliveryId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

else if(isset($_GET['action']) && $_GET['action'] == 'deliveryDeactivate') {
	
    // if there are any errors in our errors array, return a success boolean of false
    if (empty($_GET['deliveryId'])) {

        // if there are items in our errors array, return those errors
        $data['json_resp_status'] = 0;
        $data['json_resp_message'] = 'Failed. No Delivery Id!';

	} else {

        // show a message of success and provide a true success variable
		ini_set('precision', 14); 
		$epoch = microtime(true)*1000;
		$response = getpage($env_BaseURL . '/status/delivery/deactivate'
				. '/a/' . generateAPIToken($epoch)
				. '/e/' . $epoch
				. '/u/' . '0'
				. '/deliveryId/' . $_GET['deliveryId']
				);
		
		// Already in JSON format
		echo $response;
		exit;
    }
}

// return all our data to an AJAX call
echo json_encode($data);exit;

function getSlackOAuthResponse($code) {

	$response = getpage('https://slack.com/api/oauth.access?client_id=' . $GLOBALS['env_SlackClientId'] . '&client_secret=' . $GLOBALS['env_SlackClientSecret'] . '&code=' . $code);
	return json_decode($response, true);
}

function getSlackUserList() {

	$response = getpage('https://slack.com/api/users.list?token=' . $GLOBALS['env_SlackAccessToken']);
	return json_decode($response, true);
}

function searchForUserId($responseArrayUserId, $userIdSearch) {

	$userId = '';
	foreach($responseArrayUserId["members"] as $member) {

		if(strcasecmp($member["name"], $userIdSearch)==0) {

			$userId = $member["id"];
			break;
		}
	}

	return $userId;
}	

?>
