<?php

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Wire\AMQPTable;
use PhpAmqpLib\Message\AMQPMessage;

use App\Background\Repositories\PingLogMysqlRepository;

class QueueRabbitMQ
{

    private $consumerTag;
    private $queueConfig;
    private $queueName;
    private $connection;
    private $receivedMessage = null;
    private $receivedMessageArray = null;
    private $channels = [];
    private $queueDeclarations = [];
    private $longPollingInSeconds;
    private $maxTriesBeforeMoveOut = 10; // number of tries before message is moved out of queue
    public $messageCallback;
    private $maxMessages = 0; // this property is not applicable
    public $maxDelayInSeconds = 7 * 24 * 60 * 60; // No max delay but set to 7 days

    private $timeout = null;

    function __construct($queueName)
    {

        $this->queueConfig = explode(",", $GLOBALS['env_RabbitMQConfig']);
        $this->queueName = $queueName;

        $this->messageCallback = function ($message) {

            $this->setMessageReceived($message);
        };
    }

    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

    function setLongPollingInterval($longPollingInSeconds, $defaultMessageTimeout)
    {

        // Long polling and message timeout not applicable for RabbitMQ
        // Messages are put back on queue after connection is closed (must be explicitly closed)
        // No concept of long polling; we are not charged per message pull
        return true;
    }

    function connect()
    {

        try {

            if ($this->timeout != null) {
                $additionalConfig = [
                    'connection_timeout' => 130,
                    'read_write_timeout' => 130,
                ];
            }else{
                $additionalConfig=[];
            }


            $rabbitMQ_credentials_array = [];
            foreach ($this->queueConfig as $queueConfig) {

                $rabbitMQ_credentials = parse_url($queueConfig);

                $rabbitMQ_credentials_array = array_merge($rabbitMQ_credentials_array,
                    [
                        'host' => $rabbitMQ_credentials['host'] // host
                        ,
                        'port' => $rabbitMQ_credentials['port'] // port
                        ,
                        'user' => $rabbitMQ_credentials['user'] // user
                        ,
                        'password' => $rabbitMQ_credentials['pass'] // pass
                        ,
                        'vhost' => substr($rabbitMQ_credentials['path'], 1) ?: '/'
                    ] // vhost
                );
            }

            // json_error("AS_WW2", "New connection being created - " . getBackTrace(), "", 3, 1);
            if ($GLOBALS['env_RabbitMQConfigUseSSL'] == true) {

                // Instantiate the client with SSL
                $this->connection = AMQPSSLConnection::create_connection([
                    $rabbitMQ_credentials_array
                    ,
                    ['verify_peer' => true] // insist SSL
                    // ,[
                    ,
                    $additionalConfig,
                    //[
                        // // false // insist
                        // // ,'AMQPLAIN' // login_mthod
                        // // ,null // login_response
                        // // ,'en_US' // locale
                        // // ,
                        //     'connection_timeout' => 130 // connection_timeout
                        // ,   'read_write_timeout' => 130 // read_write_timeout
                        //'connection_timeout' => 130,
                        //'read_write_timeout' => 130,
                        // // ,null // context
                        // ,   'keepalive' => true // keepalive
                        // ,
                        // 'hearbeat' => 60
                        //]
                    //]
                ]);
            } else {
//                var_dump($rabbitMQ_credentials_array['host']);
//                var_dump($rabbitMQ_credentials_array['port']);
//                var_dump($rabbitMQ_credentials_array['user']);
//                var_dump($rabbitMQ_credentials_array['vhost']);
//var_dump($additionalConfig);
                // Instantiate the client with NO SSL
                $this->connection = AMQPStreamConnection::create_connection([
                    $rabbitMQ_credentials_array,

                    $additionalConfig,
                    // ,[
                    // // false // insist
                    // // ,'AMQPLAIN' // login_mthod
                    // // ,null // login_response
                    // // ,'en_US' // locale
                    // // ,
                    //     'connection_timeout' => 130 // connection_timeout

                    // ,   'read_write_timeout' => 130 // read_write_timeout
                    // // ,null // context
                    // ,   'keepalive' => true // keepalive
                    // ,   
                    // 'hearbeat' => 60]
                ]);
            }
        } catch (Exception $ex) {

            throw new exception (json_encode(json_error_return_array("AS_1021", "",
                " RabbitMQ connection failed " . json_encode($ex->getMessage()), 1)));
        }
    }

