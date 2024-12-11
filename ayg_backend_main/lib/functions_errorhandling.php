<?php

$backtracePrint = "";

function json_error_return_array($error_code, $error_message_user="", $error_message_log="", $error_severity = 3, $error_noexit = 0) {
	
	$isReadyForUse = false;

	if(empty($error_code)) {

		$isReadyForUse = true;
	}

	return [
			"isReadyForUse" => $isReadyForUse,
			"error_code" => $error_code, 
			"error_message_user" => $error_message_user, 
			"error_message_log" => $error_message_log,
			"error_severity" => $error_severity,
			"error_noexit" => $error_noexit
		];	
}

function json_error($error_code, $user_error_description, $error_descriptive="", $error_type = 3, $error_noexit = 0, $backtrace="") {
	
	global $config_orderProcessingFlag;
	
	if(empty($user_error_description)) {
		
		$user_error_description = "Something went wrong. We are working on fixing the problem.";
	}
	
	if($config_orderProcessingFlag == 1) {
		
		order_processing_error("", $error_code, $user_error_description, $error_descriptive, $error_type, $error_noexit, $backtrace);
	}
	else {
		
		log_json_error($error_code, $user_error_description, $error_descriptive, $error_type, $error_noexit, $backtrace);
	}
}

function log_json_error($error_code, $user_error_description, $error_descriptive="", $error_type = 3, $error_noexit = 0, $backtrace="") {
	
	global $app, $devErrorFilePath, $turnOnHerokuErrorsOnLocal, $env_InHerokuRun, $env_EnvironmentDisplayCode;

	$REQUEST_URI = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : "";
	$REQUEST_TIME = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : "";
	$EXITED = $error_noexit == 0 ? "Y" : "N";
	
	$error_string = ":::: %%NOTIFY_TEXT%% :: (" . $env_EnvironmentDisplayCode . ") :: " . $error_code . " # "
					 . "Request: " . $REQUEST_URI . " # "
					 . "Desc: $error_descriptive " . " # "
					 . "Backtrace: $backtrace" . " # "
					 . "Request Time: " . $REQUEST_TIME . " (" . date("Y-m-d H:i:s", $REQUEST_TIME) . ")" . " # "
					 . "REMOTE_ADDR: " . getenv('HTTP_X_FORWARDED_FOR') . ' ~ ' . getenv('REMOTE_ADDR') . " # "
					 . "PHP " . PHP_VERSION . " (" . PHP_OS . ")" . " # "
					 . "EXITED: " . $EXITED . " # ";

	if(strcasecmp($EXITED, "Y")==0) {

		 $error_string .= "User shown error: " . $user_error_description . " #\n";
	}
	else {

		 $error_string .= " #\n";
	}
	
	// Critical Errors
	if($error_type == 1.1 || $error_type == 1) {
		
		$error_string = str_replace("%%NOTIFY_TEXT%%", "Sherpa-Error-Critical", $error_string);
	}
	// 
	else if($error_type == 1.2) {
		
		$error_string = str_replace("%%NOTIFY_TEXT%%", "Sherpa-Error-Fatal", $error_string);
	}
	// Warning
	else if($error_type == 2) {
		
		$error_string = str_replace("%%NOTIFY_TEXT%%", "Sherpa-Warning", $error_string);
	}
	// Standard Response, default is set to 3
	else {
		
		$error_string = str_replace("%%NOTIFY_TEXT%%", "Sherpa-Info-Notification", $error_string);
	}
	
	// Log to Dev location is set
	if(isset($devErrorFilePath) && !empty($devErrorFilePath)) {
		
		error_log(microtime(true) . " - " . $error_string, 3, $devErrorFilePath);
	}
	
	// Write to Slim
	if(isset($app)) {
		
		// Critical Errors
		if($error_type == 1.1 || $error_type == 1) {
			
			$app->log->critical($error_string);
		}
		// 
		else if($error_type == 1.2) {
			
			$app->log->error($error_string);
		}
		// Warning
		else if($error_type == 2) {
			
			$app->log->warning($error_string);
		}
		// Standard Response, default is set to 3
		else {
			
			$app->log->debug($error_string);
		}
	}
	
	// Else use PHP's logger
	else {
		
		error_log($error_string, 0);
	}
	
	// If noexit is not set (aka left to be 0) respond with JSON and EXIT; mark this as 1 to NOT exit
	if($error_noexit == 0) {
		
		// If Flag is turned on, then display the full log message
		// It is deactivated if env_InHerokuRun = Y
		if($turnOnHerokuErrorsOnLocal == 1 && strcasecmp($env_InHerokuRun, "Y")!=0) {
			
			$user_error_description = $error_string;
		}
		
		json_echo(json_encode(array("error_code" => $error_code, "error_description" => $user_error_description)));
	}	
}

function json_echo($jsonEncodedString) {
    logResponse($jsonEncodedString);

	// Disconnect from queue
	if(count_like_php5($GLOBALS['workerQueueConnections']) > 0) {

		workerQueueConnectionsDisconnect();
	}

	// Disconnect from deadletter queue
	if(!empty($GLOBALS['workerDeadLetterQueue'])) {

		$GLOBALS['workerDeadLetterQueue']->disconnect();
	}


	// for tablet app errors must have http code 400
	// encode $jsonEncodedString check if this is error,
    $object=json_decode($jsonEncodedString);
    // check if it is error
    if (isset($object->error_code) && isset($object->error_description)){
        if (isset($_SERVER['REQUEST_URI']) 
        	&& strcmp(substr($_SERVER['REQUEST_URI'],0,8),'/tablet/')===0){
            // if this is tablet, use http status 400
            header('HTTP/1.0 400 Forbidden');
        }
    }


    header('Content-Type: application/json');

	echo($jsonEncodedString);

	exit;
}

