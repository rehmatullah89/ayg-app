<?php
namespace App\Background\Repositories;


use App\Background\Entities\Retailer;
use App\Background\Entities\RetailerList;
use Parse\ParseObject;
use Parse\ParseQuery;

class RetailerPOSConfigParseRepository extends ParseRepository implements RetailerPOSConfigRepositoryInterface
{
    public function addNotExistingRetailerPOSConfigByRetailerList(
        RetailerList $retailerList
    ) {
        /** @var Retailer $retailer */
        foreach ($retailerList as $retailer) {

            $retailerQuery = new ParseQuery('Retailers');
            $retailerQuery->equalTo("objectId", $retailer->getId());
            $retailerParseObject = $retailerQuery->first();

            // retailer not yet created
            if (is_array($retailerParseObject) && empty($retailerParseObject)) {
                continue;
            }

            $parseQuery = new ParseQuery('RetailerPOSConfig');
            $parseQuery->equalTo('retailer', $retailerParseObject);
            $parseQuery->limit(1);
            $parseRetailerPOSConfig = $parseQuery->find();
            if (count($parseRetailerPOSConfig) == 0) {
                $parseObject = new ParseObject('RetailerPOSConfig');
                $parseObject->set('retailer', $retailerParseObject);
                $parseObject->set('comments', $retailer->getNameForPOSConfig());
                $parseObject->set('continousPingCheck', false);
                $parseObject->set('avgPrepTimeInSeconds', 600);
                $parseObject->set('pushOrdersToPOS', true);
                $parseObject->save();
            }
        }
    }
}
