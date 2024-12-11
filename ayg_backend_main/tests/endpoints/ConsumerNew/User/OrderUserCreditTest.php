<?php
namespace tests\endpoints\ConsumerNew\User;

use tests\endpoints\ConsumerNew\ConsumerBaseTest;
use tests\endpoints\ConsumerNew\ObjectsStackItem;

require_once __DIR__ . '/../ConsumerBaseTest.php';

class OrderUserCreditTest extends ConsumerBaseTest
{
    public function testUserCreditApply()
    {
        $user = $this->createUser();
        $user->set('hasAdminAccess', true);
        $user->save();

        $parseQuery = new \Parse\ParseQuery("_User");
        $parseQuery->equalTo("hasConsumerAccess", true);
        $parseUser = $parseQuery->first();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);
        $uniqueRetailerItemId = $retailerItem->get('uniqueId');
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $parseUser,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('user/applyCreditsToUser', $user->getSessionToken(), "");
        $response = $this->post($url, [
            'creditsInCents' => 40,
            'reasonForCredit' => "System not working",
            'userId' => $parseUser->getObjectId(),
            "orderId" => $orderId
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('addedId', $jsonDecodedResponse);

        $this->pushOnObjectsStack(new ObjectsStackItem($jsonDecodedResponse['addedId'], 'UserCredits'));
    }


    public function t1estUserCreditApplyWithNoCentRequest()
    {
        $user = $this->createUser();
        $user->set('hasAdminAccess', true);
        $user->save();

        $parseQuery = new \Parse\ParseQuery("_User");
        $parseUser = $parseQuery->first();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);
        $uniqueRetailerItemId = $retailerItem->get('uniqueId');
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('user/applyCreditsToUser', $user->getSessionToken(), "");
        $response = $this->post($url, [
            'creditsInCents' => '',
            'reasonForCredit' => "No change",
            'userId' => $parseUser->getObjectId(),
            "orderId" => $orderId
        ]);


        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $jsonDecodedResponse);
        $this->assertEquals('AS_9004', $jsonDecodedResponse['error_code']);
    }

}