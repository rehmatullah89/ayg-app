<?php
namespace App\Background\Repositories;

use App\Background\Entities\PingLog;
use App\Background\Entities\RetailerPingLog;

/**
 * Class PingLogMysqlRepository
 * @package App\Background\Repositories
 */
class PingLogLocalStorageRepository extends LocalStorageRepository implements PingLogRepositoryInterface
{

    const DELIVERY_PING_SHORT = 'p';
    const RETAILER_LOGIN_SHORT = '1';
    const RETAILER_LOGOUT_SHORT = 'o';
    const CONNECT_FAILURE_SHORT = 'f';
    const DELIVERY_ACTIVATE_SHORT = 'a';
    const DELIVERY_DEACTIVATE_SHORT = 'd';
    const PING_LOG_DIRECTORY = 'ping_logs';
    const OBJECT_TYPE_DELIVERY = 'delivery';
    const OBJECT_TYPE_RETAILER = 'retailer';
    const PREFIX_SEPACER = '_';

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryPing($slackUsername, $timestamp): void
    {
        $pingLog = new PingLog($slackUsername, $timestamp, self::OBJECT_TYPE_DELIVERY, self::DELIVERY_PING_SHORT);
        $this->store(self::PING_LOG_DIRECTORY, $pingLog);
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryActivated($slackUsername, $timestamp): void
    {
        $pingLog = new PingLog($slackUsername, $timestamp, self::OBJECT_TYPE_DELIVERY, self::DELIVERY_ACTIVATE_SHORT);
        $this->store(self::PING_LOG_DIRECTORY, $pingLog);
    }

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryDeactivated($slackUsername, $timestamp): void
    {
        $pingLog = new PingLog($slackUsername, $timestamp, self::OBJECT_TYPE_DELIVERY, self::DELIVERY_DEACTIVATE_SHORT);
        $this->store(self::PING_LOG_DIRECTORY, $pingLog);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerLogin($retailerUniqueId, $timestamp): void
    {
        $pingLog = new PingLog($retailerUniqueId, $timestamp, self::OBJECT_TYPE_RETAILER, self::RETAILER_LOGIN_SHORT);
        $this->store(self::PING_LOG_DIRECTORY, $pingLog);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerLogout($retailerUniqueId, $timestamp): void
    {
        $pingLog = new PingLog($retailerUniqueId, $timestamp, self::OBJECT_TYPE_RETAILER, self::RETAILER_LOGOUT_SHORT);
        $this->store(self::PING_LOG_DIRECTORY, $pingLog);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerPing(string $retailerUniqueId, int $timestamp): void
    {
        $pingLog = new PingLog($retailerUniqueId, $timestamp, self::OBJECT_TYPE_RETAILER, self::DELIVERY_PING_SHORT);

        $prefix = self::OBJECT_TYPE_RETAILER . self::PREFIX_SEPACER . $retailerUniqueId . self::PREFIX_SEPACER;
        $this->store(self::PING_LOG_DIRECTORY, $pingLog, $prefix);
    }

    /**
     * @param $objectId
     */
    public function logWebsiteDownload($objectId): void
    {
        // TODO: Implement logWebsiteDownload() method.
    }

    /**
     * @param $objectId
     */
    public function logWebsiteRatingClick($objectId): void
    {
        // TODO: Implement logWebsiteRatingClick() method.
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerConnectFailure($retailerUniqueId, $timestamp): void
    {
        $pingLog = new PingLog($retailerUniqueId, $timestamp, self::OBJECT_TYPE_RETAILER, self::CONNECT_FAILURE_SHORT);
        $this->store(self::PING_LOG_DIRECTORY, $pingLog);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     * @return RetailerPingLog|null
     */
    public function getFirstRetailerPingAfterGivenTimestamp($retailerUniqueId, $timestamp): void
    {
        $pingLog = new PingLog($retailerUniqueId, $timestamp, self::OBJECT_TYPE_RETAILER, self::DELIVERY_PING_SHORT);
        $this->store(self::PING_LOG_DIRECTORY, $pingLog);
    }

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     * @return RetailerPingLog|null
     */
    public function getLastRetailerPingBeforeGivenTimestamp($retailerUniqueId, $timestamp): void
    {
        // TODO: Implement getLastRetailerPingBeforeGivenTimestamp() method.
    }

    /**
     * @param $retailerUniqueId
     * @param $startTimestamp
     * @param $endTimestamp
     * @return int
     */
    public function countRetailerPingsBetweenTimestamps($retailerUniqueId, $startTimestamp, $endTimestamp): void
    {
        // TODO: Implement countRetailerPingsBetweenTimestamps() method.
    }

    /**
     * @param $deliveruId
     * @param $startTimestamp
     * @param $endTimestamp
     * @return Array|null
     */
    public function getDeliveryPingBetweenTimestamp($deliveryId, $startTimestamp, $endTimestamp): void
    {
        // TODO: Implement getDeliveryPingBetweenTimestamp() method.
    }

}
