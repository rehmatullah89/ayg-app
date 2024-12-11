<?php

use IronMQ\IronMQ;

class QueueIronMQ {

	private $connection;
	private $queueNameWithUrl;
	private $longPollingInSeconds;
	private $defaultMessageTimeout = 120; // Setting default timeout (can be update at queue level)
	private $maxMessages = 100; // Max messages that can be received
	public $maxDelayInSeconds = 7*24*60*60; // IronMQ Max allowed delay

	function __construct($queueName) {

		$this->queueNameWithUrl = $queueName;
	}

	function setLongPollingInterval($longPollingInSeconds, $defaultMessageTimeout) {

		$this->longPollingInSeconds = $longPollingInSeconds;

		// Verify max allowed by IronMQ
		if($longPollingInSeconds > 30) {

			$this->longPollingInSeconds = 30;
		}

		// If a default message timeout is provided, then use that
		if($defaultMessageTimeout != 0) {

			$this->defaultMessageTimeout = $defaultMessageTimeout;
		}
	}

	function connect() {

		try {

		    $ironMQ_credentials = json_decode($GLOBALS['env_IronMQConfig'], true);

		    // Instantiate the client
		    $this->connection = new IronMQ($ironMQ_credentials);
		}
		catch (Exception $ex) {

			throw new exception (json_encode(json_error_return_array("AS_1021", "", "IronMQ connection failed " . json_encode($ex->getMessage()), 1)));
		}
	}

	function disconnect() {

		return true;
	}

	function sendMessage($messageArray, $delaySeconds=0) {

		try {

			// Send the message
			$result = $this->connection->postMessage(
			    $this->queueNameWithUrl,
			    json_encode($messageArray),
			    [
			    	"delay" => $delaySeconds
			    ]
			);

			// Verify if not Ok response
			if(!isset($result->id)
				|| empty($result->id)) {

				throw new Exception(json_encode($result));
			}
		}
		catch (Exception $ex) {

			throw new Exception(json_encode(json_error_return_array("AS_1022", "", "IronMQ Message send failed " . json_encode($ex->getMessage()) . json_encode($messageArray), 1)));
		}
	}

	function deleteMessage($receiptHandle, $message, $requeuedBeforeDeletion) {

		try {

			// Delete the message
			$result = $this->connection->deleteMessage(
			    $this->queueNameWithUrl,
			    $receiptHandle['message_id'],
			    $receiptHandle['reservation_id']
			);

			// Verify if not Ok response
			if(strcasecmp(json_decode($result, true)["msg"], "Deleted")!=0) {

				throw new Exception(json_encode($result));
			}
		}
		catch (Exception $ex) {

			throw new Exception(json_encode(json_error_return_array("AS_1023", "", "IronMQ Message delete failed " . json_encode($ex->getMessage()), 1)));
		}
	}

	function receiveMessage($numberOfMessages=1) {

		try {

			// Receive the message
			$result = $this->connection->reserveMessages(
			    $this->queueNameWithUrl,
			    $numberOfMessages, // fetch up to X messages,
			    $this->defaultMessageTimeout, // time after which undeleted (processed) message is put back on the queue
			    $this->longPollingInSeconds
			);
		}
		catch (Exception $ex) {

			throw new Exception(json_encode(json_error_return_array("AS_1024", "", "IronMQ Message receive failed " . json_encode($ex->getMessage()), 1)));
		}

		return $result;
	}

	function putMessageBackonQueueWithDelay($messageArray, $delayTimeInSeconds) {

		// Log Message being put back on queue
		json_error("AS_3005", "", $messageArray["action"] . " - DelayTimeSeconds = " . $delayTimeInSeconds . " - " . json_encode($messageArray), 3, 1);

		try {

			$this->sendMessage($messageArray, $delayTimeInSeconds);
		}
		catch (Exception $ex) {

			throw new Exception($ex->getMessage());
		}
	}

	function getQueueMessageCount() {

		return $this->receiveMessage($this->maxMessages);
	}

    function getQueueUackdMessageCount() {

    	return 0;
    }

	function getMessageBody($message) {

		return json_decode($message->body, true);
	}

	function getMessageId($message) {

		return ['message_id' => $message->id, 'reservation_id' => $message->reservation_id];
	}

    function doMessagesRequireAck() {

        return false;
    }

    function reprocessDelay() {

    	return 30;
    }
}

?>