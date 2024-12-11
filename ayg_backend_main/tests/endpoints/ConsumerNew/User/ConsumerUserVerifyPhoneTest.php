<?php
namespace tests\endpoints\ConsumerNew\User;

use tests\endpoints\ConsumerNew\ConsumerBaseTest;

require_once __DIR__ . '/../ConsumerBaseTest.php';

/**
 * Class ConsumerUserAddPhoneTest
 *
 * tested endpoint:
 * // Order Item Count
 * '/addPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneCountryCode/:phoneCountryCode/phoneNumber/:phoneNumber'
 */
class ConsumerUserVerifyPhoneTest extends ConsumerBaseTest
{

    public function testCanAddPhone()
    {
        //$user = $this->createUser();
        $phoneId = 'Wttc9s46XD';
        $code = '4172';

        $user = \Parse\ParseUser::logIn('itsursujit+addphone@gmail.com-c', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('user/verifyPhoneWithTwilio', $user->getSessionToken(), "phoneId/$phoneId/verifyCode/$code");

        $response = $this->get($url);

        //$response = $user->getSessionToken();
        $jsonDecodedBody = $response->getJsonDecodedBody(true);
        //var_dump($response);
        //var_dump($jsonDecodedBody);
        //$this->assertTrue($jsonDecodedBody['status']);


    }

}