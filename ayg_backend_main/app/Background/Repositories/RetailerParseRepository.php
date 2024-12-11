<?php
namespace App\Background\Repositories;

use App\Background\Entities\RetailerList;
use App\Background\Mappers\ParseRetailerIntoRetailerMapper;
use Parse\ParseQuery;

class RetailerParseRepository extends ParseRepository implements RetailerRepositoryInterface
{
    public function getRetailersByUniqueIdArray(array $retailerUniqueIdArray): RetailerList
    {
        $retailerList = new RetailerList();
        if (empty($retailerUniqueIdArray)) {
            return $retailerList;
        }

        $count = count($retailerUniqueIdArray);
        $blocksAmount = ceil($count / 10);

        // contain in failes when more then 100 items
        for ($i = 0; $i < $blocksAmount; $i++) {
            $array = array_slice($retailerUniqueIdArray, $i * 10, 10);
            $parseQuery = new ParseQuery('Retailers');
            $parseQuery->containedIn('uniqueId', $array);
            $parseRetailers = $parseQuery->find();
            foreach ($parseRetailers as $parseRetailer) {
                $retailerList->addItem(ParseRetailerIntoRetailerMapper::map($parseRetailer));
            }

        }


        return $retailerList;
    }


    public function getAllActiveRetailers(): RetailerList
    {
        $parseQuery = new ParseQuery('Retailers');
        $parseQuery->equalTo('isActive', true);
        $parseQuery->limit(10000000);
        $parseRetailers = $parseQuery->find();

        $retailerList = new RetailerList();

        foreach ($parseRetailers as $parseRetailer) {
            $retailerList->addItem(ParseRetailerIntoRetailerMapper::map($parseRetailer));
        }
        return $retailerList;
    }
}
