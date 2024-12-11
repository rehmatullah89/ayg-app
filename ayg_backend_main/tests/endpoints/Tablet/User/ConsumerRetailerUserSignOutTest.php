<?php

use Parse\ParseQuery;

require_once __DIR__ . '/../TabletBaseTest.php';

/**
 * Class ConsumerRetailerUserSignOutTest
 *
 * tested endpoint:
 * // Sign in User with username and password
 * '/user/signin/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerRetailerUserSignOutTest extends TabletBaseTest
{
    /**
     *
     */
    public function t1estCanSignInAndSignOutWhenRetailerIsConnected()
    {
        $email = 'ludwik.grochowina+tablet@gmail.com';

        $url = $this->generatePath('tablet/user/signin', 0, "");

        $response = $this->post($url, [
            'username' => ($email),
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
            'type' => 't',
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('200', $response->getHttpStatusCode());
        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('sessionToken', $arrayJsonDecodedResponse);
        $this->assertArrayHasKey('retailerName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLocationName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLogoUrl', $arrayJsonDecodedResponse['retailerShortInfo']);
        $sessionToken = $jsonDecodedResponse->sessionToken;
        $sessionToken = explode('-', $sessionToken);
        $sessionToken = $sessionToken[0];
        $url = $this->generatePath('tablet/user/signout', $sessionToken, "");
        $response = $this->post($url, [
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertTrue($jsonDecodedResponse->status);
    }

    /**
     *
     */
    public function testCanSignInAndSignOutWithWrongPasswordWhenRetailerIsConnected()
    {
        $email = 'ludwik.grochowina+tablet@gmail.com';

        $url = $this->generatePath('tablet/user/signin', 0, "");

        $response = $this->post($url, [
            'username' => ($email),
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
            'type' => 't',
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('200', $response->getHttpStatusCode());
        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('sessionToken', $arrayJsonDecodedResponse);
        $this->assertArrayHasKey('retailerName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLocationName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLogoUrl', $arrayJsonDecodedResponse['retailerShortInfo']);
        $sessionToken = $jsonDecodedResponse->sessionToken;
        $sessionToken = explode('-', $sessionToken);
        $sessionToken = $sessionToken[0];
        $url = $this->generatePath('tablet/user/signout', $sessionToken, "");
        $response = $this->post($url, [
            'password' => 'zFiUuB1RshbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_463', $jsonDecodedResponse['error_code']);
    }

    /**
     *
     */
    public function testCanSignInAndSignOutWithNotEncryptedPasswordWhenRetailerIsConnected()
    {
        $email = 'ludwik.grochowina+tablet@gmail.com';

        $url = $this->generatePath('tablet/user/signin', 0, "");

        $response = $this->post($url, [
            'username' => ($email),
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
            'type' => 't',
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('200', $response->getHttpStatusCode());
        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('sessionToken', $arrayJsonDecodedResponse);
        $this->assertArrayHasKey('retailerName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLocationName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLogoUrl', $arrayJsonDecodedResponse['retailerShortInfo']);
        $sessionToken = $jsonDecodedResponse->sessionToken;
        $sessionToken = explode('-', $sessionToken);
        $sessionToken = $sessionToken[0];
        $url = $this->generatePath('tablet/user/signout', $sessionToken, "");
        $response = $this->post($url, [
            'password' => 'someNotEncryptedPassword',
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_464', $jsonDecodedResponse['error_code']);
    }
}