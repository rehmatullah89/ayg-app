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

// History build
// build_coupon_usage_cache(0);exit;

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
$lastRunTimes = array('build_fullfillment_times_cache' => 0, 'build_curated_lists_cache' => 0, 'rebuild_menu_cache' => 0, 'build_coupon_usage_cache' => 0);
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
    $worker9001Status = getCacheAPI9001WorkerLooper2();
    if(!empty($mainApi9001Status)) {
        //shutdownProcess($workerQueue);
        if (empty($worker9001Status)){
            setCacheAPI9001WorkerLooper2();
        }
        echo '9001 found, waiting...'.PHP_EOL;
        sleep(10);
        continue;
    }

    if (empty($mainApi9001Status) && !empty($worker9001Status)){
        delCacheAPI9001WorkerLooper2();
        echo 'api 9001 empty, cleaned worker 9001 as well'.PHP_EOL;
    }

	///////////////////////////////////////////////////////////////////////////////
	// Build fullfillment cache
	// Run within twice the time of env_PingRetailerIntervalInSecs
	// Cache is created thrice the time of env_PingRetailerIntervalInSecs
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["build_fullfillment_times_cache"], (intval($GLOBALS['env_PingRetailerIntervalInSecs']/60)*2))) {
		
		json_error("AS_INFO", "", "Starting Fullfillment build", 3, 1);

		try {
		//	build_fullfillment_times_cache();
		}
		catch (Exception $ex) {
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Ping Retailer with Battery check failed - ", $error_array["error_severity"], 1);
		}

		json_error("AS_INFO", "", "Completed Fullfillment build", 3, 1);

		$lastRunTimes["build_fullfillment_times_cache"] = time();
	}

	///////////////////////////////////////////////////////////////////////////////
	// Build curated lists
	// Run within twice the time of env_PingRetailerIntervalInSecs
	// Cache is created thrice the time of env_PingRetailerIntervalInSecs
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["build_curated_lists_cache"], (intval($GLOBALS['env_PingRetailerIntervalInSecs']/60)*2))) {
		
		json_error("AS_INFO", "", "Starting Curated lists build", 3, 1);

		try {
			
			build_curated_lists_cache();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			if(isset($error_array["error_code"])) {

			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Ping Retailer with Battery check failed - ", $error_array["error_severity"], 1);
			}
			else {
				
				json_error("AS_DEFAULT", "", "Unknown error, Ping Retailer with Battery check failed - " . $ex->getMessage(), 1, 1);
			}
		}

		json_error("AS_INFO", "", "Completed curated lists build", 3, 1);

		$lastRunTimes["build_curated_lists_cache"] = time();
	}

    ///////////////////////////////////////////////////////////////////////////////
    // TAG
    // Run every 60 mins
    // Rebuild Coupon usage by code and users
    if(hasLooperLastRunTimeLimitPassed($lastRunTimes["build_coupon_usage_cache"], (60))) {

        json_error("AS_INFO", "", "Starting Coupon Usage cache build", 3, 1);

        try {

            $todayTimestamp = strtotime("today 12:00:01 am EST");

            merge_coupon_usage_cache($todayTimestamp);
            build_coupon_usage_cache($todayTimestamp);
        }
        catch (Exception $ex) {

            $error_array = json_decode($ex->getMessage(), true);
            json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Coupon usage cache build failed - ", $error_array["error_severity"], 1);
        }

        json_error("AS_INFO", "", "Completed Coupon Usage cache build", 3, 1);

        $lastRunTimes["build_coupon_usage_cache"] = time();
    }


	/////////////////////////////////////////////////////////////////////////////
	// Rebuild menu cache
	if(hasLooperLastRunTimeLimitPassed($lastRunTimes["rebuild_menu_cache"], (intval($GLOBALS['env_PingRetailerIntervalInSecs']/60)*2))) {
		
		json_error("AS_INFO", "", "Starting menu cache rebuild", 3, 1);

		try {
			
			rebuild_menu_cache();
		}
		catch (Exception $ex) {
			
			$error_array = json_decode($ex->getMessage(), true);
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Ping Retailer with Battery check failed - ", $error_array["error_severity"], 1);
		}

		json_error("AS_INFO", "", "Completed menu cache build", 3, 1);

		$lastRunTimes["rebuild_menu_cache"] = time();
	}
	

}

function shutdownProcess(&$workerDeadLetterQueue) {

	error_log("Shutting down...");

	// Disconnect
	if($workerDeadLetterQueue != '') {

		$workerDeadLetterQueue->disconnect();
	}

	workerQueueConnectionsDisconnect();

	unset($workerQueue);

	setCacheAPI9001WorkerLooper2();

	sleep(1);

	while(1>0) {

		// Wait to be shutdown
	}

	// Graceful exit
	// posix_kill(posix_getpid(), 15);

	// exit(0);	
}

?>
