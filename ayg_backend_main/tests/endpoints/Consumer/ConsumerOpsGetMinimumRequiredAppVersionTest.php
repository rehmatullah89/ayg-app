<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOpsGetMinimumRequiredAppVersionTest
 *
 * tested endpoint:
 * // Gets minimum required app version
 * '/ops/bug/a/:apikey/e/:epoch/u/:sessionToken
 */
class ConsumerOpsGetMinimumRequiredAppVersionTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testForMinimumRequiredAppVersion()
    {
        $user = $this->createUser();

        $url = $this->generatePath('ops/getMinAppVersion', $user->getSessionToken(), "");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertEquals("1.5.0", $jsonDecodedBody->minAppVersionReqForAPI);


    }

}