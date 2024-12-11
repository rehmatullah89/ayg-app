<?php
namespace tests\endpoints\ConsumerNew\User;

use Parse\ParseObject;
use Parse\ParseQuery;
use tests\endpoints\ConsumerNew\ConsumerBaseTest;

require_once __DIR__ . '/../ConsumerBaseTest.php';

/**
 * Class ConsumerUserAddPhoneTest
 *
 * tested endpoint:
 * // Order Item Count
 * '/addPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneCountryCode/:phoneCountryCode/phoneNumber/:phoneNumber'
 */
class ConsumerUserAddPhoneTest extends ConsumerBaseTest
{
    public function testCanAddPhone()
    {


        //$user = $this->createUser();
        //$phoneCountryCode = '1';
        //$phoneNumber = '215-620-9582';
        $phoneCountryCode = '1';
        $phoneNumber = '8587805512';


        //  +1
        // fake number from twilio

        //$user = \Parse\ParseUser::logIn('itsursujit+addphone@gmail.com-c', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $url = $this->generatePath('user/signin', 0, "");
        $response = $this->post($url, [
            'username' => urlencode('itsursujit+addphone@gmail.com'),
            'email' => urlencode('itsursujit+addphone@gmail.com'),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==',
            'type' => 'c',
        ]);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $sessionToken = $jsonDecodedBody->u;


        $url = $this->generatePath('user/addPhoneWithTwilio', str_replace('-c', '', $sessionToken), "phoneCountryCode/$phoneCountryCode/phoneNumber/$phoneNumber");

        $response = $this->get($url);

        //$response = $user->getSessionToken();
        $jsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertArrayHasKey('addedPhoneId', $jsonDecodedBody);
    }

}