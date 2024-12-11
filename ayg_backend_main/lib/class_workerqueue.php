<?php

// Use RabbitMQ
$GLOBALS['env_workerQueueConsumerName'] = $GLOBALS['env_RabbitMQConsumerPrimaryQueueName'];
$GLOBALS['env_workerQueueMidPriorityAsynchConsumerName'] = $GLOBALS['env_RabbitMQConsumerPrimaryMidPriorityAsynchQueueName'];

// new queues 06/02/2021
$GLOBALS['env_workerQueueSlackNotificationConsumerName'] = $GLOBALS['env_RabbitMQConsumerSlackNotificationQueueName'];
$GLOBALS['env_workerQueuePushAndSmsConsumerName'] = $GLOBALS['env_RabbitMQConsumerPushAndSmsQueueName'];
$GLOBALS['env_workerQueueEmailConsumerName'] = $GLOBALS['env_RabbitMQConsumerEmailQueueName'];
$GLOBALS['env_workerQueueFlightConsumerName'] = $GLOBALS['env_RabbitMQConsumerFlightQueueName'];
// end of new queues

$GLOBALS['env_workerQueueConsumerDeadLetterName'] = $GLOBALS['env_RabbitMQConsumerPrimaryQueueName'] . '-deadletter';
$GLOBALS['env_workerQueueDeliveryName'] = $GLOBALS['env_RabbitMQDeliveryPrimaryQueueName'];
$GLOBALS['env_workerQueueDeliveryDeadLetterName'] = $GLOBALS['env_RabbitMQDeliveryDeadLetterQueueName'];
$GLOBALS['env_QueueType'] = 'QueueRabbitMQ';
$GLOBALS['workerQueueConnections'] = [];
$GLOBALS['workerQueueConnectionsTimes'] = [];




function newWorkerQueueConnection($queueName, $longPollingInSeconds=20, $defaultMessageTimeout=0, $forceNewConnection=false) {

	$countOfConnections = 0;
	$connection = "";
	if(isset($GLOBALS['workerQueueConnections'][$queueName])) {

		$countOfConnections = count_like_php5($GLOBALS['workerQueueConnections'][$queueName]);
	}

	if($countOfConnections == 0 || $forceNewConnection == true) {

		$connection = workerQueueConnectionsConnect($queueName, $longPollingInSeconds, $defaultMessageTimeout);
	}
	else {
		
		// $connection = workerQueueConnectionsConnect($queueName, $longPollingInSeconds, $defaultMessageTimeout);

		// If connection was created more than 30 seconds earlier, create a new one
		if($GLOBALS['workerQueueConnectionsTimes'][$queueName][$countOfConnections-1] < time()-30) {

			$connection = workerQueueConnectionsConnect($queueName, $longPollingInSeconds, $defaultMessageTimeout);
		}
		else {

			$connection = $GLOBALS['workerQueueConnections'][$queueName][$countOfConnections-1];
		}
	}

	return $connection;
}

function workerQueueConnectionsConnect($queueName, $longPollingInSeconds, $defaultMessageTimeout) {

	$countOfConnections = 1;
	if(isset($GLOBALS['workerQueueConnections'][$queueName])) {

		$countOfConnections = count_like_php5($GLOBALS['workerQueueConnections'][$queueName]);
	}

	$GLOBALS['workerQueueConnections'][$queueName][$countOfConnections-1] = new WorkerQueue($queueName, $longPollingInSeconds, $defaultMessageTimeout);
	$GLOBALS['workerQueueConnectionsTimes'][$queueName][$countOfConnections-1] = time();

	return $GLOBALS['workerQueueConnections'][$queueName][$countOfConnections-1];
}

