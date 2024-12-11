<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerWebResetCacheTest
 *
 * tested endpoint:
 * // Beta Inactivation reset cache
 * '/web/beta/resetcache/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerWebResetCacheTest extends ConsumerBaseTest
{
    /**
     * @todo info about success or not needed
     */
    public function testCacheCanBeReset()
    {
        $user = $this->createUser();

        $url = $this->generatePathForWebEndpoints('web/beta/resetcache', $user->getSessionToken(), "");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertNull($jsonDecodedBody);


    }


}