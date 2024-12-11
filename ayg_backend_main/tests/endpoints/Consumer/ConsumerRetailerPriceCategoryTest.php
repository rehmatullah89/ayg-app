<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerPriceCategoryTest
 *
 * tested endpoint:
 * // Retailer Price Category
 * '/priceCategory/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerRetailerPriceCategoryTest extends ConsumerBaseTest
{

    public function testThatPriceCategoryCanBeTaken()
    {
        $user = $this->createUser();
        $url = $this->generatePath('retailer/priceCategory', $user->getSessionToken(), "");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertInternalType('array', $arrayJsonDecodedBody);

        // get first element:
        $firstElement = reset($arrayJsonDecodedBody);
        $keysThatShouldAppear = [
            'retailerPriceCategory',
            'retailerPriceCategorySign',
            'displayOrder',
            'uniqueId',
        ];

        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $firstElement);
        }


        $firstElement = $response->getJsonDecodedBody()[0];
        $this->assertEquals($arrayJsonDecodedBody[0]['uniqueId'], $firstElement->uniqueId);


    }


    /**
     *
     */
    public function testThatPriceCategoryCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken='someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('retailer/priceCategory', $sessionToken, "");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}