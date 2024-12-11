<?php
namespace App\Background\Services;

use App\Background\Exceptions\QueueServiceNotSupportedException;
use App\Background\Helpers\ConfigHelper;


/**
 * Class QueueServiceFactory
 * @package App\Tablet\Services
 *
 * Creates instance of QueueServiceInterface
 */
class QueueServiceFactory extends Service
{
    /**
     * @return QueueServiceInterface
     *
     * this class should switch between classes with QueueServiceInterface - like different for
     * Iron, Sqs, Rabbit etc,
     * now it just creates ConsumerQueueClassWrapperService with injected class from consumer part
     * (lib/ directory) which class it is depends on GLOBAL variable
     */
    public static function create()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_RabbitMQConsumerPrimaryQueueName'))
        );
    }

    public static function createMidPriorityAsynch()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_RabbitMQConsumerEmailQueueName'))
        );
    }

    public static function createRetailerUpdate()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_RabbitMQConsumerRetailersEdit'))
        );
    }

    public static function createMenuUpdate()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_RabbitMQConsumerDataEdit'))
        );
    }

    public static function createEmailQueueService()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_RabbitMQConsumerEmailQueueName'))
        );
    }

    public static function createSmsAndPushNotificationQueueService()
    {
        return new ConsumerQueueClassWrapperService(
            newWorkerQueueConnection(ConfigHelper::get('env_RabbitMQConsumerPushAndSmsQueueName'))
        );
    }


}
