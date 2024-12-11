<?php
namespace tests\endpoints\ConsumerNew\User;

use App\Tablet\Entities\Order;
use Parse\ParseObject;
use Parse\ParseUser;
use tests\endpoints\ConsumerNew\ConsumerBaseTest;
use tests\endpoints\ConsumerNew\ObjectsStackItem;

require_once __DIR__ . '/../ConsumerBaseTest.php';


define('AES_256_CBC', 'aes-256-cbc');

$env_ParseServerURL		 				= getenv('env_ParseServerURL');
$env_ParseApplicationId 				= getenv('env_ParseApplicationId');
$env_ParseRestAPIKey 					= getenv('env_ParseRestAPIKey');
$env_ParseMasterKey 					= getenv('env_ParseMasterKey');
$env_ParseMount							= getenv('env_ParseMount');
$env_CacheEnabled						= (getenv('env_CacheEnabled') === 'true');
$env_CacheRedisURL						= getenv('env_CacheRedisURL');
$env_CacheSSLCA							= getenv('env_CacheSSLCA');
$env_CacheSSLCert						= getenv('env_CacheSSLCert');
$env_CacheSSLPK							= getenv('env_CacheSSLPK');

require_once __DIR__ . '/../../../../lib/functions.php';
require_once __DIR__ . '/../../../../lib/functions_userauth.php';
require_once __DIR__ . '/../../../../lib/functions_orders.php';
require_once __DIR__ . '/../../../../lib/functions_parse.php';
require_once __DIR__ . '/../../../../lib/initiate.parse.php';
require_once __DIR__ . '/../../../../lib/initiate.redis.php';
require_once __DIR__ . '/../../../../lib/functions_cache.php';

$env_StringInMotionEncryptionKey=getenv('env_StringInMotionEncryptionKey');

class UserSignupWithCouponAndOrderTest extends ConsumerBaseTest
{
    public function testUserCouponSuccess()
    {
        /*
        $validCoupon = fetchValidCoupon("", 'signup-cr', "", "", true);
        var_dump($validCoupon);

        die('');
        */
        list($user, $sessionToken) = $this->executeUserSignupEndpoint();

        //$order = $this->executeAddOrderItemAndOrderEndPoint($user);
        //$orderId = $order->getObjectId();

        //$parseCoupon = $this->createCouponForSignupWithNoCredit();
        //$couponCode = $parseCoupon->get('couponCode'); //"signuptestcoupon101";

        $couponCode='signup-cr';
        //$couponCode='Failmode';

        $url = $this->generatePath('user/signup/promo', $sessionToken, "couponCode/$couponCode");
        $response = $this->get($url);
      //  $jsonDecodedResponse = $response->getJsonDecodedBody(true);

        var_dump($response->getJsonDecodedBody());

        /*
        $url = $this->generatePath('order/summary', $sessionToken, "orderId/$orderId");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        $this->assertTrue($arrayJsonDecodedBody['isPromoCode']);
        $this->assertEquals(903, $arrayJsonDecodedBody['totals']['Total']);

        $this->deleteObjectWithObjectId("UserCoupons", $jsonDecodedResponse['id']);
        //$this->deleteObjectWithObjectId("UserCredits", $jsonDecodedResponse['id']);
        $this->deleteObjectWithObjectId("Coupons", $parseCoupon->getObjectId());
        //$this->assertEquals('Hello world, your user ID is ' . $user->getObjectId(), $jsonDecodedResponse->info);
        */
    }

    public function t1estWithoutPromoCodeSuccess()
    {
        list($user, $sessionToken) = $this->executeUserSignupEndpoint();

        $order = $this->executeAddOrderItemAndOrderEndPoint($user);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/summary', $sessionToken, "orderId/$orderId");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertFalse($arrayJsonDecodedBody['isPromoCode']);
        $this->assertEquals(953, $arrayJsonDecodedBody['totals']['Total']);

    }

    public function t1estUserCouponWithTwoOrdersSuccess()
    {
        list($user, $sessionToken) = $this->executeUserSignupEndpoint();

        $order1 = $this->executeAddOrderItemAndOrderEndPoint($user, true);
        $orderId1 = $order1->getObjectId();

        $parseCoupon = $this->createCouponForSignupWithNoCredit();
        $couponCode = $parseCoupon->get('couponCode'); //"signuptestcoupon101";
        $url = $this->generatePath('user/signup/promo', $sessionToken, "couponCode/$couponCode");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody(true);


        $url = $this->generatePath('order/summary', $sessionToken, "orderId/$orderId1");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertFalse($arrayJsonDecodedBody['isPromoCode']);
        $this->assertEquals(953, $arrayJsonDecodedBody['totals']['Total']);

        $order2 = $this->executeAddOrderItemAndOrderEndPoint($user);
        $orderId2 = $order2->getObjectId();

        $parseCoupon = $this->createCouponForSignupWithNoCredit();
        $couponCode = $parseCoupon->get('couponCode'); //"signuptestcoupon101";
        $url = $this->generatePath('user/signup/promo', $sessionToken, "couponCode/$couponCode");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody(true);


        $url = $this->generatePath('order/summary', $sessionToken, "orderId/$orderId2");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertTrue($arrayJsonDecodedBody['isPromoCode']);
        $this->assertEquals(903, $arrayJsonDecodedBody['totals']['Total']);


        $this->deleteObjectWithObjectId("UserCoupons", $jsonDecodedResponse['id']);
        //$this->deleteObjectWithObjectId("UserCredits", $jsonDecodedResponse['id']);
        $this->deleteObjectWithObjectId("Coupons", $parseCoupon->getObjectId());
        //$this->assertEquals('Hello world, your user ID is ' . $user->getObjectId(), $jsonDecodedResponse->info);
    }

