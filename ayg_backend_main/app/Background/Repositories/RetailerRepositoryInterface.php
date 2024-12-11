<?php
namespace App\Background\Repositories;

use App\Background\Entities\RetailerList;

interface RetailerRepositoryInterface
{
    public function getRetailersByUniqueIdArray(array $retailerUniqueIdArray): RetailerList;

    public function getAllActiveRetailers(): RetailerList;
}
