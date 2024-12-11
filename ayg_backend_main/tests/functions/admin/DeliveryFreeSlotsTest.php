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
ParseClient::setServerURL(ConfigHelper::get('env_ParseServerURL'), getenv('env_ParseMount'));
ParseClient::initialize(ConfigHelper::get('env_ParseApplicationId'), ConfigHelper::get('env_ParseRestAPIKey'), ConfigHelper::get('env_ParseMasterKey'));

$GLOBALS['env_PasswordHashSalt'] = ConfigHelper::get('env_PasswordHashSalt');
include __DIR__ . '/../../../lib/functions_parse.php';
include __DIR__ . '/../../../lib/functions_userauth.php';
include __DIR__ . '/../../../admin/functions_retailers.php';

class DeliveryFreeSlotsTest extends PHPUnit_Framework_TestCase
{

    function testIfDeliveryHasFreeSlots()
    {
        $delivery = new ParseObject('zDeliverySlackUser', 'SQNTMuU9LI');


        //echo 'assignments: ' . count_like_php5($deliveryAssignments);


        for ($i = 0; $i < 10; $i++) {

            $deliveryAssignmentsQuery = new ParseQuery('zDeliverySlackOrderAssignments');
            $deliveryAssignmentsQuery->equalTo('deliveryUser', $delivery);
            $deliveryAssignmentsQuery->includeKey('order');
            $deliveryAssignmentsQuery->skip($i*100);
            $deliveryAssignments = $deliveryAssignmentsQuery->find();


            foreach ($deliveryAssignments as $assignment) {
                if ($assignment->get('order') != null) {
                    $status = $assignment->get('order')->get('status');
                    if (!in_array($status, [6, 10])) {
                        echo "\n";
                        echo var_dump($assignment->get('order')->get('status'), $assignment->get('order')->getObjectId());
                    }
                }
            }
        }

    }

}