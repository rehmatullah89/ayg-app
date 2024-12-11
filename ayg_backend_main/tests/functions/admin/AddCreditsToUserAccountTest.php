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
include __DIR__ . '/../../../admin/functions_credits.php';

class DeliveryFreeSlotsTest extends PHPUnit_Framework_TestCase
{

    function testIfCreditsAreAdded()
    {
        // ludwik@toptal.com
        $userId = 'aJlycFFXYR';
        $orderId = null;
        $creditsInCent = 150;
        $reasonForCredit = 'test adding credits by function';
        $signupCouponId = null;

        addCreditsToUsersAccount($userId, $orderId, $creditsInCent, $reasonForCredit, $signupCouponId);

        $user = new ParseObject('_User', $userId);
        $parseCreditsQuery = new ParseQuery('UserCredits');
        $parseCreditsQuery->equalTo('user',$user);
        $parseCreditsQuery->descending('createdAt');
        $parseCredit = $parseCreditsQuery->first();

        $this->assertEquals($userId, $parseCredit->get('user')->getObjectId());
        $this->assertEquals($reasonForCredit, $parseCredit->get('reasonForCredit'));
        $this->assertEquals($creditsInCent, $parseCredit->get('creditsInCents'));

        $parseCredit->destroy(true);
    }

}