<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderCouponTest
 *
 * tested endpoint:
 * // Apply coupon
 * '/coupon/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/code/:couponCode'
 */
class ConsumerOrderCouponTest extends ConsumerBaseTest
{
    public function testCouponCanBeSet()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();

        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();

        //this coupon should always be in the database
        $couponCode = 'bwi';
        $url = $this->generatePath('order/coupon', $user->getSessionToken(), "orderId/$orderId/code/$couponCode");

        $response = $this->get($url);



        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals(1, $jsonDecodedBody->applied);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertInternalType('array', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('applied', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('comments', $arrayJsonDecodedBody);


    }

    public function testCouponCanBeAppliedIfIsInvalid()
    {
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();

        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();
        $couponCode = 'SomeInvalidCouponCode';

        $url = $this->generatePath('order/coupon', $user->getSessionToken(), "orderId/$orderId/code/$couponCode");

        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_819', $responseDecoded->error_code);


    }

    public function testCouponCanBeAppliedForInvalidSession()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $user = $this->createUser();

        $retailer = $this->getActiveRetailerItemWithExistingRetailer();

        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
        $orderId = $order->getObjectId();
        $coupon = $this->addParseObjectAndPushObjectOnStack('Coupons', [
            'expiresTimestamp' => time() + 3600,
            'couponDiscountCents' => 100,
            'couponCode' => 'newcode',
            'isRetailerCompensated' => false,
            'applicableRetailerUniqueIds' => [$retailer->get('uniqueId')],
        ]);
        $couponCode = $coupon->get('couponCode');
        $url = $this->generatePath('order/coupon', $sessionToken, "orderId/$orderId/code/$couponCode");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);


    }
}