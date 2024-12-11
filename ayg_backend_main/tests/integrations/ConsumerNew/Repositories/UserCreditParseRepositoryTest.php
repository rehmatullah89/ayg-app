<?php

namespace tests\integrations\ConsumerNew\Repositories;

date_default_timezone_set('America/New_York');

use App\Consumer\Repositories\HelloWorldParseRepository;
use App\Consumer\Repositories\UserCreditParseRepository;
use Parse\ParseClient;

require_once __DIR__ . '/../../../../' . 'putenv.php';

ParseClient::setServerURL(getenv('env_ParseServerURL'), '/parse');
ParseClient::initialize(getenv('env_ParseApplicationId'), getenv('env_ParseRestAPIKey'), getenv('env_ParseMasterKey'));

class UserCreditParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testUserCreditParseRepositoryAddUserCredit()
    {
        $userQuery = new \Parse\ParseQuery("_User");
        $parseUser = $userQuery->first();
        $userId = $parseUser->getObjectId();

        $orderQuery = new \Parse\ParseQuery("Order");
        $parseOrder = $orderQuery->first();
        $orderId = $parseOrder->getObjectId();
        $userCreditParseRepository = new UserCreditParseRepository();

        $result = $userCreditParseRepository->add($userId, $orderId, 45, "No Money");

        $this->assertTrue(empty($result->getUser()));
        $this->assertTrue(empty($result->getFromOrder()));

        $userCreditQuery = new \Parse\ParseQuery("UserCredits");
        $userCreditQuery->equalTo("objectId", $result->getId());
        $parseUserCredit = $userCreditQuery->first();
        $parseUser = $parseUserCredit->get('user');
        $parseUser->fetch();
        $parseOrder = $parseUserCredit->get('fromOrder');
        $parseUser->fetch();
        $this->assertEquals($userId, $parseUser->getObjectId());
        $this->assertEquals($orderId, $parseOrder->getObjectId());


        $parseUserCredit->destroy();
    }
}