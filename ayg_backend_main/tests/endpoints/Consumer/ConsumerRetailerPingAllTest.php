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
        $url = $this->generatePath('retailer/ping/all', $user->getSessionToken(), "");

        var_dump($url);
        $response = $this->get($url);

        var_dump($response);
    }

}