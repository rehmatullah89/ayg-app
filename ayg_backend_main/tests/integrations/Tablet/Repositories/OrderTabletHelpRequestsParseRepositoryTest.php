<?php

namespace tests\integrations\Tablet\Repositories;

use App\Tablet\Entities\Order;
use App\Tablet\Entities\OrderTabletHelpRequest;
use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Repositories\OrderTabletHelpRequestsParseRepository;
use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;

if (strcasecmp(getenv('env_InHerokuRun'), "Y") != 0) {
    include __DIR__ . '/../../../../putenv.php';
}
date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));


class OrderTabletHelpRequestsParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testOrderTabletHelpRequestsCanAdd()
    {
        $parseOrderQuery = new ParseQuery('Order');
        $parseOrder = $parseOrderQuery->first();

        $orderTabletHelpRequestsParseRepository = new OrderTabletHelpRequestsParseRepository();
        $return = $orderTabletHelpRequestsParseRepository->add($parseOrder->getObjectId(), 'some content');

        $this->assertInstanceOf(OrderTabletHelpRequest::class, $return);
        $this->assertInstanceOf(Order::class, $return->getOrder());

        // destroy added value
        $parseOrderTabletHelpRequests=new ParseObject('OrderTabletHelpRequests', $return->getId());
        $parseOrderTabletHelpRequests->destroy();
    }
}