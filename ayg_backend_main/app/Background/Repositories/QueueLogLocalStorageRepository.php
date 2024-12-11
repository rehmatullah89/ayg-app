<?php
namespace App\Background\Repositories;

use App\Background\Entities\QueueLog;

/**
 * Class QueueLogS3Repository
 * @package App\Background\Repositories
 */
class QueueLogLocalStorageRepository extends LocalStorageRepository implements QueueLogRepositoryInterface
{
    const QUEUELOG_DIRECTORY = 'queue_logs';

    /**
     * @param $queueMessage
     * @param $actionIfSending
     * @param $typeOfOp
     * @param $consumerTag
     * @param $endPoint
     * @param $queueName
     */
    public function logQueueMessageTracffic(
         $queueMessage,
         $actionIfSending,
         $typeOfOp,
         $consumerTag,
         $endPoint,
         $queueName
    ): void {
        $queueLog = new QueueLog($queueMessage, $actionIfSending, $typeOfOp, $consumerTag, $endPoint, $queueName);
        $this->store(self::QUEUELOG_DIRECTORY, $queueLog);
    }
}
