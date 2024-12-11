<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerByDistanceTerminalSortTest
 *
 * tested endpoint:
 * Get Restaurant List near a gate with retailerType and Limit BUT with different Sort Order Terminal & Gate
 *
 * '/retailer/bydistance/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/terminalSort/:terminalSort/concourseSort/:concourseSort/gateSort/:gateSort/retailerType/:retailerType/limit/:limit'
 */
class ConsumerRetailerByDistanceTerminalSortTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $locationId = 'A5NYnxOiJF';
        $terminalSort = '0';
        $gateSort = '0';
        $retailerType = 0;
        $concourseSort = 'DESC';
        $limit = '5';

        $url = $this->generatePath('retailer/bydistance',
            $user->getSessionToken(),
            "airportIataCode/$airportIataCode/locationId/$locationId/terminalSort/$terminalSort/concourseSort/$concourseSort/gateSort/$gateSort/retailerType/$retailerType/limit/$limit");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        // get first element:
        $firstElement = reset($arrayJsonDecodedBody);

        $keysThatShouldAppear = [
            'distanceStepsToGate',
            'distanceMilesToGate',
            'walkingTimeToGate',
            'differentTerminalFlag',
            'sortedSequence'
        ];


        $this->assertInternalType('array', $arrayJsonDecodedBody);

        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $firstElement);
        }


    }

    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $airportIataCode = 'BWI';
        $locationId = 'A5NYnxOiJF';
        $terminalSort = '0';
        $gateSort = '0';
        $retailerType = 0;
        $concourseSort = 'DESC';
        $limit = '5';

        $url = $this->generatePath('retailer/bydistance',
            $sessionToken,
            "airportIataCode/$airportIataCode/locationId/$locationId/terminalSort/$terminalSort/concourseSort/$concourseSort/gateSort/$gateSort/retailerType/$retailerType/limit/$limit");


        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}