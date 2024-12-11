<?php
    $_SERVER['REQUEST_METHOD']='';
    $_SERVER['REMOTE_ADDR']='';
    $_SERVER['REQUEST_URI']='';
    $_SERVER['SERVER_NAME']='';

require 'dirpath.php';
$fullPathToBackendLibraries = "./../";
require $fullPathToBackendLibraries . 'lib/initiate.inc.php';


ob_start();

while (ob_get_level() > 0) {
    ob_end_flush();
}

/////////////////////////////////////////////////////////////////////////////////////////////

ini_set('auto_detect_line_endings', true);
$fileArray = array_map('str_getcsv', file('./subscriptions/SUBSCRIPTION-data/SUBSCRIPTION_PARTNERS.csv'));

// Skip the Header row and create key arrays
$objectKeys = array_map('trim', array_shift($fileArray));
$objectKeys[0]=remove_utf8_bom($objectKeys[0]);


// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
$objectKeyIsArray = array(
    "partnerName" => "N",
    "partnerEmail" => "N",
    "partnerDescription" => "N",
    "isActive" => "X",
);

$imagesIndexesWithPaths = array();

$referenceLookup = array();


prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "SubscriptionPartners", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("partnerEmail"), []);

echo("<br /><br />");
echo("<a href='generate_DistanceMetricsCache.php'>SUBSCRIPTION PARTNER DATA UPLOAD DONE</a>");

@ob_end_clean();



function remove_utf8_bom($text)
{
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return $text;
}
?>
