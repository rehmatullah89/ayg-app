<?php

use Parse\ParseQuery;
use Parse\ParseUser;

require_once __DIR__ . '/../TabletBaseTest.php';
/**
 * Class GetActiveOrdersTest
 */
class GetActiveOrdersTest extends TabletBaseTest
{
    /**
     *
     */
    public function testCanGetActiveOrdersWithStatusPaymentAccepted()
    {

        // prepare order with status 4
        // order to change is:
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 3);
        $initialOrder->save();


        $user = ParseUser::logIn('auntie@airportsherpa.io-t', md5('Tablet@123' . getenv('env_PasswordHashSalt')));


        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);




        $url = $this->generatePath('tablet/order/getActiveOrders', $user->getSessionToken(), "page/1/limit/10");
        $response = $this->get($url);

        var_dump($response->getJsonDecodedBody(true));
        return 1 ;

        $this->assertEquals('200', $response->getHttpStatusCode());
        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);


        $this->assertArrayHasKey('closeEarlyData', $arrayJsonDecodedResponse);
        $this->assertArrayHasKey('isCloseEarlyRequested', $arrayJsonDecodedResponse['closeEarlyData']);
        $this->assertArrayHasKey('isClosedEarly', $arrayJsonDecodedResponse['closeEarlyData']);



        //ordersList part
        $this->assertArrayHasKey('ordersList', $arrayJsonDecodedResponse);
        //$firstOrder = reset($arrayJsonDecodedResponse['ordersList']);
        $firstOrder = $arrayJsonDecodedResponse['ordersList'][0];
        $keysExpectedInOrderData = [
            'orderId',
            'orderSequenceId',
            'orderStatusCode',
            'orderStatusDisplay',
            'orderStatusCategoryCode',
            'orderType',
            'orderDateAndTime',
            'retailerId',
            'retailerName',
            'retailerLocation',
            'consumerName',
            'mustPickupBy',
            'numberOfItems',
            'items',
        ];
        foreach ($keysExpectedInOrderData as $keyExpectedInOrderData) {
            $this->assertArrayHasKey($keyExpectedInOrderData, $firstOrder);
        }

        $firstItem = reset($firstOrder['items']);
        $this->assertArrayHasKey('retailerItemName', $firstItem);
        $this->assertArrayHasKey('itemQuantity', $firstItem);
        $this->assertArrayHasKey('options', $firstItem);
        $this->assertArrayHasKey('itemComments', $firstItem);


        if (!empty($firstItem['options'])) {
            $firstOption = reset($firstItem['options']);
            $this->assertArrayHasKey('name', $firstOption);
            $this->assertArrayHasKey('quantity', $firstOption);
            $this->assertArrayHasKey('categoryName', $firstOption);
        } else {
            //trigger_error("Could not check item options correctness - no data in database", E_USER_NOTICE);
        }

        // check is status was changed from 3 to 4 (payment accepted to pushed to retailer)
        $order = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $order->fetch();
        $this->assertEquals(4, $order->get('status'));


        /*
        $this->assertTrue($jsonDecodedResponse->success);

        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);


        //retailerPingInfo part
        $this->assertArrayHasKey('retailerPingInfo', $arrayJsonDecodedResponse['data']);
        $this->assertArrayHasKey('pingInterval', $arrayJsonDecodedResponse['data']['retailerPingInfo']);
        $this->assertArrayHasKey('notificationSoundUrl', $arrayJsonDecodedResponse['data']['retailerPingInfo']);
        $this->assertArrayHasKey('shouldForceOpenBusiness', $arrayJsonDecodedResponse['data']['retailerPingInfo']);
        $this->assertArrayHasKey('notificationVibrateUsage', $arrayJsonDecodedResponse['data']['retailerPingInfo']);


        //ordersList part
        $this->assertArrayHasKey('ordersList', $arrayJsonDecodedResponse['data']);
        //$firstOrder = reset($arrayJsonDecodedResponse['data']['ordersList']);
        $firstOrder = $arrayJsonDecodedResponse['data']['ordersList'][0];
        $keysExpectedInOrderData = [
            'orderId',
            'orderSequenceId',
            'orderStatusCode',
            'orderStatusDisplay',
            'orderStatusCategoryCode',
            'orderType',
            'orderDateAndTime',
            'retailerId',
            'retailerName',
            'retailerLocation',
            'consumerName',
            'mustPickupBy',
            'numberOfItems',
            'items',
        ];
        foreach ($keysExpectedInOrderData as $keyExpectedInOrderData) {
            $this->assertArrayHasKey($keyExpectedInOrderData, $firstOrder);
        }

        $firstItem = reset($firstOrder['items']);
        $this->assertArrayHasKey('retailerItemName', $firstItem);
        $this->assertArrayHasKey('itemQuantity', $firstItem);
        $this->assertArrayHasKey('options', $firstItem);
        $this->assertArrayHasKey('itemComments', $firstItem);


        if (!empty($firstItem['options'])) {
            $firstOption = reset($firstItem['options']);
            $this->assertArrayHasKey('name', $firstOption);
            $this->assertArrayHasKey('quantity', $firstOption);
            $this->assertArrayHasKey('categoryName', $firstOption);
        } else {
            //trigger_error("Could not check item options correctness - no data in database", E_USER_NOTICE);
        }

        // check is status was changed from 3 to 4 (payment accepted to pushed to retailer)
        $order = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $order->fetch();
        $this->assertEquals(4, $order->get('status'));
*/

        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();

    }

}