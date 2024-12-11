<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerTipCheckTest
 *
 * tested endpoint:
 * // Check if Tip is allowed for this Retailer
 * '/tipCheck/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId',
 * @todo maybe check result when retailer is down
 */
class ConsumerRetailerTipCheckTest extends ConsumerBaseTest
{

    public function testRetailerCanCheckTip()
    {
        $user = $this->createUser();
        $retailerId = $this->parseFindFirstRetailerWithRetailerPOSConfigAndGetUniqueId();
        $url = $this->generatePath('retailer/tipCheck', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $jsonDecodedBody=$jsonDecodedBody[0];

        $this->assertTrue($jsonDecodedBody->allowed == 0 || $jsonDecodedBody->allowed ==1);


    }

    public function testNotExistingRetailerCanNotBePinged()
    {
        $user = $this->createUser();
        $retailerId = '2867fd66a496c15a470ac5486c48f60e1';
        $url = $this->generatePath('retailer/tipCheck', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());
        $jsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertEquals('AS_507', $jsonDecodedBody['error_code']);


    }

    /**
     *
     */
    public function testThatRetailerCanNotBePingedWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $retailerId = '2867fd66a496c15a470ac5486c48f60e';
        $url = $this->generatePath('retailer/tipCheck', $sessionToken, "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}