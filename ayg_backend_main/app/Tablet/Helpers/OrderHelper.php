<?php
namespace App\Tablet\Helpers;

use App\Tablet\Entities\Order;
use App\Tablet\Exceptions\Exception;
use App\Tablet\Exceptions\OrderDoesNotHaveTotalsForRetailerException;
use App\Tablet\Exceptions\OrderStatusTabletDisplayNotFoundException;

/**
 * Class OrderHelper
 * @package App\Tablet\Helpers
 */
class OrderHelper
{

    /**
     * @return array
     */
    public static function getOrderStatusDeliveryCompletedListByRetailerPerspective()
    {
        $statusDeliveryNames = $GLOBALS['statusDeliveryNames'];
        $return = [];
        foreach ($statusDeliveryNames as $k => $v) {
            if ($v['tablet_category_code_delivery'] == '400') {
                $return[] = $k;
            }
        }
        return $return;
    }

    /**
     * @param array $options
     * @return array
     */
    public static function getModifiersOptionsUniqueIdFromJson(array $options)
    {
        $optionIds = [];
        foreach ($options as $k => $v) {
            $optionIds[] = $v->id;
        }
        return $optionIds;
    }

    /**
     * @param Order $order
     *
     * returns Order Status Category Code for a given order based on current status and delivery status for delivery orders
     * to do that checks functions_orders.php $statusNames and $statusDeliveryNames
     *
     * when changing it to based on constants check git before 2017-08-17
     * @return int|null
     * @throws Exception
     */
    public static function getOrderStatusCategoryCode(Order $order)
    {
        $statusNames = $GLOBALS['statusNames'];
        $statusDeliveryNames = $GLOBALS['statusDeliveryNames'];

        $status = $order->getStatus();
        $statusDelivery = $order->getStatusDelivery();


        // check if StatusCategoryCode depends on delivery Status
        $statusCategoryCodeDependsOnDeliveryStatus = self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_status_depends_on_delivery_status', $status);

        // if not, just check what is status category code for a given status for pickup or delivery
        if ($statusCategoryCodeDependsOnDeliveryStatus === false) {
            if ($order->getFullfillmentType() == 'p') {
                return self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_category_code_pickup', $status);
            } elseif ($order->getFullfillmentType() == 'd') {
                return self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_category_code_delivery', $status);
            }
            throw new Exception('Order Status Error');
        }

        // if it depends on delivery Status
        // for pickup orders, just return pickup status category code
        if ($order->getFullfillmentType() == 'p') {
            return self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_category_code_pickup', $status);
        }

        //for delivery orders return status category code by deliveryStatus
        return self::checkValueForAGivenStatusAndKey($statusDeliveryNames, 'tablet_category_code_delivery', $statusDelivery);
    }


    /**
     * @param $statusNames
     * @param $inputKey
     * @param $orderStatus
     * @return int|null|string
     * @throws Exception
     */
    private static function checkValueForAGivenStatusAndKey($statusNames, $inputKey, $orderStatus)
    {
        if (!isset($statusNames[$orderStatus][$inputKey])) {
            throw new Exception('Order Status Error');
        }

        return $statusNames[$orderStatus][$inputKey];
    }

    /**
     * returns display name for a given order for retailer perspective
     * when changing it to based on constants check git before 2017-08-17
     *
     * @param Order $order
     * @return string
     */
    public static function getTabletOrderStatusDisplay(Order $order)
    {
        $statusNames = $GLOBALS['statusNames'];
        $statusDeliveryNames = $GLOBALS['statusDeliveryNames'];

        $status = $order->getStatus();
        $statusDelivery = $order->getStatusDelivery();

        // check if Status Display Name depends on delivery Status
        $statusCategoryCodeDependsOnDeliveryStatus = self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_status_depends_on_delivery_status', $status);

        // if not, just return status display name for a given status
        if ($statusCategoryCodeDependsOnDeliveryStatus === false) {
            return self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_order_status_display', $status);
        }

        // if it depends on delivery Status
        // for pickup orders, just return pickup status display name
        if ($order->getFullfillmentType() == 'p') {
            return self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_order_status_display', $status);
        }

        //for delivery orders return status display name by deliveryStatus
        return self::checkValueForAGivenStatusAndKey($statusDeliveryNames, 'tablet_display_by_retailer_perspective', $statusDelivery);
    }

    /**
     * @param Order $order
     * @param $currentTime
     *
     * when is ready (by prep time), but it is not received by delivery Man
     * virtual status "Ready for Delivery" with statusCategoryCode 900 should be returned
     *
     * @return bool
     */
    public static function checkIfStatusIsReadyForDelivery(Order $order, $currentTime)
    {
        $statusNames = $GLOBALS['statusNames'];
        $statusDeliveryNames = $GLOBALS['statusDeliveryNames'];

        if (
            ($order->getFullfillmentType() == 'd') &&                                                                                               // delivery
            (time() - ($order->getEtaTimestamp()) >= 0) &&                                                                                          // prepared
            (self::checkValueForAGivenStatusAndKey($statusNames, 'tablet_status_depends_on_delivery_status', $order->getStatus()) == true) &&       // if status depends on delivery                                                                                     // prepared
            (self::checkValueForAGivenStatusAndKey($statusDeliveryNames, 'tablet_category_code_delivery', $order->getStatusDelivery()) == 200)      // not received by delivery person
        ) {
            return true;
        }

        return false;
    }
}