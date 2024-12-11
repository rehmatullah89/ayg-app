<?php
namespace App\Background\Services;

class SlackServiceFactory
{
    public static function createSlackServiceByAirportIataCode(string $airportIataCode)
    {
        $airport = getAirportByIataCode($airportIataCode);
        if (empty($airport)) {
            return new SlackService($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
        }

        if (!empty($airport->get('slack_order_notifications_webhook_url'))) {
            return new SlackService($airport->get('slack_order_notifications_webhook_url'),
                'env_SlackWH_orderNotifications-' . $airport->get('airportIataCode'));
        }

        return new SlackService($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
    }
}
