<?php

namespace App\Delivery\Repositories;

use App\Delivery\Entities\Item;
use App\Delivery\Entities\ItemList;
use App\Delivery\Entities\ItemModifier;
use App\Delivery\Entities\ItemModifierList;
use Parse\ParseQuery;

class OrderModifierParseRepository implements OrderModifierRepositoryInterface
{
    public function getItemListByOrderId(string $orderId): ItemList
    {
        $orderInnerQuery = new ParseQuery('Order');
        $orderInnerQuery->equalTo('objectId', $orderId);

        $orderModifierQuery = new ParseQuery('OrderModifiers');
        $orderModifierQuery->matchesQuery('order', $orderInnerQuery);
        $orderModifierQuery->includeKey('retailerItem');
        $orderModifiers = $orderModifierQuery->find();


        $itemList = new ItemList();
        foreach ($orderModifiers as $orderModifier) {

            $itemId = $orderModifier->get('retailerItem')->getObjectId();

            $itemWasThere = true;
            $item = $itemList->findItemById($itemId);

            if ($item === null) {
                $itemWasThere = false;
                $item = new Item(
                    $orderModifier->get('retailerItem')->getObjectId(),
                    $orderModifier->get('retailerItem')->get('itemPOSName'),
                    $orderModifier->get('retailerItem')->get('itemCategoryName'),
                    $orderModifier->get('retailerItem')->get('allowedThruSecurity'),
                    $orderModifier->get('itemQuantity'),
                    new ItemModifierList()
                );
            }

            if (!$itemWasThere) {
                $itemList->addItem($item);
            }

            // collect all used options with respect to modifiers
            $modifierOptions = $orderModifier->get('modifierOptions');
            $modifierOptions = json_decode($modifierOptions);

            $optionIdList = [];
            $modifierIdList = [];
            foreach ($modifierOptions as $modifierOption){
                $optionIdList[]=$modifierOption->id;
            }

            $orderModifierOptionsQuery = new ParseQuery('RetailerItemModifierOptions');
            $orderModifierOptionsQuery->containedIn('uniqueId', $optionIdList);
            $orderModifierOptions = $orderModifierOptionsQuery->find();
            foreach ($orderModifierOptions as $orderModifierOption){
                $modifierIdList[]=$orderModifierOptions->get('uniqueRetailerItemModifierId');
            }




        }

        return $itemList;
    }
}
