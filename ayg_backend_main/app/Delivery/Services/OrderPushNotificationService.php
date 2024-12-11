<?php

namespace App\Delivery\Services;

use App\Delivery\Repositories\DeliveryUserRepositoryInterface;
use App\Tablet\Services\QueueServiceInterface;
use App\Tablet\Helpers\QueueMessageHelper;

/**
 * Class OrderNotificationService
 * @package App\Delivery\Services
 */
class OrderPushNotificationService extends Service
{
    /**
     * @var QueueServiceInterface
     */
    private $queueService;

    /**
     * @var DeliveryUserRepositoryInterface
     */
    private $deliveryUserRepository;

    public function __construct(
        QueueServiceInterface $queueService,
        DeliveryUserRepositoryInterface $deliveryUserRepository
    ) {
        $this->queueService = $queueService;
        $this->deliveryUserRepository = $deliveryUserRepository;
    }

    public function sendPushNotification($airportIataCode, $message, $notifyRunner=true, $user=null)
    {
        $oneSignalIds = $this->deliveryUserRepository->getAirportDeliveryUsersList($airportIataCode, $user, $notifyRunner);

        $orderPushNotificationMessage = QueueMessageHelper::getOrderPushNotificationMessage($oneSignalIds, $message);
        $this->queueService->sendMessage($orderPushNotificationMessage, 0);
    }

   
}
