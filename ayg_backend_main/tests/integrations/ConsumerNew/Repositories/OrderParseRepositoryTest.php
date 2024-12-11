<?php

namespace tests\integrations\ConsumerNew\Repositories;

date_default_timezone_set("America/New_York");

use App\Consumer\Repositories\OrderParseRepository;
use Parse\ParseClient;
use Parse\ParseQuery;


require_once __DIR__ . '/../../../../' . 'putenv.php';

ParseClient::setServerURL(getenv('env_ParseServerURL'), '/parse');
ParseClient::initialize(getenv('env_ParseApplicationId'), getenv('env_ParseRestAPIKey'), getenv('env_ParseMasterKey'));

class OrderParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testOrderParsedRepositoryAddOrderRatingWithFeedback()
    {
        $orderQuery = new ParseQuery("Order");
        $parseOrder = $orderQuery->first();
        $orderId = $parseOrder->getObjectId();

        $orderParseRepository = new OrderParseRepository();
        $result = $orderParseRepository->addOrderRatingWithFeedback($orderId, 2, "This is feedback");

        $this->assertEquals(2, $result->getOverAllRating());

        $query = new ParseQuery("Order");
        $query->equalTo("objectId", $orderId);
        $userCreditQuery = new ParseQuery("OrderRatings");
        $userCreditQuery->matchesQuery("order", $query);
        $userCreditQuery->equalTo("overallRating", 2);
        $parseUserCredit = $userCreditQuery->first();
        $parseUserCredit->destroy();
    }
}