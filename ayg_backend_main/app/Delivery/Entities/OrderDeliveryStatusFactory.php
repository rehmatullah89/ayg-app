<?php
namespace App\Delivery\Entities;

use App\Delivery\Exceptions\Exception;

class OrderDeliveryStatusFactory
{

    public static function getNextOf(OrderDeliveryStatus $orderDeliveryStatus):?OrderDeliveryStatus
    {
        if ($orderDeliveryStatus->getNextStatusId() === null) {
            return null;
        }

        return self::fromId($orderDeliveryStatus->getNextStatusId());
    }


    public static function fromId(int $id): OrderDeliveryStatus
    {
        $all = self::getAllPossible();

        /**
         * @var OrderDeliveryStatus $orderDeliveryStatus
         */
        foreach ($all as $orderDeliveryStatus) {

            if ($orderDeliveryStatus->getId() === $id) {
                return $orderDeliveryStatus;
            }
        }
        throw new Exception('There is no OrderDeliveryStatus with id ' . $id);
    }

    public static function fromName(string $statusName): OrderDeliveryStatus
    {
        $all = self::getAllPossible();
        /**
         * @var OrderDeliveryStatus $orderDeliveryStatus
         */
        foreach ($all as $orderDeliveryStatus) {

            if ($orderDeliveryStatus->getName() === $statusName) {
                return $orderDeliveryStatus;
            }
        }
        throw new Exception('There is no OrderDeliveryStatus with name ' . $statusName);
    }

    public static function getAllPossibleStatusNames()
    {
        $all = self::getAllPossible();

        $return = [];
        /** @var OrderDeliveryStatus $orderDeliveryStatus */
        foreach ($all as $orderDeliveryStatus) {
            $return[] = $orderDeliveryStatus->getName();
        }
        return $return;
    }


    private static function getAllPossible()
    {

        $orderDeliveryStatuses[] = new OrderDeliveryStatus(
            Order::STATUS_DELIVERY_NOT_PROCESSED,
            'Not processed',
            'notProcessed',
            '#000000',
            Order::STATUS_DELIVERY_BEING_ASSIGNED
        );

        $orderDeliveryStatuses[] = new OrderDeliveryStatus(
            Order::STATUS_DELIVERY_BEING_ASSIGNED,
            'Being Assigned',
            'beingAssigned',
            '#000000',
            Order::STATUS_DELIVERY_ASSIGNED
        );

        $orderDeliveryStatuses[] = new OrderDeliveryStatus(
            Order::STATUS_DELIVERY_ASSIGNED,
            'Assigned',
            'assigned',
            '#000000',
            Order::STATUS_DELIVERY_ARRIVED_AT_RETAILER
        );

        $orderDeliveryStatuses[] = new OrderDeliveryStatus(
            Order::STATUS_DELIVERY_ARRIVED_AT_RETAILER,
            'Arrived at Retailer',
            'arrivedAtRetailer',
            '#000000',
            Order::STATUS_DELIVERY_PICKED_UP
        );

        $orderDeliveryStatuses[] = new OrderDeliveryStatus(
            Order::STATUS_DELIVERY_PICKED_UP,
            'Picked Up',
            'pickedUp',
            '#000000',
            Order::STATUS_DELIVERY_ARRIVED_AT_CUSTOMER_PLACE
        );

        $orderDeliveryStatuses[] = new OrderDeliveryStatus(
            Order::STATUS_DELIVERY_ARRIVED_AT_CUSTOMER_PLACE,
            'Arrived at Customer Place',
            'arrivedAtCustomerPlace',
            '#000000',
            Order::STATUS_DELIVERY_DELIVERED
        );

        $orderDeliveryStatuses[] = new OrderDeliveryStatus(
            Order::STATUS_DELIVERY_DELIVERED,
            'Delivered',
            'delivered',
            '#000000',
            Order::STATUS_DELIVERY_DELIVERED
        );

        return $orderDeliveryStatuses;
    }

}
