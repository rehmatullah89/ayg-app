<?php

namespace App\Delivery\Services;

use App\Delivery\Entities\DeliveryAppConfig;
use App\Delivery\Exceptions\ConfigKeyNotFoundException;
use App\Delivery\Helpers\ConfigHelper;


class DeliveryService extends Service
{
    public function getDeliveryAppConfig()
    {
        // now all data are hardcoded
        try {
            $pingInterval = intval(ConfigHelper::get('env_DeliveryAppDefaultPingIntervalInSecs'));
        } catch (ConfigKeyNotFoundException $e) {
            $pingInterval = null;
        }

        try {
            $notificationSoundUrl = ConfigHelper::get('env_DeliveryAppDefaultNotificationSoundUrl');
        } catch (ConfigKeyNotFoundException $e) {
            $notificationSoundUrl = null;
        }

        try {
            $notificationVibrateUsage = boolval(ConfigHelper::get('env_DeliveryAppDefaultVibrateUsage'));
        } catch (ConfigKeyNotFoundException $e) {
            $notificationVibrateUsage = null;
        }

        try {
            $batteryCheckIntervalInSecs = intval(ConfigHelper::get('env_DeliveryBatteryCheckIntervalInSecs'));
        } catch (ConfigKeyNotFoundException $e) {
            $batteryCheckIntervalInSecs = null;
        }


        return new DeliveryAppConfig(
            $pingInterval,
            $notificationSoundUrl,
            $notificationVibrateUsage,
            $batteryCheckIntervalInSecs
        );
    }
}
