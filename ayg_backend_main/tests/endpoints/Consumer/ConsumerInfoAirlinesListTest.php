<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerInfoAirlinesListTest
 *
 * tested endpoint:
 * // Airlines
 * '/airlines/list/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerInfoAirlinesListTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $url = $this->generatePath('info/airlines/list', $user->getSessionToken(), "");

        $response = $this->get($url);

        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);


        $keysThatShouldAppear = [
            'airlineIcaoCode',
            'airlineCallSign',
            'airlineCountry',
            'airlineIataCode',
            'airlineName',
            'uniqueId',
        ];
        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody[0]);
        }

        // assert object parameter to check if it is object at all
        $firstElement = $response->getJsonDecodedBody()[0];
        $this->assertEquals($arrayJsonDecodedBody[0]['objectId'], $firstElement->objectId);
    }



    /**
     *
     */
    public function testThatListCanNotBeTakenWithBadSessionToken()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $url = $this->generatePath('info/airlines/list', $sessionToken, "");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}