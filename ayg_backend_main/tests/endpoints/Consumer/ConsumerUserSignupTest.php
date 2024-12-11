<?php

use Parse\ParseSession;
use Parse\ParseUser;

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserSignupTest
 *
 * tested endpoint:
 * // Order Item Count
 * '/signup/usernameCheck/a/:apikey/e/:epoch/u/:sessionToken/type/:type/email/:email'
 * @todo Illegal string offset 'error_code'</div><div><strong>File:</strong> /Users/ludwik/Projects/airportsherpa_one/public/user/index.php</div><div><strong>Line:</strong> 213
 */
class ConsumerUserSignupTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCanSignup()
    {
        $url = $this->generatePath("user/signup", 0, "");

        $response = $this->post($url, [
            'type' => 'c',
            'firstName' => 'TestFirstname',
            'lastName' => 'LastNameTest',
            'password' => 'tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==',
            'email' => 'ludwik.grochowina+' . rand(0, 1000) . '@gmail.com',
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
        ]);

        var_dump($response);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('u', $arrayJsonDecodedBody);

        // delete just created user
        $sessionToken = substr($arrayJsonDecodedBody['u'], 0, -2);
        $user = ParseUser::become($sessionToken);
        $this->pushOnObjectsStack(new ObjectsStackItem($user->getObjectId(), '_User'));


    }

}