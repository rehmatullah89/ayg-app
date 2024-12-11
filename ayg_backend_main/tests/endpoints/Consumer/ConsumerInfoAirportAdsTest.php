<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerInfoGatemapTest
 *
 * tested endpoint:
 * Terminal Gate Map
 * '/gatemap/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode'
 */
class ConsumerInfoAirportAdsTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $url = $this->generatePath('info/airports/ads', $user->getSessionToken(), "airportIataCode/$airportIataCode");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertArrayHasKey('retailerUniqueId', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('displaySeconds', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('imageAd', $arrayJsonDecodedBody[0]);
    }

}