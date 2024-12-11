<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserForgotRequestTokenTest
 *
 * tested endpoint:
 * // Checkin
 * '/user/forgot/requestToken/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email'
 */
class ConsumerUserForgotRequestTokenTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanCheckin()
    {
        $email = 'ludwik.grochowina+' . rand(1, 1000) . '@gmail.com';
        $type = 'c';

        $user = $this->createUser([
            'email' => $email,
        ]);

        $url = $this->generatePath('user/forgot/requestToken', $user->getSessionToken(), "type/$type/email/$email");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertTrue($jsonDecodedBody->status);


    }

}