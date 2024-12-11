<?php

define("WORKER", true);

require 'dirpath.php';
require $dirpath . 'scheduled/_process_orders.php';
require $dirpath . 'scheduled/_confirm_print_orders.php';
require $dirpath . 'scheduled/_ping_retailers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;

$lastRunTimes = array("ping_retailers" => 0);

define("JSON_ERROR_NO_EXIT", "1");

while(1>0) {

	error_log("::ORDER_WORKER:: Awake...");
	
	// Execute every 2 mins
	if(hasLastRunTimeLimitPassed($lastRunTimes["ping_retailers"], 2)) {
		
		// error_log("::ORDER_WORKER:: Executing Ping Retailers...");
		execute_ping_retailers();
		// error_log("::ORDER_WORKER:: Done Ping Retailers...");
		$lastRunTimes["ping_retailers"] = time();
	}

	// REMOVE BEFORE PUBLISHING
	break;
}

function hasLastRunTimeLimitPassed($lastRunTimestamp, $gapNeededInMins) {

	$timeToCompare = time()-($gapNeededInMins * 60);
	
	if($lastRunTimestamp < $timeToCompare) {
		
		return 1;
	}
	
	return 0;
}

?>