<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerFullfillmentInfoRetailerIdTest
 *
 * tested endpoint:
 * // Get Fullfillment info for all retails
 * '/fullfillmentInfo/a/:apikey/e/:epoch/u/:sessionToken/airportIataCode/:airportIataCode/locationId/:locationId/retailerId/:retailerId'
 * @todo tests checks jsondecoded to array, class structure should be tested also
 */
class ConsumerRetailerQaMenuRetailerIdTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $retailerId = $this->parseFindFirstRetailerWithRetailerPOSConfigAndGetUniqueId();
        //$url = $this->generatePath('retailer/qa/info', 0, "retailerId/$retailerId");
        $url = $this->generatePathForWebEndpoints('retailer/qa/menu', $user->getSessionToken(), "retailerId/$retailerId");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey("Entree", $arrayJsonDecodedBody[0]);

    }

}