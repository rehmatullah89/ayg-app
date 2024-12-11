<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerTripFlightSearchTest
 *
 * tested endpoint:
 * // Get Flight Schedule Details with Flight Info
 * '/trip/flight/search/a/:apikey/e/:epoch/u/:sessionToken/airlineIataCode/:airlineIataCode/flightNum/:flightNum/flightYear/:flightYear/flightMonth/:flightMonth/flightDate/:flightDate'
 */
class ConsumerTripFlightSearchByFromIataCodeAndToIataCodeTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testSearchGivesResults()
    {
        $user = $this->createUser();


        //BWI_MDW__WN_1722__2017_7_31
        //MDW_SNA__WN_1722__2017_7_31
        //WN, BWI, MDW, 2017, 08, 31
        $airlineIataCode = 'WN';
        $flightYear = '2017';
        $flightMonth = '08';
        $flightDate = '31';
        $fromIataCode = 'BWI';
        $toIataCode = 'MDW';
        ///flight/search/a/:apikey/e/:epoch/u/:sessionToken/airlineIataCode/:airlineIataCode/fromAirportIataCode/:fromAirportIataCode/toAirportIataCode/:toAirportIataCode/flightYear/:flightYear/flightMonth/:flightMonth/flightDate/:flightDate', 'apiAuth',
        $url = $this->generatePath('trip/flight/search', $user->getSessionToken(), "airlineIataCode/$airlineIataCode/fromAirportIataCode/$fromIataCode/toAirportIataCode/$toIataCode/flightYear/$flightYear/flightMonth/$flightMonth/flightDate/$flightDate");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $infoExceptedKey = [
            'arrival',
            'departure',
            'arrival',
        ];

        foreach ($infoExceptedKey as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody[0]);
        }


    }

}