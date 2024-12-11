<?php

namespace App\Tablet\Services;

use App\Tablet\Entities\CloseEarlyData;
use App\Tablet\Entities\Retailer;
use App\Tablet\Entities\RetailerAppConfig;
use App\Tablet\Entities\User;
use App\Tablet\Exceptions\ConfigKeyNotFoundException;
use App\Tablet\Exceptions\RetailerUserNotConfiguredCorrectlyException;
use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Helpers\QueueMessageHelper;
use App\Tablet\Repositories\RetailerPOSConfigRepositoryInterface;
use App\Tablet\Repositories\RetailerRepositoryInterface;


/**
 * Class RetailerService
 * @package App\Tablet\Services
 */
class RetailerService extends Service
{
    /**
     * @var RetailerRepositoryInterface
     */
    private $retailerRepository;
    /**
     * @var RetailerPOSConfigRepositoryInterface
     */
    private $retailerPOSConfigRepository;
    /**
     * @var QueueServiceInterface
     */
    private $queueService;
    /**
     * @var QueueServiceInterface
     */
    private $queueMidPriorityAsynchService;

    /**
     * RetailerService constructor.
     * @param RetailerRepositoryInterface $retailerRepository
     * @param RetailerPOSConfigRepositoryInterface $retailerPOSConfigRepository
     */
    public function __construct(
        RetailerRepositoryInterface $retailerRepository,
        RetailerPOSConfigRepositoryInterface $retailerPOSConfigRepository)
    {
        $this->retailerRepository = $retailerRepository;
        $this->retailerPOSConfigRepository = $retailerPOSConfigRepository;
        $this->queueService = QueueServiceFactory::create();
        $this->queueMidPriorityAsynchService = QueueServiceFactory::createMidPriorityAsynch();
    }

    /**
     * @param $userId
     * @return Retailer[]
     *
     * Gets Retailers by Tablet User Id
     */
    public function getRetailerByTabletUserId($userId)
    {
        return $this->retailerRepository->getByTabletUserId($userId);
    }

    /**
     * @param Retailer[] $retailers
     */
    public function setLastSuccessfulPingForRetailers($retailers)
    {
        $time = time();
        /*
        // Moved to background job, when RDS log is processed
        $this->retailerPOSConfigRepository->setLastSuccessfulPingTimestampByRetailers($retailers, strval($time));
        */
       
        foreach ($retailers as $retailer) {
            /*
            // update Ping Cache
            // Moved to background job, when RDS log is processed
            setRetailerPingTimestamp($retailer->getUniqueId(), $time);
            */
           
            // send logs
            //if (intval(date('s')) >= 30){
                $logRetailerPingMessage = QueueMessageHelper::getLogRetailerPingMessage($retailer->getUniqueId(), $time);
                $this->queueMidPriorityAsynchService->sendMessage($logRetailerPingMessage, 0);
            //}
        }
    }

    /**
     * @param User $user
     * @param Retailer[] $retailers
     * @return CloseEarlyData
     * @throws RetailerUserNotConfiguredCorrectlyException
     */
    public function getClosedEarlyData(User $user, array $retailers)
    {
        // one retailer
        if ($user->getRetailerUserType() == User::USER_TYPE_RETAILER) {
            $retailer = $retailers[0];
            $isCloseEarlyRequested = isRetailerCloseEarlyForNewOrders($retailer->getUniqueId());
            $isClosedEarly = isRetailerClosedEarly($retailer->getUniqueId());
        } elseif ($user->getRetailerUserType() == User::USER_TYPE_OPS_TEAM) {
            // multiple retailers
            $isCloseEarlyRequested = false;
            $isClosedEarly = false;
        } else {
            throw new RetailerUserNotConfiguredCorrectlyException('User ' . $user->getId() . ' has not correct RetailerUserType value');
        }

        return new CloseEarlyData($isCloseEarlyRequested, $isClosedEarly);
    }

    /**
     * @return RetailerAppConfig Gets retailer ping status
     * @throws RetailerUserNotConfiguredCorrectlyException
     *
     * Gets retailer ping status
     */
    public function getRetailerAppConfig()
    {
        // now all data are hardcoded
        try {
            $pingInterval = intval(ConfigHelper::get('env_TabletAppDefaultPingIntervalInSecs'));
        } catch (ConfigKeyNotFoundException $e) {
            $pingInterval = null;
        }

        try {
            $notificationSoundUrl = ConfigHelper::get('env_TabletAppDefaultNotificationSoundUrl');
        } catch (ConfigKeyNotFoundException $e) {
            $notificationSoundUrl = null;
        }

        try {
            $notificationVibrateUsage = boolval(ConfigHelper::get('env_TabletAppDefaultVibrateUsage'));
        } catch (ConfigKeyNotFoundException $e) {
            $notificationVibrateUsage = null;
        }

        try {
            $batteryCheckIntervalInSecs = intval(ConfigHelper::get('env_TabletBatteryCheckIntervalInSecs'));
        } catch (ConfigKeyNotFoundException $e) {
            $batteryCheckIntervalInSecs = null;
        }


        return new RetailerAppConfig(
            $pingInterval,
            $notificationSoundUrl,
            $notificationVibrateUsage,
            $batteryCheckIntervalInSecs
        );
    }
}
