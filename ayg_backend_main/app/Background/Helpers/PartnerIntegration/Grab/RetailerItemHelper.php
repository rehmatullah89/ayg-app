<?php
namespace App\Background\Helpers\PartnerIntegration\Grab;


class RetailerItemHelper
{
    public static function getAllPossibleUniqueIds(
        \App\Background\Entities\Partners\Grab\RetailerItem $retailerItem
    ): array {

        $result = [];
        $subs = $retailerItem->getRetailerItemInventorySubList();
        foreach ($subs as $sub) {
            $result[] = $retailerItem->getUniqueId($sub);
        }
        return $result;
    }
}
