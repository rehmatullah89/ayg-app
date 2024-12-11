<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerByDistanceTest
 *
 * tested endpoint:
 * // Get Restaurant List near a gate and limit WITHOUT retailerType
 * '/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/limit/:limit'
 */
class ConsumerRetailerByDistanceTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode='BWI';
        $locationId='A5NYnxOiJF';
        $limit='5';
        
        $url = $this->generatePath('retailer/bydistance', $user->getSessionToken(), "airportIataCode/$airportIataCode/locationId/$locationId/limit/$limit");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        // get first element:
        $firstElement = reset($arrayJsonDecodedBody);

        $keysThatShouldAppear = [
            'distanceStepsToGate',
            'distanceMilesToGate',
            'walkingTimeToGate',
            'differentTerminalFlag',
            'sortedSequence',
        ];

        $this->assertInternalType('array', $arrayJsonDecodedBody);

        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $firstElement);
        }

    }

    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $airportIataCode='BWI';
        $locationId='A5NYnxOiJF';
        $limit='5';

        $url = $this->generatePath('retailer/bydistance', $sessionToken, "airportIataCode/$airportIataCode/locationId/$locationId/limit/$limit");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}