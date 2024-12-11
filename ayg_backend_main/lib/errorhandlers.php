<?php

// Set error handler
set_json_error_handling();

// Check if API is down
if(($env_APIMaintenanceFlag == 1
	|| !empty(getCacheAPI9001Status()))
	&& !defined("ADMIN_JOB")) {

	// If /checkin is being called
	if(preg_match("/^\/user\/checkin/si", $_SERVER['REQUEST_URI'])) {

		$responseArray = getCurrentUserInfo();
		$responseArray["coreInstructionCode"] = "AS_9001";
		$responseArray["coreInstructionText"] = $env_APIMaintenanceMessage;

		json_echo(
			json_encode($responseArray)
		);
	}

	// else standard response
	json_error("AS_9001", $env_APIMaintenanceMessage, "API maintenance is on.", 3);
}

function set_json_error_handling() {
	
	error_reporting(E_ALL);
	
	// Set Error Handler when its Not Localhost
	if(strcasecmp($GLOBALS['env_InHerokuRun'], "Y")==0) {
		
		//error_reporting(E_ERROR);
		set_error_handler("exit_json_error");
		register_shutdown_function('shutdown_handler');
		
		// If being called from Slim-enabled (API) files
		if(isset($GLOBALS['app'])) {
			
			$GLOBALS['app']->error('exit_json_error');
		}
	}
}

// Also see a setting in lib/slim.php

?>
