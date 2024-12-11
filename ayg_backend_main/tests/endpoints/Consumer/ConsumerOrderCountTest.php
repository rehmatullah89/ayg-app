<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderCountTest
 *
 * tested endpoint:
 * // Order Item Count
 * '/count/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId'
 */
class ConsumerOrderCountTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanGetOrderItemCount()
    {
        $user = $this->createUser();
        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/count', $user->getSessionToken(), "orderId/$orderId");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals('0', $jsonDecodedBody->count);
    }

    /**
     *
     */
    public function testCanGetOrderItemCountAfterAdd()
    {
        $user = $this->createUser();
        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();




        // add 2 items

        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);
        $this->addParseObjectAndPushObjectOnStack('OrderModifiers', [
            'retailerItem' => $retailerItem,
            'itemComment' => 'some comment 1',
            'order' => $order,
            'itemQuantity' => 1
        ]);
        $this->addParseObjectAndPushObjectOnStack('OrderModifiers', [
            'retailerItem' => $retailerItem,
            'itemComment' => 'some comment 2',
            'order' => $order,
            'itemQuantity' => 1
        ]);

        $url = $this->generatePath('order/count', $user->getSessionToken(), "orderId/$orderId");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();


        $this->assertEquals('2', $jsonDecodedBody->count);



    }

    /**
     *
     */
    public function testCanNotGetOrderItemCountWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/count', $sessionToken, "orderId/$orderId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);


    }
}