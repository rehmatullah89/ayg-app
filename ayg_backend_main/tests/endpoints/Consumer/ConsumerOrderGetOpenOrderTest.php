<?php

require_once 'ConsumerBaseTest.php';

use \Mockery as M;

/**
 * Class ConsumerOrderGetOpenOrderTest
 *
 * tested endpoint:
 * // Get open Order without a retailer
 * '/getOpenOrder/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerOrderGetOpenOrderTest extends ConsumerBaseTest
{




    /**
     *
     */
    public function testJustCreatedOrderCanBeTaken()
    {
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);

        $url = $this->generatePath('order/getOpenOrder', $user->getSessionToken(), '');

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals($order->getObjectId(), $jsonDecodedBody->orderId);
        $this->assertEquals($retailer->get('uniqueId'), $jsonDecodedBody->retailerId);

    }


    /**
     *
     */
    public function testNewUserDoNotHaveOrder()
    {
        $user = $this->createUser();

        $url = $this->generatePath('order/getOpenOrder', $user->getSessionToken(), '');

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals('', $jsonDecodedBody->orderId);
        $this->assertEquals('', $jsonDecodedBody->retailerId);


    }

    /**
     *
     */
    public function testThatOrderCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('order/getOpenOrder', $sessionToken, '');

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}