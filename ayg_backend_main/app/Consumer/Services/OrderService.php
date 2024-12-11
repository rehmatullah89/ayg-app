<?php

namespace App\Consumer\Services;

use App\Consumer\Entities\DeliveryAvailability;
use App\Consumer\Entities\DeliveryAvailabilityFactory;
use App\Consumer\Entities\Order;
use App\Consumer\Entities\OrderRatingWithDelivery;
use App\Consumer\Entities\ScheduledOrderFullInfo;
use App\Consumer\Exceptions\OrderAlreadyRatedException;
use App\Consumer\Exceptions\OrderNotFoundException;
use App\Consumer\Middleware\OrderRatingMiddleware;
use App\Consumer\Repositories\DeliveryAssignmentRepositoryInterface;
use App\Consumer\Repositories\OrderDeliveryPlanRepositoryInterface;
use App\Consumer\Repositories\OrderRatingRepositoryInterface;
use App\Consumer\Repositories\OrderRepositoryInterface;
use App\Consumer\Repositories\VouchersRepositoryInterface;

/**
 * Class OrderService
 * @package App\Consumer\Services
 */
class OrderService extends Service
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var OrderRatingRepositoryInterface
     */
    private $orderRatingRepository;
    /**
     * @var DeliveryAssignmentRepositoryInterface
     */
    private $deliveryAssignmentRepository;
    /**
     * @var VouchersRepositoryInterface
     */
    private $vouchersRepository;
    /**
     * @var OrderDeliveryPlanRepositoryInterface
     */
    private $orderDeliveryPlanRepository;
    /**
     * @var DeliveryAvailabilityService
     */
    private $deliveryAvailabilityService;
    /**
     * @var PickupAvailabilityService
     */
    private $pickupAvailabilityService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderRatingRepositoryInterface $orderRatingRepository,
        DeliveryAssignmentRepositoryInterface $deliveryAssignmentRepository,
        VouchersRepositoryInterface $vouchersRepository,
        OrderDeliveryPlanRepositoryInterface $orderDeliveryPlanRepository,
        DeliveryAvailabilityService $deliveryAvailabilityService,
        PickupAvailabilityService $pickupAvailabilityService
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderRatingRepository = $orderRatingRepository;
        $this->deliveryAssignmentRepository = $deliveryAssignmentRepository;
        $this->vouchersRepository = $vouchersRepository;
        $this->orderDeliveryPlanRepository = $orderDeliveryPlanRepository;
        $this->deliveryAvailabilityService = $deliveryAvailabilityService;
        $this->pickupAvailabilityService = $pickupAvailabilityService;
    }

    /**
     * @param $userId
     * @param $orderId
     * @param $overAllRating
     * @param $feedback
     * @return bool This method is called by orderRating method in OrderController
     * @throws OrderAlreadyRatedException
     * @throws OrderNotFoundException
     *
     * This method is called by orderRating method in OrderController
     *
     * Add rating and feedback for order
     * This method will return True as the status
     *
     * rating can be -1,1,2,3,4,5
     * @see OrderRatingMiddleware::validate()
     *
     */
    public function addOrderRatingWithFeedback($userId, $orderId, $overAllRating, $feedback)
    {
        if (!$this->orderRepository->checkIfOrderExistsForAGivenUser($orderId, $userId)) {
            throw new OrderNotFoundException('Order Rating fail, Order ' . $orderId . ' does not belongs to user ' . $userId);
        }

        $lastOrderRating = $this->orderRatingRepository->getLastRating($orderId, $userId);

        if (($lastOrderRating !== null) && ($lastOrderRating->getOverAllRating() != -1)) {
            throw new OrderAlreadyRatedException('Order ' . $orderId . ' is already rated');
        }

        $this->orderRatingRepository->addOrderRatingWithFeedback($orderId, $userId, $overAllRating, $feedback);
        return true;
    }

    /**
     * @param $userId
     * @param $orderId
     * @return OrderRatingWithDelivery
     * @throws OrderNotFoundException
     */
    public function getLastOrderRatingWithDelivery($userId, $orderId)
    {
        if (!$this->orderRepository->checkIfOrderExistsForAGivenUser($orderId, $userId)) {
            throw new OrderNotFoundException('Get Last Order Rating fail, Order ' . $orderId . ' does not belongs to user ' . $userId);
        }

        $orderRating = $this->orderRatingRepository->getLastRating($orderId, $userId);
        if ($orderRating === null) {
            return null;
        }

        $delivery = null;

        if ($orderRating->getOrder()->getFullfillmentType() == 'd') {
            $deliveryAssignment = $this->deliveryAssignmentRepository->getCompletedDeliveryAssignmentWithDeliveryByOrderId($orderId);
            if ($deliveryAssignment !== null) {
                $delivery = $deliveryAssignment->getDelivery();
            }
        }

        return new OrderRatingWithDelivery(
            $orderRating,
            $delivery
        );
    }

    public function applyTipAsPercentage(string $orderId, string $userId, string $value): Order
    {
        if (!$this->orderRepository->checkIfOrderExistsForAGivenUser($orderId, $userId)) {
            throw new OrderNotFoundException('Apply Tip as Percentage fail, Order ' . $orderId . ' does not belongs to user ' . $userId);
        }

        $order = $this->orderRepository->saveTipData($orderId, $value, null);
        $this->clearCartCache($order);

        return $order;
    }

    public function applyTipAsFixedValue(string $orderId, string $userId, string $value): Order
    {
        if (!$this->orderRepository->checkIfOrderExistsForAGivenUser($orderId, $userId)) {
            throw new OrderNotFoundException('Apply Tip as Fixed Value fail, Order ' . $orderId . ' does not belongs to user ' . $userId);
        }

        $order = $this->orderRepository->saveTipData($orderId, null, $value);
        $this->clearCartCache($order);

        return $order;
    }

    public function clearCartCache(Order $order)
    {
        $namedCacheKey = 'cart' . '__u__' . $order->getUser()->getId() . '__o__' . $order->getId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));
        $namedCacheKey = 'cartv2' . '__u__' . $order->getUser()->getId() . '__o__' . $order->getId();
        delCacheByKey(getNamedRouteCacheName($namedCacheKey));
        delCacheByKey(getCacheKeyHMSHostTaxForOrder($order->getId()));
    }

    public function getScheduledOrderOptions(
        string $orderId,
        int $startTodayInAtLeastSeconds
    ) {
        $order = $this->orderRepository->getOrderWithRetailer($orderId);
        $airportIataCode = $order->getRetailer()->getAirportIataCode();
        $airport = getAirportByIataCode($airportIataCode);
        $timezone = $airport->get('airportTimezone');

        $deliveryPlanList = $this->orderDeliveryPlanRepository->getListByAirportIataCode($airportIataCode);

        return new ScheduledOrderFullInfo(
            $order,
            $timezone,
            $startTodayInAtLeastSeconds,
            $deliveryPlanList
        );
    }

    public function getVouchersOptions()
    {
        return $this->vouchersRepository->getActiveVouchers();
    }

    public function getDeliveryAvailabilityForRetailerAtGivenTime(): DeliveryAvailability
    {
        $deliveryAvailability = $this->deliveryAvailabilityService->isAirportDeliveryReady();
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }

        $deliveryAvailability = $this->deliveryAvailabilityService->doesRetailerHaveDelivery();
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }

        $deliveryAvailability = $this->deliveryAvailabilityService->isRetailerCurrentlyOpen();
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }

        $deliveryAvailability = $this->deliveryAvailabilityService->isRetailerOpenAtGivenTime();
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }

        $deliveryAvailability = $this->deliveryAvailabilityService->isRetailerCurrentlyActiveByPing();
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }

        $deliveryAvailability = $this->deliveryAvailabilityService->isDeliveryCurrentlyActive();
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }

        $deliveryAvailability = $this->deliveryAvailabilityService->isDeliveryActiveAtGivenTime();
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }

        return DeliveryAvailabilityFactory::createGenericSuccessDeliveryAvailability();

        // checks:
        // - if airport is delivery ready
        // - if retailer has delivery
        // - if retailer is not closed at that time (ideally with respect of processing time)
        // - for immediate: checks if retailer is active (pings are correct)
        // - for immediate: if delivery is set to on at the dashboard (to be more precised checks last timestamp which is set by loopers when delivery is set to on)
        // - for future: checks if it fits delivery plan (ideally with respect of processing time)
    }

    public function getDeliveryAvailabilityForRetailerAtLocation($retailerId, $locationId): DeliveryAvailability
    {
        $deliveryAvailability = $this->deliveryAvailabilityService->isDeliveryAvailableForRetailerAtLocation($retailerId, $locationId);
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }
        return DeliveryAvailabilityFactory::createGenericSuccessDeliveryAvailability();
    }

    public function getPickupAvailabilityForRetailerAtLocation($retailerId, $locationId): DeliveryAvailability
    {
        $deliveryAvailability = $this->pickupAvailabilityService->isPickupAvailableForRetailerAtLocation($retailerId, $locationId);
        if (!$deliveryAvailability) {
            return DeliveryAvailabilityFactory::createGenericFailedDeliveryAvailabilityForRetailerAtGivenTime();
        }
        return DeliveryAvailabilityFactory::createGenericSuccessDeliveryAvailability();
    }

    public function setCartItems($postVars): array
    {
        return $this->orderRepository->saveCartItems($postVars);
    }
}