    public function t1estWithoutPromoCodeAndTwoOrdersSuccess()
    {
        list($user, $sessionToken) = $this->executeUserSignupEndpoint();

        $order1 = $this->executeAddOrderItemAndOrderEndPoint($user, true);
        $orderId1 = $order1->getObjectId();

        $url = $this->generatePath('order/summary', $sessionToken, "orderId/$orderId1");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertFalse($arrayJsonDecodedBody['isPromoCode']);
        $this->assertEquals(953, $arrayJsonDecodedBody['totals']['Total']);

        $order2 = $this->executeAddOrderItemAndOrderEndPoint($user);
        $orderId2 = $order2->getObjectId();

        $url = $this->generatePath('order/summary', $sessionToken, "orderId/$orderId2");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertFalse($arrayJsonDecodedBody['isPromoCode']);
        $this->assertEquals(953, $arrayJsonDecodedBody['totals']['Total']);
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
        $parseCoupon->set('couponDiscountForFeeCents', 50);
        $parseCoupon->set('couponDiscountForFeePCT', 0);
        $parseCoupon->set('couponDiscountPCT', 0);
        $parseCoupon->set('couponDiscountPCTMaxCents',0);
        $parseCoupon->set('couponDiscountCents',50);
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
        $parseCoupon->set('couponDiscountForFeeCents', 50);
        $parseCoupon->set('couponDiscountForFeePCT', 0);
        $parseCoupon->set('couponDiscountPCT', 0);
        $parseCoupon->set('couponDiscountPCTMaxCents',0);
        $parseCoupon->set('couponDiscountCents',50);
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

    function executeUserSignupEndpoint() {
        $url = $this->generatePath("user/signup", 0, "");

        $pass='PASSword000';
        $pass = encryptStringInMotion($pass);


        $response = $this->post($url, [
            'type' => 'c',
            'firstName' => 'TestFirstname',
            'lastName' => 'LastNameTest',
            'password' => $pass,
            'email' => 'ludwik.grochowina+' . rand(0, 10000) . '@gmail.com',
            "deviceArray" => 'eyJhcHBWZXJzaW9uIjoiMSIsImlzSW9zIjoiMSIsImlzQW5kcm9pZCI6IjAiLCJkZXZpY2VUeXBlIjoic29tZVR5cGUiLCJkZXZpY2VNb2RlbCI6InNvbWVNb2RlbCIsImRldmljZU9TIjoic29tZU9zIiwiZGV2aWNlSWQiOiJzb21lSWQiLCJwdXNoTm90aWZpY2F0aW9uSWQiOiJzb21lSWQiLCJ0aW1lem9uZUZyb21VVENJblNlY29uZHMiOiIzNjAwIiwiZ2VvTGF0aXR1ZGUiOiIxMS4xMSIsImdlb0xvbmdpdHVkZSI6IjExLjExIiwiY291bnRyeSI6IlVTIiwiaXNPbldpZmkiOnRydWUsImlzUHVzaE5vdGlmaWNhdGlvbkVuYWJsZWQiOnRydWV9',
        ]);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);

        var_dump($arrayJsonDecodedBody);
        $this->assertArrayHasKey('u', $arrayJsonDecodedBody);

        // delete just created user
        $sessionToken = substr($arrayJsonDecodedBody['u'], 0, -2);


        $user = ParseUser::become($sessionToken);
        $user->set("isActive", true);
        $user->set("hasConsumerAccess", true);
        $user->set("isBetaActive", true);
        $user->save();

        return [$user, $sessionToken];
    }

    function executeAddOrderItemAndOrderEndPoint(ParseUser $user, $cancel = false) {
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

        if(!$cancel) {
            $this->addParseObjectAndPushObjectOnStack('OrderStatus', [
                'order' => $order,
                'status' => 1,
            ]);
            $this->addParseObjectAndPushObjectOnStack('OrderStatus', [
                'order' => $order,
                'status' => 2,
            ]);
            $this->addParseObjectAndPushObjectOnStack('OrderStatus', [
                'order' => $order,
                'status' => 3,
            ]);
            $this->addParseObjectAndPushObjectOnStack('OrderStatus', [
                'order' => $order,
                'status' => 4,
            ]);
            $this->modifyParseObject('Order', $orderId, ['status' => 4]);
        } else {
            $this->addParseObjectAndPushObjectOnStack('OrderStatus', [
                'order' => $order,
                'status' => 6,
            ]);
            $this->modifyParseObject('Order', $orderId, ['status' => 6]);
        }

        return $order;
    }


}