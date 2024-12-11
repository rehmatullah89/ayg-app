<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerInfoTest
 *
 * tested endpoint:
// Get Second level (Modifiers) Menu for the Retailer
 * '/info/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId'
 */
class ConsumerRetailerInfoTest extends ConsumerBaseTest
{

    public function testThatInfoCanBeTaken()
    {
        $user = $this->createUser();
        $retailerId=$this->parseFindFirstRetailerWithRetailerPOSConfigAndGetUniqueId();
        $url = $this->generatePath('retailer/info', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
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
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody);
        }


        // assert object parameter to check if it is object at all
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals($arrayJsonDecodedBody['uniqueId'], $jsonDecodedBody->uniqueId);


    }


    /**
     *
     */
    public function testThatInfoNotBeTakenWithBadRetailerId()
    {
        $user = $this->createUser();
        $retailerId='someRetailer';
        $url = $this->generatePath('retailer/info', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_506', $responseDecoded->error_code);

    }

    /**
     *
     */
    public function testThatInfoCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken='someSessionTokenThatCanNotBeCreated';
        $retailerId='2867fd66a496c15a470ac5486c48f60e';
        $url = $this->generatePath('retailer/info', $sessionToken, "retailerId/$retailerId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}