<?php
namespace App\Background\Repositories;

use Parse\ParseQuery;

class RetailerItemParseRepository extends ParseRepository implements RetailerItemRepositoryInterface
{
    public function getActiveItemsCountByRetailerUniqueId(string $retailerUniqueId): int
    {
        $parseQuery = new ParseQuery('RetailerItems');
        $parseQuery->equalTo('uniqueRetailerId', $retailerUniqueId);
        $parseQuery->equalTo('isActive', true);
        return $parseQuery->count();
    }
}
