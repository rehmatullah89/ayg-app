<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerPingTest
 *
 * tested endpoint:
 * // Ping to check if the Retailer POS is up
 * '/ping/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId'
 * @todo maybe check result when retailer is down
 */
class ConsumerRetailerPingTest extends ConsumerBaseTest
{



    public function t1estRetailerCanBePinged()
    {
        $user = $this->createUser();
        $retailerId='77c90d10ded893290b6775b98da8b719';
        $url = $this->generatePath('retailer/ping', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);var_dump($response);

        $this->assertTrue($response->isHttpResponseCorrect());
        $jsonDecodedBody = $response->getJsonDecodedBody();var_dump($response);

        //$this->assertTrue($jsonDecodedBody->available==1 || $jsonDecodedBody->available==0);


    }

    public function testRetailerCanBePinged()
    {
        $user = $this->createUser();
        $retailerId=$this->parseFindFirstRetailerWithRetailerPOSConfigAndGetUniqueId();
        $url = $this->generatePath('retailer/ping', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());
        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertTrue($jsonDecodedBody->available==1 || $jsonDecodedBody->available==0);


    }


    public function testNotExistingRetailerCanNotBePinged()
    {
        $user = $this->createUser();
        $retailerId='2867fd66a496c15a470ac5486c48f60e1';
        $url = $this->generatePath('retailer/ping', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());
        $jsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertEquals('AS_508', $jsonDecodedBody['error_code']);


    }



    /**
     *
     */
    public function testThatRetailerCanNotBePingedWithBadSessionToken()
    {
        $sessionToken='someSessionTokenThatCanNotBeCreated';
        $retailerId='2867fd66a496c15a470ac5486c48f60e';
        $url = $this->generatePath('retailer/ping', $sessionToken, "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}