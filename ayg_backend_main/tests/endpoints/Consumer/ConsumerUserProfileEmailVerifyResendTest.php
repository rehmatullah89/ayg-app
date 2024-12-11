<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserProfileEmailVerifyResendTest
 *
 * tested endpoint:
 * // Checkin
 * '/user/profile/emailVerifyResend/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerUserProfileEmailVerifyResendTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanCheckin()
    {
        $user = $this->createUser([
            'email' => 'ludwik.grochowina+' . rand(1, 1000) . '@gmail.com'
        ]);

        $url = $this->generatePath('user/profile/emailVerifyResend', $user->getSessionToken(), "");

        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertTrue($jsonDecodedBody->status);


    }

    /**
     *
     */
    public function testCanNotCheckinWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';

        $url = $this->generatePath('user/profile/emailVerifyResend', $sessionToken, "");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}