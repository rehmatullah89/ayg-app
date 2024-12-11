<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\Order;
use App\Delivery\Entities\OrderDeliveryStatusFactory;
use App\Delivery\Entities\OrderShortInfo;

class OrderIntoOrderShortInfoMapper
{
    public static function map(Order $order): OrderShortInfo
    {
        $airportTimezone = new \DateTimeZone($order->getRetailer()->getAirport()->getTimezone());

        /*
        $pickupBy = new \DateTime('@' . $order->getRetailerEtaTimestamp());
        $pickupBy->setTimezone($airportTimezone);

        $deliveryBy = new \DateTime('@' . $order->getRequestedFullFillmentTimestamp());
        $deliveryBy->setTimezone($airportTimezone);
        */
        $pickupBy = new \DateTime('@' . $order->getRequestedFullFillmentTimestamp());
        $pickupBy->setTimezone($airportTimezone);

        $deliveryBy = new \DateTime('@' . $order->getRetailerEtaTimestamp());
        $deliveryBy->setTimezone($airportTimezone);

        return new OrderShortInfo(
            $order->getId(),
            $order->getOrderSequenceId(),
            OrderDeliveryStatusFactory::fromId($order->getStatusDelivery()),
            $pickupBy,
            $deliveryBy,
            RetailerIntoRetailerShortInfoMapper::map($order->getRetailer()),
            TerminalGateMapIntoTerminalShortMapShortInfoMapper::map($order->getDeliveryLocation()),
            UserIntoUserShortInfoMapper::map($order->getUser()),
            null
        );
    }
}
