<?php
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

$_SERVER['REQUEST_METHOD'] = '';
$_SERVER['REMOTE_ADDR'] = '';
$_SERVER['REQUEST_URI'] = '';
$_SERVER['SERVER_NAME'] = '';

ini_set("memory_limit", "384M"); // Max 512M

define("WORKER", true);
define("QUEUE", true);
define("WORKER_MENU_LOADER", true);

require_once 'dirpath.php';
require_once $dirpath . 'lib/initiate.inc.php';
require_once $dirpath . 'lib/errorhandlers_scheduled.php';


///////////////////////////////////////////////////////////////////////////////
// Check if 9001 request came in
///////////////////////////////////////////////////////////////////////////////

$airportIataCode='DEN';

// DEN B 30
$fromLocationId='BpljKv5GAV';
// DEN B 60

//$fromLocationId='6uKDebc2n5';

// DEN B 90
//$fromLocationId='HLgoErHWnj';

// DEN B 22
$toRetailerLocationId='spBad6rw9D';


$x=$fromLocationId;
$y=$toRetailerLocationId;

// revert

//$toRetailerLocationId=$x;
//$fromLocationId=$y;


$referenceRetailerId="";
$setCache=false;
$x=getDirections($airportIataCode, $fromLocationId, $toRetailerLocationId, $referenceRetailerId, $setCache);



//$x=$distanceMetrics = getDistanceMetrics('B', '', '90', 'B', '', '23', true, 'DEN');

var_dump($x);
var_dump('done');

die();

