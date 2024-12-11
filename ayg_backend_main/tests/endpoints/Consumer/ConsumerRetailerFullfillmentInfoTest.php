<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerFullfillmentInfoTest
 *
 * tested endpoint:
 * // Get Fullfillment info for all retails
 * '/fullfillmentInfo/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId'
 * @todo tests checks jsondecoded to array, class structure should be tested also
 */
class ConsumerRetailerFullfillmentInfoTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $locationId = 'A5NYnxOiJF';
        $url = $this->generatePath('retailer/fullfillmentInfo', $user->getSessionToken(), "airportIataCode/$airportIataCode/locationId/$locationId");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertInternalType('array', $arrayJsonDecodedBody);

        // get first element:
        $firstElement = reset($arrayJsonDecodedBody);
        $this->assertArrayHasKey('d', $firstElement);
        $this->assertArrayHasKey('isAvailable', $firstElement['d']);
        $this->assertArrayHasKey('fullfillmentFeesInCents', $firstElement['d']);
        $this->assertArrayHasKey('fullfillmentFeesDisplay', $firstElement['d']);
        $this->assertArrayHasKey('TotalInCents', $firstElement['d']);
        $this->assertArrayHasKey('TotalDisplay', $firstElement['d']);
        $this->assertArrayHasKey('p', $firstElement);
        $this->assertArrayHasKey('isAvailable', $firstElement['p']);
        $this->assertArrayHasKey('fullfillmentFeesInCents', $firstElement['p']);
        $this->assertArrayHasKey('fullfillmentFeesDisplay', $firstElement['p']);
        $this->assertArrayHasKey('fullfillmentTimeEstimateInSeconds', $firstElement['p']);
        $this->assertArrayHasKey('TotalInCents', $firstElement['p']);
        $this->assertArrayHasKey('TotalDisplay', $firstElement['p']);


    }

    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $airportIataCode = 'BWI';
        $locationId = 'A5NYnxOiJF';
        $url = $this->generatePath('retailer/fullfillmentInfo', $sessionToken, "airportIataCode/$airportIataCode/locationId/$locationId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);

    }

}