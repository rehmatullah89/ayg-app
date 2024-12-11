<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserProfileChangeEmailTest
 *
 * tested endpoint:
 * // Checkin
 * 'user/profile/changeEmail/a/:apikey/e/:epoch/u/:sessionToken/newEmail/:newEmail'
 */
class ConsumerUserProfileChangeEmailTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanCheckin()
    {
        $user = $this->createUser();

        $newEmail = 'ludwik.grochowina+' . rand(1, 1000) . '@gmail.com';
        $url = $this->generatePath('user/profile/changeEmail', $user->getSessionToken(), "newEmail/$newEmail");

        $response=$this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        //var_dump($jsonDecodedBody);
        $this->assertTrue($jsonDecodedBody->changed);


    }


    /**
     *
     */
    public function testCanNotCheckinWithBadSessionToken()
    {
        $user = $this->createUser();
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';

        $newEmail = 'ludwik.grochowina+' . rand(1, 1000) . '@gmail.com';
        $url = $this->generatePath('user/profile/changeEmail', $sessionToken, "newEmail/$newEmail");

        $response=$this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);

    }
}