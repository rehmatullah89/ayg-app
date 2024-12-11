<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerWebBetaListTest
 *
 * tested endpoint:
 * // List of users by type beta type
 * '/web/beta/list/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerWebBetaListTest extends ConsumerBaseTest
{
    public function testBetaListCanBeTaken()
    {
        $user = $this->createUser();

        $url = $this->generatePathForWebEndpoints('web/beta/list', $user->getSessionToken(), "");
        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertArrayHasKey('totalUsers',$arrayJsonDecodedBody);
        $this->assertArrayHasKey('active',$arrayJsonDecodedBody);
        $this->assertArrayHasKey('inactive',$arrayJsonDecodedBody);
        $this->assertArrayHasKey('discrpenacy',$arrayJsonDecodedBody);
        $this->assertArrayHasKey('webbeta',$arrayJsonDecodedBody['discrpenacy']);

    }


}