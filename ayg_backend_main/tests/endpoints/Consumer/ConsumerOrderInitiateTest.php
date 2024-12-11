<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderInitiateTest
 *
 * tested endpoint:
 * // Create internal Order for the User and Retailer
 * '/initiate/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:uniqueRetailerId'
 */
class ConsumerOrderInitiateTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testOrderCanBeInitiated()
    {
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailer();
        $uniqueRetailerId=$retailer->get('uniqueId');

        $url = $this->generatePath('order/initiate', $user->getSessionToken(), "retailerId/$uniqueRetailerId");

        $response = $this->get($url);

        // call is creating new order, so order should be pushed on the stack
        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->pushOnObjectsStack(new ObjectsStackItem($jsonDecodedBody->orderId,'Order'));
        $this->assertEquals(1, $jsonDecodedBody->status);
        $this->assertEquals('', $jsonDecodedBody->openOrderId);
        $this->assertEquals('', $jsonDecodedBody->openRetailerId);


        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('orderId', $arrayJsonDecodedBody);

    }

    /**
     *
     */
    public function testOrderCanNotBeInitiatedWithoutRetailer()
    {
        $user = $this->createUser();
        $uniqueRetailerId='someRetailerUniqueIdThatWillNeverHappen';

        $url = $this->generatePath('order/initiate', $user->getSessionToken(), "retailerId/$uniqueRetailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_867', $responseDecoded->error_code);


    }

    /**
     *
     */
    public function testOrderCanNotBeInitiatedWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $retailer = $this->parseGetFirstRetailer();
        $uniqueRetailerId=$retailer->get('uniqueId');
        $url = $this->generatePath('order/initiate', $sessionToken, "retailerId/$uniqueRetailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }




}