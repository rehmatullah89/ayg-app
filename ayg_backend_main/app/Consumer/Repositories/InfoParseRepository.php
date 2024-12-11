<?php

namespace App\Consumer\Repositories;

use Parse\ParseQuery;

class InfoParseRepository implements InfoRepositoryInterface
{
    public function getAirLines(): array
    {
        $objParseQueryAirlines = parseExecuteQuery(array(), "Airlines", "", "topRanked");

           $i = 0;
           $responseArray = [];
           foreach ($objParseQueryAirlines as $airline) {

               $responseArray[$i] = $airline->getAllKeys();
               $responseArray[$i]["objectId"] = $airline->getObjectId();
               $i++;
           }

           return $responseArray;
    }

}