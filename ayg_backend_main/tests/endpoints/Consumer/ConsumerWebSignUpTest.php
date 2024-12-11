<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerWebSignUpTest
 *
 * tested endpoint:
 * // Save Beta signup from the website
 * '/web/signup/a/:apikey/e/:epoch/u/:sessionToken/email/:email/deviceId/:deviceId'
 */
class ConsumerWebSignUpTest extends ConsumerBaseTest
{
    /**
     * @todo there is no result on success
     */
    public function testSignUpCanBeDone()
    {
        $user = $this->createUser();

        $email = 'ludwik.grochowina+' . md5(time() . rand(1, 10000)) . '@gmail.com';
        $deviceId = 'someTestDeviceId';

        $url = $this->generatePathForWebEndpoints('web/signup', $user->getSessionToken(), "email/$email/deviceId/$deviceId");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertNull($jsonDecodedBody);




    }

}