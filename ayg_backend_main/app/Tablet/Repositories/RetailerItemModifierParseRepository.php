<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\RetailerItemModifier;
use App\Tablet\Mappers\ParseRetailerItemModifierIntoRetailerItemModifierMapper;
use Parse\ParseQuery;

/**
 * Class RetailerItemModifierParseRepository
 * @package App\Tablet\Repositories
 */
class RetailerItemModifierParseRepository extends ParseRepository implements RetailerItemModifierRepositoryInterface
{
    /**
     * @param array $uniqueIdList
     * @return RetailerItemModifier[]
     *
     *  Get Retailer Item Modifiers List by uniqueId
     */
    public function getListByUniqueIdList(array $uniqueIdList)
    {
        $retailerItemModifierQuery = new ParseQuery('RetailerItemModifiers');
        $retailerItemModifierQuery->containedIn('uniqueId', $uniqueIdList);
        $retailerItemModifierQuery->equalTo('isActive', true);
        $retailerItemModifierList = $retailerItemModifierQuery->find(false, true);

        if(is_bool($retailerItemModifierList)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 51", 1);
        }

        if (empty($retailerItemModifierList)) {
            return [];
        }

        $return = [];
        foreach ($retailerItemModifierList as $retailerItemModifier) {
            $return[] = ParseRetailerItemModifierIntoRetailerItemModifierMapper::map($retailerItemModifier);
        }

        return $return;
    }
}
