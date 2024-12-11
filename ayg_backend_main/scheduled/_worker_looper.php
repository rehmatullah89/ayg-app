<?php

ini_set("memory_limit","384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'scheduled/_process_orders.php';
require_once $dirpath . 'scheduled/_confirm_print_orders.php';

require_once $dirpath . 'scheduled/_confirm_pos_orders.php';
require_once $dirpath . 'scheduled/_confirm_tablet_orders.php';
require_once $dirpath . 'scheduled/_send_order_receipt.php';
require_once $dirpath . 'scheduled/_process_delivery.php';
require_once $dirpath . 'scheduled/_send_email.php';
require_once $dirpath . 'scheduled/_create_onesignal_device.php';
require_once $dirpath . 'scheduled/_queue_functions.php';
require_once $dirpath . 'scheduled/_ping_retailers.php';
require_once $dirpath . 'scheduled/_ping_slack_delivery.php';
require_once $dirpath . 'scheduled/_process_delivery_slack.php';
require_once $dirpath . 'scheduled/_worker_functions.php';
require_once $dirpath . 'scheduled/_send_user_communication.php';
require_once $dirpath . 'scheduled/_process_flight.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

///////////////////////////////////////////////////////////////////////////////
// Check if 9001 request came in
///////////////////////////////////////////////////////////////////////////////
/*
if(!empty(getCacheAPI9001Status())) {

	$nullValue = '';
	shutdownProcess($nullValue);
}
*/

///////////////////////////////////////////////////////////////////////////////
// Temporary Worker job tasks
$lastRunTimes = array("ping_retailers" => 0, "ping_slack_delivery" => 0, "check_slack_delivery_delays" => 0, "dead_letter_queue" => 0, "flight_api_calls_daily_count" => 0, "delayed_order_confirmation" => 0, "ping_retailers_w_battery_check" => 0, "delayed_refund_check" => 0);
///////////////////////////////////////////////////////////////////////////////

// Connect to Deadletter Queue
try {

	$workerDeadLetterQueue = new WorkerQueue($GLOBALS['env_workerQueueConsumerDeadLetterName'], $GLOBALS['env_workerQueueConsumerDeadLetterLPInSecs'], 5);
}
catch (Exception $ex) {

	json_error("AS_1038", "", "Queue connection failed - " . $ex->getMessage(), 1);
}

///////////////////////////////////////////////////////////////////////////////

while(1>0) {

	///////////////////////////////////////////////////////////////////////////////
	// Check if 9001 request came in
	///////////////////////////////////////////////////////////////////////////////
	/*
	if(!empty(getCacheAPI9001Status())) {

		shutdownProcess($workerDeadLetterQueue);
	}
	*/

    $mainApi9001Status = getCacheAPI9001Status();
    $worker9001Status = getCacheAPI9001WorkerLooper();
    if(!empty($mainApi9001Status)) {
        //shutdownProcess($workerQueue);
        if (empty($worker9001Status)){
            setCacheAPI9001WorkerLooper();
        }
        echo '9001 found, waiting...'.PHP_EOL;
        sleep(10);
        continue;
    }

    if (empty($mainApi9001Status) && !empty($worker9001Status)){
        delCacheAPI9001WorkerLooper();
        echo 'api 9001 empty, cleaned worker 9001 as well'.PHP_EOL;
    }

	///////////////////////////////////////////////////////////////////////////////
	// Pending Delayed Refunds
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["delayed_refund_check"], (intval(60*60/60)))) {
		
		try {
			
			execute_delayed_refund_check();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Delayed Refund check failed - ", $error_array["error_severity"], 1);
		}

		$lastRunTimes["delayed_refund_check"] = time();
	}

	///////////////////////////////////////////////////////////////////////////////
	// Ping Retailers

	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["ping_retailers"], (intval($GLOBALS['env_PingRetailerIntervalInSecs']/60)))) {

		try {
			echo 'execute_ping_retailers'.PHP_EOL;
			execute_ping_retailers();
		}
		catch (Exception $ex) {

			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Ping Retailer failed - ", $error_array["error_severity"], 1);
		}

		$lastRunTimes["ping_retailers"] = time();
	}

	///////////////////////////////////////////////////////////////////////////////
	// Ping Slack Delivery
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["ping_slack_delivery"], (intval($GLOBALS['env_PingSlackDeliveryIntervalInSecs']/60)))) {


		try {
			
			execute_ping_slack_delivery();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Ping Slack Delivery failed - ", $error_array["error_severity"], 1);
		}

		$lastRunTimes["ping_slack_delivery"] = time();
	}

	///////////////////////////////////////////////////////////////////////////////
	// Check Slack Delivery Delivery delays
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["check_slack_delivery_delays"], ($GLOBALS['env_OrderSlackDeliveryDelaysCheckIntervalInSecs']/60))) {
		
		try {
			execute_check_slack_delivery_delays();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Check Slack Delivery Delays failed - ", $error_array["error_severity"], 1);
		}

		$lastRunTimes["check_slack_delivery_delays"] = time();
	}

	///////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////////
	// Daily Flight API Calls
	///////////////////////////////////////////////////////////////////////////////
	// Execute every hour
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["flight_api_calls_daily_count"], 60)) {
		
		try {
			
			execute_flight_api_calls_daily_count();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Flight API Calls Daily count failed - ", $error_array["error_severity"], 1);
		}

		$lastRunTimes["flight_api_calls_daily_count"] = time();
	}


	///////////////////////////////////////////////////////////////////////////////
	// Dead Letter queue notifications
	///////////////////////////////////////////////////////////////////////////////
	// Execute every 5 mins
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["dead_letter_queue"], 5)) {
		
		try {
			
			execute_dead_letter_queue();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Dead Letter Queue check failed - ", $error_array["error_severity"], 1);
		}
		
		$lastRunTimes["dead_letter_queue"] = time();
	}
	///////////////////////////////////////////////////////////////////////////////


	///////////////////////////////////////////////////////////////////////////////
	// Delayed Order Retailer Confirmation (Tablet, or Print)
	///////////////////////////////////////////////////////////////////////////////
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["delayed_order_confirmation"], (intval($GLOBALS['env_OrderRetailerDelaysCheckIntervalInSecs']/60)))) {
		
		try {

			execute_delayed_order_confirmation();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Delayed Order check failed - ", $error_array["error_severity"], 1);
		}
		
		$lastRunTimes["delayed_order_confirmation"] = time();
	}
	///////////////////////////////////////////////////////////////////////////////

	///////////////////////////////////////////////////////////////////////////////
	// Ping Retailers with Tablet Battery check
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["ping_retailers_w_battery_check"], (intval($GLOBALS['env_PingRetailerWBatteryCheckIntervalInSecs']/60)))) {
		
		try {
			
			execute_ping_retailers(true);
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Ping Retailer with Battery check failed - ", $error_array["error_severity"], 1);
		}

		$lastRunTimes["ping_retailers_w_battery_check"] = time();
	}

	///////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////
	///////////////////////////////////////////////////////////////////////////////
}

function shutdownProcess(&$workerDeadLetterQueue) {

	error_log("Shutting down...");

	// Disconnect
	if($workerDeadLetterQueue != '') {

		$workerDeadLetterQueue->disconnect();
	}

	workerQueueConnectionsDisconnect();

	unset($workerQueue);

	setCacheAPI9001WorkerLooper();

	sleep(1);

	while(1>0) {

		// Wait to be shutdown
	}

	// Graceful exit
	// posix_kill(posix_getpid(), 15);

	// exit(0);	
}



function handle9001(){

}

?>
