<?php


require 'dirpath.php';
$fullPathToBackendLibraries = "../../";

require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';


$cacheKeyList[]  = $GLOBALS['redis']->keys("*zDeliverySlack*");

print_r(resetCache($cacheKeyList));
setConfMetaUpdate();


?>
