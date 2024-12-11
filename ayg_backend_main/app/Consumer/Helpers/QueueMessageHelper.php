<?php
namespace App\Consumer\Helpers;

/**
 * Class QueueMessageHelper
 * @package App\Tablet\Helpers
 */
class QueueMessageHelper
{
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
