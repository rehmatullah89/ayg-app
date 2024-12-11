<?php

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use App\Background\Repositories\PingLogMysqlRepository;

function partnerLogSession($client, $accessToken, $refreshToken, $accessTokenExpiryTimeInSecs, $userId, $partnerName) {

	// Find Partner row
	$partner = new ParseQuery("Partner");
	$partner->equalTo("groupName", $partnerName);
	// JMD
	$partnerRow = $partner->first();

	// JMD
	$partnerSessions = new ParseObject("PartnerSessions");
	$partnerSessions->set("accessToken", $accessToken);
	$partnerSessions->set("refreshToken", $refreshToken);
	$partnerSessions->set("accessTokenExpiryTimestamp", getAccessTokenExpiryTimestamp($accessTokenExpiryTimeInSecs));
	$partnerSessions->set("lastAccessedTimestamp", time());
	$partnerSessions->set("userId", $userId);
	$partnerSessions->set("partner", $partnerRow);
	$partnerSessions->set('isActive', true);
	$partnerSessions->set('lastAccessedAddr', getPartnerAccessRemoteAddr());
	$partnerSessions->save();

	try {

		$logRow =	'login' . "," .
					$userId . "," .
					$partnerName . "," .
					time() . "," .
					getPartnerAccessRemoteAddr() . "," .
					implode("||", []) . "\r\n";

		$s3_client = getS3ClientObject(true);
		$s3_client->registerStreamWrapper();

		$stream = fopen('s3://' . getS3KeyPath_Logs() . '/' . getPartnerLogFilename($partnerName), 'a');
		fwrite($stream, $logRow);
		fclose($stream);
	}
	catch (Exception $ex) {

	    $response = json_decode($ex->getMessage(), true);
	    json_error($response["error_code"], "", "Log partner action S3 loge failed " . $response["error_message_log"], 1, 1);
	}

	return $partnerSessions->getObjectId();
}

function partnerGetSession($client, $sessionToken, $page) {

	$partnerSession = parseExecuteQuery(["objectId" => $sessionToken, "isActive" => true], "PartnerSessions", "", "", ["partner"], 1);

	if(count_like_php5($partnerSession) == 0) {

		throw new Exception("Session Token Invalid");
	}

	if(parterSessionEligibleToBeRefreshed($partnerSession->get('accessTokenExpiryTimestamp'), $partnerSession->get('lastAccessedTimestamp'))) {

		// Refresh Token
		$newAccessTokenInfo = $client->refreshAuthentication($partnerSession->get('userId'), $partnerSession->get('refreshToken'));

		if(isset($newAccessTokenInfo["AccessToken"])) {

			$partnerSession->set('accessToken', $newAccessTokenInfo["AccessToken"]);
			$partnerSession->set('accessTokenExpiryTimestamp', getAccessTokenExpiryTimestamp($newAccessTokenInfo["ExpiresIn"]));
			$partnerSession->save();
		}
	}

	// Has token expired
	if($partnerSession->get('accessTokenExpiryTimestamp') < time()) {

		partnerLogoutClearSessions($partnerSession->getObjectId());
		throw new Exception("Session Token Invalid");
	}

	try {

		$logRow =	'access_attempt' . "," .
					$partnerSession->get('userId') . "," .
					$partnerSession->get('partner')->get('groupName') . "," .
					time() . "," .
					getPartnerAccessRemoteAddr() . "," .
					implode("||", ["page" => $page]) . "\r\n";

		$s3_client = getS3ClientObject(true);
		$s3_client->registerStreamWrapper();

		$stream = fopen('s3://' . getS3KeyPath_Logs() . '/' . getPartnerLogFilename($partnerSession->get('partner')->get('groupName')), 'a');
		fwrite($stream, $logRow);
		fclose($stream);
	}
	catch (Exception $ex) {

	    $response = json_decode($ex->getMessage(), true);
	    json_error($response["error_code"], "", "Log partner action S3 loge failed " . $response["error_message_log"], 1, 1);
	}

	return $partnerSession;
}

// JMD
function partnerLogoutClearSessions($sessionToken) {

	$partnerSession = parseExecuteQuery(["objectId" => $sessionToken], "PartnerSessions", "", "", ["partner"], 1);

	$partnerSession->set('accessToken', '');
	$partnerSession->set('refreshToken', '');
	$partnerSession->set('lastAccessedTimestamp', time());
	$partnerSession->set('isActive', false);
	$partnerSession->set('lastAccessedAddr', getPartnerAccessRemoteAddr());
	$partnerSession->save();
}

