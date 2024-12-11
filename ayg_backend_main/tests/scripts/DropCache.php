<?php


use Parse\ParseQuery;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../putenv.php';

date_default_timezone_set('America/New_York');

$env_ParseServerURL = getenv('env_ParseServerURL');
$env_ParseApplicationId = getenv('env_ParseApplicationId');
$env_ParseRestAPIKey = getenv('env_ParseRestAPIKey');
$env_ParseMasterKey = getenv('env_ParseMasterKey');
$env_ParseMount = getenv('env_ParseMount');

$env_CacheEnabled						= (getenv('env_CacheEnabled') === 'true');
$env_CacheRedisURL						= getenv('env_CacheRedisURL');
$env_CacheSSLCA							= getenv('env_CacheSSLCA');
$env_CacheSSLCert						= getenv('env_CacheSSLCert');
$env_CacheSSLPK							= getenv('env_CacheSSLPK');


require_once __DIR__ . '/../../lib/initiate.redis.php';

/*
$GLOBALS['redis']->flushdb();
 */