<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerTrendingTest
 *
 * tested endpoint:
 * // Get Trending Restaurant List with retailerType and Limit
 * '/trending/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/retailerType/:retailerType/limit/:limit'
 */
class ConsumerRetailerTrendingTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();

        $airportIataCode='BWI';
        $retailerType='Food';
        $limit='5';
        $url = $this->generatePath('retailer/trending', $user->getSessionToken(), "airportIataCode/$airportIataCode/retailerType/$retailerType/limit/$limit");

        $response = $this->get($url);


        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('0', $arrayJsonDecodedBody);

        $keysThatShouldAppear=[
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
            'searchTags',
            'uniqueId',
            'retailerType',
            'retailerPriceCategory',
            'retailerCategory',
            'retailerFoodSeatingType',
            'location',
        ];

        foreach ($keysThatShouldAppear as $item){
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody[0]);
        }

        // assert object parameter to check if it is object at all
        $firstElement = $response->getJsonDecodedBody()[0];
        $this->assertEquals($arrayJsonDecodedBody[0]['airportIataCode'], $firstElement->airportIataCode);


    }

    /**
     *
     */
    public function testThatListIsEmptyWithBadAirportIAtaCode()
    {
        $user = $this->createUser();
        $airportIataCode='SomeNotExistingCode';
        $retailerType='0';
        $limit='5';
        $url = $this->generatePath('retailer/trending', $user->getSessionToken(), "airportIataCode/$airportIataCode/retailerType/$retailerType/limit/$limit");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertCount(0, $arrayJsonDecodedBody);


    }

    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken='someSessionTokenThatCanNotBeCreated';
        $airportIataCode='BWI';
        $retailerType='0'; //Food
        $limit='5';
        $url = $this->generatePath('retailer/trending', $sessionToken, "airportIataCode/$airportIataCode/retailerType/$retailerType/limit/$limit");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}