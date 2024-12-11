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

$env_mysqlLogsDataBaseHost = getenv('env_mysqlLogsDataBaseHost');
$env_mysqlLogsDataBaseName = getenv('env_mysqlLogsDataBaseName');
$env_mysqlLogsDataBaseUser = getenv('env_mysqlLogsDataBaseUser');
$env_mysqlLogsDataBasePassword = getenv('env_mysqlLogsDataBasePassword');
$env_mysqlLogsDataBasePort = getenv('env_mysqlLogsDataBasePort');

include __DIR__ . '/../../../lib/functions_parse.php';
include __DIR__ . '/../../../lib/functions_userauth.php';
include __DIR__ . '/../../../admin/functions_retailers.php';
include __DIR__ . '/../../../lib/initiate.mysql_logs.php';

class IdentifyRetailerUptimeTest extends PHPUnit_Framework_TestCase
{

    function testIfUptimeCanBeIdentified()
    {
        $tabletUptime = identifyTabletsUptimePercentageByDay('2017-10-12', '728d735364aee5a26810e68dc12bcacc');
        echo $tabletUptime . '%';
    }

}