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
class ConsumerRetailerQaInfoRetailerIdTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $retailerId = $this->parseFindFirstRetailerWithRetailerPOSConfigAndGetUniqueId();
        //$url = $this->generatePath('retailer/qa/info', 0, "retailerId/$retailerId");
        $url = $this->generatePathForWebEndpoints('retailer/qa/info', $user->getSessionToken(), "retailerId/$retailerId");

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

        foreach ($keysThatShouldAppear as $item) {
            $this->assertArrayHasKey($item, $arrayJsonDecodedBody);
        }

    }

}