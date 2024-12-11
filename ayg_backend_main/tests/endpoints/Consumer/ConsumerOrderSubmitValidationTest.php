<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderSubmitTest
 *
 * tested endpoint:
 * // Get Order Status rows
 * '/order/submit/a/:apikey/e/:epoch/u/:sessionToken'
 */
class ConsumerOrderSubmitValidationTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testSubmitOrder()
    {
        $user = $this->createUser();

        // Kraze Burgers
        $retailer = $this->parseGetRetailerById('3ZHoyhxuyx');

        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'status' => 1,
            'retailer' => $retailer,
            'quotedFullfillmentFeeTimestamp' => time(),
        ]);

        $url = $this->generatePath('order/addItem', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $order->getObjectId(),
            'orderItemId' => 0,
            'uniqueRetailerItemId' => 'bwi_subway_38',
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

        $url = $this->generatePath('order/submit/validation', $user->getSessionToken(), "");

        $response = $this->post($url, [
            'orderId' => $order->getObjectId(),
            'fullfillmentType' => 'p',
            'deliveryLocation' => 'cb4CStEg4F',
            'deliveryInstructions' => 'someText',
            'requestedFullFillmentTimestamp' => '2017-02-02',
            'paymentToken' => $token,
        ]);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertEquals(false, $arrayJsonDecodedBody['validation']);
    }


}