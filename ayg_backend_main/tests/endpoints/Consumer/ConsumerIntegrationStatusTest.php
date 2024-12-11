<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerIntegrationStatusTest
 *
 * tested endpoint:
 * // Status
 * '/integrations/status/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerIntegrationStatusTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatIntegrationStatusCanBeTaken()
    {
        $user = $this->createUser();
        $url = $this->generatePath('integrations/status', $user->getSessionToken(), "");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertFalse($jsonDecodedBody->tripIt);

        $this->deleteAllAddedObjectsAndLogout();
    }

    /**
     *
     */
    public function testThatIntegrationStatusCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken='someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('integrations/status', $sessionToken, "");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}