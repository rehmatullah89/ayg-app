<?php

use Parse\ParseQuery;

require_once __DIR__ . '/../TabletBaseTest.php';
require_once __DIR__ . '/../../../../putenv.php';
define('AES_256_CBC', 'aes-256-cbc');
require_once __DIR__ . '/../../../../lib/functions.php';
require_once __DIR__ . '/../../../../lib/functions_userauth.php';


$env_StringInMotionEncryptionKey = getenv('env_StringInMotionEncryptionKey');
$env_RetailerPOSStringInMotionEncryptionKey = getenv('env_RetailerPOSStringInMotionEncryptionKey');

/**
 * Class RetailerUserSignInTest
 *
 * tested endpoint:
 * // Sign in User with username and password
 * '/user/signin/a/:apikey/e/:epoch/u/:sessionToken'
 */
class RetailerUserSignInTest extends TabletBaseTest
{
    /**
     *
     */
    public function testCanSignInWhenRetailerIsConnected()
    {

        $email = 'auntie@airportsherpa.io';
        $pass = 'Tablet@123';
        $pass=encryptString($pass, $GLOBALS['env_RetailerPOSStringInMotionEncryptionKey']);


        $url = $this->generatePath('tablet/user/signin', 0, "");

        $response = $this->post($url, [
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => $pass,
            'type' => 't',
        ]);


        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);

        var_dump($arrayJsonDecodedResponse);

        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertArrayHasKey('sessionToken', $arrayJsonDecodedResponse);
        $this->assertArrayHasKey('retailerName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLocationName', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLogoUrl', $arrayJsonDecodedResponse['retailerShortInfo']);
        $this->assertArrayHasKey('userType', $arrayJsonDecodedResponse['retailerShortInfo']);


        //retailerPingInfo part
        $this->assertArrayHasKey('config', $arrayJsonDecodedResponse);
        $this->assertArrayHasKey('pingInterval', $arrayJsonDecodedResponse['config']);
        $this->assertArrayHasKey('notificationSoundUrl', $arrayJsonDecodedResponse['config']);
        $this->assertArrayHasKey('notificationVibrateUsage', $arrayJsonDecodedResponse['config']);
        $this->assertArrayHasKey('batteryCheckInterval', $arrayJsonDecodedResponse['config']);

        /*
        $this->assertTrue($jsonDecodedResponse->success);

        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('sessionToken', $arrayJsonDecodedResponse['data']);

        $this->assertArrayHasKey('retailerName', $arrayJsonDecodedResponse['data']['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLocationName', $arrayJsonDecodedResponse['data']['retailerShortInfo']);
        $this->assertArrayHasKey('retailerLogoUrl', $arrayJsonDecodedResponse['data']['retailerShortInfo']);
        */
    }

    /**
     *
     */
    public function t1estErrorWhenNoRetailerIsConnected()
    {
        $email = 'ludwik.grochowina+tablet_no_retailer@gmail.com';

        $url = $this->generatePath('tablet/user/signin', 0, "");

        $response = $this->post($url, [
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
            'type' => 't',
        ]);


        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5300', $jsonDecodedResponse->error_code);

        //$this->assertFalse($jsonDecodedResponse->success);
        //$this->assertEquals(12153410, $jsonDecodedResponse->error_code);
        //$this->assertEquals('This user is not connected to any retailer', $jsonDecodedResponse->error->message);
    }

    /**
     *
     */
    public function t1estErrorWhenUserHasNoAccess()
    {
        $email = 'ludwik.grochowina+tablet_no_access@gmail.com';

        $url = $this->generatePath('tablet/user/signin', 0, "");

        $response = $this->post($url, [
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
            'type' => 't',
        ]);


        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_465', $jsonDecodedResponse->error_code);


        /*
        $this->assertFalse($jsonDecodedResponse->success);

        $this->assertEquals(12153411, $jsonDecodedResponse->error->code);
        $this->assertEquals('This user has no rights to use tablet application', $jsonDecodedResponse->error->message);
        */
    }

    public function t1estErrorWhenTabletUserTriesToLoginToConsumerApp()
    {
        // password encrypted by consumer key
        $email = 'ludwik.grochowina+tablet@gmail.com';

        $url = $this->generatePathWithConsumerKey('user/signin', 0, "");

        $response = $this->post($url, [
            'email' => urlencode($email),
            'username' => urlencode($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'tdzQf8DtJ99sX8v1EiVhzg==:VkY39uoO5pWZ3\/7IiVe91g==',
            'type' => 't',
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertEquals('AS_417', $jsonDecodedResponse->error_code);

        /*

        $this->assertEquals('AS_417', $jsonDecodedResponse->error_code);
        $this->assertEquals('User without consumer access tries to login to the consumer app', $jsonDecodedResponse->error_description);
        */
    }


    public function t1estCanNotSignInWithBadCredentials()
    {
        $email = 'lud1wik.grochowina+tablet@gmail.com';

        $url = $this->generatePath('tablet/user/signin', 0, "");

        $response = $this->post($url, [
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
            'type' => 't',
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_020', $jsonDecodedResponse->error_code);
    }

    public function t1estConsumerAppSessionCanBeUsed()
    {
        // token after login as consumer 'ludwik.grochowina+as2@gmail.com'
        $token = 'r:22940aff69a6f5ed05a97230e1ea1735-c';
        $token = str_replace('-c', '', $token);

        // try to get active orders

        $url = $this->generatePath('tablet/order/getActiveOrders', $token, "page/1/limit/10");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('401', $response->getHttpStatusCode());
        $this->assertEquals('AS_027', $jsonDecodedResponse->error_code);

        $url = $this->generatePathForConsumer('tablet/order/getActiveOrders', $token, "page/1/limit/10");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('401', $response->getHttpStatusCode());
        $this->assertEquals('AS_027', $jsonDecodedResponse->error_code);

    }
}