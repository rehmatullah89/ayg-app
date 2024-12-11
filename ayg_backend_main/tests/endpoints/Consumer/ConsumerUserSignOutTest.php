<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserSignOutTest
 *
 * tested endpoint:
 * // Checkin
 * '/user/info/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerUserSignOutTest extends ConsumerBaseTest
{
    /**
     * @todo sign out invalid session token
     */
    public function testCanGetInfo()
    {
        $email = 'ludwik.grochowina+' . md5(time() . rand(1, 10000)) . '@gmail.com';
        $username = $email . '-c';

        $user = $this->createUser([
            'username' => $username,
            'email' => $email,
            'password' => md5('PASSword000' . getenv('env_PasswordHashSalt')),
        ]);
        $url = $this->generatePath('user/signin', $user->getSessionToken(), "");

        // sample for password ("PASSword000")
        // tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==

        // sample for deviceArray:
        // eyJhcHBWZXJzaW9uIjoxLCJpc0lvcyI6MSwiaXNBbmRyb2lkIjowLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOjExLjExLCJnZW9Mb25naXR1ZGUiOjExLjExfQ==


        $response = $this->post($url, [
            'username' => urlencode($email),
            'email' => urlencode($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==',
            'type' => 'c',
        ]);


        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);



        $this->assertArrayHasKey('u', $arrayJsonDecodedBody);

        $sessionToken = substr($arrayJsonDecodedBody['u'], 0, -2);

        $url = $this->generatePath('user/signout', $sessionToken, "");
        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();


        $this->assertTrue($jsonDecodedBody->status);

        // we need to delete using master key, since there is no session token we can use to log in
        $this->forceMasterDelete = true;

    }

}