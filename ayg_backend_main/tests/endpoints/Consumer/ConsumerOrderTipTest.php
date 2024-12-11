<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderTipTest
 *
 * tested endpoint:
 * // Apply Tip
 * '/tip/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/tipPct/:tipPct'
 */
class ConsumerOrderTipTest extends ConsumerBaseTest
{
    public function testTipCanBeSet()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();

        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();
        $tipPct = 120;

        $url = $this->generatePath('order/tip', $user->getSessionToken(), "orderId/$orderId/tipPct/$tipPct");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertInternalType('array', $jsonDecodedBody);
        $this->assertEquals(1, $jsonDecodedBody[0]->applied);


    }


    /**
     *
     */
    public function testTipCanNotBeInitiatedWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();

        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();
        $tipPct = 120;

        $url = $this->generatePath('order/tip', $sessionToken, "orderId/$orderId/tipPct/$tipPct");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);


    }
}