<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerRetailerMenuUniqueItemIdTest
 *
 * tested endpoint:
 * // Get Second level (Modifiers) Menu for the Retailer
 * '/menu/a/:apikey/e/:epoch/u/:sessionToken/retailerId/:retailerId/itemId/:uniqueItemId'
 */
class ConsumerRetailerQaMenuUniqueItemIdTest extends ConsumerBaseTest
{

    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();
        $retailerId='2867fd66a496c15a470ac5486c48f60e';
        $uniqueItemId='b221101baab110112783984b0636b6d5';
        $url = $this->generatePathForWebEndpoints('retailer/qa/menu', $user->getSessionToken(), "retailerId/$retailerId/itemId/$uniqueItemId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('Entree Sides', $arrayJsonDecodedBody);

        // get first element:
        $firstElement = $arrayJsonDecodedBody['Entree Sides'];

        $keysThatShouldAppear=[
            'modifierDescription',
            'maxQuantity',
            'minQuantity',
            'isRequired',
            'options',
        ];
        foreach ($keysThatShouldAppear as $item){
            $this->assertArrayHasKey($item, $firstElement);
        }

        $keysThatShouldAppear=[
            'optionId',
            'optionName',
            'optionDescription',
            'pricePerUnit',
            'pricePerUnitDisplay',
        ];
        foreach ($keysThatShouldAppear as $item){
            $this->assertArrayHasKey($item, $firstElement['options'][0]);
        }

    }

}