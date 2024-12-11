<?php

use Parse\ParseUser;

require_once __DIR__ . '/../TabletBaseTest.php';
require_once __DIR__ . '/../../../../putenv.php';
$env_CacheEnabled = getenv('env_CacheEnabled');
$env_CacheRedisURL = getenv('env_CacheRedisURL');
require_once __DIR__ . '/../../../../lib/initiate.redis.php';
require_once __DIR__ . '/../../../../lib/functions_cache.php';

/**
 * Class CloseBusinessTest
 */
class CloseBusinessTest extends TabletBaseTest
{
    /**
     *
     */
    public function testCanCloseBusincessWhatItWasNotClosedBefore()
    {
        // unique id for tablet quiznos = 99935de546aaa0de4f231cd35c9734fa
        // remove cache
        $uniqueId = '99935de546aaa0de4f231cd35c9734fa';
        delCacheByKey('__RETAILERTEMPCLOSED_' . $uniqueId);
        delCacheByKey('__RETAILERCLOSEDEARLY_' . $uniqueId);


        $user = ParseUser::logIn('quiznos@airportsherpa.io-t', md5('Tablet@123' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/user/closeBusiness', $user->getSessionToken(), "");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();


        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertTrue(is_int($jsonDecodedResponse->numberOfSecondsToClose));


        $uniqueId = '99935de546aaa0de4f231cd35c9734fa';
        delCacheByKey('__RETAILERTEMPCLOSED_' . $uniqueId);
        delCacheByKey('__RETAILERCLOSEDEARLY_' . $uniqueId);
    }

    /**
     *
     */
    public function testCanNotCloseBusincessWhatItWasNotClosedBefore()
    {
        $uniqueId = '99935de546aaa0de4f231cd35c9734fa';
        delCacheByKey('__RETAILERTEMPCLOSED_' . $uniqueId);
        delCacheByKey('__RETAILERCLOSEDEARLY_' . $uniqueId);

        $user = ParseUser::logIn('quiznos@airportsherpa.io-t', md5('Tablet@123' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $user = ParseUser::logIn('quiznos@airportsherpa.io-t', md5('Tablet@123' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/user/closeBusiness', $user->getSessionToken(), "");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();


        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertTrue(is_int($jsonDecodedResponse->numberOfSecondsToClose));



        $user = ParseUser::logIn('quiznos@airportsherpa.io-t', md5('Tablet@123' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/user/closeBusiness', $user->getSessionToken(), "");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5306', $jsonDecodedResponse->error_code);

        $uniqueId = '99935de546aaa0de4f231cd35c9734fa';
        delCacheByKey('__RETAILERTEMPCLOSED_' . $uniqueId);
        delCacheByKey('__RETAILERCLOSEDEARLY_' . $uniqueId);
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

        $url = $this->generatePath('tablet/user/closeBusiness', $user->getSessionToken(), "");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5300', $jsonDecodedResponse->error_code);
    }

}