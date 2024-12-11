<?php
namespace App\Background\Helpers;

use App\Background\Exceptions\ConfigKeyNotFoundException;

/**
 * Class CacheHelper
 * @package App\Consumer\Helpers
 */
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


    private static function possibleKeys()
    {
        return [
            'env_GrabEmail',
            'env_GrabStoreWaypointID',
            'env_GrabSecretKey',
            'env_GrabSlackNotificationChannelUrl',
            'env_GrabMainApiUrl',

            'env_MenuUploadAWSS3Region',
            'env_MenuUploadAWSS3AccessId',
            'env_MenuUploadAWSS3Secret',
            'env_MenuUploadAWSS3Bucket',

            'env_RabbitMQConsumerDataEdit',
            'env_RabbitMQConsumerRetailersEdit',

            'env_PingPartnerRetailerIntervalInSecs_Grab',
            'env_PingPartnerRetailerUpdateIntervalInSecs_Grab',
            'env_PingPartnerOrdersIntervalInSecs_Grab',

            'env_workerQueueConsumerName',

            'env_PingRetailerMenuExistenceIntervalInSecs',
            'env_PingRetailerMenuExistenceNotificationIntervalInSecs',

            'env_data_edit_notification_slack_webhook_url'
        ];
    }
}
