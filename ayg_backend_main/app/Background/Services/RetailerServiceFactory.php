<?php
namespace App\Background\Services;


use App\Background\Helpers\ConfigHelper;
use App\Background\Repositories\RetailerItemParseRepository;
use App\Background\Repositories\RetailerParseRepository;

class RetailerServiceFactory
{
    public static function create(): RetailerService
    {
        $cacheService = CacheServiceFactory::create();
        return new RetailerService(
            new SlackService(ConfigHelper::get('env_data_edit_notification_slack_webhook_url')),
            $cacheService,
            new RetailerParseRepository(),
            new RetailerItemParseRepository()
        );
    }
}
