<?php
namespace App\Consumer\Services;


use App\Consumer\Repositories\RetailerItemPropertiesCacheRepository;
use App\Consumer\Repositories\RetailerItemPropertiesParseRepository;

class RetailerItemTimeRestrictionServiceFactory extends Service
{
    public static function create(): RetailerItemTimeRestrictionService
    {
        return new RetailerItemTimeRestrictionService(new RetailerItemPropertiesCacheRepository(
            new RetailerItemPropertiesParseRepository(),
            CacheServiceFactory::create()
        ));
    }
}
