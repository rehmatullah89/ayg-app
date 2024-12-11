<?php

namespace App\Consumer\Mappers;

use App\Consumer\Entities\DeliveryAssignment;
use Parse\ParseObject;

/**
 * Class ParseDeliveryAssignmentIntoDeliveryAssignmentMapper
 * @package App\Consumer\Mappers
 */
class ParseDeliveryAssignmentIntoDeliveryAssignmentMapper
{
    /**
     * @param ParseObject $parseDeliveryAssignment
     * @return DeliveryAssignment
     */
    public static function map(ParseObject $parseDeliveryAssignment)
    {
        return new DeliveryAssignment(
            [
                'id' => $parseDeliveryAssignment->getObjectId(),
                'order' => $parseDeliveryAssignment->get('order'),
                'statusCode' => $parseDeliveryAssignment->get('statusCode'),
                'earnings' => $parseDeliveryAssignment->get('earnings'),
                'assignmentTimestamp' => $parseDeliveryAssignment->get('assignmentTimestamp'),
                'isActive' => $parseDeliveryAssignment->get('isActive'),
                'delivery' => $parseDeliveryAssignment->get('delivery'),
            ]
        );
    }
}
