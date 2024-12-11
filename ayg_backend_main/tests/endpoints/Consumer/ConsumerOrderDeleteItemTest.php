<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderDeleteItemTest
 *
 * tested endpoint:
 * // Delete items from cart
 * '/deleteItem/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/orderItemId/:orderItemId'
 */
class ConsumerOrderDeleteItemTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testItemCanBeDeleted()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();

        $item = $this->addParseObject('OrderModifiers', [
            'retailerItem' => $retailerItem,
            'itemComment' => 'some comment 1',
            'order' => $order,
            'itemQuantity' => 1
        ]);
        $orderItemId = $item->getObjectId();

        $url = $this->generatePath('order/deleteItem', $user->getSessionToken(), "orderId/$orderId/orderItemId/$orderItemId");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('deleted', $arrayJsonDecodedBody);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals('1', $jsonDecodedBody->deleted);



    }

    /**
     *
     */
    public function testThatOrderItemCanNotBeDeletedWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();

        $item = $this->addParseObjectAndPushObjectOnStack('OrderModifiers', [
            'retailerItem' => $retailerItem,
            'itemComment' => 'some comment 2',
            'order' => $order,
            'itemQuantity' => 1
        ]);
        $orderItemId = $item->getObjectId();

        $url = $this->generatePath('order/deleteItem', $sessionToken, "orderId/$orderId/orderItemId/$orderItemId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);

    }





}