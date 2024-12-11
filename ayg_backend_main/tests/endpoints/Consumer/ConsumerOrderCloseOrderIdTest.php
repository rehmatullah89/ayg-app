<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderCloseOrderIdTest
 *
 * tested endpoint:
 * // Reset internal Order for the User and Retailer
 * '/close/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId'
 */
class ConsumerOrderCloseOrderIdTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testOrderCanBeClosed()
    {
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/close', $user->getSessionToken(), "orderId/$orderId");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->reset);


    }

    /**
     *
     */
    public function testOrderCanNotBeClosedWhenThereIsNoOrder()
    {
        $user = $this->createUser();
        $orderId = 'someOrderIdThatWillNeverHappen';

        $url = $this->generatePath('order/close', $user->getSessionToken(), "orderId/$orderId");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_856', $responseDecoded->error_code);


    }

    /**
     *
     */
    public function testOrderCanNotBeInitiatedWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();
        $url = $this->generatePath('order/close', $sessionToken, "orderId/$orderId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);


    }
}