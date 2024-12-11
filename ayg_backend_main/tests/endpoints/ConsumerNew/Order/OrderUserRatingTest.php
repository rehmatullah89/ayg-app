<?php
namespace tests\endpoints\ConsumerNew\Order;

use tests\endpoints\ConsumerNew\ConsumerBaseTest;

use Parse\ParseQuery;
use tests\endpoints\ConsumerNew\ObjectsStackItem;

require_once __DIR__ . '/../ConsumerBaseTest.php';

class OrderUserRatingTest extends ConsumerBaseTest
{
    public function testOrderRating()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/rate', $user->getSessionToken(), "");
        $response = $this->post($url, [
            'overallRating' => 1,
            'feedback' => "this is test",
            "orderId" => $orderId
        ]);
        //var_dump($response);
        $jsonDecodedResponse = $response->getJsonDecodedBody(true);

        $this->assertTrue($jsonDecodedResponse['status']);

        $query = new ParseQuery("Order");
        $query->equalTo("objectId", $orderId);
        $userCreditQuery = new ParseQuery("OrderRatings");
        $userCreditQuery->matchesQuery("order", $query);
        $userCreditQuery->equalTo("overallRating", 1);
        $parseUserCredit = $userCreditQuery->first();




        if(!empty($parseUserCredit)) {
            $this->pushOnObjectsStack(new ObjectsStackItem($parseUserCredit->getObjectId(), 'OrderRatings'));
        }
    }

    public function testOrderRatingWithZeroRating()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/rate', $user->getSessionToken(), "");
        $response = $this->post($url, [
            'overallRating' => 0,
            'feedback' => "this is test",
            "orderId" => $orderId
        ]);
        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $jsonDecodedResponse);
        $this->assertEquals('AS_032', $jsonDecodedResponse['error_code']);
    }

    public function t1estOrderRatingWithMinusOneRating()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/rate', $user->getSessionToken(), "");
        $response = $this->post($url, [
            'overallRating' => -1,
            'feedback' => "this is test",
            "orderId" => $orderId
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertTrue($jsonDecodedResponse['status']);
    }


    public function t1estOrderRatingWithEmptyRating()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/rate', $user->getSessionToken(), "");
        $response = $this->post($url, [
            'overallRating' => '',
            'feedback' => "this is test",
            "orderId" => $orderId
        ]);


        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $jsonDecodedResponse);
        $this->assertEquals('AS_032', $jsonDecodedResponse['error_code']);
    }


    public function t1estOrderRatingWithAboveFiveRating()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/rate', $user->getSessionToken(), "");
        $response = $this->post($url, [
            'overallRating' => 6,
            'feedback' => "this is test",
            "orderId" => $orderId
        ]);

        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $jsonDecodedResponse);
        $this->assertEquals('AS_032', $jsonDecodedResponse['error_code']);
    }
}