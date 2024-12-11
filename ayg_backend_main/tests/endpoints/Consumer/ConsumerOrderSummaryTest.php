<?php

use Parse\ParseUser;

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderSummaryTest
 *
 * tested endpoint:
 * // Order Summary
 * '/summary/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId'
 */
class ConsumerOrderSummaryTest extends ConsumerBaseTest
{

    public function testCanGetOrderSummaryForAGievenId()
    {

        $orderId = 'cqjVogGV6B';
        $user = ParseUser::logIn('ludwik+1@toptal.com-c', md5('PASSword000' . getenv('env_PasswordHashSalt')));
        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);


        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId");
        $response = $this->get($url);
        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        var_dump($response);
        var_dump($arrayJsonDecodedBody);
    }


    public function t1estCanGetOrderSummaryForAGievenId1()
    {
        $orderId = 'wdClkyyCEr';
        $user = ParseUser::logIn('ludwik.grochowina+as2@gmail.com-c', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        var_dump($response);
        var_dump($arrayJsonDecodedBody);
    }


    /**
     *
     */
    public function t1estCanGetOrderSummary()
    {
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailerWithPosConfig();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 1,
        ]);
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
        $this->modifyParseObject('Order', $order->getObjectId(), ['status' => 4]);


        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId");

        $response = $this->get($url);

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertInternalType('array', $arrayJsonDecodedBody);

        $this->assertArrayHasKey('internal', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('totals', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('payment', $arrayJsonDecodedBody);

        $internalExpectedKeys = [
            'retailerUniqueId',
            'retailerName',
            'retailerAirportIataCode',
            'retailerImageLogo',
            'retailerLocation',
            'orderId',
            'orderIdDisplay',
            'orderStatusCode',
            'orderStatusDisplay',
            'orderStatusDeliveryCode',
            'orderStatusCategoryCode',
            'orderDate',
            'fullfillmentETATimestamp',
            'fullfillmentETATimeDisplay',
            'fullfillmentType',
            'fullfillmentFee',
            'orderSubmitAirportTimeDisplay',
            'orderSubmitTimestampUTC',
            'deliveryLocation',
            'deliveryName',
            'itemQuantityCount',
            'orderNotAllowedThruSecurity',
        ];
        $totalsExpectedKeys = [
            'PreTaxTotal',
            'PreTaxTotalDisplay',
            'Taxes',
            'TaxesDisplay',
            'PreTipTotal',
            'PreTipTotalDisplay',
            'TipsPCT',
            'Tips',
            'TipsDisplay',
            'PreCouponTotal',
            'PreCouponTotalDisplay',
            'Coupon',
            'CouponDisplay',
            'CouponCodeApplied',
            'AirEmployeeDiscount',
            'AirEmployeeDiscountDisplay',
            'PreFeeTotal',
            'PreFeeTotalDisplay',
            'AirportSherpaFee',
            'AirportSherpaFeeDisplay',
            'Total',
            'TotalDisplay',
        ];
        $paymentExptectedKeys = [
            'paymentType',
            'paymentTypeName',
            'paymentTypeId',
            'paymentTypeIconURL',
        ];

        foreach ($internalExpectedKeys as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody['internal']);
        }

        foreach ($totalsExpectedKeys as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody['totals']);
        }

        foreach ($paymentExptectedKeys as $key) {
            $this->assertArrayHasKey($key, $arrayJsonDecodedBody['payment']);
        }


        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals($arrayJsonDecodedBody['internal']['retailerUniqueId'], $jsonDecodedBody->internal->retailerUniqueId);


    }


    /**
     *
     */
    public function t1estCanNotGetSummaryWithoutUser()
    {
        $sessionToken = 'someSessionTokenThatCanNotBeCreated';
        $user = $this->createUser();
        $retailer = $this->parseGetFirstRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 4,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/summary', $sessionToken, "orderId/$orderId");

        $response = $this->get($url);
        $this->assertTrue($response->isHttpResponseCorrect());

        $arrayJsonDecodedBody = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('error_code', $arrayJsonDecodedBody);
        $this->assertArrayHasKey('error_description', $arrayJsonDecodedBody);

        $responseDecoded = $response->getJsonDecodedBody();
        $this->assertEquals('AS_015', $responseDecoded->error_code);


    }
}