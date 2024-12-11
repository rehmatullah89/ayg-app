<?php
namespace App\Tablet\Services;

use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Repositories\OrderCacheRepository;
use App\Tablet\Repositories\OrderParseRepository;
use App\Tablet\Repositories\OrderTabletHelpRequestsCacheRepositoryInterface;
use App\Tablet\Repositories\OrderTabletHelpRequestsParseRepository;
use App\Tablet\Repositories\OrderModifierCacheRepository;
use App\Tablet\Repositories\OrderModifierParseRepository;
use App\Tablet\Repositories\RetailerItemModifierCacheRepository;
use App\Tablet\Repositories\RetailerItemModifierOptionCacheRepository;
use App\Tablet\Repositories\RetailerItemModifierOptionParseRepository;
use App\Tablet\Repositories\RetailerItemModifierParseRepository;


/**
 * Class OrderServiceFactory
 * @package App\Tablet\Services
 *
 * Creates instance of OrderService
 */
class OrderServiceFactory extends Service
{
    /**
     * @param CacheService $cacheService
     * @return OrderService
     */
    public static function create(CacheService $cacheService)
    {
        /*
        $cache = false;
        if (!$cache) {
            return new OrderService(
                    new RetailerItemModifierParseRepository(),
                    new RetailerItemModifierOptionParseRepository(),
                    new OrderParseRepository(),
                    new OrderModifierParseRepository(),
                    new OrderTabletHelpRequestsParseRepository(),
                new SlackOrderHelpRequestService(
                    ConfigHelper::get('env_SlackWH_orderHelp'),
                    'env_SlackWH_orderHelp'
                ),
                QueueServiceFactory::create(),
                $cacheService,
                new LoggingService()
            );
        }
        */

        return new OrderService(
            new RetailerItemModifierCacheRepository(
                new RetailerItemModifierParseRepository(),
                $cacheService
            ),
            new RetailerItemModifierOptionCacheRepository(
                new RetailerItemModifierOptionParseRepository(),
                $cacheService
            ),
            new OrderCacheRepository(
                new OrderParseRepository(),
                $cacheService
            ),
            new OrderModifierCacheRepository(
                new OrderModifierParseRepository(),
                $cacheService
            ),
            new OrderTabletHelpRequestsCacheRepositoryInterface(
                new OrderTabletHelpRequestsParseRepository(),
                $cacheService
            ),
            new SlackOrderHelpRequestService(
                ConfigHelper::get('env_SlackWH_orderHelp'),
                'env_SlackWH_orderHelp'
            ),
            QueueServiceFactory::create(),
            $cacheService,
            new LoggingService()
        );
    }
}
