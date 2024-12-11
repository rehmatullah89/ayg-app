<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerInfoGatemapTest
 *
 * tested endpoint:
 * Terminal Gate Map
 * '/gatemap/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode'
 */
class ConsumerInfoGatemapTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $url = $this->generatePath('info/gatemap', $user->getSessionToken(), "airportIataCode/$airportIataCode");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $keysThatShouldAppear = [
            'airportIataCode',
            'terminal',
            'concourse',
            'gate',
            'locationDisplayName',
            'displaySequence',
            'geoPointLocation',
            'uniqueId',
            'gateDisplayName',
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
        $airportIataCode = 'BWI';
        $url = $this->generatePath('info/gatemap', $sessionToken, "airportIataCode/$airportIataCode");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}