function partnerLogSuccessSession($sessionToken) {

	$partnerSession = parseExecuteQuery(["objectId" => $sessionToken], "PartnerSessions", "", "", ["partner"], 1);

	$partnerSession->set('lastAccessedTimestamp', time());
	$partnerSession->set('lastAccessedAddr', getPartnerAccessRemoteAddr());
	$partnerSession->save();

	try {

		$logRow =	'access_attempt_success' . "," .
					$partnerSession->get('userId') . "," .
					$partnerSession->get('partner')->get('groupName') . "," .
					time() . "," .
					getPartnerAccessRemoteAddr() . "," .
					implode("||", ["page" => $GLOBALS['partnerPageName']]) . "\r\n";

		$s3_client = getS3ClientObject(true);
		$s3_client->registerStreamWrapper();

		$stream = fopen('s3://' . getS3KeyPath_Logs() . '/' . getPartnerLogFilename($partnerSession->get('partner')->get('groupName')), 'a');
		fwrite($stream, $logRow);
		fclose($stream);
	}
	catch (Exception $ex) {

	    $response = json_decode($ex->getMessage(), true);
	    json_error($response["error_code"], "", "Log partner action S3 loge failed " . $response["error_message_log"], 1, 1);
	    // JMD
	}
}

function partnerLogDisabledAccountAttempt($userId, $partnerName, $page='') {

	try {

		$logRow =	'access_disabled' . "," .
					$userId . "," .
					$partnerName . "," .
					time() . "," .
					getPartnerAccessRemoteAddr() . "," .
					implode("||", ["page" => $GLOBALS['partnerPageName']]) . "\r\n";

		$s3_client = getS3ClientObject(true);
		$s3_client->registerStreamWrapper();

		$stream = fopen('s3://' . getS3KeyPath_Logs() . '/' . getPartnerLogFilename($partnerName), 'a');
		fwrite($stream, $logRow);
		fclose($stream);
	}
	catch (Exception $ex) {

	    $response = json_decode($ex->getMessage(), true);
	    json_error($response["error_code"], "", "Log partner action S3 loge failed " . $response["error_message_log"], 1, 1);
	    // JMD
	}
}

function getAccessTokenExpiryTimestamp($accessTokenExpiryTimeInSecs) {

	return (time()+$accessTokenExpiryTimeInSecs-30);
}

function parterSessionEligibleToBeRefreshed($expiryTimestamp, $lastAccessedTimestamp) {

	// If token expired
	// And was accessed in last 60 mins
	if($expiryTimestamp < time()
		&& $lastAccessedTimestamp > time()-60*60) {

		return true;
	}

	return false;
}

// JMD
function getPartnerAccessRemoteAddr() {

	return getenv('HTTP_X_FORWARDED_FOR') . '-' . getenv('REMOTE_ADDR');
}

function getPartnerLogoURL($parterLogoFileName) {

	return preparePublicS3URL($parterLogoFileName, getS3KeyPath_ImagesPartnerLogo(), $GLOBALS['env_S3Endpoint']);
}

function generatePartnerPageName(&$route) {

	// Route's parent name, e.g. retailer, user, i.e folder name
	// Find the position of apikey parameter, capture everything before it and replace slashes with underscores
	$pos = strpos($_SERVER['SCRIPT_NAME'], '/', 1);
	$parentRouteName = substr($_SERVER['SCRIPT_NAME'], 1, $pos-1);

	// Route's call name, e.g. trending, info, i.e method name
	$pos = strpos($route->getPattern(), '/a/', 1);
	$callRouteName = str_replace('/', '_', substr($route->getPattern(), 1, $pos-1));

	return $callRouteName;
}

function partnerLogin($username, $passwordEncrypted) {

	$primaryGroup = 'unknown';
	$page = $GLOBALS['partnerPageName'];
	// JMD
	try {

		$password = decryptStringInMotion($passwordEncrypted);
		$cognito_client = getCognitoClientObject();
	    $authenticationResponse = $cognito_client->authenticate($username, $password);
	}
	catch (CognitoIdentityProviderException $e) {

		if(strcasecmp($e->getAwsErrorMessage(), 'User is disabled')==0) {

	  	    partnerLogDisabledAccountAttempt($username, 'unknown', $page);
			return [false, "", "Your account is disabled. Please contact administrator.", $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage()];
		}

		return [false, "", "Incorrect Credentials entered.", $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage()];
	}
	catch (Exception $e) {

		return [false, "", "Incorrect Credentials entered.", $e->getMessage()];
	}

	$accessToken = $authenticationResponse["AccessToken"];
	$refreshToken = $authenticationResponse["RefreshToken"];
	$accessTokenExpiryTimeInSecs = $authenticationResponse["ExpiresIn"];
	$usernameById = $cognito_client->getUserViaToken($accessToken);
	$primaryGroup = $cognito_client->getPrimaryGroupForUsername($usernameById);

	$formattedAttributes = $cognito_client->getUser($usernameById);
	if($formattedAttributes["internal"]["Enabled"] == false) {

		partnerLogDisabledAccountAttempt($usernameById, $primaryGroup, $page);
		return [false, "", "Your account is disabled. Please contact administrator.", $e->getMessage()];
	}

	$sessionToken = partnerLogSession($cognito_client, $accessToken, $refreshToken, $accessTokenExpiryTimeInSecs, $usernameById, $primaryGroup);

	return [true, $sessionToken, "", ""];
}

