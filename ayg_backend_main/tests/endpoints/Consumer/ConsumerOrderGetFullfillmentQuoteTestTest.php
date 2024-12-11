<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderGetFullfillmentQuoteTestTest
 *
 * tested endpoint:
 * Order fullfillment quote
 * '/getFullfillmentQuote/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/deliveryLocation/:deliveryLocation/requestedFullFillmentTimestamp/:requestedFullFillmentTimestamp'
 */
class ConsumerOrderGetFullfillmentQuoteTestTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanGetQuote()
    {
        $user = $this->createUser();
        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();
        $deliveryLocation = 'A5NYnxOiJF';
        $requestedFullFillmentTimestamp = '0';

        $url = $this->generatePath('order/getFullfillmentQuote', $user->getSessionToken(), "orderId/$orderId/deliveryLocation/$deliveryLocation/requestedFullFillmentTimestamp/$requestedFullFillmentTimestamp");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertArrayHasKey('d', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('p', $arrayJsonDecodedBody);

        $dExpectedKeys = [
            'isAvailable',
            'isAvailableAtDeliveryLocation',
            'fullfillmentFeesInCents',
            'fullfillmentFeesDisplay',
            'fullfillmentTimeEstimateInSeconds',
            'TotalInCents',
            'TotalDisplay',
        ];
        $pExpectedKeys = [
            'isAvailable',
            'fullfillmentFeesInCents',
            'fullfillmentFeesDisplay',
            'fullfillmentTimeEstimateInSeconds',
            'TotalInCents',
            'TotalDisplay',
        ];

        foreach ($dExpectedKeys as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody['d']);
        }
        foreach ($pExpectedKeys as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody['p']);
        }

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals($arrayJsonDecodedBody['d']['isAvailable'],$jsonDecodedBody->d->isAvailable);


    }


    /**
     *
     */
    public function testCanNotGetQuoteWithoutUser()
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
        $deliveryLocation = 'A5NYnxOiJF';
        $requestedFullFillmentTimestamp = '0';

        $url = $this->generatePath('order/getFullfillmentQuote', $sessionToken, "orderId/$orderId/deliveryLocation/$deliveryLocation/requestedFullFillmentTimestamp/$requestedFullFillmentTimestamp");


        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);


    }
}