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
$queue->sendMessage(['test','test2']);