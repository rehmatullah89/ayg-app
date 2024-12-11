<?php


require 'dirpath.php';
$fullPathToBackendLibraries = "./../";

require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';


$cacheKeyList[] = $GLOBALS['redis']->keys("*__TERMINALGATEMAP_*");
$cacheKeyList[] = $GLOBALS['redis']->keys("*__DIRECTIONS_*");
$cacheKeyList[] = $GLOBALS['redis']->keys("RR*gate*");
$cacheKeyList[] = $GLOBALS['redis']->keys("*TerminalGateMap*");
$cacheKeyList[] = $GLOBALS['redis']->keys("*fullfillmentInfo*");

print_r(resetCache($cacheKeyList));
setConfMetaUpdate();


?>
