<?php
namespace App\Tablet\Services;

use App\Tablet\Exceptions\QueueSendException;
use Exception;
use IronMQ\IronMQ;
/**
 * Class QueueServiceInterface
 * @package App\Consumer\Services
 */
class IronMQService extends Service implements QueueServiceInterface
{
    /**
     * @var IronMQ $client
     */
    private $client;
    /**
     * @var string
     */
    private $queueNameWithUrl;

    function __construct(IronMQ $client, $queueNameWithUrl)
    {
        $this->client = $client;
        $this->queueNameWithUrl = $queueNameWithUrl;
    }


    /**
     * @param $messageArray
     * @param $delaySeconds
     * @return mixed
     * @throws QueueSendException
     *
     * Sends message to IronMQ
     */
    function sendMessage($messageArray, $delaySeconds)
    {
        try{
            // Send the message
            $result = $this->client->postMessage(
                $this->queueNameWithUrl,
                json_encode($messageArray),
                [
                    "delay" => $delaySeconds
                ]
            );

            // Verify if not Ok response
            if (!isset($result->id) || empty($result->id)) {
                throw new Exception(json_encode($result));
            }
        }catch (Exception $e){
            throw new QueueSendException();
        }

        return true;
    }


}