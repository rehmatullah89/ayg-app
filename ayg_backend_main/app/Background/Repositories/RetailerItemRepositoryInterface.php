<?php
namespace App\Background\Repositories;

interface RetailerItemRepositoryInterface{

    public function getActiveItemsCountByRetailerUniqueId(string $retailerUniqueId):int;
}
