<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerWebTokenCheckTest
 *
 * tested endpoint:
 * // Check Web Token from saved list
 * '/web/token/check/a/:apikey/e/:epoch/u/:sessionToken/token/:token'
 */
class ConsumerWebTokenCheckTest extends ConsumerBaseTest
{
    /**
     * @todo there is no result on success
     */
    public function testTokenCanBeChecked()
    {
        $user = $this->createUser();

        $token = 'someToken';

        $url = $this->generatePathForWebEndpoints('web/token/check', $user->getSessionToken(), "token/$token");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->used);


    }

}