    function disconnect()
    {

        // Close Channel
        foreach ($this->channels as $channel) {

            $channel->close();
        }

        // Close Connection
        $this->connection->close();
    }

    function sendMessage($messageArray, $delaySeconds = 0)
    {

        try {

            // clear any existing messages
            $this->resetMessage();

            // To create a unique message id
            ini_set('precision', 14);

            // For requeued messages, this value would already be present
            if (!isset($messageArray["__rabbitmq__send_timestamp"])) {

                $messageArray["__rabbitmq__send_timestamp"] = microtime(true) * 1000;
                $messageArray["__rabbitmq__msg_id"] = md5(json_encode($messageArray));
                // echo(" --- " . $messageArray["__rabbitmq__msg_id"] . " --- ");
            }

            // Assigns a queue name based on delaySeconds
            // Initializes the queue accordingly
            list($queueNameToPublish, $channelName) = $this->getQueueAndChannelToPublish($delaySeconds);

            $this->logQueueMessageTracffic($messageArray, $messageArray['action'], 'send', '', '', $queueNameToPublish);

            if (empty($queueNameToPublish)) {

                throw new Exception("Queue could not be retrived!");
            }

            $this->channels[$channelName]->basic_publish(
                new AMQPMessage(json_encode($messageArray), ['delivery_mode' => 2]),
                '',
                $queueNameToPublish
            );
        } catch (Exception $ex) {

            throw new Exception(json_encode(json_error_return_array("AS_1022", "",
                "RabbitMQ Message send failed " . json_encode($ex->getMessage()) . json_encode($messageArray), 1)));
        }
    }

    function deleteMessage($messageId, $message, $requeuedBeforeDeletion)
    {

        try {

            // Send acknowledgement
            $message->delivery_info['channel']->basic_ack($this->getMessageId($message));

            $this->logQueueMessageTracffic($this->getMessageBody($message), '', 'delete', '', '', '');
        } catch (Exception $ex) {

            throw new Exception(json_encode(json_error_return_array("AS_1023", "",
                "RabbitMQ Message ack failed " . json_encode($ex->getMessage()), 1)));
        }
    }

    private function generateConsumerTag()
    {
        try {
            $dyno = getenv('DYNO');
            if (!is_string($dyno)) {
                $dyno = '';
            }
        } catch (Exception $exception) {
            $dyno = '';
        }

        if (empty($dyno)) {
            $dyno = rand(0, 999999);
        }

        ini_set('precision', 14);
        return microtime(true) . '-' . $dyno . '-' . rand(0, 999999);
    }

    function receiveMessage($numberOfMessages = 1)
    {

        try {

            // clear older message
            $this->resetMessage();

            $channelName = $this->getChannelPrimary();

            // Overriden to only pull 1 message

            $this->consumerTag = $consumerTag = $this->generateConsumerTag();

            $this->logQueueMessageTracffic('', '', 'receive', $consumerTag, '', $this->getQueueNamePrimary());

            // Temporary logging to research CON-645
            // json_error("AS_3020", "", "Consumer Tag - " . $consumerTag, 3, 1);
            //////////////////////////////////////////////////////
        } catch (Exception $ex) {

            // Non exiting
            throw new Exception(json_encode(json_error_return_array("AS_1024", "",
                "RabbitMQ Message receive failed " . json_encode($ex->getMessage().$ex->getTraceAsString()), 1, 1)));
        }

        try {

            $this->channels[$channelName]->basic_qos(0, 1, false);
            $this->channels[$channelName]->basic_consume($this->getQueueNamePrimary(), $consumerTag, false, false,
                false, false, $this->messageCallback);

            // Receive the message
            // This will wait here till a message is received
            $this->channels[$channelName]->wait();

            // If Message received is null
            if (is_null($this->receivedMessage) || empty($this->receivedMessage)) {

                json_error("AS_1079", "", "RabbitMQ Message Empty received", 1, 1);
                return null;
            }

            // Decode message
            $messageArray = $this->getMessageBody($this->receivedMessage);

            // Deadletter cache count > maxTriesBeforeMoveOut
            // Deliver a nack
            if ($this->getDeliveryCounter($messageArray) > $this->maxTriesBeforeMoveOut) {

                // Nack it
                $this->rejectMessage();

                // Clear the message
                $this->resetMessage();
            }
        } catch (Exception $ex) {

            throw new Exception(json_encode(json_error_return_array("AS_1077", "",
                "RabbitMQ Message receive failed " . json_encode($ex->getMessage()), 1)));
        }

        // Set by the callback as array
        if ($this->receivedMessage != null) {
            $this->channels[$channelName]->basic_cancel($consumerTag);
            return [$this->receivedMessage];
        } else {

            return null;
        }
    }

