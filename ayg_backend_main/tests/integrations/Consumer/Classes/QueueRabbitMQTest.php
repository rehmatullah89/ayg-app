<?php

require_once __DIR__ . '/../../../../putenv.php';

$env_RabbitMQConfig = getenv('env_RabbitMQConfig');
$env_RabbitMQConfigUseSSL = (getenv('env_RabbitMQConfigUseSSL') === 'true');
$env_RabbitMQConsumerPrimaryQueueName = getenv('env_RabbitMQConsumerPrimaryQueueName');
$env_RabbitMQAPIUrl = getenv('env_RabbitMQAPIUrl');
$env_RabbitMQDeliveryPrimaryQueueName = getenv('env_RabbitMQDeliveryPrimaryQueueName');
$env_RabbitMQDeliveryDeadLetterQueueName = getenv('env_RabbitMQDeliveryDeadLetterQueueName');
$env_CacheEnabled = (getenv('env_CacheEnabled') === 'true');
$env_CacheRedisURL = getenv('env_CacheRedisURL');
$env_CacheSSLCA = getenv('env_CacheSSLCA');
$env_CacheSSLCert = getenv('env_CacheSSLCert');
$env_CacheSSLPK = getenv('env_CacheSSLPK');

require_once __DIR__ . '/../../../../lib/class_rabbitmq.php';
require_once __DIR__ . '/../../../../lib/functions_cache.php';
require_once __DIR__ . '/../../../../lib/functions_errorhandling.php';
require_once __DIR__ . '/../../../../lib/initiate.redis.php';


class QueueRabbitMQTest extends \PHPUnit_Framework_TestCase
{
    public function testQueueCanSendAndReceiveMessage()
    {
        // create object
        $GLOBALS['env_workerQueueConsumerName'] = $GLOBALS['env_RabbitMQConsumerPrimaryQueueName'];
        $GLOBALS['env_workerQueueConsumerDeadLetterName'] = $GLOBALS['env_RabbitMQConsumerPrimaryQueueName'] . '-deadletter';
        $GLOBALS['env_workerQueueDeliveryName'] = $GLOBALS['env_RabbitMQDeliveryPrimaryQueueName'];
        $GLOBALS['env_workerQueueDeliveryDeadLetterName'] = $GLOBALS['env_RabbitMQDeliveryDeadLetterQueueName'];
        $GLOBALS['env_QueueType'] = 'QueueRabbitMQ';

        //$queue = new $GLOBALS['env_QueueType']($GLOBALS['env_workerQueueConsumerName']);
        $queue = new QueueRabbitMQ($GLOBALS['env_workerQueueConsumerName']);
        $queue->connect();

        //$queue->sendMessage(['test'=>'test']);
        $x = $queue->receiveMessage();

        if (empty($x)){
            echo 'no message';
            return 1;
        }

        var_dump($x);
        $msg=$x[0];
        $body=$msg->getBody();
        $body=json_decode($body);
        if ($body->test=='test'){
            echo 'TEST';
            // remove it
            $queue->deleteMessage('',$msg);
            echo ' DELETED';
        }else{
            echo "DIFFERENT MESSAGE";
        }

        $queue->disconnect();
    }


//string(354) "{"action":"order_submission_process","processAfter":{"timestamp":1503420491},"content":{"orderId":"P6qUScuCEC","retailerType":"Food","retailerUniqueId":"728d735364aee5a26810e68dc12bcacc","airportIataCode":"BWI","requestedFullFillmentTimestamp":1503422291,"fullfillmentTimeInSeconds":1800,"backOnQueue":false},"__rabbitmq__send_timestamp":1503422295676.5}"
//string(354) "{"action":"order_submission_process","processAfter":{"timestamp":1503420491},"content":{"orderId":"P6qUScuCEC","retailerType":"Food","retailerUniqueId":"728d735364aee5a26810e68dc12bcacc","airportIataCode":"BWI","requestedFullFillmentTimestamp":1503422291,"fullfillmentTimeInSeconds":1800,"backOnQueue":false},"__rabbitmq__send_timestamp":1503422295676.5}"
}