function json_echo_compressed($jsonEncodedString) {

	// Disconnect from queue
	if(count_like_php5($GLOBALS['workerQueueConnections']) > 0) {

		workerQueueConnectionsDisconnect();
	}

	// Disconnect from deadletter queue
	if(!empty($GLOBALS['workerDeadLetterQueue'])) {

		$GLOBALS['workerDeadLetterQueue']->disconnect();
	}

	ob_start('ob_gzhandler');

	// header('Content-Encoding: gzip');
	// header('Content-Type: text/plain');

	echo($jsonEncodedString);

	exit;
}

function gracefulExit() {

	// Disconnect from queue
	if(count_like_php5($GLOBALS['workerQueueConnections']) > 0) {

		workerQueueConnectionsDisconnect();
	}

	// Disconnect from deadletter queue
	if(!empty($GLOBALS['workerDeadLetterQueue'])) {

		$GLOBALS['workerDeadLetterQueue']->disconnect();
	}

	exit;	
}

function exit_json_error_scheduled($errno, $errstr="", $errfile, $errline) {
	// If being run from inside the queue processing
	if(defined("QUEUE")) {

		noexit_json_error($errno, $errstr, $errfile, $errline);
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
	}
	else {

		exit_json_error($errno, $errstr, $errfile, $errline);
	}
}

function exit_json_error($errno, $errstr="", $errfile="", $errline="") {
	
	default_json_error($errno, $errstr, $errfile, $errline, 0);
}

function noexit_json_error($errno, $errstr="", $errfile="", $errline="") {
	
	// Set no_exit_code = 1, meaning to not exit
	default_json_error($errno, $errstr, $errfile, $errline, 1);
}
	
function default_json_error($errno, $errstr, $errfile, $errline, $no_exit_code) {
	
	$backtrace = getBackTrace();

	// For desctrutor to run
   	if(defined("WORKER")
   		&& isset($GLOBALS['workerQueue'])) {

   		unset($GLOBALS['workerQueue']);
	}

    switch ($errno) {

		// Non-exit error
		case E_WARNING:
		case E_NOTICE:
		case E_USER_WARNING:
		case E_USER_NOTICE:
			json_error('AS_001', '', "Error No: $errno # Error String: $errstr # Error File: $errfile # Error Line: $errline", 2, 1, $backtrace);
			break;

		default:
			json_error('AS_000', '', "Error No: $errno # Error String: $errstr # Error File: $errfile # Error Line: $errline", 1, $no_exit_code, $backtrace);
			break;
	}
}

function shutdown_handler() {

	$backtrace = getBackTrace();
	$lasterror = error_get_last();

	// For desctrutor to run
   	if(defined("WORKER")
   		&& isset($GLOBALS['workerQueue'])) {

   		unset($GLOBALS['workerQueue']);
	}

	switch ($lasterror['type']) {
		
		case E_ERROR:
		case E_CORE_ERROR:
		case E_COMPILE_ERROR:
		case E_USER_ERROR:
		case E_RECOVERABLE_ERROR:
		case E_CORE_WARNING:
		case E_COMPILE_WARNING:
		case E_PARSE:
			json_error('AS_1000', '', "Error No: " . $lasterror['type'] . " # Error String: " . $lasterror['message'] . " # Error File: " . $lasterror['file'] . " # Error Line: " . $lasterror['line'], 1, 0, $backtrace);
	}
}


function getBackTrace() {

	global $backtracePrint;
	
	// ob_start();
	// DEBUG_BACKTRACE_IGNORE_ARGS
	// debug_print_backtrace();
	// $backtrace = ob_get_contents();	
	// ob_end_clean();
	if(empty($backtracePrint)) {
		
		$backtrace = debug_backtrace();
	
		$backtracePrint = "";
		for($i=0;$i<count_like_php5($backtrace);$i++) {
			
			$file = isset($backtrace[$i]['file']) ? $backtrace[$i]['file'] : "";
			$function = isset($backtrace[$i]['function']) ? $backtrace[$i]['function'] : "";
			$line = isset($backtrace[$i]['line']) ? $backtrace[$i]['line'] : "";
			
			$backtracePrint .= ($i). "--" . $file .":" .$function ."(" .$line.") :: ";
		}
	}

 	return $backtracePrint;
}

function shutdown_handler_menu_loader() {

	// $log = ob_get_contents();ob_end_clean();
	$log = "";
    $errfile = "unknown file";
    $errstr  = "shutdown";
    $errno   = E_CORE_ERROR;
    $errline = 0;

    $error = error_get_last();

   	$error_log = json_encode($error);

    if(defined("WORKER_MENU_LOADER")) {

		logMenuLoaderAction(($GLOBALS['menu_loader_S3_log_backlog'] . "\r\n" . $log . "\r\n" . $error_log), getS3KeyPath_RetailerMenuLoaderLog());
	}

	// JMD
	json_error("AS_10001", "Menu Loader Error" . "\r\n" . $error_log . $log, $error_log . $log, 1);
}

function warning_handler_menu_loader($errno, $errstr, $errfile, $errline, $errcontext) { 

	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}
