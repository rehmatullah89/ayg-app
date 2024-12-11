<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderActiveCountTest
 *
 * tested endpoint:
 * //
 * '/activecount/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerOrderActiveCountTest extends ConsumerBaseTest
{
    public function testActiveCountCanBeGet()
    {
        $user = $this->createUser();

        $url = $this->generatePath('order/activecount', $user->getSessionToken(), "");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(0, $jsonDecodedBody->count);


    }

    /**
     *
     */
    public function testActiveCountCanNotBeGetWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';

        $url = $this->generatePath('order/activecount', $sessionToken, "");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);

    }
}