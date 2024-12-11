<?php

use Parse\ParseQuery;

require_once __DIR__ . '/../TabletBaseTest.php';
require_once __DIR__ . '/../../../../putenv.php';
define('AES_256_CBC', 'aes-256-cbc');
require_once __DIR__ . '/../../../../lib/functions.php';

/**
 * Class LogoutWhenSessionDeviceIsChangedToNotActiveTest
 *
 */
class LogoutWhenSessionDeviceIsChangedToNotActiveTest extends TabletBaseTest
{
    public function testCanSignInWhenRetailerIsConnected()
    {

        $email = 'ludwik.grochowina+tablet@gmail.com';

        $url = $this->generatePath('tablet/user/signin', 0, "");
        var_dump($url);

        // 1) signin - expect success (http code 200), also get session token for next calls
        $response = $this->post($url, [
            'email' => ($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'zFiUuB1RbhbB0nJPp1d0rw==:y3wXNRmPYtZwbrZgo6vkSQ==',
            'type' => 't',
        ]);
        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertEquals('200', $response->getHttpStatusCode());

        $sessionToken = $arrayJsonDecodedResponse['sessionToken'];
        $sessionToken = str_replace('-t', '', $sessionToken);

        // 2) check if SessionDevices is added
        $parseUserInnerQuery = new ParseQuery('_User');
        $parseUserInnerQuery->equalTo('email', $email);
        $parseQuery = new ParseQuery('SessionDevices');
        $parseQuery->equalTo('sessionTokenRecall', $sessionToken);
        $parseQuery->equalTo('isActive', true);
        $parseQuery->matchesQuery('user', $parseUserInnerQuery);
        $currentParseSessionDeviceCount = $parseQuery->count();
        $this->assertEquals(1, $currentParseSessionDeviceCount);


        // 3) try to use any endpoint (get Active orders) - expect success (http code 200)
        $url = $this->generatePath('tablet/order/getActiveOrders', $sessionToken, "page/1/limit/10");
        $response = $this->get($url);
        $this->assertEquals('200', $response->getHttpStatusCode());


        // 4) modify SessionDevices to be inactive
        $currentParseSessionDevice = $parseQuery->first();
        $currentParseSessionDevice->set('isActive', false);
        $currentParseSessionDevice->save();

        // 5) check if endpoint fails
        $url = $this->generatePath('tablet/order/getActiveOrders', $sessionToken, "page/1/limit/10");
        $response = $this->get($url);
        var_dump($response);
        //$this->assertEquals('401', $response->getHttpStatusCode());
    }
}