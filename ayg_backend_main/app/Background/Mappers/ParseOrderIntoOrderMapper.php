<?php
namespace App\Background\Mappers;

use App\Background\Entities\Order;
use Parse\ParseObject;

/**
 * Class ParseUserIntoUserMapper
 * @package App\Background\Mappers
 */
class ParseOrderIntoOrderMapper
{
    /**
     * @param ParseObject $parseOrder
     * @return Order
     */
    public static function map(ParseObject $parseOrder)
    {

        return new Order([
            'id' => $parseOrder->getObjectId(),
            'interimOrderStatus' => $parseOrder->get('interimOrderStatus'),
            'paymentType' => $parseOrder->get('paymentType'),
            'paymentId' => $parseOrder->get('paymentId'),
            'submissionAttempt' => $parseOrder->get('submissionAttempt'),
            'orderPOSId' => $parseOrder->get('orderPOSId'),
            'totalsWithFees' => $parseOrder->get('totalsWithFees'),
            'etaTimestamp' => $parseOrder->get('etaTimestamp'),
            'coupon' => $parseOrder->get('coupon'),
            'statusDelivery' => $parseOrder->get('statusDelivery'),
            'tipPct' => $parseOrder->get('tipPct'),
            'cancelReason' => $parseOrder->get('cancelReason'),
            'quotedFullfillmentFeeTimestamp' => $parseOrder->get('quotedFullfillmentFeeTimestamp'),
            'fullfillmentType' => $parseOrder->get('fullfillmentType'),
            'invoicePDFURL' => $parseOrder->get('invoicePDFURL'),
            'orderSequenceId' => $parseOrder->get('orderSequenceId'),
            'totalsForRetailer' => $parseOrder->get('totalsForRetailer'),
            'paymentTypeName' => $parseOrder->get('paymentTypeName'),
            'fullfillmentProcessTimeInSeconds' => $parseOrder->get('fullfillmentProcessTimeInSeconds'),
            'updatedAt' => $parseOrder->getUpdatedAt(),
            'quotedFullfillmentPickupFee' => $parseOrder->get('quotedFullfillmentPickupFee'),
            'status' => $parseOrder->get('status'),
            'fullfillmentFee' => $parseOrder->get('fullfillmentFee'),
            'requestedFullFillmentTimestamp' => $parseOrder->get('requestedFullFillmentTimestamp'),
            'orderPrintJobId' => $parseOrder->get('orderPrintJobId'),
            'deliveryInstructions' => $parseOrder->get('deliveryInstructions'),
            'quotedFullfillmentDeliveryFee' => $parseOrder->get('quotedFullfillmentDeliveryFee'),
            'createdAt' => $parseOrder->getCreatedAt(),
            'totalsFromPOS' => $parseOrder->get('totalsFromPOS'),
            'paymentTypeId' => $parseOrder->get('paymentTypeId'),
            'submitTimestamp' => $parseOrder->get('submitTimestamp'),
            'comment' => $parseOrder->get('comment'),
            'tipCents' => $parseOrder->get('tipCents'),
            'tipAppliedAs' => $parseOrder->get('tipAppliedAs'),
            'partnerName' => $parseOrder->get('partnerName'),
            'partnerOrderId' => $parseOrder->get('partnerOrderId'),
        ]);
    }
}
