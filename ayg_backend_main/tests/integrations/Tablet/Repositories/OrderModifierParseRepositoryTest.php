<?php

namespace tests\integrations\Tablet\Repositories;

use App\Tablet\Entities\OrderModifier;
use App\Tablet\Entities\RetailerItem;
use App\Tablet\Entities\RetailerItemModifierOption;
use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Repositories\OrderModifierParseRepository;
use Parse\ParseClient;
use Parse\ParseQuery;

if (strcasecmp(getenv('env_InHerokuRun'), "Y") != 0) {
    include __DIR__ . '/../../../../putenv.php';
}
date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));

class OrderModifierParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testOrderModifierParseRepositoryCanGetOrderModifiersByOrderId()
    {
        $orderModifiersQuery = new ParseQuery('OrderModifiers');
        $orderModifiersQuery->includeKey('order');
        $orderModifiersQuery->includeKey('retailerItem');
        $orderModifiersQuery->descending('updatedAt');
        $orderModifier = $orderModifiersQuery->first();


        $orderModifierParseRepository = new OrderModifierParseRepository();
        $orderModifiers = $orderModifierParseRepository->getOrderModifiersByOrderId($orderModifier->get('order')->getObjectId());
        $firstOrderModifier = reset($orderModifiers);

        $this->assertInstanceOf(OrderModifier::class, $firstOrderModifier);
        $this->assertInstanceOf(RetailerItem::class, $firstOrderModifier->getRetailerItem());

        $this->assertEquals($orderModifier->getObjectId(), $firstOrderModifier->getId());
    }


}