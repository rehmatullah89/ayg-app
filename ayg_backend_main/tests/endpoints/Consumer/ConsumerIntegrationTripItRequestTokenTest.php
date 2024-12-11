<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerIntegrationTripItRequestTokenTest
 *
 * tested endpoint:
 * // Request Token
 * '/integrations/tripIt/requestToken/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerIntegrationTripItRequestTokenTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatTripItRequestTokenCanBeRequested()
    {
        $user = $this->createUser();
        $url = $this->generatePath('integrations/tripIt/requestToken', $user->getSessionToken(), "");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('oauth_token', $arrayJsonDecodedBody);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals($jsonDecodedBody->oauth_token, $arrayJsonDecodedBody['oauth_token']);

    }

    /**
     *
     */
    public function testThatTripItRequestTokenCanNotBeRequestedWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('integrations/tripIt/requestToken', $sessionToken, "");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}