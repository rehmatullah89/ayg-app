<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerTripFlightSearchTest
 *
 * tested endpoint:
 * // Get Flight Schedule Details with Flight Info
 * '/trip/flight/search/a/:apikey/e/:epoch/u/:sessionToken/airlineIataCode/:airlineIataCode/flightNum/:flightNum/flightYear/:flightYear/flightMonth/:flightMonth/flightDate/:flightDate'
 */
class ConsumerTripFlightSearchTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testSearchGivesResults()
    {
        $user = $this->createUser();


        //BWI_MDW__WN_1722__2017_7_31
        //MDW_SNA__WN_1722__2017_7_31

        $airlineIataCode = 'WN';
        $flightNum = '1722';
        $flightYear = '2017';
        $flightMonth = '07';
        $flightDate = '31';
        $url = $this->generatePath('trip/flight/search', $user->getSessionToken(), "airlineIataCode/$airlineIataCode/flightNum/$flightNum/flightYear/$flightYear/flightMonth/$flightMonth/flightDate/$flightDate");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $infoExceptedKey = [
            'info',
            'departure',
            'arrival',
        ];

        foreach ($infoExceptedKey as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody[0]);
        }

    }

}