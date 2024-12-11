<?php

require 'dirpath.php';

require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;


// Status
$app->get('/status/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {
	
	$responseArray = array();
	
	//////////////////////////////////
	// TripIt
	//////////////////////////////////
	$tripItTokens = parseExecuteQuery(array("user" => $GLOBALS['user'], "isActive" => true), "TripItTokens");
	
	// If Access token found, then return authorization
	if(count_like_php5($tripItTokens) > 0) {
		
		$responseArray["tripIt"] = true;
	}
	else {
		
		$responseArray["tripIt"] = false;
	}
	//////////////////////////////////

	json_echo(
		json_encode($responseArray)
	);
});

// Request Token
$app->get('/tripIt/requestToken/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
    function ($apikey, $epoch, $sessionToken) {

	// Request Token from TripIt
	$oauthCredential = new OAuthConsumerCredential($GLOBALS['env_TripITOAuthConsumerKey'], $GLOBALS['env_TripITOAuthConsumerSecret']);

	// Get OAuth Credential
	// JMD
	$tripIt = new TripIt($oauthCredential);
	$response = $tripIt->get_request_token();

	// Check if the response is valid JSON response
	try {

		validateTripitResponse($response);
	}
	catch (Exception $ex) {

		json_error("AS_110", "", "Invalid TripIt response, TripIt Response dump: " . $ex->getMessage(), 2);
	}

	$tripItSessionInfo = ["requestToken" => $response["oauth_token"], "requestTokenSecretEncrypted" => encryptStringInMotion($response["oauth_token_secret"])];
	
	setTripItSessionToCache($tripItSessionInfo);

	// Continue processing JSON response
	json_echo(
		json_encode(array("oauth_token" => $response["oauth_token"]))
	);
});

// Access Token, this is a temporary token and sent back to user
$app->get('/tripIt/accessToken/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {
	
    $tripItToken = getTripItToken();

	// If Access token found, then return authorization
	if(count_like_php5($tripItToken) > 0) {
		
		// Remove the TripIt session
		deleteTripItSessionToCache();

		json_echo(
			json_encode(array("authorized" => "1"))
		);
	}

	//////////////////////////
	// Else, Get Request Token information, which was Step 1
	$tripItSession = getTripItSessionToCache();
	// print_r($tripItSession);exit;
	if(!is_array($tripItSession)) {
		
		json_error("AS_101", "", "No valid TripIt Session key found.");
	}

	$request_token = $tripItSession["requestToken"];
	$request_token_secret = decryptStringInMotion($tripItSession["requestTokenSecretEncrypted"]);

	$oauthCredential = new OAuthConsumerCredential($GLOBALS['env_TripITOAuthConsumerKey'], $GLOBALS['env_TripITOAuthConsumerSecret'], $request_token, $request_token_secret);
	
	$tripit = new TripIt($oauthCredential);
	$response = $tripit->get_access_token();
	// print_r($response);exit;
	// Check if the response is valid JSON response
	try {

		validateTripitResponse($response);
	}
	catch (Exception $ex) {

		json_error("AS_110", "", "Invalid TripIt response, TripIt Response dump: " . $ex->getMessage(), 2);
	}

	// Add Token for TripIt User in Parse
	$tripItTokens = new ParseObject("TripItTokens");
	$tripItTokens->set("user", $GLOBALS['user']);
	$tripItTokens->set("oauthAccessToken", $response["oauth_token"]);
	$tripItTokens->set("oauthAccessTokenSecretEncrypted", encryptStringInMotion($response["oauth_token_secret"]));
	$tripItTokens->set("isActive", true);
	$tripItTokens->save();
	//////////////////////////

	// Remove the TripIt session
	deleteTripItSessionToCache();

	// Connect with new credentials
	$oauthCredential = connectToTripIt($tripItToken);

	// Fetch TripIt trips and flights
	try {

		fetchTripItTrips($oauthCredential);
	}
	catch(Exception $ex) {

		// non existing error
		$error_array = json_decode($ex->getMessage(), true);
		json_error($error_array["error_code"], $error_array["error_message_user"], $error_array["error_message_log"] . " TripIt Error - ", 3, 1);
	}
	
	json_echo(
		json_encode(array("authorized" => "1"))
	);
});

// Revoke TripIt Access
$app->get('/tripIt/revokeAccess/a/:apikey/e/:epoch/u/:sessionToken', \App\Consumer\Middleware\UserAuthMiddleware::class . '::apiAuth',
	function ($apikey, $epoch, $sessionToken) {
	
	//////////////////////////////////
	// Check if we already have the Access Token
	$tripItTokens = parseExecuteQuery(array("user" => $GLOBALS['user'], "isActive" => true), "TripItTokens");
	
	// If Access token found, then return authorization
	if(count_like_php5($tripItTokens) == 0) {
		
		json_error("AS_102", "You must first authorize TripIt to allow AtYourGate to access your account.", "No Authorized User found! No TripIt tokens found for this user.");
	}

	// Revoke Access
	revokeTripItAccess();

	json_echo(
		json_encode(array("revoked" => "1"))
	);
});

$app->notFound(function () {
	
	json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

?>
