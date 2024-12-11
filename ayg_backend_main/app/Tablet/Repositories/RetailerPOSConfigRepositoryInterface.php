<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\Retailer;

interface RetailerPOSConfigRepositoryInterface
{
    /**
     * @param Retailer[] $retailers
     * @param int $timestamp
     * @return void
     *
     * save last successful ping timestamp
     */
    public function setLastSuccessfulPingTimestampByRetailers($retailers, $timestamp);

    /**
     * @param array $retailerUniqueId
     * @param int $timestamp
     * @return void
     *
     * save last successful ping timestamp
     */
    public static function setLastSuccessfulPingTimestampByRetailersStatic($retailerUniqueIds, $timestamp);
}