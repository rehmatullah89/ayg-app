<?php
namespace App\Tablet\Repositories;

use App\Tablet\Entities\OrderTabletHelpRequest;
use App\Tablet\Mappers\ParseOrderIntoOrderMapper;
use App\Tablet\Mappers\ParseOrderTabletHelpRequestIntoOrderTabletHelpRequestMapper;
use App\Tablet\Mappers\ParseRetailerIntoRetailerMapper;
use App\Tablet\Mappers\ParseTerminalGateMapIntoTerminalGateMapMapper;
use App\Tablet\Mappers\ParseUserIntoUserMapper;
use Parse\ParseObject;
use Parse\ParseQuery;

/**
 * Class OrderTabletHelpRequestsParseRepository
 * @package App\Tablet\Repositories
 */
class OrderTabletHelpRequestsParseRepository extends ParseRepository implements OrderTabletHelpRequestsRepositoryInterface
{
    /**
     * @param $orderId
     * @param $content
     * @return OrderTabletHelpRequest
     *
     *  Add Order Help Request for particular order
     */
    public function add($orderId, $content)
    {
        $parseOrderQuery = new ParseQuery('Order');
        $parseOrderQuery->equalTo('objectId', $orderId);
        $parseOrderQuery->includeKey('user');
        $parseOrderQuery->includeKey('retailer');
        $parseOrderQuery->includeKey('retailer.location');
        $parseOrder = $parseOrderQuery->find(false, true);

        if(is_bool($parseOrder)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 31", 1);
        }

        $parseOrder = $parseOrder[0];
        $parseOrderTabletHelpRequest = new ParseObject('OrderTabletHelpRequests');
        $parseOrderTabletHelpRequest->set('order', $parseOrder);
        $parseOrderTabletHelpRequest->set('content', $content);
        $parseOrderTabletHelpRequest->set('isResolved', false);
        $parseOrderTabletHelpRequest->save();

        $orderTabletHelpRequest = ParseOrderTabletHelpRequestIntoOrderTabletHelpRequestMapper::map($parseOrderTabletHelpRequest);


        $order = ParseOrderIntoOrderMapper::map($parseOrder);
        $retailer = ParseRetailerIntoRetailerMapper::map($parseOrder->get('retailer'));
        $retailerLocation = ParseTerminalGateMapIntoTerminalGateMapMapper::map($parseOrder->get('retailer')->get('location'));
        $retailer->setLocation($retailerLocation);
        $user = ParseUserIntoUserMapper::map($parseOrder->get('user'));
        $order->setRetailer($retailer);
        $order->setUser($user);
        $orderTabletHelpRequest->setOrder($order);

        return $orderTabletHelpRequest;
    }

    /**
     * @param array $orderIds
     * @return OrderTabletHelpRequest[]
     *
     *  Gets all non-resolved help requests by orderID
     */
    public function getNotResolvedByOrderIds(array $orderIds)
    {
        if (empty($orderIds)) {
            return [];
        }
        $parseInnerOrderQuery = new ParseQuery('Order');
        $parseInnerOrderQuery->containedIn('objectId', $orderIds);


        $parseOrderTabletHelpRequestsQuery = new ParseQuery('OrderTabletHelpRequests');
        $parseOrderTabletHelpRequestsQuery->matchesQuery('order', $parseInnerOrderQuery);
        $parseOrderTabletHelpRequestsQuery->equalTo('isResolved', false);
        $parseOrderTabletHelpRequestsList = $parseOrderTabletHelpRequestsQuery->find(false, true);

        if(is_bool($parseOrderTabletHelpRequestsList)) {

            json_error("AS_1000", "", "AS_2004 - Multi-fetch in getActiveOrdersMultiFetch 32", 1);
        }

        $return = [];
        foreach ($parseOrderTabletHelpRequestsList as $parseOrderTabletHelpRequest) {
            $return[$parseOrderTabletHelpRequest->get('order')->getObjectId()][] = ParseOrderTabletHelpRequestIntoOrderTabletHelpRequestMapper::map($parseOrderTabletHelpRequest);
        }

        return $return;
    }
}