<?php

use Parse\ParseUser;

require_once __DIR__ . '/../TabletBaseTest.php';

/**
 * Class ApiMiddlewareTest
 *
 * tests \App\Tablet\Middleware\ApiMiddleware class
 */
class ApiMiddlewareTest extends TabletBaseTest
{
    /**
     * @covers \App\Tablet\Middleware\ApiMiddleware::apiAuth()
     *
     * sets url with correct session token
     * expects http response to be 200 OK
     * expects result to be success
     */
    public function testUserCanGetActiveOrdersWhenSessionTokenIsCorrect()
    {
        // prepare order with status 4
        // order to change is:
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 3);
        $initialOrder->save();
        $user = ParseUser::logIn('ludwik.grochowina+tablet@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));
        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/order/getActiveOrders', $user->getSessionToken(), "page/1/limit/10");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('200', $response->getHttpStatusCode());

        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

    /**
     * @covers \App\Tablet\Middleware\ApiMiddleware::apiAuth()
     *
     * sets url with not existing session token
     * expect that http response will be 401 Unauthorized
     * expect success to be false
     */
    public function testUserCanNotGetActiveOrdersWhenSessionTokenIsIncorrect()
    {
        // prepare order with status 4
        // order to change is:
        $initialOrder = new \Parse\ParseObject('Order', 'pmh4UNQDGG');
        $initialOrder->fetch();
        $initialOrderOldStatus = $initialOrder->get('status');
        $initialOrder->set('status', 3);
        $initialOrder->save();
        $user = ParseUser::logIn('ludwik.grochowina+tablet@gmail.com-t', md5('PASSword000' . getenv('env_PasswordHashSalt')));
        $this->addParseObjectAndPushObjectOnStack('SessionDevices', [
            'user' => $user,
            'sessionTokenRecall' => $user->getSessionToken(),
            'isActive' => true
        ]);

        $url = $this->generatePath('tablet/order/getActiveOrders', 'notExistingSessionToken', "page/1/limit/10");
        $response = $this->get($url);
        $jsonDecodedResponse = $response->getJsonDecodedBody();
        $this->assertEquals('401', $response->getHttpStatusCode());
        $this->assertEquals('AS_015', $jsonDecodedResponse->error_code);

        $initialOrder->set('status', $initialOrderOldStatus);
        $initialOrder->save();
    }

}