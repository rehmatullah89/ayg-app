<?php

namespace App\Dashboard\Helpers;

use App\Dashboard\Entities\Order;

/**
 * Class QueueMessageHelper
 * @package App\Dashboard\Helpers
 */
class QueueMessageHelper
{
    /**
     * @param $orderId
     * @param $refundType
     * @param $inCents
     * @param $reason
     * @return array
     */
    public static function getOrderOpsPartialRefundRequestMessage($orderId, $refundType, $inCents, $reason)
    {
        return [
            "action" => "order_ops_partial_refund_request",
            "content" => [
                "orderId" => $orderId,
                "options" => json_encode(["orderSequenceId" => intval($orderId),
                    "refundType" => $refundType,
                    "inCents" => intval($inCents),
                    "reason" => $reason])
            ]
        ];
    }
}
