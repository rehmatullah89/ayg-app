<?php


use Parse\ParseQuery;

require_once __DIR__ . '/../ConsumerBaseTest.php';
require_once __DIR__ . '/../../../../putenv.php';
define('AES_256_CBC', 'aes-256-cbc');
$env_PasswordHashSalt = getenv('env_PasswordHashSalt');
$env_StringInMotionEncryptionKey = getenv('env_StringInMotionEncryptionKey');

require_once __DIR__ . '/../../../../lib/functions.php';
require_once __DIR__ . '/../../../../lib/functions_userauth.php';

/**
 * Class LogoutWhenSessionDeviceIsChangedToNotActiveTest
 *
 */
class LogoutWhenSessionDeviceIsChangedToNotActiveTest extends ConsumerBaseTest
{
    public function testCanSignInWhenRetailerIsConnected()
    {

        $email = 'ludwik.grochowina+as2@gmail.com';
        // user Id 9bl5cZpt0p


        $url = $this->generatePath('user/signin', 0, "");

        $passToSaveInDb = generatePasswordHash('PASSword000');
        //var_dump($passToSaveInDb);

        $passwordToSend = encryptStringInMotion('PASSword000');
        //var_dump($passwordToSend);

        // 1) signin - expect success (http code 200), also get session token for next calls
        $response = $this->post($url, [
            'username' => urlencode($email),
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
            'password' => 'T8xt9R9Utx3b1nu4fNyQuw==:W7yej/EqsWEhDYNYDbQDtg==',
            'type' => 'c',
        ]);

        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('u', $arrayJsonDecodedResponse);
        $sessionToken = $arrayJsonDecodedResponse['u'];
        $sessionToken = str_replace('-c', '', $sessionToken);


        // 2) check if SessionDevices is added
        $parseUserInnerQuery = new ParseQuery('_User');
        $parseUserInnerQuery->equalTo('email', $email);
        $parseQuery = new ParseQuery('SessionDevices');
        $parseQuery->equalTo('sessionTokenRecall', $sessionToken);
        $parseQuery->equalTo('isActive', true);
        $parseQuery->matchesQuery('user', $parseUserInnerQuery);
        $currentParseSessionDeviceCount = $parseQuery->count();
        $this->assertEquals(1, $currentParseSessionDeviceCount);

        // 3) try to use any endpoint (get airports list) - expect success (http code 200)
        $url = $this->generatePath('info/airports/list', $sessionToken, "");
        $response = $this->get($url);
        $this->assertEquals('200', $response->getHttpStatusCode());
        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('airportName', $arrayJsonDecodedResponse[0]);


        // 4) modify SessionDevices to be inactive
        $currentParseSessionDevice = $parseQuery->first();
        $currentParseSessionDevice->set('isActive', false);
        $currentParseSessionDevice->save();

        // 5) try to use any endpoint (get airports list) - check if fails
        $url = $this->generatePath('info/airports/list', $sessionToken, "");
        $response = $this->get($url);
        var_dump($response);

    }


    /**
     *
     */
    public function notActive_t_estDeleteOldSessionDevices()
    {
        $parseUserInnerQuery = new ParseQuery('_User');
        $parseUserInnerQuery->equalTo('objectId', '9bl5cZpt0p');
        $parseQuery = new ParseQuery('SessionDevices');
        $parseQuery->matchesQuery('user', $parseUserInnerQuery);
        echo $parseQuery->count();
        /*
        $list = $parseQuery->find();
        foreach ($list as $item) {
            $item->destroy(true);
        }
        */
    }
}