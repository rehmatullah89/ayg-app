<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserAddPhoneTest
 *
 * tested endpoint:
 * // Order Item Count
 * '/addPhone/a/:apikey/e/:epoch/u/:sessionToken/phoneCountryCode/:phoneCountryCode/phoneNumber/:phoneNumber'
 */
class ConsumerUserAddPhoneTest extends ConsumerBaseTest
{
    /**
     * for now skipped, always returns AS_422
     * not env_AuthyAPIKey
     * {"error_code":"60001","message":"Invalid API key","errors":{"message":"Invalid API key"},"success":false}
     */
    public function testCanAddPhone()
    {
        $user = $this->createUser();
        $phoneCountryCode = '1';
        $phoneNumber = '215-620-9582';

        $userDevice = $this->addParseObjectAndPushObjectOnStack('UserDevices', [
            'user' => $user,
        ]);
        $sessionDevice = $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'userDevice' => $userDevice,
            'sessionToken' => $user->getSessionToken()
        ]);

        $url = $this->generatePath('user/addPhone', $user->getSessionToken(), "phoneCountryCode/$phoneCountryCode/phoneNumber/$phoneNumber");

        $response = $this->get($url);


        $jsonDecodedBody = $response->getJsonDecodedBody();
        var_dump($jsonDecodedBody);
        //$this->assertTrue($jsonDecodedBody->isAvailable);


    }

}