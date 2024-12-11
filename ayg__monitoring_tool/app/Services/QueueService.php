<?php
namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class QueueService
{
    private $config;
    private $queueName;

    public function __construct(
        $config,
        $queueName
    ) {
        $this->config = $config;
        $this->queueName = $queueName;
    }

    public function getMessageCount()
    {
        try {
            $connection = $this->getConnection();
            $channel = $connection->channel();
            list(, $messageCount,) = $channel->queue_declare($this->queueName, true);
            $channel->close();
            $connection->close();
            return $messageCount;
        } catch (\Exception $exception) {
            return null;
        }
    }

    private function getConnection()
    {
        $rabbitMQ_credentials = parse_url($this->config);

        $rabbitMQ_credentials_array =
            [
                'host' => $rabbitMQ_credentials['host'],
                'port' => $rabbitMQ_credentials['port'],
                'user' => $rabbitMQ_credentials['user'],
                'password' => $rabbitMQ_credentials['pass'],
                'vhost' => substr($rabbitMQ_credentials['path'], 1) ?: '/'
            ];

        return new AMQPStreamConnection(
            $rabbitMQ_credentials_array['host'],
            $rabbitMQ_credentials_array['port'],
            $rabbitMQ_credentials_array['user'],
            $rabbitMQ_credentials_array['password'],
            $rabbitMQ_credentials_array['vhost']);
    }
}
