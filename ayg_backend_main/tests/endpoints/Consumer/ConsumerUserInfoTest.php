<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserInfoTest
 *
 * tested endpoint:
 * // User Info
 * '/user/info/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerUserInfoTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanGetInfo()
    {
        $user = $this->createUser();
        $url = $this->generatePath('user/info', $user->getSessionToken(), "");
        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $keysThatShouldAppear=[
            'isLoggedIn',
            'isEmailVerified',
            'isBetaActive',
            'isLocked',
            'isPhoneVerified',
            'firstName',
            'lastName',
            'email',
            'phoneCountryCode',
            'phoneNumber',
            'isActive',
            'availableCredits',
            'availableCreditsDisplay',
        ];

        foreach ($keysThatShouldAppear as $item){
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody);
        }

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertTrue($jsonDecodedBody->isLoggedIn);


    }

    /**
     *
     */
    public function testCanNotGetInfoWithBadSessionToken()
    {
        $sessionToken='someSessionTokenThatCanNotBeCreated';

        $url = $this->generatePath('user/info', $sessionToken, "");
        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}