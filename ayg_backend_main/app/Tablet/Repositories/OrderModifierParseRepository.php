<?php
namespace App\Tablet\Repositories;

use App\Tablet\Mappers\ParseOrderIntoOrderMapper;
use App\Tablet\Mappers\ParseOrderModifierIntoOrderModifierMapper;
use App\Tablet\Entities\OrderModifier;
use App\Tablet\Mappers\ParseRetailerItemIntoRetailerItemMapper;
use Parse\ParseQuery;

/**
 * Class OrderModifierParseRepository
 * @package App\Tablet\Repositories
 */
class OrderModifierParseRepository extends ParseRepository implements OrderModifierRepositoryInterface
{
    /**
     * @param string $orderId
     * @return OrderModifier[]
     *
     *  Gets Order Modifier list by Order Id
     */
    public function getOrderModifiersByOrderId($orderId)
    {
        $orderIdsQuery = new ParseQuery('Order');
        $orderIdsQuery->equalTo('objectId', $orderId);

        $orderModifiersQuery = new ParseQuery('OrderModifiers');
        $orderModifiersQuery->matchesQuery('order', $orderIdsQuery);
        $orderModifiersQuery->includeKey('retailerItem');
        $orderModifiersQuery->descending('updatedAt');
        $orderModifiers = $orderModifiersQuery->find(false, true);

        if(is_bool($orderModifiers)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 11", 1);
        }

        if (empty($orderModifiers)) {
            return [];
        }

        $return = [];
        foreach ($orderModifiers as $orderModifier) {
            $item = ParseOrderModifierIntoOrderModifierMapper::map($orderModifier);

            $retailerItem = ParseRetailerItemIntoRetailerItemMapper::map($orderModifier->get('retailerItem'));
            $item->setRetailerItem($retailerItem);

            // not needed
            $item->setOrder(null);
            $return[] = $item;
        }

        return $return;
    }
}
