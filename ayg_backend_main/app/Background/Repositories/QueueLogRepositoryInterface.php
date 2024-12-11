<?php
namespace App\Background\Repositories;

/**
 * Interface QueueLogRepositoryInterface
 * @package App\Background\Repositories
 */
interface QueueLogRepositoryInterface
{

    /**
     * @param $queueMessage
     * @param $actionIfSending
     * @param $typeOfOp
     * @param $consumerTag
     * @param $endPoint
     * @param $queueName
     */
    public function logQueueMessageTracffic( $queueMessage,  $actionIfSending,  $typeOfOp,  $consumerTag,  $endPoint,  $queueName): void;
}
