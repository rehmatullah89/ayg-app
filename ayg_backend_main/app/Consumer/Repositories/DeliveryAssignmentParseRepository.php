<?php
namespace App\Consumer\Repositories;

use App\Consumer\Entities\DeliveryAssignment;
use App\Consumer\Mappers\ParseDeliveryAssignmentIntoDeliveryAssignmentMapper;
use App\Consumer\Mappers\ParseUserIntoUserMapper;
use Parse\ParseQuery;


/**
 * Class DeliveryAssignmentParseRepository
 * @package App\Consumer\Repositories
 */
class DeliveryAssignmentParseRepository extends ParseRepository implements DeliveryAssignmentRepositoryInterface
{
    /**
     * @param $orderId
     *
     * @return DeliveryAssignment|null
     */
    public function getCompletedDeliveryAssignmentWithDeliveryByOrderId($orderId)
    {
        $parseOrderInnerQuery = new ParseQuery('Order');
        $parseOrderInnerQuery->equalTo('objectId', $orderId);

        $parseQuery = new ParseQuery('DeliveryAssignment');
        $parseQuery->matchesQuery('order', $parseOrderInnerQuery);
        $parseQuery->equalTo('statusCode', DeliveryAssignment::STATUS_COMPLETED);
        $parseQuery->equalTo('active', true);
        $parseQuery->includeKey('delivery');
        $parseDeliveryAssignment = $parseQuery->find(false, true);

        if(is_bool($parseDeliveryAssignment)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 21", 1);
        }

        if (empty($parseDeliveryAssignment)) {
            return null;
        }

        $deliveryAssignment = ParseDeliveryAssignmentIntoDeliveryAssignmentMapper::map($parseDeliveryAssignment);
        $deliveryAssignment->setDelivery(ParseUserIntoUserMapper::map($parseDeliveryAssignment->get('delivery')));

        return $deliveryAssignment;
    }
}