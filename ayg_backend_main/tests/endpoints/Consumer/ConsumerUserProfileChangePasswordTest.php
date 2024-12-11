<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserProfileChangePasswordTest
 *
 * tested endpoint:
 * // Checkin
 * '/user/profile/changePassword/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerUserProfileChangePasswordTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanCheckin()
    {
        $user = $this->createUser();

        $url = $this->generatePath('user/profile/changePassword', $user->getSessionToken(), "");

        // sample for password ("PASSword000")
        // tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==

        // sample for password ("NewPASSword000")
        // 33T1OvinHTdTDfYScUbExg==:MNLvsF9eUIVhimNqiUs4Qw==

        $postData = [
            'oldPassword' => 'tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==',
            'newPassword' => '33T1OvinHTdTDfYScUbExg==:MNLvsF9eUIVhimNqiUs4Qw==',
        ];

        $response = $this->post($url, $postData);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertTrue($jsonDecodedBody->changed);


    }

    /**
     *
     */
    public function testCanNotCheckinWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('user/checkin', $sessionToken, "");

        $postData = [
            'oldPassword' => 'tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==',
            'newPassword' => '33T1OvinHTdTDfYScUbExg==:MNLvsF9eUIVhimNqiUs4Qw==',
        ];

        $response = $this->post($url, $postData);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_455', $responseDecoded->error_code);
    }
}