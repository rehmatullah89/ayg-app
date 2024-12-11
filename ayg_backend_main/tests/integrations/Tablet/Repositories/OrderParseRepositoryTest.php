<?php

namespace tests\integrations\Tablet\Repositories;

use App\Tablet\Entities\Order;
use App\Tablet\Entities\Retailer;
use App\Tablet\Entities\User;
use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Helpers\OrderHelper;
use App\Tablet\Mappers\ParseOrderIntoOrderMapper;
use App\Tablet\Repositories\OrderParseRepository;
use Parse\ParseClient;
use Parse\ParseQuery;

if (strcasecmp(getenv('env_InHerokuRun'), "Y") != 0) {
    include __DIR__ . '/../../../../putenv.php';
}

include __DIR__ . '/../../../../lib/functions_orders.php';


date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));

class OrderParseRepositoryTest extends \PHPUnit_Framework_TestCase
{


    /**
     * @covers \App\Tablet\Repositories\OrderParseRepository::getActiveOrdersCountByRetailerIdList
     */
    public function testOrderParseRepositoryCanCountActiveOrdersCountByRetailerIdList()
    {
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 4);
        $initialOrder->save();


        $orderQuery = new ParseQuery('Order');
        $orderQuery->includeKey('retailer');
        $orderQuery->includeKey('retailer.location');
        $orderQuery->includeKey('user');
        $orderQuery->containedIn('status', listStatusesForInProgress());
        $orderQuery->limit(5);
        $orders = $orderQuery->find();

        $retailerIdList = [];
        foreach ($orders as $order) {
            $retailerIdList[] = $order->get('retailer')->getObjectId();
        }

        $query = $this->createActiveOrderByRetailerIdListParseQuery($retailerIdList);
        $count = $query->count();

        $orderParseRepository = new OrderParseRepository();
        $countResult = $orderParseRepository->getActiveOrdersCountByRetailerIdList($retailerIdList);

        $this->assertEquals($count, $countResult);


        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

    /**
     * @covers \App\Tablet\Repositories\OrderParseRepository::getActiveOrdersListByRetailerIdListPaginated
     */
    public function testOrderParseRepositoryCanGetActiveOrdersCountByRetailerIdList()
    {
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 4);
        $initialOrder->save();


        $orderQuery = new ParseQuery('Order');
        $orderQuery->includeKey('retailer');
        $orderQuery->includeKey('retailer.location');
        $orderQuery->includeKey('user');
        $orderQuery->containedIn('status', listStatusesForInProgress());
        $orderQuery->limit(5);
        $orders = $orderQuery->find();

        $retailerIdList = [];
        foreach ($orders as $order) {
            $retailerIdList[] = $order->get('retailer')->getObjectId();
        }
        // count orders for those retailers
        $orderQuery = $this->createActiveOrderByRetailerIdListParseQuery($retailerIdList);
        $count = $orderQuery->count();


        $orderParseRepository = new OrderParseRepository();
        // get first
        $foundOrder = $orderParseRepository->getActiveOrdersListByRetailerIdListPaginated($retailerIdList, 1, 1);

        $this->assertInstanceOf(Order::class, $foundOrder[0]);
        $this->assertInstanceOf(User::class, $foundOrder[0]->getUser());
        $this->assertInstanceOf(Retailer::class, $foundOrder[0]->getRetailer());

        // check if can take only in the found range (for example will take empty array when there is 5 items and we want to get 6th)
        $foundOrder = $orderParseRepository->getActiveOrdersListByRetailerIdListPaginated($retailerIdList, $count + 1, 1);

        $this->assertTrue(empty($foundOrder));


        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

    /**
     * @covers \App\Tablet\Repositories\OrderParseRepository::getPastOrdersCountByRetailerIdList
     */
    public function testOrderParseRepositoryCanCountPastOrdersByRetailerIdList()
    {

        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 10);
        $initialOrder->save();