    function putMessageBackonQueueWithDelay($messageArray, $delayTimeInSeconds)
    {

        // Log Message being put back on queue
        // json_error("AS_3005", "", $messageArray["action"] . " - DelayTimeSeconds = " . $delayTimeInSeconds . " - " . json_encode($messageArray), 3, 1);

        try {

            $this->sendMessage($messageArray, $delayTimeInSeconds);
        } catch (Exception $ex) {

            throw new Exception($ex->getMessage());
        }
    }

    function getQueueMessageCount()
    {

        // Get the list of messages in the queue
        $queueInfo = json_decode(getpage($GLOBALS['env_RabbitMQAPIUrl'] . $this->getQueueNamePrimary()), true);

        return intval($queueInfo["messages"]);
    }

    function getQueueUackdMessageCount()
    {

        // Get the list of unacked messages in the queue
        $queueInfo = json_decode(getpage($GLOBALS['env_RabbitMQAPIUrl']) . $this->getQueueNamePrimary(), true);

        return intval($queueInfo["messages_unacknowledged"]);
    }

    function getQueueAndChannelToPublish($delaySeconds)
    {

        // Message needs to be delayed
        if ($delaySeconds > 0) {

            if ($delaySeconds > $this->maxDelayInSeconds) {

                $delaySeconds = $this->maxDelayInSeconds;
            }

            // Initialize channel
            $channelName = $this->getChannelDelay();

            // Initalize queue
            $queueName = $this->declareQueueDelay($channelName, $delaySeconds);

            return [$queueName, $channelName];
        } else {

            // Initialize channel
            $channelName = $this->getChannelPrimary();

            // Initalize queue
            $queueName = $this->declareQueuePrimary($channelName);

            return [$queueName, $channelName];
        }
    }

    function getQueueNamePrimary()
    {

        return $this->queueName;
    }

    function getQueueNameDeadletter()
    {
        return $GLOBALS['env_workerQueueConsumerDeadLetterName'];
    }

    function getQueueNameDelay($delaySeconds)
    {

        return $this->getQueueNamePrimary() . '-delayBySecs-' . strval($delaySeconds);
    }

    function declareQueuePrimary($channelName)
    {

        // Queue name
        $queueName = $this->getQueueNamePrimary();

        if (!isset($this->queueDeclarations[$queueName . $channelName])) {

            // Initialize queue
            $this->channels[$channelName]->queue_declare(
                $queueName, false, true, false, false, false,
                new AMQPTable([
                    "x-queue-mode" => "lazy",
                    "x-dead-letter-exchange" => "", // not needed assumed default
                    "x-dead-letter-routing-key" => $this->getQueueNameDeadletter() // deadletter queue name
                ]));
        }

        return $queueName;
    }

    function declareQueueDelay($channelName, $delaySeconds)
    {

        // Queue name
        $queueName = $this->getQueueNameDelay($delaySeconds);

        if (!isset($this->queueDeclarations[$queueName . $channelName])) {

            // Initialize delay specific queue
            $this->channels[$channelName]->queue_declare(
                $queueName, false, true, false, false, false,
                new AMQPTable([
                    "x-queue-mode" => "lazy",
                    "x-expires" => intval($delaySeconds * 1000 + 30 * 1000),
                    // deletes queue after expire (+ 30 seconds)
                    "x-message-ttl" => intval($delaySeconds * 1000),
                    // move message to main queue after this wait time
                    "x-dead-letter-exchange" => "",
                    // not needed assumed default
                    "x-dead-letter-routing-key" => $this->getQueueNamePrimary()
                    // primary queue name to direct to after delay seconds
                ]));
        }

        return $queueName;
    }

    function getChannelPrimary()
    {

        return $this->getChannel('primary');
    }

    function getChannelDelay()
    {

        return $this->getChannel('delay');
    }

    function getChannel($type)
    {

        if (!isset($this->channels[$type])) {

            // Initalize channel
            $this->channels[$type] = $this->connection->channel();
        }

        return $type;
    }

