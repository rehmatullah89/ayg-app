<?php
namespace App\Background\Services;


use App\Background\Helpers\ConfigHelper;
use App\Background\Repositories\OrderCacheRepository;
use App\Background\Repositories\OrderParseRepository;
use App\Background\Repositories\RetailerCacheRepository;
use App\Background\Repositories\RetailerParseRepository;
use App\Background\Repositories\RetailerPartnerCacheRepository;
use App\Background\Repositories\RetailerPartnerParseRepository;
use App\Background\Repositories\RetailerPOSConfigCacheRepository;
use App\Background\Repositories\RetailerPOSConfigParseRepository;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class PartnerIntegrationServiceFactory
{
    public static function create(): PartnerIntegrationService
    {
        $cacheService = CacheServiceFactory::create();
        return new PartnerIntegrationService(
            new GrabIntegrationService(
                ConfigHelper::get('env_GrabEmail'),
                ConfigHelper::get('env_GrabMainApiUrl'),
                ConfigHelper::get('env_GrabSecretKey'),
                new S3Service(
                    new S3Client([
                        'version' => 'latest',
                        'region' => ConfigHelper::get('env_MenuUploadAWSS3Region'),
                        'credentials' => new Credentials(
                            ConfigHelper::get('env_MenuUploadAWSS3AccessId'),
                            ConfigHelper::get('env_MenuUploadAWSS3Secret')
                        )
                    ]),
                    ConfigHelper::get('env_MenuUploadAWSS3Bucket')
                ),
                new RetailerPartnerCacheRepository(
                    new RetailerPartnerParseRepository(),
                    $cacheService
                ),
                new RetailerPOSConfigCacheRepository(
                    new RetailerPOSConfigParseRepository(),
                    $cacheService
                ),
                new RetailerCacheRepository(
                    new RetailerParseRepository(),
                    $cacheService
                ),
                new OrderCacheRepository(
                    new OrderParseRepository(),
                    $cacheService
                ),
                new SlackService(ConfigHelper::get('env_GrabSlackNotificationChannelUrl')),
                $cacheService,
                QueueServiceFactory::createEmailQueueService()
            ),
            $cacheService
        );
    }
}
