<?php
namespace App\Background\Repositories;

use App\Background\Entities\RetailerList;

interface RetailerPOSConfigRepositoryInterface
{
    public function addNotExistingRetailerPOSConfigByRetailerList(RetailerList $retailerList);
}
