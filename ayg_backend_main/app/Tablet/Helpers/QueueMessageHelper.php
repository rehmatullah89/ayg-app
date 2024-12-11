<?php
namespace App\Tablet\Helpers;

use App\Tablet\Entities\Order;

/**
 * Class QueueMessageHelper
 * @package App\Tablet\Helpers
 */
class QueueMessageHelper
{
    /**
     * @param Order $order
     * @return array
     */
    public static function getOrderEmailReceiptMessage(Order $order)
    {
        return [
            "action" => "order_email_receipt",
            "content" => [
                "orderId" => $order->getId(),
            ]
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public static function getOrderPickupMarkCompleteMessage(Order $order)
    {
        return [
            "action" => "order_pickup_mark_complete",
            "processAfter" => ["timestamp" => $order->getEtaTimestamp()],
            "content" => [
                "orderId" => $order->getId(),
                "etaTimestamp" => $order->getEtaTimestamp()
            ]
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public static function getSendNotificationOrderPickupAccepted(Order $order)
    {
        return [
            "action" => "send_notification_order_pickup_accepted",
            "content" => [
                "orderId" => $order->getId(),
            ]
        ];
    }

    /**
     * @param Order $order
     * @return array
     */
    public static function getOrderDeliveryAssignDeliveryMessage(Order $order)
    {
        return [
            "action" => "order_delivery_assign_delivery",
            "content" => [
                "orderId" => $order->getId(),
            ]
        ];
    }

    /**
     * @param $retailerUniqueId
     * @param $time
     * @return array
     */
    public static function getLogRetailerPingMessage($retailerUniqueId, $time)
    {
        return [
            "action" => "log_retailer_ping",
            "content" => [
                "retailerUniqueId" => $retailerUniqueId,
                "time" => $time,
            ]
        ];
    }

    /**
     * @param $sessionTokenEnc
     * @param $time
     * @return array
     */
    public static function getLogRetailerConnectFailureMessage($sessionTokenEnc, $time)
    {
        return [
            "action" => "log_retailer_connect_failure",
            "content" => [
                "sessionTokenEnc" => $sessionTokenEnc,
                "time" => $time,
            ]
        ];
    }

    /**
     * @param $slackUsername
     * @param $time
     * @return array
     */
    public static function getLogDeliveryPingMessage($slackUsername, $time)
    {

        return [
            "action" => "log_delivery_ping",
            "content" => [
                "slackUsername" => $slackUsername,
                "time" => $time,
            ]
        ];
    }


    /**
     * @param $retailerUniqueId
     * @param $time
     * @return array
     */
    public static function getLogRetailerLoginMessage($retailerUniqueId, $time)
    {

        return [
            "action" => "log_retailer_login",
            "content" => [
                "retailerUniqueId" => $retailerUniqueId,
                "time" => $time,
            ]
        ];
    }


    /**
     * @param $retailerUniqueId
     * @param $time
     * @return array
     */
    public static function getLogRetailerLogoutMessage($retailerUniqueId, $time)
    {

        return [
            "action" => "log_retailer_logout",
            "content" => [
                "retailerUniqueId" => $retailerUniqueId,
                "time" => $time,
            ]
        ];
    }

    /**
     * @param $slackUsername
     * @param $time
     * @return array
     */
    public static function getLogDeliveryActivatedMessage($slackUsername, $time)
    {

        return [
            "action" => "log_delivery_activated",
            "content" => [
                "slackUsername" => $slackUsername,
                "time" => $time,
            ]
        ];
    }

    /**
     * @param $slackUsername
     * @param $time
     * @return array
     */
    public static function getLogDeliveryDeactivatedMessage($slackUsername, $time)
    {

        return [
            "action" => "log_delivery_deactivated",
            "content" => [
                "slackUsername" => $slackUsername,
                "time" => $time,
            ]
        ];
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $dateTime
     * @return array
     */
    public static function getLogActiveDelivery($airportIataCode, $action, $dateTime)
    {
        return [
            "action" => "log_delivery_status_active",
            "content" => [
                "airportIataCode" => $airportIataCode,
                "action" => $action,
                "timeStamp" => $dateTime
            ]
        ];
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $dateTime
     * @return array
     */
    public static function getLogInActiveDelivery($airportIataCode, $action, $dateTime)
    {
        return [
            "action" => "log_delivery_status_inactive",
            "content" => [
                "airportIataCode" => $airportIataCode,
                "action" => $action,
                "timeStamp" => $dateTime
            ]
        ];
    }

    /**
     * @param $airportIataCode
     * @param $action
     * @param $timestamp
     * @param $orderSequenceId
     * @return array
     */
    public static function getLogOrderDeliveryStatuses($airportIataCode, $action, $timestamp, $orderSequenceId)
    {
        return [
            "action" => "log_order_delivery_statuses",
            "content" => [
                "airportIataCode" => $airportIataCode,
                "action" => $action,
                "timestamp" => $timestamp,
                "orderSequenceId" => $orderSequenceId
            ]
        ];
    }
}
