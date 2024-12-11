<?php

require 'dirpath.php';
require $dirpath . 'lib/initiate.inc.php';
require $dirpath . 'lib/errorhandlers.php';

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseUser;
use Parse\ParseFile;
use Httpful\Request;

// Add Partner Token to saved list
$app->get('/token/add/a/:apikey/e/:epoch/u/:sessionToken/token/:token', 'apiAuthWithoutSessionForPartnerAPI',
		function ($apikey, $epoch, $sessionToken, $token) {

		setCache('__PARTNERTOKEN__' . $token, 1, 0);
});

// Check Partner Token from saved list
$app->get('/token/check/a/:apikey/e/:epoch/u/:sessionToken/token/:token', 'apiAuthWithoutSessionForPartnerAPI', 
	function ($apikey, $epoch, $sessionToken, $token) {

		if(doesCacheExist('__PARTNERTOKEN__' . $token)) {
			
			$responseArray = array("used" => 1);
		}
		else {
			
			$responseArray = array("used" => 0);
		}
		
	json_echo(
		json_encode($responseArray)
	);
});

// List files
$app->get('/fileList/a/:apikey/e/:epoch/u/:sessionToken/startDate/:startDate/throughDate/:throughDate/sNum/:sNum/tNum/:tNum', 'apiAuthForPartnerAPI',
	function ($apikey, $epoch, $sessionToken, $startDate, $throughDate, $sNum, $tNum) {

	// getRouteCache();
	$sNum = intval($sNum);
	$tNum = intval($tNum);
	logPartnerActionViaQueue('file_list', ["startDate" => $startDate, "throughDate" => $throughDate, "sNum" => $sNum, "tNum" => $tNum]);

	// Find Partner account
	list($partnerName, $partnerLogoURL, $partnerFileStartDate) = partnerInfo($GLOBALS['partner']['primaryGroup']);

	if(!validateFileSearchDate($startDate) ||
		!validateFileSearchDate($throughDate) ||
		intval($startDate) < $partnerFileStartDate) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Invalid Dates");
	}
	else {

		$startDate = intval($startDate);
		$throughDate = intval($throughDate);

		//////////////////////////////////////////////
		$startDateTime = date_create($startDate);
	    $throughDateTime = date_create($throughDate);
	    $interval = date_diff($startDateTime, $throughDateTime);
	    $numberOfDays = $interval->format("%a") + 1;
		//////////////////////////////////////////////

	    $fileTimestamp = strtotime($startDate);
	    $fileCount = 1;
	    $fileList = [];
	    while($fileCount <= $numberOfDays) {

	    	list($folderPath, $fileName) = getS3KeyPath_PartnerExtractFile($fileTimestamp, $GLOBALS['partner']['primaryGroup']);

		    // JMD
		    $fileCount++;
		    $fileList[] = ["filename" => $fileName, "date" => date("Y-m-d", $fileTimestamp)];

	    	$fileTimestamp = $fileTimestamp + 24*60*60;
	    }

	    // Select the part requested by page
	    $fileTimestamp = strtotime($startDate);

	    if(intval($sNum) < 1) {

	    	$sNum = 1;
	    }

	    $startDays = $sNum-1;
	    $throughDays = $tNum;
	    $fileListSelected = [];

	    if($tNum > $numberOfDays) {

	    	$throughDays = $numberOfDays;
	    }

	    for($i=$startDays;$i<$startDays+$throughDays;$i++) {

	    	if(isset($fileList[$i]))
		    $fileListSelected[] = $fileList[$i];
	    }

		$responseArray = array("fileList" => $fileListSelected, "totalAvailable" => count_like_php5($fileList));
	}

	// Cache for 60 secs
	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
				"expireInSeconds" => 60
		])
	);
});

// Get file link
$app->get('/fileDownload/a/:apikey/e/:epoch/u/:sessionToken/date/:date', 'apiAuthForPartnerAPI',
	function ($apikey, $epoch, $sessionToken, $date) {

	logPartnerActionViaQueue('file_download', ["date" => $date]);

	// Find Partner account
	list($partnerName, $partnerLogoURL, $partnerFileStartDate) = partnerInfo($GLOBALS['partner']['primaryGroup']);

	if(!validateFileSearchDate($date) ||
		intval($date) < $partnerFileStartDate) {

		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Invalid Request");
	}
	else {

	    $s3_client = getS3ClientObject();

	    $fileTimestamp = strtotime($date);
	   	list($folderPath, $fileName) = getS3KeyPath_PartnerExtractFile($fileTimestamp, $GLOBALS['partner']['primaryGroup']);

		// If file doesn't exist skip
		if(!S3FileExsists($s3_client, $GLOBALS['env_S3BucketNameExtPartner'], $folderPath, $fileName)) {

			$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Invalid Request");
		}
		else {

			$getTempExtractURL = S3GetPrivateFile(getS3ClientObject(), $GLOBALS['env_S3BucketNameExtPartner'], $folderPath . '/' . $fileName, 1);
			$responseArray = array("status" => true, "fileName" => $fileName, "fileDownloadURL" => $getTempExtractURL);
		}
	}

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
		])
	);
});

