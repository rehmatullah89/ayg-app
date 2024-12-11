<?php
namespace App\Consumer\Services;

use App\Consumer\Repositories\DeliveryAssignmentCacheRepository;
use App\Consumer\Repositories\DeliveryAssignmentParseRepository;
use App\Consumer\Repositories\OrderCacheRepository;
use App\Consumer\Repositories\OrderDeliveryPlanCacheRepository;
use App\Consumer\Repositories\OrderDeliveryPlanParseRepository;
use App\Consumer\Repositories\OrderParseRepository;
use App\Consumer\Repositories\OrderRatingCacheRepository;
use App\Consumer\Repositories\OrderRatingParseRepository;
use App\Consumer\Repositories\TerminalGateMapRetailerRestrictionsCacheRepository;
use App\Consumer\Repositories\TerminalGateMapRetailerRestrictionsParseRepository;
use App\Consumer\Repositories\VouchersCacheRepository;
use App\Consumer\Repositories\VouchersParseRepository;
use Predis\Client;


/**
 * Class OrderServiceFactory
 * @package App\Consumer\Services
 */
class OrderServiceFactory extends Service
{
    /**
     * @param CacheService $cacheService
     * @return OrderService
     */
    public static function create(CacheService $cacheService)
    {
        return new OrderService(
            new OrderCacheRepository(
                new OrderParseRepository(),
                $cacheService
            ),
            new OrderRatingCacheRepository(
                new OrderRatingParseRepository(),
                $cacheService
            ),
            new DeliveryAssignmentCacheRepository(
                new DeliveryAssignmentParseRepository(),
                $cacheService
            ),
            new VouchersCacheRepository(
                new VouchersParseRepository(),
                $cacheService
            ),
            new OrderDeliveryPlanCacheRepository(
                new OrderDeliveryPlanParseRepository(),
                $cacheService
            ),
            new DeliveryAvailabilityService(
                new TerminalGateMapRetailerRestrictionsCacheRepository(
                    new TerminalGateMapRetailerRestrictionsParseRepository(),
                    $cacheService
                )
            ),
            new PickupAvailabilityService(
                new TerminalGateMapRetailerRestrictionsCacheRepository(
                    new TerminalGateMapRetailerRestrictionsParseRepository(),
                    $cacheService
                )
            )
        );
    }
}
