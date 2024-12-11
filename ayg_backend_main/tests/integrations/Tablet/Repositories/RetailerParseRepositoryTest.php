<?php

namespace tests\integrations\Tablet\Repositories;

use App\Tablet\Helpers\ConfigHelper;
use App\Tablet\Repositories\RetailerParseRepository;
use Parse\ParseClient;

if (strcasecmp(getenv('env_InHerokuRun'), "Y") != 0) {
    include __DIR__ . '/../../../../putenv.php';
}
date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));

class RetailerParseRepositoryTest extends \PHPUnit_Framework_TestCase
{
    // everything is in the database
    public function testRetailerParseRepositoryCanGetByTabletUserId()
    {
        // create user
        $user = new \Parse\ParseUser();
        $username = 'RetailerParseRepositoryTest' . rand(0, 10000);
        $user->set('username', $username);
        $user->set('password', $username);
        $user->signUp();

        // create location
        $retailerLocation = new \Parse\ParseObject('TerminalGateMap');
        $retailerLocation->set('geoPointLocation', new \Parse\ParseGeoPoint(11.1111, 22.2222));
        $retailerLocation->set('locationDisplayName', 'some gate');
        $retailerLocation->save();

        // create Retailer with location
        $retailer = new \Parse\ParseObject('Retailers');
        $retailer->set('retailerName', 'RetailerABC');
        $retailer->set('location', $retailerLocation);
        $retailer->save();

        // create RetailerPOSConfig with user and retailer
        $retailerPosConfig = new \Parse\ParseObject('RetailerTabletUsers');
        $retailerPosConfig->set('retailer', $retailer);
        $retailerPosConfig->set('tabletUser', $user);
        $retailerPosConfig->save();
        //


        $retailerParseRepository = new RetailerParseRepository();
        $result = $retailerParseRepository->getByTabletUserId($user->getObjectId());



        $this->assertInstanceOf(\App\Tablet\Entities\Retailer::class, $result[0]);
        $this->assertEquals($retailer->getObjectId(), $result[0]->getId());
        $this->assertInstanceOf(\App\Tablet\Entities\TerminalGateMap::class, $result[0]->getLocation());
        $this->assertEquals($retailerLocation->getObjectId(), $result[0]->getLocation()->getId());

        $retailerPosConfig->destroy();
        $retailer->destroy();
        $retailerLocation->destroy();
        $user->destroy();

    }


    // there is no retailer in the RetailerPOSConfig
    public function testRetailerParseRepositoryCanNotGetByTabletUserIdWithoutRetailer()
    {
        // create user
        $user = new \Parse\ParseUser();
        $username = 'RetailerParseRepositoryTest' . rand(0, 10000);
        $user->set('username', $username);
        $user->set('password', $username);
        $user->signUp();

        // create RetailerPOSConfig with user and retailer
        $retailerPosConfig = new \Parse\ParseObject('RetailerPOSConfig');
        $retailerPosConfig->set('retailer', null);
        $retailerPosConfig->set('tabletUser', $user);
        $retailerPosConfig->save();


        $retailerParseRepository = new RetailerParseRepository();
        $result = $retailerParseRepository->getByTabletUserId($user->getObjectId());

        $this->assertInternalType('array', $result);
        $this->assertTrue(empty($result));

        $retailerPosConfig->destroy();
        $user->destroy();
    }


    // there is no retailerPosConfig
    public function testRetailerParseRepositoryCanNotGetByTabletUserIdWithoutRetailerPosConfig()
    {
        // create user
        $user = new \Parse\ParseUser();
        $username = 'RetailerParseRepositoryTest' . rand(0, 10000);
        $user->set('username', $username);
        $user->set('password', $username);
        $user->signUp();


        $retailerParseRepository = new RetailerParseRepository();
        $result = $retailerParseRepository->getByTabletUserId($user->getObjectId());

        $this->assertInternalType('array', $result);
        $this->assertTrue(empty($result));

        $user->destroy();
    }

}