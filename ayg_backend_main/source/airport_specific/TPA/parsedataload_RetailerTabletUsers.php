<?php
$_SERVER['REQUEST_METHOD']='';
$_SERVER['REMOTE_ADDR']='';
$_SERVER['REQUEST_URI']='';
$_SERVER['SERVER_NAME']='';

	require 'dirpath.php';
$fullPathToBackendLibraries = "../";
	
	require_once $fullPathToBackendLibraries . 'vendor/autoload.php';
	require_once $fullPathToBackendLibraries . 'lib/initiate.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/gatemaps.inc.php';
	require_once $fullPathToBackendLibraries . 'lib/functions_directions.php';
	require_once $fullPathToBackendLibraries . 'admin/functions_retailers.php';

	// require 'parsedataload_functions.php';

$x = explode('airport_specific/',__DIR__);
$airportIataCode=substr($x[1],0,3);


	ob_start();	
	while (ob_get_level() > 0)
    ob_end_flush();
	
	$fileArray = array_map('str_getcsv', file('./airport_specific/'.$airportIataCode.'/'.$airportIataCode.'-data/'.$airportIataCode.'-RetailerPOSUser.csv'));

	// Skip the Header row and create key arrays
	$objectKeys = array_map('trim', array_shift($fileArray));
	
	$total = count_like_php5($fileArray);

	foreach($fileArray as $i => $line) {

		// Build variables
		foreach($objectKeys as $key => $keyName) {

			${$keyName} = $line[$key];
		}

		// Create user or add access
		list($response, $errorMsg) = createTabletUserForRetailer($retailerUniqueId, $tabletUserEmail, $tabletUserPassword, $comments, $isAdminUser == 'Y' ? true : false);

		if(!$response) {

			die("$retailerUniqueId, $tabletUserEmail, $tabletUserPassword, $comments - " . $errorMsg . "<br />");
		}
		else {

			echo("$retailerUniqueId, $tabletUserEmail - " . $errorMsg . "<br/>");
		}

		echo("---------------------------------------------------------" . "<br />\n");
		echo("Row " . ($i+1) . " / $total" . "<br />\n");
		echo("---------------------------------------------------------" . "<br />\n");

		flush();
		@ob_flush();
	}

	@ob_end_clean();

	$cacheKeyList[] = $GLOBALS['redis']->keys("*__RETAILERTABLETUSER*");

	resetCache($cacheKeyList);

?>
