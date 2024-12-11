<?php
namespace App\Tablet\Services;

/**
 * Class QueueServiceInterface
 * @package App\Tablet\Services
 */
interface QueueServiceInterface
{
    /**
     * @param $messageArray
     * @param $delaySeconds
     * @return mixed
     */
    function sendMessage($messageArray, $delaySeconds);

    /**
     * @param $startTimestamp
     * @return int
     *
     * wrapper for
     * @see WorkerQueue::getWaitTimeForDelay()
     */
    function getWaitTimeForDelay($startTimestamp);
}