<?php
namespace App\Consumer\Helpers;

use App\Consumer\Entities\Order;
use App\Consumer\Entities\User;

/**
 * Class UserHelper
 * @package App\Consumer\Helpers
 */
class OrderHelper
{
    /**
     * @param array (returned by getFullfillmentInfoWithOrder)
     * @return array
     */
    public static function addOrderAllowedWithoutCreditCardInfo($responseArray): array
    {
        $responseArray['p']['allowWithoutCreditCard'] = false;
        $responseArray['d']['allowWithoutCreditCard'] = false;

        if ($responseArray['p']['TotalInCents'] == 0) {
            $responseArray['p']['allowWithoutCreditCard'] = true;
        }
        if ($responseArray['d']['TotalInCents'] == 0) {
            $responseArray['d']['allowWithoutCreditCard'] = true;
        }

        return $responseArray;
    }


    /**
     * @param array $orderStatusReturnArray
     * @see getPrintableOrderStatusList()
     * @return array
     */
    public static function updatePickupStagesFromOrderStatusReturnArray(array $orderStatusReturnArray): array
    {
        foreach ($orderStatusReturnArray['status'] as $k => $status) {

            // READY FOR PICKUP removed
            if ($status['statusCode'] == Order::STATUS_COMPLETED) {
                unset($orderStatusReturnArray['status'][$k]);
            }

            // BEING PREPARED changed to "GO TO RETAILER"
            if ($status['statusCode'] == Order::STATUS_ACCEPTED_BY_RETAILER) {
                $orderStatusReturnArray['status'][$k]['status'] = 'GO TO RETAILER';
            }

        }

        return $orderStatusReturnArray;
    }




    /**
     * @param array $orderListReturnArraySingleOrder
     * @see getPrintableOrderStatusList()
     * @return array
     */
    public static function updatePickupStagesFromOrderListReturnArray(array $orderListReturnArraySingleOrder): array
    {

            // READY FOR PICKUP removed
            if ($orderListReturnArraySingleOrder['orderStatusCode'] == Order::STATUS_COMPLETED) {
                $orderListReturnArraySingleOrder['orderStatus'] = '';
                $orderListReturnArraySingleOrder['orderInternalStatus'] = '';
            }

            // BEING PREPARED changed to "GO TO RETAILER"
            if ($orderListReturnArraySingleOrder['orderStatusCode'] == Order::STATUS_ACCEPTED_BY_RETAILER) {
                $orderListReturnArraySingleOrder['orderStatus'] = 'GO TO RETAILER';
                $orderListReturnArraySingleOrder['orderInternalStatus'] = 'GO TO RETAILER';
            }


        return $orderListReturnArraySingleOrder;
    }
}
