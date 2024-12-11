<?php
namespace App\Delivery\Helpers;

use App\Delivery\Exceptions\ConfigKeyNotFoundException;


class ConfigHelper
{
    public static function get($key)
    {
        // look in env
        $value = getenv($key);
        if ($value !== false) {
            if ($value === 'true') {
                return true;
            }
            if ($value === 'false') {
                return false;
            }

            return $value;
        }

        // look in globals
        if (isset($GLOBALS[$key]) && in_array($key, self::possibleKeys())) {
            if ($GLOBALS[$key] === 'true') {
                return true;
            }
            if ($GLOBALS[$key] === 'false') {
                return false;
            }

            return $GLOBALS[$key];
        }

        throw new ConfigKeyNotFoundException('Config does not have key ' . $key);
    }

    /**
     * @return array
     * returns keys that are possible to get in config from $GLOBALS
     */
    private static function possibleKeys()
    {
        return [
            'env_S3Path_PublicImages',
            'env_S3Path_PublicImagesAirportBackground',
            'env_S3Path_PublicImagesDirection',
            'env_S3Path_PublicImagesAirportSpecific',
            'env_S3Path_PublicImagesRetailerLogo',
            'env_S3Path_PublicImagesRetailerBackground',
            'env_S3Path_PublicImagesRetailerItem',
            'env_S3Path_PublicImagesUserSubmitted',
            'env_S3Path_PublicImagesUserSubmittedBug',
            'env_S3Path_PublicImagesUserSubmittedProfile',
            'env_S3Path_PrivateFiles',
            'env_S3Path_PrivateFilesInvoice',
            'env_S3Path_PrivateFilesAirEmployee',
            'env_FlightStatsAPIURLPrefix_Status',
            'env_FlightStatsAPIURLPrefix_Schedule',
            'env_OmnivoreAPIURLPrefix',
            'env_AuthyPhoneStartURL',
            'env_AuthyPhoneCheckURL',
            'env_OneSignalAddDeviceURL',
            'env_OneSignalNotificationsURL',
            'env_SlackPingAPIURLPrefix',
            'jsonGateMap',
            'jsonT2TMap',
            'jsonGateCords',
            'directions_oneStepInMiles',
            'directions_secondsPerStep',
            'directions_factorMultiplier',
            'directions_assetsLocation',
            'env_SlackWH_orderHelp',
            'env_IronMQConfig',

            'env_workerQueueConsumerName',
            'env_workerQueueConsumerDeadLetterName',
            'env_workerQueueMidPriorityAsynchConsumerName',
            'env_workerQueueDeliveryName',
            'env_workerQueueDeliveryDeadLetterName',
            'env_QueueType',
        ];
    }
}
