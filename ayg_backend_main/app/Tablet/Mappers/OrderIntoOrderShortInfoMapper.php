<?php
namespace App\Tablet\Mappers;

use App\Tablet\Entities\Order;
use App\Tablet\Entities\OrderShortInfo;
use App\Tablet\Helpers\OrderHelper;
use Parse\ParseObject;

/**
 * Class ParseOrderIntoOrderShortInfoMapper
 * @package App\Tablet\Mappers
 */
class OrderIntoOrderShortInfoMapper
{
    /**
     * @param Order $order
     * @return OrderShortInfo
     *
     * maps Order into OrderShortInfo
     * OrderShort info is a class which objects are returned in the json responses
     */
    public static function map(Order $order)
    {
        $orderType = strcasecmp($order->getFullfillmentType(), "p") == 0 ? "Pickup" : "Delivery";
        $airporTimeZone = \fetchAirportTimeZone($order->getRetailer()->getAirportIataCode(), date_default_timezone_get());
        $orderDateAndTime = \orderFormatDate($airporTimeZone, $order->getSubmitTimestamp(), 'both');
        $mustBePreparedBy = \orderFormatDate($airporTimeZone, ($order->getEtaTimestamp()) - $order->getFullfillmentProcessTimeInSeconds(), 'time');


        if (OrderHelper::checkIfStatusIsReadyForDelivery($order, time())) {
            $orderCategoryCode = Order::ORDER_STATUS_CATEGORY_OTHER;
            $tabletOrderStatusDisplay = Order::ORDER_STATUS_READY_FOR_DELIVERY_DISPLAY;
        } else {
            $orderCategoryCode = OrderHelper::getOrderStatusCategoryCode($order);
            $tabletOrderStatusDisplay = OrderHelper::getTabletOrderStatusDisplay($order);
        }

        $discounts = [];
        if($order->getHasAirportEmployeeDiscount()) {

            $discounts[] = ["discountTextDisplay" => "Airport Employee Discount", "discountPercentageDisplay" => $order->getAirportEmployeeDiscountPercentage()];
        }
        if($order->getHasMilitaryDiscount()) {

            $discounts[] = ["discountTextDisplay" => "Military Discount", "discountPercentageDisplay" => $order->getMilitaryDiscountPercentage()];
        }

        return new OrderShortInfo(
            $order->getId(),
            $order->getOrderSequenceId(),
            $order->getStatus(),
            $tabletOrderStatusDisplay,
            $orderCategoryCode,
            $orderType,
            $orderDateAndTime,
            $order->getRetailer()->getId(),
            $order->getRetailer()->getRetailerName(),
            $order->getRetailer()->getLocation()->getLocationDisplayName(),
            $order->getUser()->getFirstName() . ' ' . $order->getUser()->getLastName(),
            $mustBePreparedBy,
            $discounts,
            null,
            null,
            null
        );
    }
}