<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\Retailer;
use App\Tablet\Mappers\ParseRetailerIntoRetailerMapper;
use App\Tablet\Mappers\ParseTerminalGateMapIntoTerminalGateMapMapper;
use Parse\ParseQuery;

/**
 * Class RetailerParseRepository
 * @package App\Tablet\Repositories
 */
class RetailerParseRepository extends ParseRepository implements RetailerRepositoryInterface
{

    /**
     * @param $userId
     * @return array Retailer
     *
     * uses parse to
     * get Retailer Entity List by UserId
     */
    public function getByTabletUserId($userId)
    {
        $innerQueryGetUser = new ParseQuery('_User');
        $innerQueryGetUser->equalTo('objectId', $userId);
        $query = new ParseQuery('RetailerTabletUsers');
        $query->matchesQuery("tabletUser", $innerQueryGetUser);
        $query->includeKey("retailer");
        $query->includeKey("retailer.location");
        $retailerPOSConfigs = $query->find(false, true);

        if(is_bool($retailerPOSConfigs)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 61", 1);
        }

        $return = [];
        foreach ($retailerPOSConfigs as $retailerPOSConfig) {
            if (empty($retailerPOSConfig)) {
                continue;
            }
            if (empty($retailerPOSConfig->get('retailer'))) {
                continue;
            }

            if ($retailerPOSConfig->get('retailer')->get('location')) {
                $retailerLocation = ParseTerminalGateMapIntoTerminalGateMapMapper::map($retailerPOSConfig->get('retailer')->get('location'));
            } else {
                $retailerLocation = null;
            }

            $retailer = ParseRetailerIntoRetailerMapper::map($retailerPOSConfig->get('retailer'));
            $retailer->setLocation($retailerLocation);

            $return[] = $retailer;
        }

        return $return;
    }
}