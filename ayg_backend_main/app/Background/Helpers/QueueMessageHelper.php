<?php
namespace App\Background\Helpers;


use App\Background\Entities\Order;

class QueueMessageHelper
{
    public static function getOrderDeliveryAssignDeliveryMessage(Order $order)
    {
        return [
            "action" => "order_delivery_assign_delivery",
            "content" => [
                "orderId" => $order->getId(),
            ]
        ];
    }

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

    public static function getSendNotificationOrderPickupAccepted(Order $order)
    {
        return [
            "action" => "send_notification_order_pickup_accepted",
            "content" => [
                "orderId" => $order->getId(),
            ]
        ];
    }

    public static function getRetailersUpdateMessage(string $airportIataCode, bool $skipUniqueIdGeneration, bool $silentMode)
    {
        return [
            "action" => "retailers_update",
            "content" => [
                "airportIataCode" => $airportIataCode,
                "skipUniqueIdGeneration" => $skipUniqueIdGeneration,
                "silentMode" => $silentMode,
            ]
        ];
    }

    public static function getMenuUpdateMessage(string $airportIataCode)
    {
        return [
            "action" => "data_update",
            "content" => [
                "airportIataCode" => $airportIataCode,
                "silentMode" => true,
            ]
        ];
    }

    public static function getOrderEmailReceiptMessage(Order $order)
    {
        return [
            "action" => "order_email_receipt",
            "content" => [
                "orderId" => $order->getId(),
            ]
        ];
    }

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

    public static function getSendMessageBySMSMessage($phoneCountryCode, $phoneNumber, $message)
    {
        return [
            "action" => "send_sms_notification_with_phone_number",
            "content" => [
                "phoneCountryCode" => $phoneCountryCode,
                "phoneNumber" => $phoneNumber,
                "message" => $message,
            ]
        ];
    }
}
