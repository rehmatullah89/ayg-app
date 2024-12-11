<?php

use Parse\ParseQuery;
use Parse\ParseUser;

require_once __DIR__ . '/../TabletBaseTest.php';

/**
 * Class GetPastOrdersTest
 */
class GetPastOrdersTest extends TabletBaseTest
{
    /**
     *
     */
    public function testCanGetPastOrdersTest()
    {
        // prepare order with status 10
        // order to change is:
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 10);
        $initialOrder->save();


        $user = ParseUser::logIn('ludwik.grochowina+tablet@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);


        $url = $this->generatePath('tablet/order/getPastOrders', $user->getSessionToken(), "page/1/limit/80");
        $response = $this->get($url);


        $jsonDecodedResponse = $response->getJsonDecodedBody();


        $this->assertEquals('200', $response->getHttpStatusCode());


        $arrayJsonDecodedResponse = $response->getJsonDecodedBody(true);


        $this->assertArrayHasKey('closeEarlyData', $arrayJsonDecodedResponse);
        $this->assertArrayHasKey('isCloseEarlyRequested', $arrayJsonDecodedResponse['closeEarlyData']);
        $this->assertArrayHasKey('isClosedEarly', $arrayJsonDecodedResponse['closeEarlyData']);


        //ordersList part
        $this->assertArrayHasKey('ordersList', $arrayJsonDecodedResponse);
        //$firstOrder = reset($arrayJsonDecodedResponse['ordersList']);
        $firstOrder=$arrayJsonDecodedResponse['ordersList'][2];

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

        $firstItem=reset($firstOrder['items']);
        $this->assertArrayHasKey('retailerItemName', $firstItem);
        $this->assertArrayHasKey('itemQuantity', $firstItem);
        $this->assertArrayHasKey('options', $firstItem);
        $this->assertArrayHasKey('itemComments', $firstItem);


        if (!empty($firstItem['options'])){
            $firstOption = reset($firstItem['options']);
            $this->assertArrayHasKey('name', $firstOption);
            $this->assertArrayHasKey('quantity', $firstOption);
            $this->assertArrayHasKey('categoryName', $firstOption);
        }else{
            trigger_error("Could not check item options correctness - no data in database", E_USER_NOTICE);
        }
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
        $firstOrder=$arrayJsonDecodedResponse['data']['ordersList'][2];

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

        $firstItem=reset($firstOrder['items']);
        $this->assertArrayHasKey('retailerItemName', $firstItem);
        $this->assertArrayHasKey('itemQuantity', $firstItem);
        $this->assertArrayHasKey('options', $firstItem);
        $this->assertArrayHasKey('itemComments', $firstItem);


        if (!empty($firstItem['options'])){
            $firstOption = reset($firstItem['options']);
            $this->assertArrayHasKey('name', $firstOption);
            $this->assertArrayHasKey('quantity', $firstOption);
            $this->assertArrayHasKey('categoryName', $firstOption);
        }else{
            trigger_error("Could not check item options correctness - no data in database", E_USER_NOTICE);
        }
        */

        $initialOrder->set('status',$initialOrderOldStatus);
        $initialOrder->save();

    }

}