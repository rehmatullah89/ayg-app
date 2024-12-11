<?php
namespace App\Background\Services;

use App\Background\Repositories\CheckInRepositoryInterface;
use App\Background\Repositories\PingLogRepositoryInterface;
use App\Background\Repositories\QueueLogRepositoryInterface;
use App\Background\Repositories\UserActionLogRepositoryInterface;
use App\Background\Repositories\DeliveryLogRepositoryInterface;

/**
 * Class LogService
 * @package App\Backround\Services
 */
class LogService extends Service
{
    /**
     * @var CheckInRepositoryInterface
     */
    private $checkInRepository;

    /**
     * @var PingLogRepositoryInterface
     */
    private $pingLogRepository;

    /**
     * @var QueueLogRepositoryInterface
     */
    private $queueLogRepository;

    /**
     * @var UserActionLogRepositoryInterface
     */
    private $userActionLogRepository;

    /**
     * @var DeliveryLogRepositoryInterface
     */
    private $deliveryLogRepository;

    public function __construct(
        PingLogRepositoryInterface $pingLogRepository,
        CheckInRepositoryInterface $checkInRepository,
        QueueLogRepositoryInterface $queueLogRepository,
        UserActionLogRepositoryInterface $userActionLogRepository,
        DeliveryLogRepositoryInterface $deliveryLogRepository
    )
    {
        $this->pingLogRepository = $pingLogRepository;
        $this->checkInRepository = $checkInRepository;
        $this->queueLogRepository = $queueLogRepository;
        $this->userActionLogRepository = $userActionLogRepository;
        $this->deliveryLogRepository = $deliveryLogRepository;
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     */
    public function logDeliveryStatusChangedToActive($airportIataCode, $action, $timeStamp)
    {
        $this->deliveryLogRepository->logDeliveryStatusChangedToActive($airportIataCode, $action, $timeStamp);
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     */
    public function logDeliveryStatusChangedToInactive($airportIataCode, $action, $timeStamp)
    {
        $this->deliveryLogRepository->logDeliveryStatusChangedToInactive($airportIataCode, $action, $timeStamp);
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timeStamp
     */
    public function logOrderDeliveryStatus($airportIataCode, $action, $timeStamp, $orderSequenceId)
    {
        $this->deliveryLogRepository->logOrderDeliveryStatus($airportIataCode, $action, $timeStamp, $orderSequenceId);
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryPing($slackUsername, $timestamp)
    {
        $this->pingLogRepository->logDeliveryPing($slackUsername, $timestamp);
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryActivated($slackUsername, $timestamp)
    {
        $this->pingLogRepository->logDeliveryActivated($slackUsername, $timestamp);
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryDeactivated($slackUsername, $timestamp)
    {
        $this->pingLogRepository->logDeliveryDeactivated($slackUsername, $timestamp);
    }

    /**
     * @param $objectId
     */
    public function logWebsiteDownload($objectId)
    {
        $this->pingLogRepository->logWebsiteDownload($objectId);
    }

    /**
     * @param $objectId
     */
    public function logWebsiteRatingClick($objectId)
    {
        $this->pingLogRepository->logWebsiteRatingClick($objectId);
    }

    /**
     * @param $userId
     * @param $sessionObjectId
     */
    public function logUserCheckin($userId, $sessionObjectId)
    {
        $this->checkInRepository->logUserCheckin($userId, $sessionObjectId);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerPing($retailerUniqueId, $timestamp)
    {
        $this->pingLogRepository->logRetailerPing($retailerUniqueId, $timestamp);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerLogin($retailerUniqueId, $timestamp)
    {
        $this->pingLogRepository->logRetailerLogin($retailerUniqueId, $timestamp);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerLogout($retailerUniqueId, $timestamp)
    {
        $this->pingLogRepository->logRetailerLogout($retailerUniqueId, $timestamp);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerConnectFailure($retailerUniqueId, $timestamp)
    {
        $this->pingLogRepository->logRetailerConnectFailure($retailerUniqueId, $timestamp);
    }

    /**
     * @param $queueMessage
     * @param $actionIfSending
     * @param $typeOfOp
     * @param $consumerTag
     * @param $endPoint
     * @param $queueName
     */
    public function logQueueMessageTracffic($queueMessage, $actionIfSending, $typeOfOp, $consumerTag, $endPoint, $queueName)
    {
        $this->queueLogRepository->logQueueMessageTracffic($queueMessage, $actionIfSending, $typeOfOp, $consumerTag, $endPoint, $queueName);
    }

    /**
     * @param $objectId
     * @param $action
     * @param $data
     * @param $location
     * @param $timestamp
     */
    public function logUserAction($objectId, $action, $data, $location, $timestamp)
    {
        $this->userActionLogRepository->logUserAction($objectId, $action, $data, $location, $timestamp);
    }


}