    function getMessageBody($message)
    {

        try {

            if (is_null($this->receivedMessageArray)) {

                $this->receivedMessageArray = json_decode($message->body, true);
                $this->logQueueMessageTracffic($this->receivedMessageArray, $this->receivedMessageArray['action'],
                    'receive_msg_body', $this->consumerTag, '', $this->getQueueNamePrimary());
            }
        } catch (Exception $ex) {

            throw new Exception(json_encode(json_error_return_array("AS_1078", "",
                "RabbitMQ Message json decode failed " . json_encode($ex->getMessage()), 1)));
        }

        return $this->receivedMessageArray;
    }

    function getMessageId($message)
    {

        return $message->delivery_info['delivery_tag'];
    }

    function getMessageUniqueId($message = '')
    {

        if (empty($message)) {

            // Decode message
            $messageArray = $this->getMessageBody($message);
        } else {

            // Decode message
            $messageArray = $this->getMessageBody($this->receiveMessage);
        }

        return $messageArray["__rabbitmq__msg_id"];

        // generates internal uniqueId
        // return md5($message->body);

        // return md5(json_encode($message->get_properties()) . '~' . $message->body . '~' . $message->body_size);
    }

    function setMessageReceived($message)
    {

        $this->receivedMessage = $message;
    }

    function resetMessage()
    {

        $this->receivedMessage = null;
        $this->receivedMessageArray = null;
    }

    function rejectMessage()
    {

        try {

            // Send nack so message can be deadlettered
            $this->receivedMessage->delivery_info['channel']->basic_nack($this->getMessageId($this->receivedMessage));
        } catch (Exception $ex) {

            throw new Exception(json_encode(json_error_return_array("AS_1023", "",
                "RabbitMQ Message nack failed " . json_encode($ex->getMessage()), 1)));
        }
    }

    function doMessagesRequireAck()
    {

        return true;
    }

    function getDeliveryCounter($messageArray)
    {

        // If set, increment it
        if (isset($messageArray["__rabbitmq__delivery_cnt"])) {

            $messageArray["__rabbitmq__delivery_cnt"] = intval($messageArray["__rabbitmq__delivery_cnt"]) + 1;
        } // set it to 1 as first seen delivery
        else {

            $messageArray["__rabbitmq__delivery_cnt"] = 1;
        }

        // Update message array locally
        $this->receivedMessageArray = $messageArray;

        // Set the message body to include updated counter
        $this->receiveMessage = json_encode($messageArray);

        return $messageArray["__rabbitmq__delivery_cnt"];
    }

    function reprocessDelay()
    {

        return $this->getMessageBody($this->receivedMessage)["__rabbitmq__delivery_cnt"] * 60;
    }

    function logQueueMessageTracffic($queueMessage, $actionIfSending, $typeOfOp, $consumerTag, $endPoint, $queueName)
    {

        if ($GLOBALS['env_LogQueueTransactionsToDB'] == true) {

            try {
                $logsPdoConenction = new PDO('mysql:host=' . $GLOBALS['env_mysqlLogsDataBaseHost'] . ';port=' . $GLOBALS['env_mysqlLogsDataBasePort'] . ';dbname=' . $GLOBALS['env_mysqlLogsDataBaseName'],
                    $GLOBALS['env_mysqlLogsDataBaseUser'], $GLOBALS['env_mysqlLogsDataBasePassword'],
                    [PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../cert/rds-combined-ca-bundle.pem']);
                $GLOBALS['logsPdoConnection'] = $logsPdoConenction;
            } catch (Exception $e) {
                $GLOBALS['logsPdoConnection'] = null;
                // @todo logging lack of connection
            }

            if ($GLOBALS['logsPdoConnection'] instanceof PDO) {

                if (!isset($_SERVER['REQUEST_URI'])
                    || empty($_SERVER['REQUEST_URI'])
                ) {

                    if (!isset($_SERVER['SCRIPT_NAME'])
                        || empty($_SERVER['SCRIPT_NAME'])
                    ) {

                        $REQUEST_URI = '';
                    } else {

                        $REQUEST_URI = $_SERVER['SCRIPT_NAME'];
                    }
                } else {

                    $REQUEST_URI = $_SERVER['REQUEST_URI'];
                }

                if (is_array($queueMessage)) {

                    $queueMessage = json_encode($queueMessage);
                } else {

                    $queueMessage = serialize($queueMessage);
                }

                //$pingLogRepository = new PingLogMysqlRepository($GLOBALS['logsPdoConnection']);
                $pingLogService = \App\Background\Services\LogServiceFactory::create();


                $pingLogService->logQueueMessageTracffic($queueMessage, $actionIfSending, $typeOfOp, $consumerTag,
                    $REQUEST_URI, $queueName
                );
            }
        }
    }
}

?>
