<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\Retailer;
use App\Tablet\Helpers\RetailerHelper;
use Parse\ParseQuery;


/**
 * Class RetailerPOSConfigParseRepository
 * @package App\Tablet\Repositories
 */
class RetailerPOSConfigParseRepository extends ParseRepository implements RetailerPOSConfigRepositoryInterface
{
    /**
     * @param Retailer[] $retailers
     * @param int $timestamp
     * @return void
     *
     *  save last successful ping timestamp
     */
    public function setLastSuccessfulPingTimestampByRetailers($retailers, $timestamp)
    {
        $retailerIdList = RetailerHelper::retailersListIntoRetailerIdsList($retailers);

        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseRetailerPOSConfigQuery = new ParseQuery('RetailerPOSConfig');
        $parseRetailerPOSConfigQuery->matchesQuery('retailer', $parseRetailersQuery);
        $retailerPOSConfigs = $parseRetailerPOSConfigQuery->find(false, true);

        if(is_bool($retailerPOSConfigs)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 71", 1);
        }

        foreach ($retailerPOSConfigs as $retailerPOSConfig) {
            $retailerPOSConfig->set('lastSuccessfulPingTimestamp', strval($timestamp));
            $retailerPOSConfig->save();
        }
    }

    public static function setLastSuccessfulPingTimestampByRetailersStatic($retailersUniqueIds, $timestamp)
    {
        /*
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('uniqueId', $retailersUniqueIds);

        $parseRetailerPOSConfigQuery = new ParseQuery('RetailerPOSConfig');
        $parseRetailerPOSConfigQuery->matchesQuery('retailer', $parseRetailersQuery);
        $parseRetailerPOSConfigQuery->includeKey('retailer');
        $retailerPOSConfigs = $parseRetailerPOSConfigQuery->find();

        foreach ($retailerPOSConfigs as $retailerPOSConfig) {

            // Set Ping cache
            setRetailerPingTimestamp($retailerPOSConfig->get('retailer')->get('uniqueId'), $timestamp);

            // Set in DB
            $retailerPOSConfig->set('lastSuccessfulPingTimestamp', strval($timestamp));
            $retailerPOSConfig->save();
        }
        */

        foreach ($retailersUniqueIds as $uniqueId) {

            // Set Ping cache
            setRetailerPingTimestamp($uniqueId, $timestamp);
        }
    }
}