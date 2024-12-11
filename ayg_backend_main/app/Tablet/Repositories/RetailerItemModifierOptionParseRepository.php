<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\RetailerItemModifierOption;
use App\Tablet\Mappers\ParseRetailerItemModifierOptionIntoRetailerItemModifierMapperOption;
use Parse\ParseQuery;

/**
 * Class RetailerItemModifierOptionParseRepository
 * @package App\Tablet\Repositories
 */
class RetailerItemModifierOptionParseRepository extends ParseRepository implements RetailerItemModifierOptionRepositoryInterface
{
    /**
     * @param array $uniqueIdList
     * @return RetailerItemModifierOption[]
     *
     *  Get Retailer Item Modifiers Option List by uniqueId
     */
    public function getListByUniqueIdList(array $uniqueIdList)
    {
        $retailerItemModifierOptionsQuery = new ParseQuery('RetailerItemModifierOptions');
        $retailerItemModifierOptionsQuery->containedIn('uniqueId', $uniqueIdList);
        $retailerItemModifierOptionsQuery->equalTo('isActive', true);
        $retailerItemModifierOptionsList = $retailerItemModifierOptionsQuery->find(false, true);

        if(is_bool($retailerItemModifierOptionsList)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 41", 1);
        }

        if (empty($retailerItemModifierOptionsList)) {
            return [];
        }

        $return = [];
        foreach ($retailerItemModifierOptionsList as $retailerItemModifierOption) {
            $return[] = ParseRetailerItemModifierOptionIntoRetailerItemModifierMapperOption::map($retailerItemModifierOption);
        }

        return $return;
    }

}
