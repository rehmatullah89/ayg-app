<?php

use Parse\ParseObject;
use Parse\ParseQuery;
use Parse\ParseUser;

require_once __DIR__ . '/../TabletBaseTest.php';

/**
 * Class OrderHelpRequestTest
 */
class OrderHelpRequestTest extends TabletBaseTest
{


    /**
     *
     */
    public function testCanRequestHelpForOrder()
    {
        $user = ParseUser::logIn('ludwik.grochowina+tablet@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);
        //$parseRetailers = $this->getRetailerConnectedToUser($user->getObjectId());
        //$parseOrder = $this->getFirstOrderByParseRetailers($parseRetailers);

        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 4);
        $initialOrder->save();
        $orderId = $initialOrder->getObjectId();

        // remove all help requests for pmh4UNQDGG
        $helpRequestsQuery=new ParseQuery('OrderTabletHelpRequests');
        $helpRequestsQuery->equalTo('order', $initialOrder);
        $helpRequests=$helpRequestsQuery->find();
        foreach ($helpRequests as $helpRequest){
            $helpRequest->destroy(true);
        }

        // checking if get active orders will response with helpRequestPending == false
        $url = $this->generatePath('tablet/order/getActiveOrders', $user->getSessionToken(), "page/1/limit/1000");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $helpRequested = $this->getHelpRequestValueFromGetActiveOrdersJsonDecodedResponseAndOrderSeqId($jsonDecodedResponse,$initialOrder->get('orderSequenceId'));
        $this->assertFalse($helpRequested);


        $url = $this->generatePath('tablet/order/helpRequest', $user->getSessionToken(), '');

        $response = $this->post($url, [
            'orderId' => $orderId,
            'content' => 'some text'
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('200', $response->getHttpStatusCode());

        $jsonDecodedResponseArray = $response->getJsonDecodedBody(true);
        $firstOrder = $jsonDecodedResponseArray['order'];
        $keysExpectedInOrderData = [
            'orderId',
            'orderSequenceId',
            'orderStatusCode',
            'orderStatusDisplay',
            'orderStatusCategoryCode',
            'orderType',
            'orderDateAndTime',
            'retailerId',
            'retailerName',
            'retailerLocation',
            'consumerName',
            'mustPickupBy',
            'numberOfItems',
            'items',
        ];
        foreach ($keysExpectedInOrderData as $keyExpectedInOrderData) {
            $this->assertArrayHasKey($keyExpectedInOrderData, $firstOrder);
        }

        $firstItem = reset($firstOrder['items']);
        $this->assertArrayHasKey('retailerItemName', $firstItem);
        $this->assertArrayHasKey('itemQuantity', $firstItem);
        $this->assertArrayHasKey('options', $firstItem);
        $this->assertArrayHasKey('itemComments', $firstItem);


        if (!empty($firstItem['options'])) {
            $firstOption = reset($firstItem['options']);
            $this->assertArrayHasKey('name', $firstOption);
            $this->assertArrayHasKey('quantity', $firstOption);
            $this->assertArrayHasKey('categoryName', $firstOption);
        } else {
            //trigger_error("Could not check item options correctness - no data in database", E_USER_NOTICE);
        }
        /*
        $this->assertTrue($jsonDecodedResponse->success);

        $jsonDecodedResponseArray = $response->getJsonDecodedBody(true);
        $firstOrder = $jsonDecodedResponseArray['data']['order'];
        $keysExpectedInOrderData = [
            'orderId',
            'orderSequenceId',
            'orderStatusCode',
            'orderStatusDisplay',
            'orderStatusCategoryCode',
            'orderType',
            'orderDateAndTime',
            'retailerId',
            'retailerName',
            'retailerLocation',
            'consumerName',
            'mustPickupBy',
            'numberOfItems',
            'items',
        ];
        foreach ($keysExpectedInOrderData as $keyExpectedInOrderData) {
            $this->assertArrayHasKey($keyExpectedInOrderData, $firstOrder);
        }

        $firstItem = reset($firstOrder['items']);
        $this->assertArrayHasKey('retailerItemName', $firstItem);
        $this->assertArrayHasKey('itemQuantity', $firstItem);
        $this->assertArrayHasKey('options', $firstItem);
        $this->assertArrayHasKey('itemComments', $firstItem);


        if (!empty($firstItem['options'])) {
            $firstOption = reset($firstItem['options']);
            $this->assertArrayHasKey('name', $firstOption);
            $this->assertArrayHasKey('quantity', $firstOption);
            $this->assertArrayHasKey('categoryName', $firstOption);
        } else {
            //trigger_error("Could not check item options correctness - no data in database", E_USER_NOTICE);
        }
        */

        // checking if get active orders will response with helpRequestPending == true
        $url = $this->generatePath('tablet/order/getActiveOrders', $user->getSessionToken(), "page/1/limit/1000");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $helpRequested = $this->getHelpRequestValueFromGetActiveOrdersJsonDecodedResponseAndOrderSeqId($jsonDecodedResponse,$initialOrder->get('orderSequenceId'));
        $this->assertTrue($helpRequested);


        $parseOrderHelpRequestQuery = new ParseQuery('OrderTabletHelpRequests');
        $parseOrderHelpRequestQuery->equalTo('content', 'some text');
        $parseOrderHelpRequestQuery->equalTo('order', $initialOrder);
        $parseOrderHelpRequest = $parseOrderHelpRequestQuery->first();
        $this->pushOnObjectsStack(new ObjectsStackItem($parseOrderHelpRequest->getObjectId(), 'OrderTabletHelpRequests'));


        // resolve it
        $parseOrderHelpRequest->set('isResolved', true);
        $parseOrderHelpRequest->save();

        // checking if get active orders will response with helpRequestPending == false
        $url = $this->generatePath('tablet/order/getActiveOrders', $user->getSessionToken(), "page/1/limit/1000");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $helpRequested = $this->getHelpRequestValueFromGetActiveOrdersJsonDecodedResponseAndOrderSeqId($jsonDecodedResponse,$initialOrder->get('orderSequenceId'));
        $this->assertFalse($helpRequested);



        $initialOrder->set('status',$initialOrderOldStatus);
        $initialOrder->save();
    }


    public function testCanNotRequestHelpForOrderWhenBadInput()
    {
        $user = ParseUser::logIn('ludwik.grochowina+tablet@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);


        $url = $this->generatePath('tablet/order/helpRequest', $user->getSessionToken(), '');
        $response = $this->post($url, [
            'orderId' => '',
            'content' => 'some text'
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        //$this->assertEquals('400', $response->getHttpStatusCode());
        /*
        $this->assertFalse($jsonDecodedResponse->success);
        $this->assertEquals('12101210', $jsonDecodedResponse->error->code);
        */

        $url = $this->generatePath('tablet/order/helpRequest', $user->getSessionToken(), '');
        $response = $this->post($url, [
            'content' => 'some text'
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5399', $jsonDecodedResponse->error_code);

        /*
        $this->assertFalse($jsonDecodedResponse->success);
        $this->assertEquals('12101210', $jsonDecodedResponse->error->code);
        */


        $url = $this->generatePath('tablet/order/helpRequest', $user->getSessionToken(), '');
        $response = $this->post($url, [
            'orderId' => 'someOrder',
            'content' => ''
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5399', $jsonDecodedResponse->error_code);
        /*
        $this->assertFalse($jsonDecodedResponse->success);
        $this->assertEquals('12101210', $jsonDecodedResponse->error->code);
        */


        $url = $this->generatePath('tablet/order/helpRequest', $user->getSessionToken(), '');
        $response = $this->post($url, [
            'orderId' => '',
            'content' => ''
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5399', $jsonDecodedResponse->error_code);
        /*
        $this->assertFalse($jsonDecodedResponse->success);
        $this->assertEquals('12101210', $jsonDecodedResponse->error->code);
        */
    }

    private function getHelpRequestValueFromGetActiveOrdersJsonDecodedResponseAndOrderSeqId($response, $orderSequenceId)
    {
        //foreach ($response->data->ordersList as $item){
        foreach ($response->ordersList as $item){
            if ($item->orderSequenceId==$orderSequenceId){
                return $item->helpRequestPending;
            }
        }
        return null;
    }

    private function getRetailerConnectedToUser($userId)
    {
        $innerQueryGetUser = new ParseQuery('_User');
        $innerQueryGetUser->equalTo('objectId', $userId);
        $query = new ParseQuery('RetailerPOSConfig');
        $query->matchesQuery("user", $innerQueryGetUser);
        $query->includeKey("retailer");
        $query->includeKey("retailer.location");
        $retailerPOSConfigs = $query->find();

        $return = [];
        foreach ($retailerPOSConfigs as $retailerPOSConfig) {
            if (empty($retailerPOSConfig)) {
                continue;
            }
            if (empty($retailerPOSConfig->get('retailer'))) {
                continue;
            }

            $return[] = $retailerPOSConfig->get('retailer');
        }

        return $return;
    }

    private function getFirstOrderByParseRetailers($parseRetailers)
    {
        $orderQuery = new ParseQuery('Order');
        $orderQuery->containedIn('retailer', $parseRetailers);
        return $orderQuery->first();
    }
}