<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerListTest
 *
 * tested endpoint:
 * // Retailers List by Airport with Retailer Type filter
 * '/list/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/retailerType/:retailerType'
 */
class ConsumerRetailerListTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $airportIataCode = 'BWI';
        $retailerId='0';
        $url = $this->generatePath('retailer/list', $user->getSessionToken(), "airportIataCode/$airportIataCode/retailerType/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        // get first element:
        $firstElement = reset($arrayJsonDecodedBody);

        $keysThatShouldAppear = [
            'airportIataCode',
            'openTimesMonday',
            'openTimesTuesday',
            'openTimesWednesday',
            'openTimesThursday',
            'openTimesFriday',
            'openTimesSaturday',
            'openTimesSunday',
            'closeTimesMonday',
            'closeTimesTuesday',
            'closeTimesWednesday',
            'closeTimesThursday',
            'closeTimesFriday',
            'closeTimesSaturday',
            'closeTimesSunday',
            'description',
            'hasDelivery',
            'hasPickup',
            'imageBackground',
            'imageLogo',
            'isActive',
            'isChain',
            'retailerName',
            'retailerType',
            'retailerPriceCategory',
            'retailerCategory',
            'retailerFoodSeatingType',
            'location',
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
        $sessionToken='someSessionTokenThatCanNotBeCreated';
        $airportIataCode = 'BWI';
        $retailerId='0';
        $url = $this->generatePath('retailer/list', $sessionToken, "airportIataCode/$airportIataCode/retailerType/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}