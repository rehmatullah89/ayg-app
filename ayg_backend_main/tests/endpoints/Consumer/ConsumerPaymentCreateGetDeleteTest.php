<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerPaymentCreateGetDeleteTest
 *
 * tested endpoint:
 *
 * // Create Payment Method type for the given paymentMethodNonce
 * '/payment/create/a/:apikey/e/:epoch/u/:sessionToken/paymentMethodNonce/:paymentMethodNonce'
 *
 *  * // Get List of Payment types associated with Customer
 * '/payment/list/a/:apikey/e/:epoch/u/:sessionToken'
 *
 * // Delete Payment type for the given Token
 * '/payment/delete/a/:apikey/e/:epoch/u/:sessionToken/token/:token'
 *
 * // Get Client Token with Customer Id
 * '/payment/token/a/:apikey/e/:epoch/u/:sessionToken'
 *
 */
class ConsumerPaymentCreateGetDeleteTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testThatListCanBeTaken()
    {
        $user = $this->createUser();


        $paymentMethodNonce = 'fake-valid-nonce';
        $url = $this->generatePath('payment/create', $user->getSessionToken(), "paymentMethodNonce/$paymentMethodNonce");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('created', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('token', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('paymentTypes', $arrayJsonDecodedBody);

        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->created);
        $token = $jsonDecodedBody->token;

        // add new payment
        $payment = $this->parseGetPaymentForUser($user);
        $this->pushOnObjectsStack(new ObjectsStackItem($payment->getObjectId(), 'Payments'));


        $url = $this->generatePath('payment/list', $user->getSessionToken(), "");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('paymentTypes', $arrayJsonDecodedBody);



        $url = $this->generatePath('payment/token', $user->getSessionToken(), "");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('token', $arrayJsonDecodedBody);




        $url = $this->generatePath('payment/delete', $user->getSessionToken(), "token/$token");
        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->deleted);



    }

}