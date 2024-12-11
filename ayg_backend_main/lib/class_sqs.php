<?php

use Aws\Sqs\SqsClient;

class QueueSQS {

	private $connection;
	private $queueNameWithUrl;
	private $longPollingInSeconds;
	private $maxMessages = 100;
	public $maxDelayInSeconds = 15*60; // AWS SQS Max allowed delay

	function __construct($queueName) {

		$this->queueNameWithUrl = $GLOBALS['env_SQSConsumerQueueURL'] . $queueName;
	}

	function setLongPollingInterval($longPollingInSeconds) {

		$this->longPollingInSeconds = $longPollingInSeconds;

		// Verify max allowed by SQS
		if($longPollingInSeconds > 20) {

			$this->longPollingInSeconds = 20;
		}
	}

	function connect() {

		try {

		    $sqs_credentials = array(
		        'region' => 'us-east-1',
		        'version' => 'latest',
		        'credentials' => array(
		            'key'    => $GLOBALS['env_SQSConsumerAWSKey'],
		            'secret' => $GLOBALS['env_SQSConsumerAWSSecret']
		        )
		    );

		    // Instantiate the client
		    $this->connection = new SqsClient($sqs_credentials);
		}
		catch (Exception $ex) {

			throw new exception (json_encode(json_error_return_array("AS_1021", "", "AWS SQS connection failed " . json_encode($ex->getMessage()), 1)));
		}
	}

	function disconnect() {

		return true;
	}

	function sendMessage($messageArray, $delaySeconds=0) {

		try {

			// Send the message
			$result = $this->connection->sendMessage(array(
			    'QueueUrl'        => $this->queueNameWithUrl,
			    'MessageBody' 	  => json_encode($messageArray),
			    'DelaySeconds' 	  => $delaySeconds,
			));

			// Verify if not Ok response
			if(intval($result->toArray()["@metadata"]["statusCode"]) != 200) {

				throw new Exception(json_encode($result->toArray()));
			}
		}
		catch (Exception $ex) {

			throw new Exception(json_encode(json_error_return_array("AS_1022", "", "AWS SQS Message send failed " . json_encode($ex->getMessage()) . json_encode($messageArray), 1)));
		}
	}

	function deleteMessage($receiptHandle, $message, $requeuedBeforeDeletion) {

		try {

			// Delete the message
			$result = $this->connection->deleteMessage(array(
			    'QueueUrl'        => $this->queueNameWithUrl,
			    'ReceiptHandle'   => $receiptHandle
			));

			// Verify if not Ok response
			if(intval($result->toArray()["@metadata"]["statusCode"]) != 200) {

				throw new Exception(json_encode($result->toArray()));
			}
		}
		catch (Exception $ex) {

			throw new Exception(json_encode(json_error_return_array("AS_1023", "", "AWS SQS Message delete failed " . json_encode($ex->getMessage()), 1)));
		}
	}

	function receiveMessage($numberOfMessages=1) {

		try {

			// Receive the message
			$result = $this->connection->receiveMessage(array(
			    'QueueUrl'        => $this->queueNameWithUrl,
			    'WaitTimeSeconds' => $this->longPollingInSeconds, // Long-polling, waits here for X seconds before proceeding
			    'MaxNumberOfMessages' => $numberOfMessages // e.g. fetch up to 10 messages
			));

			// Verify if not Ok response
			if(intval($result->toArray()["@metadata"]["statusCode"]) != 200) {

				throw new Exception(json_encode($result->toArray()));
			}
		}
		catch (Exception $ex) {

			throw new Exception(json_encode(json_error_return_array("AS_1024", "", "AWS SQS Message receive failed " . json_encode($ex->getMessage()), 1)));
		}

		if($result['Messages'] == null) {

			$result = null;
		}

		return $result['Messages'];
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

		return json_decode($message['Body'], true);
	}

	function getMessageId($message) {

		return $message['ReceiptHandle'];
	}

    function doMessagesRequireAck() {

        return false;
    }

    function reprocessDelay() {

    	return 30;
    }
}

?>