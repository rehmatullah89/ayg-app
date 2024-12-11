<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderListTypeTest
 *
 * tested endpoint:
 * // Order List
 * '/list/a/:apikey/e/:epoch/u/:sessionToken/type/:type'
 */
class ConsumerOrderListTypeTest extends ConsumerBaseTest
{


    public function testEmptyListCanBeGet()
    {
        $user = $this->createUser();
        $type = 'a';

        $url = $this->generatePath('order/list', $user->getSessionToken(), "type/$type");

        $response = $this->get($url);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertInternalType('array', $jsonDecodedBody);
        $this->assertCount(0, $jsonDecodedBody);


    }



    /**
     *
     */
    public function testListCanNotBeGetWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $type = 'a';

        $url = $this->generatePath('order/list', $sessionToken, "type/$type");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);
    }
}