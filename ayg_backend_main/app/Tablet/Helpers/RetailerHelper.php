<?php
namespace App\Tablet\Helpers;

use App\Tablet\Entities\Retailer;

/**
 * Class RetailerHelper
 * @package App\Tablet\Helpers
 */
class RetailerHelper
{
    /**
     * @param Retailer[] $retailers
     * @return array
     */
    public static function retailersListIntoRetailerIdsList(array $retailers)
    {
        $return = [];
        foreach ($retailers as $retailer) {
            $return[] = $retailer->getId();
        }
        return $return;
    }
}