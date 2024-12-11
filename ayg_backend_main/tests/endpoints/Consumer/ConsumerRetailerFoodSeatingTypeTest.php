<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerFoodSeatingTypeTest
 *
 * tested endpoint:
 * // Retailer Food Seating Type
 * '/foodSeatingType/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerRetailerFoodSeatingTypeTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $url = $this->generatePath('retailer/foodSeatingType', $user->getSessionToken(), "");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('0', $arrayJsonDecodedBody);

        $keysThatShouldAppear = [
            'retailerFoodSeatingType',
            'displayOrder',
            'iconCode',
            'uniqueId',
        ];

        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody[0]);
        }

        // assert object parameter to check if it is object at all
        $firstElement = $response->getJsonDecodedBody()[0];
        $this->assertEquals($arrayJsonDecodedBody[0]['uniqueId'], $firstElement->uniqueId);


    }

    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('retailer/foodSeatingType', $sessionToken, "");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }

}