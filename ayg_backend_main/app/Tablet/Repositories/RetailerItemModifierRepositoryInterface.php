<?php

namespace App\Tablet\Repositories;
use App\Tablet\Entities\RetailerItemModifier;

/**
 * Interface RetailerItemModifierRepositoryInterface
 * @package App\Tablet\Repositories
 */
interface RetailerItemModifierRepositoryInterface
{

    /**
     * @param array $uniqueIdList
     * @return RetailerItemModifier[]
     *
     * Get Retailer Item Modifiers List by uniqueId List
     */
    public function getListByUniqueIdList(array $uniqueIdList);
}