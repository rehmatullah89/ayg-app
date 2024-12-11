<?php

namespace tests\integrations\Tablet\Repositories;

use App\Tablet\Helpers\ConfigHelper;
use Parse\ParseClient;
use Parse\ParseObject;
use Parse\ParseQuery;
use PHPUnit_Framework_TestCase;

if (strcasecmp(getenv('env_InHerokuRun'), "Y") != 0) {
    include __DIR__ . '/../../../putenv.php';
}
date_default_timezone_set('America/New_York');
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), '/parse');
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));

$GLOBALS['env_PasswordHashSalt'] = ConfigHelper::get('env_PasswordHashSalt');
include __DIR__ . '/../../../lib/functions_parse.php';
include __DIR__ . '/../../../lib/functions_userauth.php';
include __DIR__ . '/../../../admin/functions_retailers.php';

class RetailerFunctionTest extends PHPUnit_Framework_TestCase
{
    // everything is in the database
    /**
     * @covers createTabletUserForRetailer($retailerId, $email, $password)
     */
    public function testRetailerFunction()
    {
        $retailer = $this->createRetailer();
        $email = 'sujit.baniya+posusercreate' . md5(time() . rand(1, 10000)) . '@gmail.com';
        $password = "Passw0rd";
        $result = createTabletUserForRetailer($retailer->getObjectId(), $email, $password);

        $this->assertEquals($result->get('retailer')->getObjectId(), $retailer->getObjectId());
        $this->assertEquals($result->get('tabletUser')->get('email'), $email);
        $this->assertEquals($result->get('tabletUser')->get('firstName'), $retailer->get('retailerName'));
        $this->assertEquals('Tablet', $result->get('tabletUser')->get('lastName'));
        $this->assertEquals('t', $result->get('tabletUser')->get('typeOfLogin'));
        $this->assertTrue($result->get('tabletUser')->get('hasTabletPOSAccess'));
        $this->assertFalse($result->get('tabletUser')->get('hasDeliveryAccess'));
        $this->assertFalse($result->get('tabletUser')->get('hasConsumerAccess'));

        $this->deleteTestObjectsWithId("Retailers", $retailer->getObjectId());
        $this->deleteTestObjectsWithValues("RetailerTabletUsers", "retailer", $retailer);
        $this->deleteTestObjectsWithValues("_User", "email", $email);

    }

    function createRetailer() {
        $parseRetailer = new ParseObject("Retailers");

        $parseRetailerTypeQuery = new ParseQuery("RetailerType");
        $parseRetailerType = $parseRetailerTypeQuery->first();

        $parseLocationQuery = new ParseQuery("TerminalGateMap");
        $parseLocation = $parseLocationQuery->first();

        $parseRetailerPriceCategoryQuery = new ParseQuery("RetailerPriceCategory");
        $parseRetailerPriceCategory = $parseRetailerPriceCategoryQuery->first();

        $parseRetailer->set('airportIataCode', "BWI");
        $parseRetailer->set('openTimesMonday', "5:00 AM");
        $parseRetailer->set('openTimesTuesday', "5:00 AM");
        $parseRetailer->set('openTimesWednesday', "5:00 AM");
        $parseRetailer->set('openTimesThursday', "5:00 AM");
        $parseRetailer->set('openTimesFriday', "5:00 AM");
        $parseRetailer->set('openTimesSaturday', "5:00 AM");
        $parseRetailer->set('openTimesSunday', "5:00 AM");
        $parseRetailer->set('closeTimesMonday', "10:00 PM");
        $parseRetailer->set('closeTimesTuesday', "10:00 PM");
        $parseRetailer->set('closeTimesWednesday', "10:00 PM");
        $parseRetailer->set('closeTimesThursday', "10:00 PM");
        $parseRetailer->set('closeTimesFriday', "10:00 PM");
        $parseRetailer->set('closeTimesSaturday', "10:00 PM");
        $parseRetailer->set('closeTimesSunday', "10:00 PM");
        $parseRetailer->set('description', "Provides vegetables");
        $parseRetailer->set('hasDelivery', true);
        $parseRetailer->set('hasPickup', true);
        $parseRetailer->set('isActive', true);
        $parseRetailer->set('isChain', false);
        $parseRetailer->set('retailerName', "Retailer Function");
        $parseRetailer->setArray('searchTags', ["Food"]);
        $parseRetailer->set('uniqueId', "123456789");
        $parseRetailer->set('retailerType', $parseRetailerType);
        $parseRetailer->set('location', $parseLocation);
        $parseRetailer->set('retailerPriceCategory', $parseRetailerPriceCategory);
        $parseRetailer->save();

        return $parseRetailer;

    }

    public function deleteTestObjectsWithId($className, $id) {
        $parseObject = new ParseObject($className, $id);
        if(!empty($parseObject)) {
            $parseObject->destroy();
        }
    }

    public function deleteTestObjectsWithValues($className, $field, $value) {
        $parseQuery = new ParseQuery($className);
        $parseQuery->equalTo($field, $value);
        $parseObject = $parseQuery->first();
        if(!empty($parseObject)) {
            $parseObject->destroy();
        }
    }

}
