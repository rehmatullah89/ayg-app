<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerWebTokenTest
 *
 * tested endpoint:
 * // Add Web Token to saved list
 * '/web/token/add/a/:apikey/e/:epoch/u/:sessionToken/token/:token'
 */
class ConsumerWebTokenTest extends ConsumerBaseTest
{
    /**
     * @todo there is no result on success
     */
    public function testTokenCanBeSaved()
    {
        $user = $this->createUser();

        $token = 'someToken';

        $url = $this->generatePathForWebEndpoints('web/token/add', $user->getSessionToken(), "token/$token");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertNull($jsonDecodedBody);


    }

}