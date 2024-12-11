<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerDistanceTest
 *
 * tested endpoint:
 * // Get Distance between Current Location and give Retailer object id
 * '/distance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/fromLocationId/:fromLocationId/toRetailerLocationId/:toRetailerLocationId'
 */
class ConsumerRetailerDistanceTest extends ConsumerBaseTest
{

    public function testThatDistanceCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode='BWI';
        $fromLocationId='A5NYnxOiJF';
        $toRetailerLocationId='A5NYnxOiJF';
        $url = $this->generatePath('retailer/distance', $user->getSessionToken(), "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('pathToDestination', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('distanceStepsToGate', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('distanceMilesToGate', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('walkingTimeToGate', $arrayJsonDecodedBody);


    }


    /**
     *
     */
    public function testThatDistanceanNotBeTakenWithBadLocationId()
    {
        $user = $this->createUser();
        $airportIataCode='BWI';
        $fromLocationId='someBadLocationId';
        $toRetailerLocationId='A5NYnxOiJF';
        $url = $this->generatePath('retailer/distance', $user->getSessionToken(), "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_514', $responseDecoded->error_code);

    }

    /**
     *
     */
    public function testThatDistanceCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken='someSessionTokenThatCanNotBeCreated';
        $airportIataCode='BWI';
        $fromLocationId='A5NYnxOiJF';
        $toRetailerLocationId='A5NYnxOiJF';
        $url = $this->generatePath('retailer/distance', $sessionToken, "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}