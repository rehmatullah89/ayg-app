<?php

use App\Consumer\Helpers\ConfigHelper;

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';




$client = new Predis\Client(
    [
        'host' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_HOST),
        'port' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PORT),
        'password' => parse_url(ConfigHelper::get('env_CacheRedisURL'), PHP_URL_PASS),
    ]
);


$keys=$client->keys('*COUPON_*');


foreach ($keys as $key){
    $hashKeys=$client->hgetall($key);
    foreach ($hashKeys as $k=>$v){
        file_put_contents('scheduled/coupons/'.$key.'_'.$k.'.json',$v);
    }
}




die();
