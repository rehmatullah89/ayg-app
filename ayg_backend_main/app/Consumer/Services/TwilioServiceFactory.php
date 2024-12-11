<?php
namespace App\Consumer\Services;

use App\Consumer\Helpers\ConfigHelper;

class TwilioServiceFactory extends Service
{
    public static function create()
    {
        return new TwilioService(
            new \Twilio\Rest\Client(ConfigHelper::get('env_TwilioSID'), ConfigHelper::get('env_TwilioToken')),
            ConfigHelper::get('env_TwilioPhoneNumber'),
            ConfigHelper::get('env_AuthyPhoneCheckURL')
        );
    }
}

