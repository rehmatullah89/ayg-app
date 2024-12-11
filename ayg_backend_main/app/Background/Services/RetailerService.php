<?php
namespace App\Background\Services;

use App\Background\Entities\Retailer;
use App\Background\Helpers\ConfigHelper;
use App\Background\Repositories\RetailerItemRepositoryInterface;
use App\Background\Repositories\RetailerRepositoryInterface;

class RetailerService
{

    private $cacheService;
    /**
     * @var RetailerRepositoryInterface
     */
    private $retailerRepository;
    /**
     * @var RetailerItemRepositoryInterface
     */
    private $retailerItemRepository;
    /**
     * @var SlackService
     */
    private $slackService;

    public function __construct(
        SlackService $slackService,
        CacheService $cacheService,
        RetailerRepositoryInterface $retailerRepository,
        RetailerItemRepositoryInterface $retailerItemRepository

    ) {
        $this->cacheService = $cacheService;
        $this->retailerRepository = $retailerRepository;
        $this->retailerItemRepository = $retailerItemRepository;
        $this->slackService = $slackService;
    }

    /**
     * Iterates through all active retailers,
     * checks if there is at least one active item,
     * if it is clear "DO NOT SHOW" cache key for that retailer
     * if is is not - add "DO NOT SHOW" cache key for that retailer
     *
     * Notify on slack about no items
     */
    public function updateCacheForItemExistenceInAllRetailers()
    {
        $retailers = $this->retailerRepository->getAllActiveRetailers();
        /** @var Retailer $retailer */
        foreach ($retailers as $retailer) {
            $count = $this->retailerItemRepository->getActiveItemsCountByRetailerUniqueId($retailer->getUniqueId());

            if ($count == 0) {
                $currentTimestamp = (new \DateTime('now'))->getTimestamp();
                // there is no items, check when last time there was no items,

                $shouldWeNotify = true;
                $lastNotificationTimestamp = $this->cacheService->getDoNotDisplayRetailerLastNotificationTimestampCache($retailer->getUniqueId());
                if (
                    $lastNotificationTimestamp !== null &&
                    ($currentTimestamp - $lastNotificationTimestamp) < ConfigHelper::get('env_PingRetailerMenuExistenceNotificationIntervalInSecs')
                ) {
                    $shouldWeNotify = false;
                }

                $this->cacheService->setDoNotDisplayRetailerCache($retailer->getUniqueId(), $currentTimestamp);

                if ($shouldWeNotify) {
                    $this->slackService->sendMessage(':bangbang: Retailer ' . $retailer->getRetailerName() . ' (' . $retailer->getAirportIataCode() . ') has no active items.');
                    $this->cacheService->setDoNotDisplayRetailerLastNotificationTimestampCache(
                        $retailer->getUniqueId(),
                        $currentTimestamp);
                }
            }else{
                $this->cacheService->clearDoNotDisplayRetailerCache($retailer->getUniqueId());
                $this->cacheService->clearDoNotDisplayRetailerLastNotificationTimestampCache($retailer->getUniqueId());
            }
        }
    }
}
