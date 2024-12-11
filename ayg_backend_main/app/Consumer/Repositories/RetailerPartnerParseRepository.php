<?php

namespace App\Consumer\Repositories;


use App\Consumer\Entities\RetailerPartner;
use App\Consumer\Mappers\ParseRetailerPartnerIntoRetailerPartnerMapper;
use Parse\ParseQuery;

class RetailerPartnerParseRepository extends ParseRepository implements RetailerPartnerRepositoryInterface
{

    public function getPartnerNameByRetailerId($retailerId):?RetailerPartner
    {
        $retailerInnerQuery = new ParseQuery('Retailers');
        $retailerInnerQuery->equalTo('objectId', $retailerId);
        $parseQuery = new ParseQuery('RetailerPartners');
        $parseQuery->matchesQuery('retailer', $retailerInnerQuery);
        $parseQuery->equalTo('isActive', true);
        $parseQuery->limit(1);
        $parseRetailerPartners = $parseQuery->find();
        if (empty($parseRetailerPartners)) {
            return null;
        }

        return ParseRetailerPartnerIntoRetailerPartnerMapper::map($parseRetailerPartners[0]);

    }

    public function getRetailerPartnerByRetailerUniqueId($retailerUniqueId):?RetailerPartner
    {
        $parseQuery = new ParseQuery('RetailerPartners');
        $parseQuery->equalTo('retailerUniqueId', $retailerUniqueId);
        $parseQuery->equalTo('isActive', true);
        $parseQuery->limit(1);
        $parseRetailerPartners = $parseQuery->find();
        if (empty($parseRetailerPartners)) {
            return null;
        }

        return ParseRetailerPartnerIntoRetailerPartnerMapper::map($parseRetailerPartners[0]);
    }
}

