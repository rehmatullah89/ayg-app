<?php
namespace App\Consumer\Services;

use App\Consumer\Exceptions\QueueServiceNotSupportedException;
use App\Consumer\Helpers\ConfigHelper;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;


/**
 * Class QueueServiceFactory
 * @package App\Consumer\Services
 *
 * Creates instance of QueueServiceInterface
 */
class QueueServiceFactory extends Service
{
    /**
     * @return QueueServiceInterface
     * @throws QueueServiceNotSupportedException
     *
     * this class should switch between classes with QueueServiceInterface - like different for
     * Iron, Sqs, Rabbit etc,
     * now it just creates ConsumerQueueClassWrapperService with injected class from consumer part
     * (lib/ directory) which class it is depends on GLOBAL variable
     */
    public static function create()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_workerQueueConsumerName'))
        );
    }

    public static function createMidPriorityAsynch()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_workerQueueMidPriorityAsynchConsumerName'))
        );
    }
}
