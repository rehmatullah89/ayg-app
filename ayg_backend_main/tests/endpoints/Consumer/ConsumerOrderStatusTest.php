<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderStatusTest
 *
 * tested endpoint:
 * // Get Order Status rows
 * '/status/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId'
 */
class ConsumerOrderStatusTest extends ConsumerBaseTest
{
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
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/status', $sessionToken, "orderId/$orderId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }


}