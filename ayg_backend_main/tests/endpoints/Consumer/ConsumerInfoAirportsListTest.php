<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerInfoAirportsListTest
 *
 * tested endpoint:
 * // Airports List
 * '/airports/list/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerInfoAirportsListTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $url = $this->generatePath('info/airports/list', $user->getSessionToken(), '');

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('0', $arrayJsonDecodedBody);

        // phpunit can not assert existing of object parameters,
        // that is why it is converted to array, and array keys existing are asserted
        $this->assertArrayHasKey('airportCity', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('airportIataCode', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('airportName', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('geoPointLocation', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('imageBackground', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('objectId', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('isDeliveryReady', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('isPickupReady', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('isReady', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('employeeDiscountPCT', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('airportCountry', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('airportTimezone', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('airportTimezoneShort', $arrayJsonDecodedBody[0]);

        // assert object parameter to check if it is object at all
        $firstElement = $response->getJsonDecodedBody()[0];
        $this->assertEquals($arrayJsonDecodedBody[0]['objectId'], $firstElement->objectId);


    }


    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('info/airports/list', $sessionToken, '');

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}