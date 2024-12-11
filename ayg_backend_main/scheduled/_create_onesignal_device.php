<?php

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Httpful\Request;


// $sqs_client = SQSConnect();
// print_r(create_onesignal_device('sGL0HFIAmR'));

function create_onesignal_device($userDeviceId) {

	// json_error("AS_WWW", "", "Onesignal creating - " . $userDeviceId, 1, 1);

	///////////////////////////////////////////////////////////////////////////////////////
	// Fetch User Device detailes
	///////////////////////////////////////////////////////////////////////////////////////
	$userDevice = parseExecuteQuery(array("objectId" => $userDeviceId), "UserDevices", "", "", array("user"), 1);

	if(count_like_php5($userDevice) == 0) {

		return json_error_return_array("AS_1032", "", "OneSignal device creation failed, no device row found device objectId = " . $userDeviceId, 1);
	}
	else if(count_like_php5($userDevice->get('user')) == 0) {

		return json_error_return_array("AS_1053", "", "OneSignal device creation failed, no user row found device objectId = " . $userDeviceId, 1);
	}
	// One Signal Id is already present
	else if(!empty($userDevice->get('oneSignalId'))) {

		return "";
	}
	///////////////////////////////////////////////////////////////////////////////////////

	// Find the latest session device
	$sessionDevice = parseExecuteQuery(array("userDevice" => $userDevice), "SessionDevices", "", "createdAt", [], 1, false);

	if(count_like_php5($sessionDevice) == 0) {

		json_error("AS_1061", "", "OneSignal device creation failed, no session device row found device objectId = " . $userDeviceId . " - message will not be reprocessed.", 2, 1);

		return "";
	}

	$parameters = [
		'identifier' => ($userDevice->get('isIos') == true ? stripNonAlphaNumericSpaces($userDevice->get('pushNotificationId')) : $userDevice->get('pushNotificationId')), 
		'language' => "en", 
		'game_version' => $userDevice->get('appVersion'), 
		'device_os' => $userDevice->get('deviceOS'), 
		'device_type' => $userDevice->get('isIos') == true ? 0 : 1, 
		'device_model' => $userDevice->get('deviceModel'), 
		'created_at' => $userDevice->get('user')->getCreatedAt()->getTimestamp(),
		'notification_types' => 1,
		'country' => $sessionDevice->get('country'),
		'timezone' => $sessionDevice->get('timezoneFromUTCInSeconds'),
		'tags' => array("type_of_user" => $userDevice->get('user')->get('hasConsumerAccess') == true ? "c" : "d", "phone_type" => $userDevice->get('deviceType'), "db_unique_id" => $userDevice->get('uniqueId'))
	];

	// Override Test Type (as 1=Development, 2=Adhoc, skip parameter for production, i.e. no value is set for variable)
	if(!empty($GLOBALS['env_OneSignalTestType'])) {

		$parameters['test_type'] = $GLOBALS['env_OneSignalTestType'];
	}

	// json_error("AS_WWW", "", "Onesignal attempt - " . $userDeviceId . json_encode($parameters), 1, 1);

	///////////////////////////////////////////////////////////////////////////////////////
	// Create Onesignal Id
	///////////////////////////////////////////////////////////////////////////////////////


	try {
				
		list($success, $oneSignalId, $error_array) = onesignalCreateDevice($parameters);
	}
	catch (Exception $ex) {

	    // clear cache for sending
        delCacheByKey(getCacheOneSignalQueueMessageSend($userDevice->getObjectId()));
		return json_error_return_array("AS_1026", "", "Exception: " . $ex->getMessage(), 1);
	}

	// json_error("AS_WWW", "", "Onesignal id - " . $userDeviceId . ' - ' . $oneSignalId, 1, 1);

	// Id received, save it
	if($success) {

		$userDevice->set("oneSignalId", $oneSignalId);
		$userDevice->save();

		// Clear User's session and user device cache
		$keyList = getCacheKeyList("PQ__*" . getCacheKeyForUserDevice($userDevice->get("user")->getObjectId(), "") . "*");
		foreach($keyList as $keyName) {

			delCacheByKey($keyName);
		}

		// delete cache for "one signal processing message already send"
        $cacheKey = getCacheKeyForCacheOneSignalQueueMessageSend($userDeviceId);
        delCacheByKey($cacheKey);
	}
	// Gracefully exited but received error
	else {

		return json_error_return_array("AS_1027", "", "OneSignal error response = " . $error_array, 1);
	}
	///////////////////////////////////////////////////////////////////////////////////////

	return "";
}

?>
