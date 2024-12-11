<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerUserProfileUpdateAirEmployeeTest
 *
 * tested endpoint:
 * // Change Profile options -- Apply for Air Employee
 * '/user/profile/update/airEmployee/a/:apikey/e/:epoch/u/:sessionToken'
 *
 */
class ConsumerUserProfileUpdateAirEmployeeTest extends ConsumerBaseTest
{
    public function testCanUpdate()
    {
        $user = $this->createUser();

        $url = $this->generatePath('user/profile/update/airEmployee', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'employerName' => 'nameTest',
            'employeeSince' => '2016-12-12',
            'employmentCardImage' => 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl'
                . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr'
                . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r'
                . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==',
        ]);

        $jsonDecodedBody = $response->getJsonDecodedBody();

        $this->assertEquals(1, $jsonDecodedBody->applied);
    }
}