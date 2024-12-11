<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerInfoAirportsNearTest
 *
 * tested endpoint:
 * // Airports near Geo Location
 * '/airports/near/a/:apikey/e/:epoch/u/:sessionToken/latitude/:latitude/longitude/:longitude'
 */
class ConsumerInfoAirportsNearTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $latitude = '39.18316';
        $longitude = '-76.66717';
        $url = $this->generatePath('info/airports/near', $user->getSessionToken(), "latitude/$latitude/longitude/$longitude");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $keysThatShouldAppear = [
            'airportCity',
            'airportIataCode',
            'airportName',
            'geoPointLocation',
            'imageBackground',
            'isReady',
            'isPickupReady',
            'isDeliveryReady',
            'employeeDiscountPCT',
            'airportCountry',
            'airportTimezone',
        ];
        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody[0]);
        }

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
        $latitude = '39.18316';
        $longitude = '-76.66717';
        $url = $this->generatePath('info/airports/near', $sessionToken, "latitude/$latitude/longitude/$longitude");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}