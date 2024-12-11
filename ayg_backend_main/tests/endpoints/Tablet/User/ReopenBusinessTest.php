<?php

use Parse\ParseUser;

require_once __DIR__ . '/../TabletBaseTest.php';

/**
 * Class ReopenBusinessTest
 */
class ReopenBusinessTest extends TabletBaseTest
{
    /**
     *
     */
    public function testCanSignInWhenRetailerIsConnected()
    {
        $user = ParseUser::logIn('quiznos@airportsherpa.io-t', md5('Tablet@123' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/user/reopenBusiness', $user->getSessionToken(), "");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertTrue($jsonDecodedResponse->status);
    }

    /**
     *
     */
    public function testErrorWhenNoRetailerIsConnected()
    {
        $user = ParseUser::logIn('ludwik.grochowina+tablet_no_retailer@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/user/reopenBusiness', $user->getSessionToken(), "");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5300', $jsonDecodedResponse->error_code);
    }

}