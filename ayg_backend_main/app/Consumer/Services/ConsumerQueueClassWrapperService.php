<?php
namespace App\Consumer\Services;

use App\Consumer\Exceptions\QueueSendException;

/**
 * Class ConsumerQueueClassWrapperService
 * @package App\Consumer\Services
 */
class ConsumerQueueClassWrapperService extends Service implements QueueServiceInterface
{
    /**
     * @var \WorkerQueue
     */
    private $consumerQueueObject;

    /**
     * RabbitMQService constructor.
     * @param \WorkerQueue $consumerQueueObject (inside this object always RabbitMQ object will be)
     * @internal param $client
     * @internal param $queueNameWithUrl
     */

    public function __construct($consumerQueueObject)
    {
        $this->consumerQueueObject = $consumerQueueObject;
    }


    /**
     * @param $messageArray
     * @param $delaySeconds
     * @return mixed
     * @throws QueueSendException
     *
     * wrapper for
     * @see WorkerQueue::sendMessage()
     */
    public function sendMessage($messageArray, $delaySeconds)
    {
        $this->consumerQueueObject->sendMessage($messageArray, $delaySeconds);
    }

    /**
     * @param $startTimestamp
     * @return int
     *
     * wrapper for
     * @see WorkerQueue::getWaitTimeForDelay()
     */
    public function getWaitTimeForDelay($startTimestamp)
    {
        return $this->consumerQueueObject->getWaitTimeForDelay($startTimestamp);
    }
}
