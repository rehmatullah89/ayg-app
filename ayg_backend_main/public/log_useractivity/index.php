<?php

require 'dirpath.php';
require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;


// Log Retailer Visits
$app->get('/rvisits/a/:apikey/e/:epoch/u/:sessionToken/retailer/:uniqueRetailerIdSent/airportiatacode/:airportiatacode', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
		function ($apikey, $epoch, $sessionToken, $uniqueRetailerIdSent, $airportIataCode) {

	global $timeWindowInMinsToBarDuplicateEntries, $dirpath;
	
	$responseArray = array("logged" => 1);
	
	json_echo(
		json_encode($responseArray)
	);
});

$app->notFound(function () {
	
	json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

?>
