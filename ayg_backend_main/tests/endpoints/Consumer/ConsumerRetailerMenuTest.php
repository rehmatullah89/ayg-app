<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerMenuTest
 *
 * tested endpoint:
 * // Get first level Menu for the Retailer
 * '/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId'
 */
class ConsumerRetailerMenuTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $retailerId = 'f280412bdac343cabaec407413339ac1';
        $url = $this->generatePath('retailer/menu', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertInternalType('array', $arrayJsonDecodedBody);

        // get first element:
        $firstElement = reset($arrayJsonDecodedBody);
        $firstElement = reset($firstElement);


        $keysThatShouldAppear = [
            'itemId',
            'itemName',
            'itemDescription',
            'itemPrice',
            'itemPriceDisplay',
            'itemImageURL',
        ];
        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $firstElement[0]);
        }


    }


    public function testThatEmptyListCanBeTaken()
    {
        $user = $this->createUser();
        $retailerId = 'someretailer';
        $url = $this->generatePath('retailer/menu', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertEquals('AS_508', $jsonDecodedBody->error_code);

    }

    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $retailerId = '2867fd66a496c15a470ac5486c48f60e';
        $url = $this->generatePath('retailer/menu', $sessionToken, "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}