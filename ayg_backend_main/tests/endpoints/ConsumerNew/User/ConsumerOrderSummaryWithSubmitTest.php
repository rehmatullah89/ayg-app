<?php
namespace tests\endpoints\ConsumerNew\User;

use tests\endpoints\ConsumerNew\ConsumerBaseTest;
use tests\endpoints\ConsumerNew\ObjectsStackItem;

require_once __DIR__ . '/../ConsumerBaseTest.php';

/**
 * Class ConsumerOrderSummaryTest
 *
 * tested endpoint:
 * // Order Summary
 * '/summary/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId'
 */
class ConsumerOrderSummaryWithSubmitTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testCreditGreaterThanOrder()
    {
        $user = $this->createUser();

        $this->addParseObjectAndPushObjectOnStack('UserCredits', [
            'creditsInCents' => 300,
            'user' => $user,
        ]);

        // Kraze Burgers
        $retailer = $this->parseGetRetailerById('c7Lfv7Jm7l');
        $retailer->fetch();
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);

        $uniqueRetailerItemId = $retailerItem->get('uniqueId');


        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
            'quotedFullfillmentFeeTimestamp' => time(),
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/addItem', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $orderId,
            'orderItemId' => 0,
            'uniqueRetailerItemId' => $uniqueRetailerItemId,
            'itemQuantity' => 1,
            'itemComment' => 'some comment',
            'options' => 0,
        ]);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->pushOnObjectsStack(new ObjectsStackItem($jsonDecodedBody->orderItemObjectId, 'OrderModifiers'));


        $paymentMethodNonce = 'fake-valid-nonce';
        $url = $this->generatePath('payment/create', $user->getSessionToken(), "paymentMethodNonce/$paymentMethodNonce");
        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->created);
        $token = $jsonDecodedBody->token;

        // add new payment
        $payment = $this->parseGetPaymentForUser($user);
        $this->pushOnObjectsStack(new ObjectsStackItem($payment->getObjectId(), 'Payments'));

        $url = $this->generatePath('order/submit', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $order->getObjectId(),
            'fullfillmentType' => 'p',
            'deliveryLocation' => 'cb4CStEg4F',
            'deliveryInstructions' => 'someText',
            'requestedFullFillmentTimestamp' => '2017-02-02',
            'paymentToken' => $token,
        ]);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        if (isset($arrayJsonDecodedBody['error_code']) && $arrayJsonDecodedBody['error_code']=='AS_823'){
            // "The retailer is currently not accepting orders. Please try again in a few minutes."
        }else{
            $this->assertArrayHasKey('ordered', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('orderId', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentTypeDisplay', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentETATimestamp', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentETATimeDisplay', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentLocation', $arrayJsonDecodedBody);



            $jsonDecodedBody = $response->getJsonDecodedBody();
            $this->assertEquals(1, $jsonDecodedBody->ordered);
        }

    }

    public function testCreditLesserThanOrder()
    {
        $user = $this->createUser();

        $this->addParseObjectAndPushObjectOnStack('UserCredits', [
            'creditsInCents' => 200,
            'user' => $user,
        ]);

        // Kraze Burgers
        $retailer = $this->parseGetRetailerById('c7Lfv7Jm7l');
        $retailer->fetch();
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);

        $uniqueRetailerItemId = $retailerItem->get('uniqueId');


        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
            'quotedFullfillmentFeeTimestamp' => time(),
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/addItem', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $orderId,
            'orderItemId' => 0,
            'uniqueRetailerItemId' => $uniqueRetailerItemId,
            'itemQuantity' => 1,
            'itemComment' => 'some comment',
            'options' => 0,
        ]);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->pushOnObjectsStack(new ObjectsStackItem($jsonDecodedBody->orderItemObjectId, 'OrderModifiers'));


        $paymentMethodNonce = 'fake-valid-nonce';
        $url = $this->generatePath('payment/create', $user->getSessionToken(), "paymentMethodNonce/$paymentMethodNonce");
        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->created);
        $token = $jsonDecodedBody->token;

        // add new payment
        $payment = $this->parseGetPaymentForUser($user);
        $this->pushOnObjectsStack(new ObjectsStackItem($payment->getObjectId(), 'Payments'));

        $url = $this->generatePath('order/submit', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $order->getObjectId(),
            'fullfillmentType' => 'p',
            'deliveryLocation' => 'cb4CStEg4F',
            'deliveryInstructions' => 'someText',
            'requestedFullFillmentTimestamp' => '2017-02-02',
            'paymentToken' => $token,
        ]);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        if (isset($arrayJsonDecodedBody['error_code']) && $arrayJsonDecodedBody['error_code']=='AS_823'){
            // "The retailer is currently not accepting orders. Please try again in a few minutes."
        }else{
            $this->assertArrayHasKey('ordered', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('orderId', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentTypeDisplay', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentETATimestamp', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentETATimeDisplay', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentLocation', $arrayJsonDecodedBody);



            $jsonDecodedBody = $response->getJsonDecodedBody();
            $this->assertEquals(1, $jsonDecodedBody->ordered);
        }

    }

    public function testNoCredit()
    {
        $user = $this->createUser();

        $this->addParseObjectAndPushObjectOnStack('UserCredits', [
            'creditsInCents' => 0,
            'user' => $user,
        ]);

        // Kraze Burgers
        $retailer = $this->parseGetRetailerById('c7Lfv7Jm7l');
        $retailer->fetch();
        $retailerUniqueId = $retailer->get('uniqueId');
        $retailerItem = $this->getFirstActiveRetailerItemByRetailerUniqueId($retailerUniqueId);

        $uniqueRetailerItemId = $retailerItem->get('uniqueId');


        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
            'quotedFullfillmentFeeTimestamp' => time(),
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/addItem', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $orderId,
            'orderItemId' => 0,
            'uniqueRetailerItemId' => $uniqueRetailerItemId,
            'itemQuantity' => 1,
            'itemComment' => 'some comment',
            'options' => 0,
        ]);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->pushOnObjectsStack(new ObjectsStackItem($jsonDecodedBody->orderItemObjectId, 'OrderModifiers'));


        $paymentMethodNonce = 'fake-valid-nonce';
        $url = $this->generatePath('payment/create', $user->getSessionToken(), "paymentMethodNonce/$paymentMethodNonce");
        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->created);
        $token = $jsonDecodedBody->token;

        // add new payment
        $payment = $this->parseGetPaymentForUser($user);
        $this->pushOnObjectsStack(new ObjectsStackItem($payment->getObjectId(), 'Payments'));

        $url = $this->generatePath('order/submit', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $order->getObjectId(),
            'fullfillmentType' => 'p',
            'deliveryLocation' => 'cb4CStEg4F',
            'deliveryInstructions' => 'someText',
            'requestedFullFillmentTimestamp' => '2017-02-02',
            'paymentToken' => $token,
        ]);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        if (isset($arrayJsonDecodedBody['error_code']) && $arrayJsonDecodedBody['error_code']=='AS_823'){
            // "The retailer is currently not accepting orders. Please try again in a few minutes."
        }else{
            $this->assertArrayHasKey('ordered', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('orderId', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentTypeDisplay', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentETATimestamp', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentETATimeDisplay', $arrayJsonDecodedBody);
            $this->assertArrayHasKey('fullfillmentLocation', $arrayJsonDecodedBody);



            $jsonDecodedBody = $response->getJsonDecodedBody();
            $this->assertEquals(1, $jsonDecodedBody->ordered);
        }

    }
}