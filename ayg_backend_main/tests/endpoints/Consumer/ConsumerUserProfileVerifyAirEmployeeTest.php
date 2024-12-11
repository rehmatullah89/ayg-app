<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserProfileVerifyAirEmployeeTest
 *
 * tested endpoint:
 * // Change Profile options -- Apply for Air Employee
 * '/user/profile/update/airEmployee/a/:apikey/e/:epoch/u/:sessionToken'
 *
 *
 *
 */
class ConsumerUserProfileVerifyAirEmployeeTest extends ConsumerBaseTest
{
    public function testCanUpdate()
    {
        $user = $this->createUser();

        $url = $this->generatePath('user/profile/verify/isAirEmployee', $user->getSessionToken(), "");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(0, $jsonDecodedBody->status);
        $this->assertEquals('', $jsonDecodedBody->rejectionReason);



    }
}