<?php

use App\Background\Repositories\PingLogMysqlRepository;
use App\Consumer\Helpers\ConfigHelper;
use Parse\ParseClient;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../putenv.php';
require_once __DIR__ . '/../../scheduled/_queue_functions.php';


$env_mysqlLogsDataBaseHost = getenv('env_mysqlLogsDataBaseHost');
$env_mysqlLogsDataBaseName = getenv('env_mysqlLogsDataBaseName');
$env_mysqlLogsDataBaseUser = getenv('env_mysqlLogsDataBaseUser');
$env_mysqlLogsDataBasePassword = getenv('env_mysqlLogsDataBasePassword');
$env_mysqlLogsDataBasePort = getenv('env_mysqlLogsDataBasePort');
require_once __DIR__ . '/../../lib/initiate.mysql_logs.php';

$message['content']['deliveryUniqueId'] = 1;
$message['content']['time'] = 2;
$x='';
queue__log_delivery_ping($message, $x);

