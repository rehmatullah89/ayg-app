<?php
namespace tests\endpoints\ConsumerNew\User;

use Parse\ParseObject;
use tests\endpoints\ConsumerNew\ConsumerBaseTest;

require_once __DIR__ . '/../ConsumerBaseTest.php';

class UserCouponAddTest extends ConsumerBaseTest
{
    public function testUserCouponSuccess()
    {
        $user = $this->createUser();

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

        $parseCoupon = $this->createCouponForSignupWithNoCredit();

        $couponCode = $parseCoupon->get('couponCode'); //"signuptestcoupon101";
        $url = $this->generatePath('user/signup/promo', $user->getSessionToken(), "couponCode/$couponCode");
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('id', $jsonDecodedResponse);
        $this->assertEquals('coupon', $jsonDecodedResponse['type']);
        $this->assertEquals("Welcome to At Your Gate. Your promo code was accepted and will be applied to your next order.", $jsonDecodedResponse['welcomeMessage']);
        $this->assertEquals(0, $jsonDecodedResponse['creditsInCents']);


        $this->deleteObjectWithObjectId("UserCoupons", $jsonDecodedResponse['id']);
        $this->deleteObjectWithObjectId("Coupons", $parseCoupon->getObjectId());
        //$this->assertEquals('Hello world, your user ID is ' . $user->getObjectId(), $jsonDecodedResponse->info);
    }

    public function testUserCouponWithCredit()
    {
        $user = $this->createUser();

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

        $parseCoupon = $this->createCouponForSignupWithCredit();

        $couponCode = $parseCoupon->get('couponCode'); //"signuptestcoupon101";
        $url = $this->generatePath('user/signup/promo', $user->getSessionToken(), "couponCode/$couponCode");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody(true);

        $this->assertArrayHasKey('id', $jsonDecodedResponse);
        $this->assertEquals(700, $jsonDecodedResponse['creditsInCents']);
        $this->assertEquals('credit', $jsonDecodedResponse['type']);
        $this->assertEquals("Thank you for using Promo Code", $jsonDecodedResponse['welcomeMessage']);


        //$this->deleteObjectWithObjectId("UserCoupons", $jsonDecodedResponse['addedId']);
        $this->deleteObjectWithObjectId("Coupons", $parseCoupon->getObjectId());
        $this->deleteObjectWithObjectId("UserCredits", $jsonDecodedResponse['id']);
        //$this->assertEquals('Hello world, your user ID is ' . $user->getObjectId(), $jsonDecodedResponse->info);
    }

    public function testUserCouponNotForSignup()
    {
        $user = $this->createUser();

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

        $parseCoupon = $this->createCouponNotForSignup();

        $couponCode = $parseCoupon->get('couponCode'); //"signuptestcoupon101";
        $url = $this->generatePath('user/signup/promo', $user->getSessionToken(), "couponCode/$couponCode");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        //var_dump($jsonDecodedResponse);
        $this->assertEquals('AS_819', $jsonDecodedResponse->error_code);
        //$this->assertArrayHasKey('addedId', $jsonDecodedResponse);


        //$this->deleteObjectWithObjectId("UserCoupons", $jsonDecodedResponse['addedId']);
        $this->deleteObjectWithObjectId("Coupons", $parseCoupon->getObjectId());
        //$this->assertEquals('Hello world, your user ID is ' . $user->getObjectId(), $jsonDecodedResponse->info);
    }

    public function testUserCouponWithInvalidCoupon()
    {
        $user = $this->createUser();

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

        $couponCode = "abcdefgh";
        $url = $this->generatePath('user/signup/promo', $user->getSessionToken(), "couponCode/$couponCode");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();


        $this->assertEquals('AS_819', $jsonDecodedResponse->error_code);
    }

    function createCouponForSignupWithNoCredit() {
        $parseCoupon = new ParseObject("Coupons");
        $parseCoupon->set('expiresTimestamp', 1600000000);
        $parseCoupon->set('couponCode', 'signuptestcoupon' . md5(time() . rand(1, 1000)));
        $parseCoupon->set('couponDiscountPCT', 0);
        $parseCoupon->set('forSignup', true);
        $parseCoupon->set('isFirstUseOnly', false);
        $parseCoupon->set('activeTimestamp', 1490025660);
        $parseCoupon->set('isActive', true);
        $parseCoupon->set('applyDiscountToOrderMinOfInCents', 0);
        $parseCoupon->set('maxUserAllowedByAll', 0);
        $parseCoupon->set('maxUserAllowedByUser', 11);
        $parseCoupon->set('isRetailerCompensated', false);
        //$parseCoupon->set('onSignupAcctCreditsInCents', false);
        //$parseCoupon->set('onSignupAcctCreditsWelcomeMsg', "Thank you for using Promo Code");
        $parseCoupon->setArray('applicableRetailerUniqueIds', []);
        $parseCoupon->setArray('applicableAirportIataCodes', []);
        $parseCoupon->save();
        return $parseCoupon;

    }

    function createCouponForSignupWithCredit() {
        $parseCoupon = new ParseObject("Coupons");
        $parseCoupon->set('expiresTimestamp', 1600000000);
        $parseCoupon->set('couponCode', 'signuptestcoupon' . md5(time() . rand(1, 1000)));
        $parseCoupon->set('couponDiscountPCT', 0);
        $parseCoupon->set('forSignup', true);
        $parseCoupon->set('isFirstUseOnly', false);
        $parseCoupon->set('activeTimestamp', 1490025660);
        $parseCoupon->set('isActive', true);
        $parseCoupon->set('applyDiscountToOrderMinOfInCents', 0);
        $parseCoupon->set('maxUserAllowedByAll', 0);
        $parseCoupon->set('maxUserAllowedByUser', 11);
        $parseCoupon->set('isRetailerCompensated', false);
        $parseCoupon->set('onSignupAcctCreditsInCents', 700);
        $parseCoupon->set('onSignupAcctCreditsWelcomeMsg', "Thank you for using Promo Code");
        $parseCoupon->setArray('applicableRetailerUniqueIds', []);
        $parseCoupon->setArray('applicableAirportIataCodes', []);
        $parseCoupon->save();
        return $parseCoupon;
    }

    function createCouponNotForSignup() {
        $parseCoupon = new ParseObject("Coupons");
        $parseCoupon->set('expiresTimestamp', 1600000000);
        $parseCoupon->set('couponCode', 'signuptestcoupon' . md5(time() . rand(1, 1000)));
        $parseCoupon->set('couponDiscountPCT', 0);
        $parseCoupon->set('forSignup', false);
        $parseCoupon->set('isFirstUseOnly', false);
        $parseCoupon->set('activeTimestamp', 1490025660);
        $parseCoupon->set('isActive', true);
        $parseCoupon->set('applyDiscountToOrderMinOfInCents', 0);
        $parseCoupon->set('maxUserAllowedByAll', 0);
        $parseCoupon->set('maxUserAllowedByUser', 11);
        $parseCoupon->set('isRetailerCompensated', false);
        //$parseCoupon->set('onSignupAcctCreditsInCents', 700);
        //$parseCoupon->set('onSignupAcctCreditsWelcomeMsg', "Thank you for using Promo Code");
        $parseCoupon->setArray('applicableRetailerUniqueIds', []);
        $parseCoupon->setArray('applicableAirportIataCodes', []);
        $parseCoupon->save();

        return $parseCoupon;
    }

    function deleteObjectWithObjectId($className, $id) {
        $parseUserCoupon = new ParseObject($className, $id);
        $parseUserCoupon->destroy();
    }


}
