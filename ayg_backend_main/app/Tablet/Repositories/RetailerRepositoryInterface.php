<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\Retailer;

interface RetailerRepositoryInterface
{
    /**
     * @param $userId
     * @return Retailer[]
     *
     * get Retailer Entity List by Id
     */
    public function getByTabletUserId($userId);
}