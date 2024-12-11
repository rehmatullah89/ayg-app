<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderCloseTest
 *
 * tested endpoint:
 * // Create internal Order for the User and Retailer
 * '/close/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:uniqueRetailerId'
 */
class ConsumerOrderCloseTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testOrderCanBeClosed()
    {
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailer();
        $uniqueRetailerId=$retailer->get('uniqueId');
        $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);

        $url = $this->generatePath('order/close', $user->getSessionToken(), "retailerId/$uniqueRetailerId");

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
        $retailer = $this->parseGetFirstRetailer();
        $uniqueRetailerId=$retailer->get('uniqueId');

        $url = $this->generatePath('order/close', $user->getSessionToken(), "retailerId/$uniqueRetailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_857', $responseDecoded->error_code);
    }

    /**
     *
     */
    public function testOrderCanNotBeInitiatedWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $retailer = $this->parseGetFirstRetailer();
        $uniqueRetailerId=$retailer->get('uniqueId');
        $url = $this->generatePath('order/close', $sessionToken, "retailerId/$uniqueRetailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }



}