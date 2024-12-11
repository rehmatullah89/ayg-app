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
$fileArray = array_map('str_getcsv', file('./subscriptions/SUBSCRIPTION-data/SUBSCRIPTION.csv'));


// Skip the Header row and create key arrays
$objectKeys = array_map('trim', array_shift($fileArray));

// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
$objectKeyIsArray = array(
    "subscriptionPartner" => "N",
    "subscriptionPartnerType" => "N",
    "subscriptionPlanName" => "N",
    "subscriptionPlanDescription" => "N",
    "subscriptionCost" => "I",
    "subscriptionWelcomeEmailMessage" => "N",
    "subscriptionUIMessage" => "N",
    "subscriptionPlanStartDate" => "N",
    "subscriptionPlanEndDate" => "N",
    "isActive" => "N",
    "benefitPeriodLength" => "N",
    "orderLimitPerUserPerPeriod" => "I",
    "orderOffAmountLimitPerUserPerPeriod" => "I",
    "orderOffAmountThreshold" => "I",
    "deliveryOffAmount" => "I",
    "deliveryOffPercent" => "I",
    "orderOffAmount" => "I",
    "orderOffPercent" => "I",
);

$imagesIndexesWithPaths = array();

$referenceLookup = array();

// Verify no new airport codes were added
verifyNewValues($fileArray, "SubscriptionPartners", "partnerName", "subscriptionPartner", array_search("subscriptionPartner", $objectKeys));

prepareAndPostToParse($env_ParseApplicationId, $env_ParseRestAPIKey, "SubscriptionPlans", $fileArray, $objectKeyIsArray, $objectKeys, "N", array("subscriptionPlanDescription"), []);

echo("<br /><br />");
echo("<a href='generate_DistanceMetricsCache.php'>SUBSCRIPTION DATA UPLOAD DONE</a>");

@ob_end_clean();

?>
