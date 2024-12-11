<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerDirectionsTest
 *
 * tested endpoint:
 * // Get Directions between Current Location and give Retailer object id
 * '/directions/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/fromLocationId/:fromLocationId/toRetailerLocationId/:toRetailerLocationId/referenceRetailerId/:referenceRetailerId'
 */
class ConsumerRetailerDirectionsTest extends ConsumerBaseTest
{

    public function testThatDirectionsCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $fromLocationId = 'A5NYnxOiJF';
        $toRetailerLocationId = 'soMCUsutBq';
        $referenceRetailerId = '2867fd66a496c15a470ac5486c48f60e';
        $url = $this->generatePath('retailer/directions', $user->getSessionToken(), "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId/referenceRetailerId/$referenceRetailerId");

        $response = $this->get($url);


        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('directionsBySegments', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('totalDistanceMetricsForTrip', $arrayJsonDecodedBody);

        $this->assertInternalType('array', $arrayJsonDecodedBody['directionsBySegments']);
        $firstElementDirectionsBySegments = reset($arrayJsonDecodedBody['directionsBySegments']);
        $keysThatShouldAppear = [
            'pathImage',
            'segmentPathText',
            'directions',
            'distanceSteps',
            'distanceMiles',
            'walkingTime',
        ];
        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $firstElementDirectionsBySegments);
        }

        $this->assertArrayHasKey('distanceSteps', $arrayJsonDecodedBody['totalDistanceMetricsForTrip']);
        $this->assertArrayHasKey('distanceMiles', $arrayJsonDecodedBody['totalDistanceMetricsForTrip']);
        $this->assertArrayHasKey('walkingTime', $arrayJsonDecodedBody['totalDistanceMetricsForTrip']);
        $this->assertArrayHasKey('reEnterSecurityFlag', $arrayJsonDecodedBody['totalDistanceMetricsForTrip']);



    }


    public function testThatDirectionsCanNotBeTakenWithNotSupportedAirport()
    {
        $user = $this->createUser();
        $airportIataCode = 'someAirport';
        $fromLocationId = 'A5NYnxOiJF';
        $toRetailerLocationId = 'soMCUsutBq';
        $referenceRetailerId = '2867fd66a496c15a470ac5486c48f60e';
        $url = $this->generatePath('retailer/directions', $user->getSessionToken(), "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId/referenceRetailerId/$referenceRetailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_511', $responseDecoded->error_code);

    }


    public function testThatDirectionsCanNotBeTakenWithInvalidLocationId()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $fromLocationId = 'SomeInvalidLocationId';
        $toRetailerLocationId = '1';
        $referenceRetailerId = '1';
        $url = $this->generatePath('retailer/directions', $user->getSessionToken(), "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId/referenceRetailerId/$referenceRetailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_514', $responseDecoded->error_code);

    }


    public function testThatDirectionsCanNotBeTakenWithInvalidRetailerId()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $fromLocationId = 'A5NYnxOiJF';
        $toRetailerLocationId = 'SomeRetailerId';
        $referenceRetailerId = '1';
        $url = $this->generatePath('retailer/directions', $user->getSessionToken(), "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId/referenceRetailerId/$referenceRetailerId");

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
    public function testThatDirectionsCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $airportIataCode = '0';
        $fromLocationId = '0';
        $toRetailerLocationId = '0';
        $referenceRetailerId = '0';
        $url = $this->generatePath('retailer/directions', $sessionToken, "airportIataCode/$airportIataCode/fromLocationId/$fromLocationId/toRetailerLocationId/$toRetailerLocationId/referenceRetailerId/$referenceRetailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}