function partnerLogout($accessToken, $sessionToken) {

	try {

		$cognito_client = getCognitoClientObject();

		$cognito_client->logout($accessToken);
		partnerLogoutClearSessions($sessionToken);
	}
	catch (CognitoIdentityProviderException $e) {

		return [false, "", "Logout failed - " . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage()];
	}
	catch (Exception $e) {

		return [false, "", "Logout failed - " . $e->getMessage()];
	}

	try {

		$logRow =	'logout' . "," .
					$GLOBALS['partner']['usernameById'] . "," .
					$GLOBALS['partner']['primaryGroup'] . "," .
					time() . "," .
					getPartnerAccessRemoteAddr() . "," .
					implode("||", []) . "\r\n";

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

	// JMD
	return [true, "", ""];
}

function partnerChangePassword($accessToken, $oldPasswordEncrypted, $newPasswordEncrypted) {

	try {

		$oldPassword = decryptStringInMotion($oldPasswordEncrypted);
		$newPassword = decryptStringInMotion($newPasswordEncrypted);

		$cognito_client = getCognitoClientObject();
		$cognito_client->changePassword($accessToken, $oldPassword, $newPassword);
	}
	catch (Exception $e) {

		if($e->getAwsErrorCode() == 'LimitExceededException') {

			return [false, "Change Password Failed - Limit exceeded", "Change Password Failed - " . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage()];
		}
		else if($e->getAwsErrorCode() == 'PasswordPolicyViolationException') {
			
			return [false, "Change Password Failed - Password Policy not met", "Change Password Failed - " . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage()];
		}
		else {

			return [false, "Change Password Failed - Incorrect credentials", "Change Password Failed - " . $e->getAwsErrorCode() . ' - ' . $e->getAwsErrorMessage()];
		}
	}

	try {

		$logRow =	'change_password' . "," .
					$GLOBALS['partner']['usernameById'] . "," .
					$GLOBALS['partner']['primaryGroup'] . "," .
					time() . "," .
					getPartnerAccessRemoteAddr() . "," .
					implode("||", []) . "\r\n";

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

	// JMD
	return [true, "", ""];
}

function logPartnerActionViaQueue($action, $data) {

	try {

	    $data['page'] = $GLOBALS['partnerPageName'];

		$logRow =	$action . "," .
					$GLOBALS['partner']['usernameById'] . "," .
					$GLOBALS['partner']['primaryGroup'] . "," .
					time() . "," .
					getPartnerAccessRemoteAddr() . "," .
					implode("||", $data) . "\r\n";

		$s3_client = getS3ClientObject(true);
		$s3_client->registerStreamWrapper();

		$stream = fopen('s3://' . getS3KeyPath_Logs() . '/' . getPartnerLogFilename($GLOBALS['partner']['primaryGroup']), 'a');
		fwrite($stream, $logRow);
		fclose($stream);
	}
	catch (Exception $ex) {

	    $response = json_decode($ex->getMessage(), true);
	    json_error($response["error_code"], "", "Log partner action S3 loge failed " . $response["error_message_log"], 1, 1);
	}
}

function partnerInfo($groupName) {

	$partner = parseExecuteQuery(["groupName" => $groupName], "Partner", "", "", [], 1);

	if(count_like_php5($partner) == 0) {

		return["", "", ""];
	}

	return [$partner->get("displayName"), getPartnerLogoURL($partner->get('logoImageName')), $partner->get('startDateOfExtracts')];
}

function validateFileSearchDate($date) {

	$year = intval(substr($date, 0, 4));
	$month = intval(substr($date, 4, 2));
	$day = intval(substr($date, 6, 2));

	if(!checkdate($month, $day, $year)
		|| strtotime($year . "-" . $month . "-" . $day) > strtotime("Yesterday")) {

		return false;
	}

	return true;
}

function getPartnerLogFilename($partner) {

	return date("Y-m-d", time()) . '-' . $partner;
}