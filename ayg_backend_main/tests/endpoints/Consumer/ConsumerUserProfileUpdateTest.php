<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserProfileUpdateTest
 *
 * tested endpoint:
 * // Change Profile options -- First Name, Last Name, SMSNotificationsEnabled
 * '/user/profile/update/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerUserProfileUpdateTest extends ConsumerBaseTest
{
    public function testCanUpdate()
    {
        $user = $this->createUser();

        $this->addParseObjectAndPushObjectOnStack('UserPhones', [
            'user' => $user,
            'isActive' => true,
            'phoneVerified' => true,
        ]);

        $this->addParseObjectAndPushObjectOnStack('UserDevices', [
            'user' => $user,
        ]);
        $url = $this->generatePath('user/profile/update', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'firstName' => 'testFirstname',
            'lastName' => 'testLastname',
            'SMSNotificationsEnabled' => 'false',
            'pushNotificationsEnabled' => 'false',
        ]);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertTrue($jsonDecodedBody->changed);



    }
}