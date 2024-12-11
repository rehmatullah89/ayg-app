<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerInfoGatemapNearTest
 *
 * tested endpoint:
 * // Terminal Gate Map - Nearest
 * '/gatemap/near/a/:apikey/e/:epoch/u/:sessionToken/latitude/:latitude/longitude/:longitude'
 */
class ConsumerInfoGatemapNearTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $latitude = '39.18316';
        $longitude = '-76.66717';
        $url = $this->generatePath('info/gatemap/near', $user->getSessionToken(), "latitude/$latitude/longitude/$longitude");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $keysThatShouldAppear = [
            'airportIataCode',
            'concourse',
            'displaySequence',
            'gate',
            'geoPointLocation',
            'locationDisplayName',
            'terminal',
            'gateDisplayName',
        ];
        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody);
        }

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
        $latitude = '39.18316';
        $longitude = '-76.66717';
        $url = $this->generatePath('info/gatemap/near', $sessionToken, "latitude/$latitude/longitude/$longitude");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}