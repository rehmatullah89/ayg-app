<?php

namespace App\Tablet\Services;

use App\Tablet\Entities\ListOfOrderShortInfoPaginatedAndClosedEarlyData;
use App\Tablet\Entities\OrderShortInfo;
use App\Tablet\Entities\User;
use App\Tablet\Exceptions\TabletUserRetailerConnectionNotFoundException;


/**
 * Class OrderProcessingService
 * @package App\Tablet\Services
 *
 * This service combines functions from order and retailer controllers
 * If one job / function is connected with order and retailer this
 */
class OrderProcessingService extends Service
{
    /**
     * @var OrderService
     */
    private $orderService;
    /**
     * @var RetailerService
     */
    private $retailerService;

    public function __construct(
        OrderService $orderService,
        RetailerService $retailerService
    )
    {
        $this->orderService = $orderService;
        $this->retailerService = $retailerService;
    }

    /**
     * @param User $user
     * @param $page
     * @param $limit
     * @return ListOfOrderShortInfoPaginatedAndClosedEarlyData
     *
     * gets retailer Ping info (active order refresh interval in seconds, sound url, logo url)
     * gets active orders paginated
     * sets user as active (set ping value in cache)
     * @throws TabletUserRetailerConnectionNotFoundException
     */
    public function getActiveOrdersAndCloseEarlyDataAndSetRetailerAsActive(User $user, $page, $limit)
    {
        $retailersConnectedToLoggedTabletUser = $this->retailerService->getRetailerByTabletUserId($user->getId());
        if (empty($retailersConnectedToLoggedTabletUser)) {
            throw new TabletUserRetailerConnectionNotFoundException();
        }

        $activeOrdersPaginated = $this->orderService->getActiveOrdersPaginatedByRetailers($retailersConnectedToLoggedTabletUser, $page, $limit);
        $closeEarlyData = $this->retailerService->getClosedEarlyData($user, $retailersConnectedToLoggedTabletUser);

        // ping is updated only when it is retailer user (not ops team with multiple retailers connected)
        if ($user->getRetailerUserType()==User::USER_TYPE_RETAILER) {

            $this->retailerService->setLastSuccessfulPingForRetailers($retailersConnectedToLoggedTabletUser);

        }

        return new ListOfOrderShortInfoPaginatedAndClosedEarlyData($activeOrdersPaginated, $closeEarlyData);
    }

    /**
     * @param User $user
     * @param $page
     * @param $amount
     * @return ListOfOrderShortInfoPaginatedAndClosedEarlyData
     *
     * gets retailer Ping info (active order refresh interval in seconds, sound url, logo url)
     * gets active orders paginated
     * sets user as active (set ping value in cache)
     * @throws TabletUserRetailerConnectionNotFoundException
     */
    public function getPastOrdersAndCloseEarlyData(User $user, $page, $amount)
    {
        $retailersConnectedToLoggedTabletUser = $this->retailerService->getRetailerByTabletUserId($user->getId());
        if (empty($retailersConnectedToLoggedTabletUser)) {
            throw new TabletUserRetailerConnectionNotFoundException();
        }

        $pastOrdersPaginated = $this->orderService->getPastOrdersPaginatedByRetailers($retailersConnectedToLoggedTabletUser, $page, $amount);
        $closeEarlyData = $this->retailerService->getClosedEarlyData($user, $retailersConnectedToLoggedTabletUser);

        return new ListOfOrderShortInfoPaginatedAndClosedEarlyData($pastOrdersPaginated, $closeEarlyData);
    }

    /**
     * @param User $user
     * @param $orderId
     * @param $content
     * @return OrderShortInfo
     *
     * sets new entry for order request in the database
     * sends slack message
     */
    public function requestHelp(User $user, $orderId, $content)
    {
        $retailersConnectedToLoggedTabletUser = $this->retailerService->getRetailerByTabletUserId($user->getId());
        return $this->orderService->saveHelpRequest($retailersConnectedToLoggedTabletUser, $orderId, $content);
    }

    /**
     * @param User $user
     * @param $orderId
     * @return OrderShortInfo
     *
     * confirm order (change status from Order::STATUS_PUSHED_TO_RETAILER to Order::STATUS_ACCEPTED_BY_RETAILER)
     * confirm order (change status from Order::STATUS_PUSHED_TO_RETAILER to Order::STATUS_ACCEPTED_ON_TABLET for DualConfig retailers)
     */
    public function confirm(User $user, $orderId)
    {
        $retailersConnectedToLoggedTabletUser = $this->retailerService->getRetailerByTabletUserId($user->getId());
        return $this->orderService->confirm($retailersConnectedToLoggedTabletUser, $orderId);
    }
}
