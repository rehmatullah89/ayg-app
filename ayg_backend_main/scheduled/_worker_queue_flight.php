<?php

ini_set("memory_limit","384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);
define("QUEUE_WORKER", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';
require_once $dirpath . 'lib/initiate.mysql_logs.php';

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
require_once $dirpath . 'lib/functions_order_ops.php';

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

$runningTime = 0;
unset($workerQueue);
while(1>0) {

    var_dump('starting (begining of while 1)');

    $mainApi9001Status = getCacheAPI9001Status();
    $worker9001Status = getCacheAPI9001WorkerQueueFlight();


    if(!empty($mainApi9001Status)) {
        if (empty($worker9001Status)){
            setCacheAPI9001WorkerQueueFlight();
        }
        echo '9001 found, waiting...'.PHP_EOL;
        sleep(10);
        continue;
    }

    if (empty($mainApi9001Status) && !empty($worker9001Status)){
        delCacheAPI9001WorkerQueueFlight();
        echo 'api 9001 empty, cleaned worker 9001 as well'.PHP_EOL;
    }



	if(!isset($workerQueue)) {
		// Connect to Queue
		try {
			$workerQueue = new WorkerQueue($GLOBALS['env_workerQueueFlightConsumerName'], $GLOBALS['env_workerQueueConsumerLPInSecs']);
			$runningTime = time();
		}
		catch (Exception $ex) {


			json_error("AS_1038", "", "Queue connection failed - " . $ex->getMessage(), 1);
		}
	}

	///////////////////////////////////////////////////////////////////////////////
	// Check if 9001 request came in
	///////////////////////////////////////////////////////////////////////////////
	/*
	if(!empty(getCacheAPI9001Status())) {
		shutdownProcess($workerQueue);
	}
	*/


	///////////////////////////////////////////////////////////////////////////////
    // Check any unacknowledged messages (only applicable for RabbitMQ)
    // Failsafe no message was left pending state from a previous failed connection (non-graceful shutdown)
    // Only useful until there is one worker, else it will start generating false postives
	///////////////////////////////////////////////////////////////////////////////
	try {
		$countOfUnacdMessage = $workerQueue->getQueueUackdMessageCount();
		if($countOfUnacdMessage > 0) {
			json_error("AS_3017", "", "Unacknowledged messages found (" . $countOfUnacdMessage . ")", 1, 1);
		}
	}
	catch (Exception $ex) {
		$error_array = json_decode($ex->getMessage(), true);
		if(isset($error_array["error_code"])) {
			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Queue Message pull failed! - ", 1, 1);
		}
		else {
			json_error("AS_DEFAULT", "", "Unknown queue message, skipping." . $ex->getMessage(), 1, 1);
		}
	}


	///////////////////////////////////////////////////////////////////////////////
    // Receive messages from the Queue
	///////////////////////////////////////////////////////////////////////////////
	try {

		$result = $workerQueue->receiveMessage(1);
	}
	catch (Exception $ex) {
		$error_array = json_decode($ex->getMessage(), true);

		$result = null;

		// Conditional Exiting
		if(isset($error_array["error_code"])) {

			json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Queue Message pull failed! - ", 1, $error_array["error_noexit"]);
		}
		else {
			
			json_error("AS_DEFAULT", "", "Unknown queue message, skipping." . $ex->getMessage(), 1, 1);
		}
	}

    $worker9001Status = getCacheAPI9001WorkerQueueFlight();
    if (!empty($worker9001Status)){
		echo 'found 9001, disconnecting'.PHP_EOL;
        $workerQueue->disconnect();
        unset($workerQueue);
        continue;
        }


  	if($result == null) {

        // No message to process
  		// sleep(10);
  		// break;
        continue;
    }

	foreach($result as $processMessage) {

		// Processed Flag reset
		$processed = 0;

		// Process Messages here
		$message = $workerQueue->getMessageBody($processMessage);
		// print_r($processMessage->body);exit;


		// If message of shutdown request came in
		// Cache must also be set, else the message will get processed and deleted
		if(strcasecmp($message["action"], "shutdown_request_9001")==0) {
            echo 'shut down process triggered'.PHP_EOL;
            $workerQueue->deleteMessage($workerQueue->getMessageId($processMessage), $processMessage);
            $workerQueue->disconnect();
            unset($workerQueue);
            break 1;
		}

		// Identify function name to call for processing
		$queueFunction = 'queue__' . $message["action"];
		$queueFunctionPre = 'queue_pre__' . $message["action"];
		$queueFunctionFail = 'queue_fail__' . $message["action"];


		// Process pre-processtimestamp check function, if it exists
		if(function_exists($queueFunctionPre)) {

			try {

				$queueFunctionResponse = $queueFunctionPre($message, $workerQueue);

				// If an error was returned
				if(is_array($queueFunctionResponse)) {

					throw new Exception (json_encode(json_error_return_array($queueFunctionResponse["error_code"], "", $queueFunctionResponse["error_message_log"] . " Queue Message - " . json_encode($message), 2)));
				}
			}
			catch (Exception $ex) {

				$processed = 0;

				$error_array = json_decode($ex->getMessage(), true);

				// If it is not a manually thrown error, handle like an unknown error
				if(is_null($error_array)) {

					json_error("AS_1050", "", "Unknown Worker prefunction queue error - " . $ex->getMessage() . " Message: " . json_encode($message), 1, 1);	
				}
				// Show manually thrown error
				else {

					json_error($queueFunctionResponse["error_code"], "", $queueFunctionResponse["error_message_log"], $queueFunctionResponse["error_severity"], 1);	
				}

				// Don't do further processing
				continue;
			}
		}

		// If the message should NOT be processed right now
		// Then let it be deleted and put another version of it back on the queue
		if(isset($message["processAfter"]["timestamp"])
			&& !$workerQueue->isMessageReadyToBeProcessed(floatval($message["processAfter"]["timestamp"]))) {

			// Mark current message processed, so it can be deleted
			$processed = 1;

			try {

				$workerQueue->putMessageBackonQueueWithDelay($message, $workerQueue->getWaitTimeForDelay(floatval($message["processAfter"]["timestamp"])));
			} catch (Exception $ex) {

				$processed = 0;
				$error_array = json_decode($ex->getMessage(), true);

				json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Queue Message - " . json_encode($message), 1, 1);

				// Don't do further processing
				continue;
			}
		}
		else {

			// Process message
			if(function_exists($queueFunction)) {

				try {

					$processed = 1;
					$queueFunctionResponse = $queueFunction($message, $workerQueue);


					// If an error was returned
					if(is_array($queueFunctionResponse)) {

						throw new Exception (json_encode(json_error_return_array($queueFunctionResponse["error_code"], "", $queueFunctionResponse["error_message_log"] . " Queue Message - " . json_encode($message), 2)));
					}
				}
				catch (Exception $ex) {

					$processed = 0;

					$error_array = json_decode($ex->getMessage(), true);

					// If it is not a manually thrown error, handle like an unknown error
					if(is_null($error_array)) {

						json_error("AS_1051", "", "Unknown Worker queue function error - " . $ex->getMessage() . " Message: " . json_encode($message), 1, 1);
					}
					// Show manually thrown error
					else {

						json_error($queueFunctionResponse["error_code"], "", $queueFunctionResponse["error_message_log"], $queueFunctionResponse["error_severity"], 1);	
					}
				}

				// If message was not processed, check if an failure method needs to be run
				if($processed == 0) {

					// Process post queue fail function, if it exists
					if(function_exists($queueFunctionFail)) {

						try {

							$queueFunctionResponse = $queueFunctionFail($message, $workerQueue);

							// If an error was returned
							if(is_array($queueFunctionResponse)) {

								throw new Exception (json_encode(json_error_return_array($queueFunctionResponse["error_code"], "", $queueFunctionResponse["error_message_log"] . " Queue Message - " . json_encode($message), 2)));
							}
						}
						catch (Exception $ex) {

							$error_array = json_decode($ex->getMessage(), true);

							// If it is not a manually thrown error, handle like an unknown error
							if(is_null($error_array)) {

								json_error("AS_1052", "", "Unknown Worker post function queue error - " . $ex->getMessage() . " Message: " . json_encode($message), 1, 1);	
							}
							// Show manually thrown error
							else {

								json_error($queueFunctionResponse["error_code"], "", $queueFunctionResponse["error_message_log"], $queueFunctionResponse["error_severity"], 1);	
							}
						}
					}
				}
			}
			else {

				json_error("AS_1080", "", "No method found for: " . $queueFunction . " to process Queue Message - " . json_encode($message), 1, 1);
			}
		}


		// Delete Message if processed
		if($processed == 1) {

			try {

				$workerQueue->deleteMessage($workerQueue->getMessageId($processMessage), $processMessage);
			}
			catch (Exception $ex) {

				$error_array = json_decode($ex->getMessage(), true);
				json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Queue Message - " . json_encode($message), 1, 1);
			}
		}
		// If all messages require acknowledgement
		// Then we must delete the original message and put it back on the queue for reprocessing
		// until deadletter process max is hit
		else if($workerQueue->doMessagesRequireAck()) {

			try {

				// Reprocess after 30 seconds
				$workerQueue->putMessageBackonQueueWithDelay($message, $workerQueue->reprocessDelay());

				// Delete current message
				$workerQueue->deleteMessage($workerQueue->getMessageId($processMessage), $processMessage, true);
			}
			catch (Exception $ex) {

				$error_array = json_decode($ex->getMessage(), true);
				json_error($error_array["error_code"], "", $error_array["error_message_log"] . " Queue Message - " . json_encode($message), 1, 1);
			}
		}

		// exit;
	}

	// if force

	/*
	// Reset object every hour
	if(time()-$runningTime > 60*60) {

		workerQueueConnectionsDisconnect();

		if(isset($workerQueue))
			unset($workerQueue);

		if(isset($result))
			unset($result);

		if(isset($message))
			unset($message);

		if(isset($processMessage))
			unset($processMessage);

		break;			
	}
	*/
}

function shutdownProcess(&$workerQueue) {

	error_log("Shutting down...");

	// Disconnect
	if($workerQueue != '') {

		$workerQueue->disconnect();
	}

	workerQueueConnectionsDisconnect();

	unset($workerQueue);

	setCacheAPI9001WorkerQueue();

	sleep(1);

	while(1>0) {

		// Wait to be shutdown
	}

	// Graceful exit
	// posix_kill(posix_getpid(), 15);

	// exit(0);	
}

?>
