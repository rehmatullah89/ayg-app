<?php

namespace App\Tablet\Repositories;
use App\Tablet\Entities\RetailerItemModifierOption;

/**
 * Interface RetailerItemModifierOptionRepositoryInterface
 * @package App\Tablet\Repositories
 */
interface RetailerItemModifierOptionRepositoryInterface
{
    /**
     * @param array $uniqueIdList
     * @return RetailerItemModifierOption[]
     *
     * Get Retailer Item Modifiers List by uniqueId
     */
    public function getListByUniqueIdList(array $uniqueIdList);
}