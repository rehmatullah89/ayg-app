<?php

// Local use only
// Check local environment variables

	require_once($dirpath . 'local/putenv.php');
	$_SERVER['HTTP_X_FORWARDED_FOR'] = '';

// Random Salt for token generation
$env_RandomTokenString = getenv('env_RandomTokenString');

// Base URL to API
$env_BaseURL = getenv('env_BaseURL');

// Ops API key
$env_OpsRestAPIKeySalt = getenv('env_OpsRestAPIKeySalt');

// Environment name
$env_EnvironmentDisplayCode = getenv('env_EnvironmentDisplayCode');

// Slack
$env_SlackClientId = getenv('env_SlackClientId');
$env_SlackClientSecret = getenv('env_SlackClientSecret');
$env_SlackAccessToken = getenv('env_SlackAccessToken');

// Mobilock
$env_MobiLock_APIKey = getenv('env_MobiLock_APIKey');

function isSSL() {

	// Allow locally
	if(strcasecmp(getenv('env_InHerokuRun'), "Y")!=0) {

		return true;
	}

	// Standard PHP
	if(isset($_SERVER['HTTPS']) &&
	    $_SERVER['HTTPS'] == "on") {

	    return true;
	}

	// Used by Heroku
	else if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		$_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
	        
		return true;
	}

	// Not HTTPS
	return false;
}

function checkToken($token, $tokenEpoch, $metadataCall = false) {


	if(generateToken($tokenEpoch) != $token
		|| (time()-$tokenEpoch) > 60*60
		|| hasTokenBeenUsed($token, $metadataCall)) {

		return false;
	}

	$response = addTokenToUsedList($token);

	if(empty($response)) {
		
		// Something didn't go right
		// echo($response);exit;
		
		return false;
	}
	
	return true;
}

function generateToken($tokenEpoch) {

	// If not on SSL, return email token
	if(!isSSL()) {
		return "";
	}

	return md5($_SERVER['HTTP_X_FORWARDED_FOR'] . $tokenEpoch . $GLOBALS['env_RandomTokenString']);
}

function addTokenToUsedList($token) {
	
	ini_set('precision', 14); 
	$epoch = microtime(true)*1000;



	$response = getpage($GLOBALS['env_BaseURL'] . '/token/add'
			. '/a/' . generateAPIToken($epoch)
			. '/e/' . $epoch
			. '/u/' . '0'
			. '/token/' . urlencode($token)
	);

	return $response;
}

function hasTokenBeenUsed($token, $metadataCall = false) {
	
	ini_set('precision', 14); 
	$epoch = microtime(true)*1000;
	$response = getpage($GLOBALS['env_BaseURL'] . '/token/check'
			. '/a/' . generateAPIToken($epoch)
			. '/e/' . $epoch
			. '/u/' . '0'
			. '/token/' . urlencode($token)
	);


	$responseArray = json_decode($response, true);

	// Something went wrong in the API call
	if(!isset($responseArray["count"])) {
		
		return false;
	}

	if($metadataCall == true) {

		return ($responseArray["count"] >= 30 ? true : false);
	}	

	else if($metadataCall == false) {

		return ($responseArray["count"] == 1 ? true : false);
	}
}

function sanitize($value) {
	
	$value = replaceSpecialChars(addslashes(htmlspecialchars(strip_tags($value))));
	
	return $value;
}

function sanitizeEmail($value) {
	
	$value = filter_var($value, FILTER_SANITIZE_EMAIL);

	return $value;
}

function replaceSpecialChars($string){ 

    $strlength = strlen($string); 
    $retString = ""; 

    for($i = 0; $i < $strlength; $i++){ 
        
		$code = ord($string[$i]);

		if($code < 32 || $code > 126) {
           
 		    // echo("'" . $string[i]. "'<br />");
			$retString .= " ";
        } 
		else {
			
			$retString .= $string[$i]; 
		}
    }	
    return $retString;
}

function ordutf8($string, &$offset) {
	
    $code = ord(substr($string, $offset,1)); 
    if ($code >= 128) {        //otherwise 0xxxxxxx

		if ($code < 224) $bytesnumber = 2;                //110xxxxx
        else if ($code < 240) $bytesnumber = 3;        //1110xxxx
        else if ($code < 248) $bytesnumber = 4;    //11110xxx
		
        $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
		
        for ($i = 2; $i <= $bytesnumber; $i++) {
  
            $offset ++;
            $code2 = ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
            $codetemp = $codetemp*64 + $code2;
        }
        $code = $codetemp;
    }

    $offset += 1;

    if ($offset >= strlen($string)) $offset = -1;

    return $code;
}

function generateAPIToken($epoch) {
	
	global $env_OpsRestAPIKeySalt;
	
	return md5($epoch . $env_OpsRestAPIKeySalt);
}

function getpage($url, $display_error=false) {

	$ch=curl_init();
	$timeout=100;

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_TIMEOUT, 400);

	$response_string=curl_exec($ch);

	if(curl_errno($ch) && $display_error == true) {
		
		return 'error:' . curl_error($ch);
	}
	
	curl_close($ch);

	return $response_string;
}

function array_sort($array, $on, $order=SORT_ASC) {
	
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {

        foreach ($array as $k => $v) {

            if (is_array($v)) {

                foreach ($v as $k2 => $v2) {

                    if ($k2 == $on) {

                        $sortable_array[$k] = $v2;
                    }
                }
            }
            else {

                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {

            case SORT_ASC:
                asort($sortable_array);
            break;

            case SORT_DESC:
                arsort($sortable_array);
            break;
        }

        foreach ($sortable_array as $k => $v) {

            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function generateEpoch($lastEpoch) {
	
	if(empty($lastEpoch)) {
		
		return microtime(true)*1000;
	}
	
	return ($lastEpoch+1);
}

function empty_zero_allowed($string) {

	// a value of 0 is not seen as empty

	if(empty($string) && 
		((is_string($string) && $string != "0")
		 || (is_int($string) && $string != 0)))
		 {

		return true;
	}

	return false;
}

?>