function workerQueueConnectionsDisconnect() {

	foreach($GLOBALS['workerQueueConnections'] as $queueName => $queueConnections) {

		foreach($queueConnections as $counter => $connection) {		

			try {

				// $connection->disconnect();
			}
			catch (Exception $ex) { }

			unset($GLOBALS['workerQueueConnections'][$queueName][$counter]);
			unset($GLOBALS['workerQueueConnectionsTimes'][$queueName][$counter]);
		}
	}

	$GLOBALS['workerQueueConnections'] = [];
	$GLOBALS['workerQueueConnectionsTimes'] = [];
}

class WorkerQueue {

	// JMD
	private $queue;

	function __construct($queueName, $longPollingInSeconds=20, $defaultMessageTimeout=0) {

		$this->queue = new $GLOBALS['env_QueueType']($queueName);
		$this->queue->connect();

		// Set long polling time
		$this->queue->setLongPollingInterval(intval($longPollingInSeconds), intval($defaultMessageTimeout));
	}



    public function setTimeout($timeout){
        $this->queue->setTimeout($timeout);
    }

    function getWaitTimeForDelay($startTimestamp) {

		$delayTimeInSeconds = 0;
		$currentTimestamp = time();

		// If Scheduled/request START timestamp > current time
		if($startTimestamp > $currentTimestamp) {

			// If Scheduled/request START timestamp > current time + max delay allowed by SQS
			// Then use the max delay
			if($startTimestamp > ($currentTimestamp+$this->queue->maxDelayInSeconds)) {

				$delayTimeInSeconds = $this->queue->maxDelayInSeconds;
			}

			// Else use the difference
			else {

				$delayTimeInSeconds = $startTimestamp - $currentTimestamp;
			}
		}

		return $delayTimeInSeconds;
	}

	function connect() {

		if(!empty($GLOBALS['workerQueue'])) {

			$this->queue = $GLOBALS['workerQueue'];
		}
		else {

			try {

				$this->queue->connect();
				$this->queue = $GLOBALS['workerQueue'];
			}
			catch (Exception $ex) {

				throw new Exception($ex->getMessage());
			}
		}
	}

	function disconnect() {

		if(!empty($GLOBALS['workerQueue'])) {

			$this->queue->disconnect();
		}
		else {

			return true;
		}
	}

	function sendMessage($messageArray, $delaySeconds=0) {

		try {

			$this->queue->sendMessage($messageArray, $delaySeconds);
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}
	}

	function deleteMessage($receiptHandle, $message='', $requeuedBeforeDeletion=false) {

		try {

			$this->queue->deleteMessage($receiptHandle, $message, $requeuedBeforeDeletion);
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}
	}

	function receiveMessage($numberOfMessages) {

		try {

			$result = $this->queue->receiveMessage($numberOfMessages);
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}

		return $result;
	}

	function putMessageBackonQueueWithDelay($messageArray, $delayTimeInSeconds) {

		try {

			$this->queue->putMessageBackonQueueWithDelay($messageArray, $delayTimeInSeconds);
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}
	}

	function getQueueMessageCount() {

		try {

			$result = $this->queue->getQueueMessageCount();
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}

		return $result;
	}

	function getQueueUackdMessageCount() {

		try {

			$result = $this->queue->getQueueUackdMessageCount();
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}

		return $result;
	}

	function getMessageBody($message) {

		return $this->queue->getMessageBody($message);
	}

	function getMessageId($message) {

		return $this->queue->getMessageId($message);
	}

	function isMessageReadyToBeProcessed($processAfterTimestamp) {

		if(time() >= $processAfterTimestamp) {

			return true;
		}

		return false;
	}

    function doMessagesRequireAck() {

        return $this->queue->doMessagesRequireAck();
    }

    function reprocessDelay() {

    	return $this->queue->reprocessDelay();
    }

    function __destruct() {

    	if(defined("QUEUE_WORKER")) {

    		// Log the memory used by the object
    		json_error("AS_", "", "Queue object memory allocation = ". memory_get_usage() . " bytes", 3, 1);
    	}
    }
}
