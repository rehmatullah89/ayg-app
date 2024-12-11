<?php

require_once 'ConsumerBaseTest.php';

/**
 * Class ConsumerOrderHelpTest
 *
 * tested endpoint:
 * // Order help
 * '/order/help/a/:apikey/e/:epoch/u/:sessionToken/orderId/:orderId/comments/:comments'
 */
class ConsumerOrderHelpTest extends ConsumerBaseTest
{
    /**
     *
     */
    public function testOrderCanGiveHelp()
    {
        $user = $this->createUser();
        $comments='some comment';
        $comments=urlencode($comments);
        $retailer = $this->getActiveRetailerItemWithExistingRetailer();
        $order = $this->addParseObjectAndPushObjectOnStack('Order', [
            'user' => $user,
            'retailer' => $retailer,
            'status' => 2,
        ]);
        $orderId = $order->getObjectId();

        $url = $this->generatePath('order/help', $user->getSessionToken(), "orderId/$orderId/comments/$comments");
        $response = $this->get($url);
        $jsonDecodedBody = $response->getJsonDecodedBody();
        $this->assertEquals('1', $jsonDecodedBody->saved);

    }
}