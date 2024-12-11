<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOpsBugTest
 *
 * tested endpoint:
 * // Bug Reports
 * '/ops/bug/a/:apikey/e/:epoch/u/:sessionToken
 */
class ConsumerOpsBugTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testBug()
    {
        $user = $this->createUser();

        $url = $this->generatePath('ops/bug', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'deviceId' => '2XyEgHr',
            'deviceType' => 'Android',
            'description' => 'test description',
            'buildVersion' => '0',
            'iOSVersion' => '2.2.1',
            'bugSeverity' => 'High',
            'bugCategory' => 'Test',
            'appVersion' => '1.2',
            'screenshot' => 'iVBORw0KGgoAAAANSUhEUgAAABwAAAASCAMAAAB/2U7WAAAABl' . 'BMVEUAAAD///+l2Z/dAAAASUlEQVR4XqWQUQoAIAxC2/0vXZDr' . 'EX4IJTRkb7lobNUStXsB0jIXIAMSsQnWlsV+wULF4Avk9fLq2r' . '8a5HSE35Q3eO2XP1A1wQkZSgETvDtKdQAAAABJRU5ErkJggg==',

        ]);
        $this->assertTrue($response->isHttpResponseCorrect());

        $jsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertEquals(1, $jsonDecodedBody['saved']);




    }

}