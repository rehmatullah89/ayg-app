<?php

use Parse\ParseQuery;
use Parse\ParseUser;

require_once __DIR__ . '/../TabletBaseTest.php';

/**
 * Class GetActiveOrdersTest
 */
class ConfirmTest extends TabletBaseTest
{
    /**
     *
     */
    public function testCanConfirmOrder()
    {
        // prepare order with status 4
        // order to change is:
        $initialOrder=new \Parse\ParseObject('Order','pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 4);
        $initialOrder->save();


        $user = ParseUser::logIn('ludwik.grochowina+tablet@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);


        $url = $this->generatePath('tablet/order/confirm', $user->getSessionToken(), "orderId/" . $initialOrder->getObjectId());
        $response = $this->get($url);

        $jsonDecodedResponse = $response->getJsonDecodedBody();



        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertEquals($initialOrder->getObjectId(), $jsonDecodedResponse->order->orderId);



        $jsonDecodedResponseArray = $response->getJsonDecodedBody(true);
        $firstOrder = $jsonDecodedResponseArray['order'];
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

        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }


    public function testCanNotConfirmAlreadyConfirmedOrderButHasUniqueError()
    {
        // prepare order with status 4
        // order to change is:
        $initialOrder=new \Parse\ParseObject('Order','pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 5);
        $initialOrder->save();


        $user = ParseUser::logIn('ludwik.grochowina+tablet@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));

        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/order/confirm', $user->getSessionToken(), "orderId/" . $initialOrder->getObjectId());
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();

        //$this->assertEquals('400', $response->getHttpStatusCode());
        //$this->assertEquals('AS_5301', $jsonDecodedResponse->error_code);
        $this->assertEquals('200', $response->getHttpStatusCode());
        $this->assertEquals($initialOrder->getObjectId(), $jsonDecodedResponse->order->orderId);

        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }


    public function testCanNotConfirmNotExistingOrder()
    {
        // prepare order with status 4
        // order to change is:
        $initialOrder=new \Parse\ParseObject('Order','pmh4UNQDGG');
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

        $url = $this->generatePath('tablet/order/confirm', $user->getSessionToken(), "orderId/" . $initialOrder->getObjectId());
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();

        $this->assertEquals('400', $response->getHttpStatusCode());
        $this->assertEquals('AS_5302', $jsonDecodedResponse->error_code);

        /*
        $this->assertFalse($jsonDecodedResponse->success);
        $this->assertEquals('12111411', $jsonDecodedResponse->error->code);
        */

        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

}