// Login
// $app->get('/login/a/:apikey/e/:epoch/u/:sessionToken/username/:username/password/:passwordEncrypted', 'apiAuthWithoutSessionForPartnerAPI',
// 	function ($apikey, $epoch, $sessionToken, $username, $passwordEncrypted) {
$app->post('/login/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthWithoutSessionForPartnerAPI',
	function ($apikey, $epoch, $sessionToken) use ($app) {

	// Fetch Post variables
	$postVars = array();

	$postVars['username'] = $username = urldecode($app->request()->post('username'));
	$postVars['passwordEncrypted'] = $passwordEncrypted = urldecode($app->request()->post('password'));

	if(empty($username)
		|| empty($passwordEncrypted)) {

		json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars), 1, 1);
		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Incorrect Credentials.");
	}


	list($status, $sessionToken, $error_user, $error_log) = partnerLogin($username, $passwordEncrypted);

	if($status == false) {

		json_error("AS_1092", "", $error_log, 3);
		$responseArray = array("json_resp_status" => 0, "json_resp_message" => $error_user . $error_log);
	}
	else {

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "", "json_resp_token" => $sessionToken);
	}

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
		])
	);
});

// Logout
$app->get('/logout/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForPartnerAPI',
	function ($apikey, $epoch, $sessionToken) {

	list($status, $error_user, $error_log) = partnerLogout($GLOBALS['partner']['accessToken'], $sessionToken);

	if($status == false) {

		json_error("AS_1094", "", $error_log, 3);
	}

	$responseArray = array("json_resp_status" => 1, "json_resp_message" => "");

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
		])
	);
});


// Partner Info
$app->get('/info/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForPartnerAPI',
	function ($apikey, $epoch, $sessionToken) {

	list($partnerName, $partnerLogoURL, $partnerFileStartDate) = partnerInfo($GLOBALS['partner']['primaryGroup']);

	$responseArray = array("partnerName" => $partnerName, "partnerLogoURL" => $partnerLogoURL, "partnerFileStartDate" => $partnerFileStartDate, "userFullName" => $GLOBALS['partner']['formattedAttributes']['name']);

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray)
		])
	);
});


// JMD
// Change password
// $app->post('/changePassword/a/:apikey/e/:epoch/u/:sessionToken/oldPassword/:oldPasswordEncrypted/newPassword/:newPasswordEncrypted', 'apiAuthForPartnerAPI',
// 	function ($apikey, $epoch, $sessionToken, $oldPasswordEncrypted, $newPasswordEncrypted) {

$app->post('/changePassword/a/:apikey/e/:epoch/u/:sessionToken', 'apiAuthForPartnerAPI',
	function ($apikey, $epoch, $sessionToken) use ($app) {

	// Fetch Post variables
	$postVars = array();

	$postVars['oldPasswordEncrypted'] = $oldPasswordEncrypted = urldecode($app->request()->post('oldPassword'));
	$postVars['newPasswordEncrypted'] = $newPasswordEncrypted = urldecode($app->request()->post('newPassword'));

	if(empty($oldPasswordEncrypted)
		|| empty($newPasswordEncrypted)) {

		json_error("AS_005", "", "Incorrect API Call. PostVars = " . var_dump_ob($postVars), 1, 1);
		$responseArray = array("json_resp_status" => 0, "json_resp_message" => "Incorrect Credentials.");
	}

	list($status, $error_user, $error_log) = partnerChangePassword($GLOBALS['partner']['accessToken'], $oldPasswordEncrypted, $newPasswordEncrypted);

	if($status == false) {

		json_error("AS_1095", "", $error_log, 3, 1);
		$responseArray = array("json_resp_status" => 0, "json_resp_message" => $error_user);

		try {

			$logRow =	'change_password_failed' . "," .
						$GLOBALS['partner']['usernameById'] . "," .
						$GLOBALS['partner']['primaryGroup'] . "," .
						time() . "," .
						getPartnerAccessRemoteAddr() . "," .
						implode("||", ["error" => $error_user]) . "\r\n";

			$s3_client = getS3ClientObject(true);
			$s3_client->registerStreamWrapper();

			$stream = fopen('s3://' . getS3KeyPath_Logs() . '/' . getPartnerLogFilename($GLOBALS['partner']['primaryGroup']), 'a');
			fwrite($stream, $logRow);
			fclose($stream);
		}
		catch (Exception $ex) {

		    $response = json_decode($ex->getMessage(), true);
		    json_error($response["error_code"], "", "Log partner action S3 loge failed " . $response["error_message_log"], 1, 1);
		    // JMD
		}
	}
	else {

		$responseArray = array("json_resp_status" => 1, "json_resp_message" => "");
	}

	json_echo(
		setRouteCache([
				"jsonEncodedString" => json_encode($responseArray),
		])
	);
});

$app->notFound(function () {
	
	json_error("AS_005", "", "Incorrect API Call.");
});

$app->run();

?>