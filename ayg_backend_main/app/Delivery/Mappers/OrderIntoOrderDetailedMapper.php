<?php
namespace App\Delivery\Mappers;

use App\Delivery\Entities\Item;
use App\Delivery\Entities\ItemList;
use App\Delivery\Entities\ItemModifierOption;
use App\Delivery\Entities\ItemModifierOptionList;
use App\Delivery\Entities\Order;
use App\Delivery\Entities\OrderDeliveryStatusFactory;
use App\Delivery\Entities\OrderDetailed;

class OrderIntoOrderDetailedMapper
{
    public static function map(Order $order): OrderDetailed
    {
        $airportTimezone = new \DateTimeZone($order->getRetailer()->getAirport()->getTimezone());

        /*
        $pickupBy = new \DateTime('@' . $order->getRetailerEtaTimestamp());
        $pickupBy->setTimezone($airportTimezone);

        $deliveryBy = new \DateTime('@' . $order->getRequestedFullFillmentTimestamp());
        $deliveryBy->setTimezone($airportTimezone);
        */
        $pickupBy = new \DateTime('@' . $order->getRequestedFullFillmentTimestamp());
        $pickupBy->setTimezone($airportTimezone);

        $deliveryBy = new \DateTime('@' . $order->getRetailerEtaTimestamp());
        $deliveryBy->setTimezone($airportTimezone);

        $orderDeliveryStatus = OrderDeliveryStatusFactory::fromId($order->getStatusDelivery());

        $itemList = new ItemList();
        $itemListJson = json_decode($order->getItemList(), true);
        foreach ($itemListJson as $i) {
            if (is_array($i)) {
                foreach ($i as $id => $object) {
                    $modifierOptionList = new ItemModifierOptionList();

                    if (isset($object['options'])) {
                        foreach ($object['options'] as $o) {
                            $modifierOptionList->addItem(
                                new ItemModifierOption(
                                    $o['optionName'],
                                    $o['modifierName'],
                                    $o['optionQuantity']
                                )
                            );
                        }
                    }

                    $itemList->addItem(
                        new Item(
                            $object['itemName'],
                            $object['itemCategoryName'],
                            $object['allowedThruSecurity'],
                            $object['itemQuantity'],
                            $modifierOptionList
                        )
                    );
                }
            }
        }

        return new OrderDetailed(
            $order->getId(),
            $order->getOrderSequenceId(),
            $orderDeliveryStatus,
            OrderDeliveryStatusFactory::getNextOf($orderDeliveryStatus),
            $pickupBy,
            $deliveryBy,
            RetailerIntoRetailerShortInfoMapper::map($order->getRetailer()),
            TerminalGateMapIntoTerminalShortMapShortInfoMapper::map($order->getDeliveryLocation()),
            UserIntoUserShortInfoMapper::map($order->getUser()),
            null,
            null,
            $itemList,
            $order->getDeliveryInstructions() === "0" ? '' : $order->getDeliveryInstructions(),
            null
        );
    }
}
