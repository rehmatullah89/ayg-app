<?php
namespace tests\endpoints\ConsumerNew\User;

use App\Tablet\Entities\Order;
use Parse\ParseObject;
use Parse\ParseUser;
use tests\endpoints\ConsumerNew\ConsumerBaseTest;
use tests\endpoints\ConsumerNew\ObjectsStackItem;

require_once __DIR__ . '/../ConsumerBaseTest.php';

class RetailerActiveOrderTest extends ConsumerBaseTest
{

    public function testUserCouponWithTwoOrdersSuccess()
    {
        $user = $this->createUser();
        $type = 'a';

        list($retailer, $orderId1) = $this->executeAddOrderItemAndOrderEndPoint($user, rand(0, 1000));
        //$orderId1 = $order1->getObjectId();
        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId1");
        $response = $this->get($url);
        $orderSummaryWithActive1 = $response->getJsonDecodedBody(true);

        list($retailer, $orderId2) = $this->executeAddOrderItemAndOrderEndPoint($user, rand(0, 1000));
        //$orderId2 = $order2->getObjectId();
        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId2");
        $response = $this->get($url);
        $orderSummaryWithActive2 = $response->getJsonDecodedBody(true);

        list($retailer, $orderId3) = $this->executeAddOrderItemAndOrderEndPoint($user, rand(0, 1000));
        //$orderId3 = $order3->getObjectId();
        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId3");
        $response = $this->get($url);
        $orderSummaryWithActive3 = $response->getJsonDecodedBody(true);

        $url = $this->generatePath('order/list', $user->getSessionToken(), "type/$type");
        $response = $this->get($url);
        $orderList1 = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('orderId', $orderList1[0]);

        $retailer->set('isActive', false);
        $retailer->save();

        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId1");
        $response = $this->get($url);
        $orderSummaryWithoutActive1 = $response->getJsonDecodedBody(true);

        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId2");
        $response = $this->get($url);
        $orderSummaryWithoutActive2 = $response->getJsonDecodedBody(true);

        $url = $this->generatePath('order/summary', $user->getSessionToken(), "orderId/$orderId3");
        $response = $this->get($url);
        $orderSummaryWithoutActive3 = $response->getJsonDecodedBody(true);

        $url = $this->generatePath('order/list', $user->getSessionToken(), "type/$type");
        $response = $this->get($url);
        $orderList2 = $response->getJsonDecodedBody(true);
        $this->assertArrayHasKey('orderId', $orderList2[0]);

        $this->checkAsserts($orderSummaryWithActive1, $orderSummaryWithoutActive1);
        $this->checkAsserts($orderSummaryWithActive2, $orderSummaryWithoutActive2);
        $this->checkAsserts($orderSummaryWithActive3, $orderSummaryWithoutActive3);

        $this->checkOrderListAsserts($orderList1, $orderList2);

    }


    function executeAddOrderItemAndOrderEndPoint(ParseUser $user, $itemId = 0) {
        // Kraze Burgers
        $retailer = $this->parseGetRetailerById('c7Lfv7Jm7l');
        $retailer->fetch();
        $uniqueRetailerId = $retailer->get('uniqueId');

        $url = $this->generatePath('order/initiate', $user->getSessionToken(), "retailerId/$uniqueRetailerId");

        $response = $this->get($url);

        // call is creating new order, so order should be pushed on the stack
        $jsonDecodedBody = $response->getJsonDecodedBody();

        $order = new ParseObject("Order", $jsonDecodedBody->orderId);


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
        $this->modifyParseObject('Order', $jsonDecodedBody->orderId, ['status' => 4]);


        return [$retailer, $jsonDecodedBody->orderId];
    }

    function checkAsserts($firstOrderSummary, $secondOrderSummary)
    {
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

            $this->assertEquals($firstOrderSummary['internal'][$key], $secondOrderSummary['internal'][$key]);
        }

        foreach ($totalsExpectedKeys as $key) {
            $this->assertEquals($firstOrderSummary['totals'][$key], $secondOrderSummary['totals'][$key]);
        }

        foreach ($paymentExptectedKeys as $key) {
            $this->assertEquals($firstOrderSummary['payment'][$key], $secondOrderSummary['payment'][$key]);
        }
    }

    function checkOrderListAsserts($firstOrder, $secondOrder)
    {
        $expectedKeys = [
            'orderId',
            'orderIdDisplay',
            'retailerId',
            'orderInternalStatus',
            'orderInternalStatusCode',
            'orderStatus',
            'orderStatusCode',
            'orderStatusCategoryCode',
        ];

        foreach ($expectedKeys as $key) {

            $this->assertEquals($firstOrder[0][$key], $secondOrder[0][$key]);
        }
    }


}