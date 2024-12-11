<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerTripFlightAddDeleteInfoTest
 *
 * tested endpoint:
 * // Get Flight Schedule Details with Flight ID
 * '/trip/flight/add/a/:apikey/e/:epoch/u/:sessionToken/flightId/:flightId'
 *
 * // Get Flight Schedule Details with Flight Id
 * '/trip/flight/a/:apikey/e/:epoch/u/:sessionToken/flightId/:flightId'
 *
 * // Trip list
 * '/trip/list/a/:apikey/e/:epoch/u/:sessionToken/refresh/:refresh
 *
 * // Trip list with Trip Id
 * '/trip/a/:apikey/e/:epoch/u/:sessionToken/tripId/:tripId'
 *
 * // Get Trip and Flight Ids of the next flight
 * '/trip/next/a/:apikey/e/:epoch/u/:sessionToken'
 *
 * // Flight delete
 * '/trip/flight/delete/a/:apikey/e/:epoch/u/:sessionToken/flightId/:flightId'
 *
 * // Trip delete
 * '/trip/delete/a/:apikey/e/:epoch/u/:sessionToken/tripId/:tripId'
 */
class ConsumerTripFlightAddDeleteInfoTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testUserCanAddTrip()
    {
        $user = $this->createUser();

        $flight1Id = 'BWI_MDW__WN_1722__2017_07_31';
        $url = $this->generatePath('trip/flight/add', $user->getSessionToken(),
            "flightId/$flight1Id");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        var_dump($arrayJsonDecodedBody);
/*
        $this->assertArrayHasKey('tripId', $arrayJsonDecodedBody);
        $trip1Id = $arrayJsonDecodedBody['tripId'];


        $flight2Id = 'MDW_SNA__WN_1722__2017_7_31';
        $url = $this->generatePath('trip/flight/add', $user->getSessionToken(),
            "flightId/$flight2Id");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertArrayHasKey('tripId', $arrayJsonDecodedBody);
        $trip2Id = $arrayJsonDecodedBody['tripId'];

        $this->assertEquals($trip1Id, $trip2Id);


        $url = $this->generatePath('trip/flight', $user->getSessionToken(),
            "flightId/$flight1Id");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);


        $this->assertArrayHasKey('info', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('departure', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('arrival', $arrayJsonDecodedBody);

        $refresh = '0';
        $url = $this->generatePath('trip/list', $user->getSessionToken(),
            "refresh/$refresh");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);


        $expectedKeys = [
            'tripId',
            'tripName',
            'firstFlightDepartureTimestamp',
            'firstFlightDepartureTimezone',
            'firstFlightDepartureTimezoneShort',
            'firstFlightDepartureAirportIataCode',
            'lastFlightArrivalTimestamp',
            'lastFlightArrivalTimezone',
            'lastFlightArrivalTimezoneShort',
            'lastFlightArrivalAirportIataCode',
            'isFromTripIt',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody[0]);
        }

        $tripId = $trip1Id;
        $url = $this->generatePath('trip', $user->getSessionToken(),
            "tripId/$tripId");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('info', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('departure', $arrayJsonDecodedBody[0]);
        $this->assertArrayHasKey('arrival', $arrayJsonDecodedBody[0]);


        $url = $this->generatePath('trip/next', $user->getSessionToken(), "");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertEquals($flight1Id, $arrayJsonDecodedBody['nextFlightId']);
        $this->assertEquals($trip2Id, $arrayJsonDecodedBody['nextTripId']);


        $flightId=$flight2Id;
        $url = $this->generatePath('trip/flight/delete', $user->getSessionToken(),
            "flightId/$flightId");
        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertTrue($jsonDecodedBody->deleted);

        $tripId=$trip1Id;
        $url = $this->generatePath('trip/delete', $user->getSessionToken(),
            "tripId/$tripId");
        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertTrue($jsonDecodedBody->deleted);*/


    }

}