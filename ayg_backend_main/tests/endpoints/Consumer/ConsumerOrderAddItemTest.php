<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderAddItemTest
 *
 * tested endpoint:
 * // Reset internal Order for the User and Retailer
 * '/addItem/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerOrderAddItemTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testItemCanBeAdded()
    {
        $user = $this->createUser();

        //$retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $retailer=$this->getRetailerByUniqueId('5f64bf374967e82e7f66f9e7d84c25cc');
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);

        $uniqueRetailerItemId = $retailerItem->get('uniqueId');


        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/addItem', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $orderId,
            'orderItemId' => 0,
            'uniqueRetailerItemId' => $uniqueRetailerItemId,
            'itemQuantity' => 1,
            'itemComment' => 'some comment',
            'options' => 0,
        ]);

        var_dump($response);

        $jsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('orderItemObjectId', $jsonDecodedBody);

        $this->pushOnObjectsStack(new ObjectsStackItem($jsonDecodedBody['orderItemObjectId'], 'OrderModifiers'));


    }


}