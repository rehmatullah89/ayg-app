<?php
namespace App\Background\Repositories;

use App\Background\Entities\RetailerPingLog;

/**
 * Interface PingLogRepositoryInterface
 * @package App\Background\Repositories
 */
interface PingLogRepositoryInterface
{
    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryPing(string $slackUsername, int $timestamp): void;

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryActivated(string $slackUsername, int $timestamp): void;

    /**
     * @param $slackUsername
     * @param $timestamp
     */
    public function logDeliveryDeactivated(string $slackUsername, int $timestamp): void;

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerPing(string $retailerUniqueId, int $timestamp): void;


    /**
     * @param $objectId
     */
    public function logWebsiteDownload(string $objectId): void;

    /**
     * @param $objectId
     */
    public function logWebsiteRatingClick(string $objectId): void;

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     */
    public function logRetailerConnectFailure(string $retailerUniqueId, int $timestamp): void;

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     * @return RetailerPingLog|null
     */
    public function getFirstRetailerPingAfterGivenTimestamp(string $retailerUniqueId, int $timestamp): void;

    /**
     * @param $retailerUniqueId
     * @param $timestamp
     * @return RetailerPingLog|null
     */
    public function getLastRetailerPingBeforeGivenTimestamp(string $retailerUniqueId, int $timestamp): void;

    /**
     * @param $retailerUniqueId
     * @param $startTimestamp
     * @param $endTimestamp
     * @return int
     */
    public function countRetailerPingsBetweenTimestamps(string $retailerUniqueId, int $startTimestamp, int $endTimestamp): void;

    /**
     * @param $deliveruId
     * @param $startTimestamp
     * @param $endTimestamp
     * @return Array|null
     */
    public function getDeliveryPingBetweenTimestamp(string $deliveryId, int $startTimestamp, int $endTimestamp): void;

    /**
    * @param $retailerUniqueId
    * @param $timestamp
    */
    public function logRetailerLogin(string $retailerUniqueId, int $timestamp): void;
}