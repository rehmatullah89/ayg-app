<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerInfoAirportsFindTest
 *
 * tested endpoint:
 * // Airports Find
 * '/airports/find/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode'
 */
class ConsumerInfoAirportsFindTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $url = $this->generatePath('info/airports/find', $user->getSessionToken(), "airportIataCode/$airportIataCode");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        // phpunit can not assert existing of object parameters,
        // that is why it is converted to array, and array keys existing are asserted
        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('airportCity', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('airportIataCode', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('airportName', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('geoPointLocation', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('imageBackground', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('isReady', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('isPickupReady', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('isDeliveryReady', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('employeeDiscountPCT', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('airportCountry', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('airportTimezone', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('airportTimezoneShort', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('objectId', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('weather', $arrayJsonDecodedBody);

        $this->assertArrayHasKey('date', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('timestampUTC', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('tempFahrenheit', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('tempMinFahrenheit', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('tempMaxFahrenheit', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('weatherText', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('iconURL', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('windSpeed', $arrayJsonDecodedBody['weather'][0]);
        $this->assertArrayHasKey('longitude', $arrayJsonDecodedBody['geoPointLocation']);
        $this->assertArrayHasKey('latitude', $arrayJsonDecodedBody['geoPointLocation']);


        // assert object parameter to check if it is object at all
        $firstElement = $response->getJsonDecodedBody();
        $this->assertEquals($arrayJsonDecodedBody['objectId'], $firstElement->objectId);

    }

    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $airportIataCode = 'BWI';
        $url = $this->generatePath('info/airports/find', $sessionToken, "airportIataCode/$airportIataCode");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}