        $orderQuery = new ParseQuery('Order');
        $orderQuery->includeKey('retailer');
        $orderQuery->includeKey('retailer.location');
        $orderQuery->includeKey('user');
        $orderQuery->containedIn('status', array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled()));
        $orderQuery->limit(5);
        $orders = $orderQuery->find();

        $retailerIdList = [];
        foreach ($orders as $order) {
            $retailerIdList[] = $order->get('retailer')->getObjectId();
        }

        $query = $this->createPastOrderByRetailerIdListParseQuery($retailerIdList);
        $count = $query->count();

        $orderParseRepository = new OrderParseRepository();
        $countResult = $orderParseRepository->getPastOrdersCountByRetailerIdList($retailerIdList);

        $this->assertEquals($count, $countResult);


        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

    /**
     * @covers \App\Tablet\Repositories\OrderParseRepository::getPastOrdersListByRetailerIdListPaginated
     *
     */
    public function testOrderParseRepositoryCanGetPastOrdersByRetailerIdList()
    {
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 10);
        $initialOrder->save();

        $orderQuery = new ParseQuery('Order');
        $orderQuery->includeKey('retailer');
        $orderQuery->includeKey('retailer.location');
        $orderQuery->includeKey('user');
        $orderQuery->containedIn('status', array_merge(listStatusesForSuccessCompleted(), listStatusesForCancelled()));
        $orderQuery->limit(5);
        $orders = $orderQuery->find();

        $retailerIdList = [];
        foreach ($orders as $order) {
            $retailerIdList[] = $order->get('retailer')->getObjectId();
        }
        // count orders for those retailers
        $orderQuery = $this->createPastOrderByRetailerIdListParseQuery($retailerIdList);
        $count = $orderQuery->count();


        $orderParseRepository = new OrderParseRepository();
        // get first
        $foundOrder = $orderParseRepository->getPastOrdersListByRetailerIdListPaginated($retailerIdList, 1, 1);

        $this->assertInstanceOf(Order::class, $foundOrder[0]);
        $this->assertInstanceOf(User::class, $foundOrder[0]->getUser());
        $this->assertInstanceOf(Retailer::class, $foundOrder[0]->getRetailer());

        // check if can take only in the found range (for example will take empty array when there is 5 items and we want to get 6th)
        $foundOrder = $orderParseRepository->getPastOrdersListByRetailerIdListPaginated($retailerIdList, $count + 1, 1);

        $this->assertTrue(empty($foundOrder));


        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

    /**
     * @covers \App\Tablet\Repositories\OrderParseRepository::getOrderWithUserRetailerAndLocationByIdAndRetailerIdList
     */
    public function testOrderParseRepositoryCanGetWithUserRetailerAndLocationByIdAndRetailerIdList()
    {
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $retailerId = $initialOrder->get('retailer')->getObjectId();
        $userId = $initialOrder->get('user')->getObjectId();
        $initialOrder->get('retailer')->fetch();
        $locationId = $initialOrder->get('retailer')->get('location')->getObjectId();


        $orderParseRepository = new OrderParseRepository();
        // get first
        $foundOrder = $orderParseRepository->getOrderWithUserRetailerAndLocationByIdAndRetailerIdList('pmh4UNQDGG', [$retailerId, 'someother']);

        $this->assertEquals('pmh4UNQDGG', $foundOrder->getId());


        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $retailerId = $initialOrder->get('retailer')->getObjectId();


        $orderParseRepository = new OrderParseRepository();
        // get first
        $foundOrder = $orderParseRepository->getOrderWithUserRetailerAndLocationByIdAndRetailerIdList('pmh4UNQDGG', ['retailer', 'someother']);

        $this->assertNull($foundOrder);
    }

    /**
     * @covers \App\Tablet\Repositories\OrderParseRepository::changeStatusToAcceptedByRetailer
     */
    public function testOrderParseRepositoryCanChangeStatusToAcceptedByRetailer()
    {
        // prepare order with status 4
        // order to change is:
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 4);
        $initialOrder->save();


        $order = ParseOrderIntoOrderMapper::map($initialOrder);


        $orderParseRepository = new OrderParseRepository();
        $orderParseRepository->changeStatusToAcceptedByRetailer($order);


        $parseOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $parseOrder->fetch();
        $changedStatus = $parseOrder->get('status');

        $this->assertEquals(Order::STATUS_ACCEPTED_BY_RETAILER, $changedStatus);


        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

    /**
     * @covers \App\Tablet\Repositories\OrderParseRepository::changeStatusToPushedToRetailer
     */
    public function testOrderParseRepositoryCanChangeStatusToPushedToRepository()
    {
        // prepare order with status 4
        // order to change is:
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 3);
        $initialOrder->save();


        $order = ParseOrderIntoOrderMapper::map($initialOrder);


        $orderParseRepository = new OrderParseRepository();
        $orderParseRepository->changeStatusToPushedToRetailer($order);


        $parseOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $parseOrder->fetch();
        $changedStatus = $parseOrder->get('status');

        $this->assertEquals(Order::STATUS_PUSHED_TO_RETAILER, $changedStatus);


        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }


    private function createPastOrderByRetailerIdListParseQuery(array $retailerIdList)
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseOrdersQueryPickup = new ParseQuery('Order');
        $parseOrdersQueryPickup->equalTo('fullfillmentType', 'p');
        $parseOrdersQueryPickup->containedIn('status', [
            Order::STATUS_CANCELED_BY_SYSTEM,
            Order::STATUS_CANCELED_BY_USER,
            Order::STATUS_COMPLETED,
        ]);

        $parseOrdersQueryDeliveryBeforeAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryBeforeAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryBeforeAccept->containedIn('status', [
            Order::STATUS_CANCELED_BY_SYSTEM,
            Order::STATUS_CANCELED_BY_USER,
            Order::STATUS_COMPLETED,
        ]);

        // for delivery orders after picked up by delivery man
        $parseOrdersQueryDeliveryAfterAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryAfterAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryAfterAccept->containedIn('status', [
            Order::STATUS_ACCEPTED_BY_RETAILER,
        ]);
        $parseOrdersQueryDeliveryAfterAccept->containedIn('statusDelivery', OrderHelper::getOrderStatusDeliveryCompletedListByRetailerPerspective());

        $parseOrdersQueryDelivery = ParseQuery::orQueries([$parseOrdersQueryDeliveryBeforeAccept, $parseOrdersQueryDeliveryAfterAccept]);
        $parseOrdersQuery = ParseQuery::orQueries([$parseOrdersQueryPickup, $parseOrdersQueryDelivery]);
        $parseOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);

        return $parseOrdersQuery;
    }

    private function createActiveOrderByRetailerIdListParseQuery(array $retailerIdList)
    {
        $parseRetailersQuery = new ParseQuery('Retailers');
        $parseRetailersQuery->containedIn('objectId', $retailerIdList);

        $parseOrdersQueryPickup = new ParseQuery('Order');
        $parseOrdersQueryPickup->equalTo('fullfillmentType', 'p');
        $parseOrdersQueryPickup->containedIn('status', [
            Order::STATUS_PAYMENT_ACCEPTED,
            Order::STATUS_PUSHED_TO_RETAILER,
            Order::STATUS_ACCEPTED_BY_RETAILER,
        ]);

        $parseOrdersQueryDeliveryBeforeAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryBeforeAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryBeforeAccept->containedIn('status', [
            Order::STATUS_PAYMENT_ACCEPTED,
            Order::STATUS_PUSHED_TO_RETAILER,
        ]);

        // for delivery orders after retailer confirm it is in active till it is not collected by delivery man
        $parseOrdersQueryDeliveryAfterAccept = new ParseQuery('Order');
        $parseOrdersQueryDeliveryAfterAccept->equalTo('fullfillmentType', 'd');
        $parseOrdersQueryDeliveryAfterAccept->containedIn('status', [
            Order::STATUS_ACCEPTED_BY_RETAILER,
        ]);
        $parseOrdersQueryDeliveryAfterAccept->notContainedIn('statusDelivery',
            OrderHelper::getOrderStatusDeliveryCompletedListByRetailerPerspective()
        );

        $parseOrdersQueryDelivery = ParseQuery::orQueries([$parseOrdersQueryDeliveryBeforeAccept, $parseOrdersQueryDeliveryAfterAccept]);
        $parseOrdersQuery = ParseQuery::orQueries([$parseOrdersQueryPickup, $parseOrdersQueryDelivery]);
        $parseOrdersQuery->matchesQuery('retailer', $parseRetailersQuery);

        return $parseOrdersQuery;
    }

}