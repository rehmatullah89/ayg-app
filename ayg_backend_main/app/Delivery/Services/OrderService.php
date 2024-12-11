<?php

namespace App\Delivery\Services;

use App\Background\Repositories\OrderCommentRepositoryInterface;
use App\Delivery\Entities\ListOfOrderShortInfoPaginated;
use App\Delivery\Entities\Order;
use App\Delivery\Entities\OrderComment;
use App\Delivery\Entities\OrderDeliveryStatus;
use App\Delivery\Entities\Pagination;
use App\Delivery\Entities\User;
use App\Delivery\Entities\UserContact;
use App\Delivery\Exceptions\Exception;
use App\Delivery\Mappers\OrderIntoOrderDetailedMapper;
use App\Delivery\Mappers\OrderListIntoOrderShortInfoListMapper;
use App\Delivery\Mappers\ParseAirportIntoAirportMapper;
use App\Delivery\Repositories\DeliveryUserRepositoryInterface;
use App\Delivery\Repositories\OrderDeliveryStatusRepositoryInterface;
use App\Delivery\Repositories\OrderRepositoryInterface;
use App\Delivery\Repositories\UserPhoneRepositoryInterface;

/**
 * Class OrderService
 * @package App\Tablet\Services
 */
class OrderService extends Service
{

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var DeliveryUserRepositoryInterface
     */
    private $deliveryUserRepository;
    /**
     * @var UserPhoneRepositoryInterface
     */
    private $userPhoneRepository;
    /**
     * @var OrderDeliveryStatusRepositoryInterface
     */
    private $orderDeliveryStatusRepository;
    /**
     * @var OrderCommentRepositoryInterface
     */
    private $orderCommentRepository;


    public function __construct(
        OrderRepositoryInterface $orderRepository,
        DeliveryUserRepositoryInterface $deliveryUserRepository,
        UserPhoneRepositoryInterface $userPhoneRepository,
        OrderDeliveryStatusRepositoryInterface $orderDeliveryStatusRepository,
        OrderCommentRepositoryInterface $orderCommentRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->deliveryUserRepository = $deliveryUserRepository;
        $this->userPhoneRepository = $userPhoneRepository;
        $this->orderDeliveryStatusRepository = $orderDeliveryStatusRepository;
        $this->orderCommentRepository = $orderCommentRepository;
    }

    public function getActiveOrdersPaginated(User $user, $page, $limit)
    {
        $airportIataCode = $this->deliveryUserRepository->getDeliveryUserAirportIataCode($user->getId());
        $allOrdersCount = $this->orderRepository->getActiveOrdersCountByAirportIataCode($airportIataCode, $page,
            $limit);
        $selectedOrders = $this->orderRepository->getActiveOrdersByAirportIataCode($airportIataCode, $page, $limit);

        return new ListOfOrderShortInfoPaginated(
            OrderListIntoOrderShortInfoListMapper::map($selectedOrders),
            new Pagination($page, ceil($allOrdersCount / $limit), $limit, $allOrdersCount)
        );
    }

    public function getCompletedOrdersPaginated(User $user, $page, $limit)
    {
        $airportIataCode = $this->deliveryUserRepository->getDeliveryUserAirportIataCode($user->getId());
        $allOrdersCount = $this->orderRepository->getCompletedOrdersCountByAirportIataCode($airportIataCode, $page,
            $limit);
        $selectedOrders = $this->orderRepository->getCompletedOrdersByAirportIataCode($airportIataCode, $page, $limit);

        return new ListOfOrderShortInfoPaginated(
            OrderListIntoOrderShortInfoListMapper::map($selectedOrders),
            new Pagination($page, ceil($allOrdersCount / $limit), $limit, $allOrdersCount)
        );
    }

    public function getOrderDetails(User $user, string $orderId)
    {
        $airportIataCode = $this->deliveryUserRepository->getDeliveryUserAirportIataCode($user->getId());
        $order = $this->getOrderDetailsByOrderIdAndAirportIataCode($orderId, $airportIataCode);

        // enhance customer by phone number
        $userPhone = $this->userPhoneRepository->getActiveUserPhoneByUserId($order->getCustomer()->getUserId());
        $order->setCustomerContact(new UserContact($userPhone->getPhoneNumberFormatted()));

        $airport = ParseAirportIntoAirportMapper::map(getAirportByIataCode($airportIataCode));
        $order->setOrderComments($this->orderCommentRepository->getByOrderAndTimezone($order, $airport->getTimezone()));

        return $order;
    }

    public function changeDeliveryStatus(User $user, string $orderId, OrderDeliveryStatus $newStatus)
    {
        $airportIataCode = $this->deliveryUserRepository->getDeliveryUserAirportIataCode($user->getId());
        $order = $this->getOrderDetailsByOrderIdAndAirportIataCode($orderId, $airportIataCode);

        if ($order->getNextOrderDeliveryStatus() != $newStatus) {
            throw new Exception('Wrong status, next status should be ' . $order->getNextOrderDeliveryStatus()->getDisplayName());
        }

        $fromStatus = $order->getOrderDeliveryStatus();
        $toStatus = $order->getNextOrderDeliveryStatus();

        $completed = false;
        if ($toStatus->getId() === Order::STATUS_DELIVERY_DELIVERED) {
            $completed = true;
        }

        $changeStatus = $this->orderDeliveryStatusRepository->changeDeliveryStatus(
            $order,
            $fromStatus,
            $toStatus,
            $user,
            $completed);

        return $changeStatus;
    }

    public function addComment(User $user, string $orderId, string $comment): OrderComment
    {
        $airportIataCode = $this->deliveryUserRepository->getDeliveryUserAirportIataCode($user->getId());

        // if order not found (for that airport), then it will throw exception
        $order = $this->getOrderDetailsByOrderIdAndAirportIataCode($orderId, $airportIataCode);
        $author = $user->getUserNameForComments();

        $airport = ParseAirportIntoAirportMapper::map(getAirportByIataCode($airportIataCode));

        $orderComment = new OrderComment(
            $order->getOrderId(),
            $author,
            $comment,
            new \DateTime('now', new \DateTimeZone($airport->getTimezone()))
        );

        $this->orderCommentRepository->store($orderComment);

        return $orderComment;
    }

    private function getOrderDetailsByOrderIdAndAirportIataCode(string $orderId, string $airportIataCode)
    {
        $parseOrder = $this->orderRepository->getOrderByIdAndAirportIataCode($orderId, $airportIataCode);
        return OrderIntoOrderDetailedMapper::map($parseOrder);
    }
}
