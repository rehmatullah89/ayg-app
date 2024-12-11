<?php

namespace tests\integrations\Tablet\Repositories;

use App\Tablet\Entities\RetailerItemModifierOption;
use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Repositories\HelloWorldParseRepository;
use App\Tablet\Repositories\RetailerItemModifierOptionParseRepository;
use Parse\ParseClient;
use Parse\ParseQuery;

if (strcasecmp(getenv('env_InHerokuRun'), "Y") != 0) {
    include __DIR__ . '/../../../../putenv.php';
}
date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));

class RetailerItemModifierOptionParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    public function testRetailerItemModifierOptionParseRepositoryCanGetListByUniqueIdList()
    {
        $retailerItemModifierOptionsQuery = new ParseQuery('RetailerItemModifierOptions');
        $retailerItemModifierOptionsQuery->limit(2);
        $retailerItemModifierOptions = $retailerItemModifierOptionsQuery->find();

        if (count_like_php5($retailerItemModifierOptions) != 2) {
            trigger_error("Could not check retailer item modifier options correctness - no data in database", E_USER_WARNING);
            return false;
        }

        // get first 2
        $ids = [
            $retailerItemModifierOptions[0]->get('uniqueId'),
            $retailerItemModifierOptions[1]->get('uniqueId'),
        ];

        $retailerItemModifierOptionParseRepository = new RetailerItemModifierOptionParseRepository();
        $optionsList = $retailerItemModifierOptionParseRepository->getListByUniqueIdList($ids);

        $this->assertCount(2, $optionsList);
        $this->assertInstanceOf(RetailerItemModifierOption::class, $optionsList[0]);
        $this->assertEquals($retailerItemModifierOptions[0]->getObjectId(), $optionsList[0]->getId());
        $this->assertEquals($retailerItemModifierOptions[1]->getObjectId(), $optionsList[1]->getId());
    }


    public function testRetailerItemModifierOptionParseRepositoryCanGetEmptyList(){
        $retailerItemModifierOptionParseRepository = new RetailerItemModifierOptionParseRepository();
        $optionsList = $retailerItemModifierOptionParseRepository->getListByUniqueIdList([]);

        $this->assertCount(0, $optionsList);
    }
}