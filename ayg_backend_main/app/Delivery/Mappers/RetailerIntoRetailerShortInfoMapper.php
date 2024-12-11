<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\OrderShortInfo;
use App\Delivery\Entities\Retailer;
use App\Delivery\Entities\RetailerShortInfo;
use App\Delivery\Entities\TerminalGateMapShortInfo;

class RetailerIntoRetailerShortInfoMapper
{
    public static function map(Retailer $retailer): RetailerShortInfo
    {
        return new RetailerShortInfo(
            $retailer->getRetailerName(),
            $retailer->getImageLogo(),
            TerminalGateMapIntoTerminalShortMapShortInfoMapper::map($retailer->getLocation())
        );
    }
}
