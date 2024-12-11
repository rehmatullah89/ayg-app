<?php
require_once __DIR__.'/../vendor/autoload.php';

require_once __DIR__ . '/../putenv.php';
require_once __DIR__.'/../lib/functions_errorhandling.php';
$env_RabbitMQConsumerPrimaryQueueName = getenv('env_RabbitMQConsumerPrimaryQueueName');
$env_RabbitMQDeliveryDeadLetterQueueName = getenv('env_RabbitMQDeliveryDeadLetterQueueName');
$env_RabbitMQDeliveryPrimaryQueueName = getenv('env_RabbitMQDeliveryPrimaryQueueName');
$env_RabbitMQDeliveryPrimaryQueueName = '';
$env_RabbitMQConfig = getenv('env_RabbitMQConfig');
$env_RabbitMQConfigUseSSL = false;


require_once __DIR__ . '/../lib/class_rabbitmq.php';
require_once __DIR__ . '/../lib/class_workerqueue.php';


$queue = new QueueRabbitMQ('test');
$queue->connect();

// receive message stays forever, and increase number of consumers
// killing process disconnects
//$x = $queue->receiveMessage();
//var_dump($x);

$c=0;
while (1>0){

    $x = $queue->receiveMessage(1);
    /**
     * @var PhpAmqpLib\Message\AMQPMessage[] $x
     */
    $id=$x[0]->getBody();
    $id=json_decode($id);
    var_dump($id);
    $queue->deleteMessage($id->__rabbitmq__msg_id,$x[0],'');

    var_dump($queue);

}

$queue->disconnect();
unset($queue);
// when there is a message "listening" ends