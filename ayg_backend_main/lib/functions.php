<?php

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Httpful\Request;
use Jsor\HalClient\Client;

function convertToInt(&$string) {

    $string = intval($string);
}

function isSSL() {

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

function getConfigValue($configName) {

    if(!isset($GLOBALS['__CONFIG'][$configName])) {

        // Cachable query
        $GLOBALS['cacheParseQuery'] = true;
        $objParseQueryConfig = parseExecuteQuery(array("isActive" => true, "configName" => $configName), "Config", "", "", [], 1);

        if(count_like_php5($objParseQueryConfig) > 0) {

            $GLOBALS['__CONFIG'][$configName] = $objParseQueryConfig->get('configValue');
        }
        else {

            return null;
        }
    }

    return $GLOBALS['__CONFIG'][$configName];
}

function dollar_format_no_cents($cents) {

    if(intval($cents) > 0) {

        $centsRounded = floor(intval($cents) / 100);
    }
    else {

        $centsRounded = intval($cents);
    }

    return ('$' . strval($centsRounded));
}

function dollar_format($cents) {

    setlocale(LC_MONETARY, 'en_US.UTF-8');
    return money_format_custom('%.2n', $cents / 100);
}

function dollar_format_float($cents) {

    return (floatval(str_replace(',', '', str_replace('$', '', dollar_format($cents)))) * 100);
}

function dollar_format_float_with_decimals($cents) {

    return (number_format(str_replace(',', '', str_replace('$', '', dollar_format($cents))), 2));
}

function getTimezoneShort($timezone) {

    if(!isset($GLOBALS['__TIMEZONE_SHORTCODES__'][$timezone])) {

        $dateTime = new DateTime();
        $dateTime->setTimeZone(new DateTimeZone($timezone));
        $GLOBALS['__TIMEZONE_SHORTCODES__'][$timezone] = $dateTime->format('T');
    }

    return $GLOBALS['__TIMEZONE_SHORTCODES__'][$timezone];
}

function convertToUTC($fullTextTime, $originalTimeZone) {

    $date = new DateTime($fullTextTime, new DateTimeZone($originalTimeZone));
    return gmdate("Y-m-d", $date->getTimestamp()) . "T" . gmdate("H:i:s", $date->getTimestamp()) . ".000";
}

function convertToTimestamp($fullTextTime, $originalTimeZone) {

    $currentTimezone = date_default_timezone_get();

    date_default_timezone_set($originalTimeZone);

    $timestamp = strtotime($fullTextTime);

    // Set Default Timezone
    date_default_timezone_set($currentTimezone);

    return $timestamp;

    // $date = new DateTime($fullTextTime, new DateTimeZone($originalTimeZone));
    // return $date->getTimestamp();
}

function convertUTCToTimestamp($fullTextTime) {

    $date = new DateTime($fullTextTime, new DateTimeZone('UTC'));
    return $date->getTimestamp();
}

function convertTimestampToUTC($timestamp) {

    return gmdate("Y-m-d", $timestamp) . "T" . gmdate("H:i:s", $timestamp) . ".000Z";
}

function extractDateFromDateTime($fullTextTime, $originalTimeZone) {

    $date = new DateTime($fullTextTime, new DateTimeZone($originalTimeZone));

    $currentTimeZone = date_default_timezone_get();

    // Set Airport Timezone
    date_default_timezone_set($timezone);

    $date = date("Y-m-d", $date->getTimestamp());

    date_default_timezone_set($currentTimeZone);

    return $date;
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

        throw new Exception (json_error_return_array("AS_031", "", "Flight status call faild; $url - " . curl_error($ch), 1));
    }

    curl_close($ch);

    return $response_string;
}

function preparedMicroTime() {

    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function check_base64_pngimage($base64) {

    try {

        $img = @imagecreatefromstring(base64_decode($base64));
    }
    catch (Exception $ex) {

        return false;
    }

    if (!$img) {

        return false;
    }

    // Create Image and echo it; collect contents to save
    $notImageFlag = 0;
    ob_start();
    if(!imagepng($img)) {

        $notImageFlag = 1;
    }
    $imageTemp = ob_get_contents();
    ob_end_clean();

    if($notImageFlag) {

        return false;
    }

    // Set a temporary global variable so it can be used as placeholder
    global $myvar; $myvar = "";

    $fp = fopen("var://myvar", "w");
    fwrite($fp, $imageTemp);
    fclose($fp);

    $info = getimagesize("var://myvar");
    unset($myvar);
    unset($imageTemp);

    // If the height & width are > 0 and the mime type is of PNG
    if ($info[0] > 0 && $info[1] > 0 && $info['mime'] == 'image/png') {

        return true;
    }

    return false;
}

function microtime_float() {

    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

function time_taken_in_seconds($time_start) {

    $time_end = microtime_float();
    $time = $time_end - $time_start;

    return $time;
}

function testSpecialChars($text) {

    $offset = 0; $found = 0;

    while ($offset >= 0) {
        $code = ordutf8($text, $offset);

        if($code < 32 || $code > 126) {

            $found = 1;
            echo "<b style='font-weight: bold; color: red;'>Character # $offset: " . $code . " (ASCII Code)</b><br />";
        }
    }

    if($found != 1) {

        echo("None found.");
    }
}

function urldecode_and_sanitize($string) {

    return sanitize(urldecode($string));
}

function sanitize_array($array) {

    foreach($array as $key => $value) {

        if(is_array($value)) {

            $value = sanitize_array($value);
        }
        else {

            $array[$key] = sanitize($value);
        }
    }

    return $array;
}

function sanitize($value) {

    // $value = trim(replaceSpecialChars(addslashes(htmlspecialchars(strip_tags($value)))));
    $value = trim(filter_var(stripslashes($value), FILTER_SANITIZE_SPECIAL_CHARS));

    return $value;
}

function removeAllSpecialCharactersManuallyForCart($value) {

    // Only do for item Comments
    if(isItemCommentToBeTreated($GLOBALS['user'])) {

        return replaceSpecialChars($value);
    }

    return $value;
}

function sanitizeWithReference(&$value) {

    $value = trim(filter_var(stripslashes($value), FILTER_SANITIZE_SPECIAL_CHARS));
    // $value = trim(replaceSpecialChars(addslashes(htmlspecialchars(strip_tags($value)))));
}

function sanitizeEmail($value) {

    $value = trim(filter_var(sanitize($value), FILTER_SANITIZE_EMAIL));

    return $value;
}

function sanitizeEncryptedPassword($value) {

    $value = trim(replaceSpecialChars(addslashes(htmlspecialchars(strip_tags($value)))));

    return $value;
}

function replaceSpecialCharsAllowNumsAndLettersOnly($string) {

    return preg_replace('/[^A-Za-z0-9]/', '', $string);
}

function trimFull($string) {

    $string = trim($string, "\xA0");
    $string = trim($string, "\r\n");

    // This just removes 0-31 and 127. This works in ASCII and UTF-8 because both share the same control set range
    $string = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);

    return trim($string);

    //return htmlspecialchars($string, ENT_DISALLOWED);
}

function replaceSpecialChars($string, $replaceWith=" ", $allowExtendedAscii=false) {

    // If not string type
    if(strcasecmp(gettype($string), 'string')!=0) {

        return $string;
    }

    // $normalizeChars = array(
    //     'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
    //     'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
    //     'Ï'=>'I', 'Ñ'=>'N', 'Ń'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
    //     'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
    //     'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'è', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
    //     'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ń'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
    //     'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f',
    //     'ă'=>'a', 'î'=>'i', 'â'=>'a', 'ș'=>'s', 'ț'=>'t', 'Ă'=>'A', 'Î'=>'I', 'Â'=>'A', 'Ș'=>'S', 'Ț'=>'T',
    // );

    // //Output: E A I A I A I A I C O E O E O E O O e U e U i U i U o Y o a u a y c
    // if($fixAccented == true) {

    // 	$string = strtr($string, $normalizeChars);
    // }
    $strlength = strlen($string);
    $retString = "";

    for($i = 0; $i < $strlength; $i++) {

        $code = ord($string[$i]);
        // http://www.asciitable.com/
        // JMD
        if($code < 32
            || $code == 127) {

            $retString .= $replaceWith;
        }
        else if($allowExtendedAscii == false
            && $code > 127) {

            // JMD
            $retString .= $replaceWith;
        }
        else if($allowExtendedAscii == true
            && $code > 165) {

            $retString .= $replaceWith;
        }

        // JMD
        else if($allowExtendedAscii == true
            && $code > 127
            && $code <= 165) {

            $retString .= $string[$i];
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

function cleanURL($url) {

    $localHostFound = strpos($url, '127.0.0.1');
    if($localHostFound === false) {

        // Don't replace for localhost URLs
        $url = preg_replace('/http\:\/\//', 'https://', $url, 1);
    }

    return $url;
}

function getJSONAirportWeatherForecast($parameters) {

    global $env_OpenWeatherMapAPIURL, $env_OpenWeatherMapAPIKey;

    $parameterString = '?appid=' . $env_OpenWeatherMapAPIKey;
    foreach($parameters as $key => $value) {

        $parameterString .= "&" . $key . "=" . $value;
    }

    $response = \Httpful\Request::get($env_OpenWeatherMapAPIURL . $parameterString)->expectsJson()->send();

    return $response->body->list;
}

function authyPhoneVerificationAPI($env_AuthyPhoneAPIURL, $method, $parameters) {

    $parameterString = '?api_key=' . $GLOBALS['env_AuthyAPIKey'];
    foreach($parameters as $key => $value) {

        $parameterString .= "&" . urlencode($key) . "=" . urlencode($value);
    }

    if($method == "get") {

        $response = \Httpful\Request::get($env_AuthyPhoneAPIURL . $parameterString)->expectsJson()->send();
    }
    else {

        $response = \Httpful\Request::post($env_AuthyPhoneAPIURL . $parameterString)->expectsJson()->send();
    }

    return $response->body;
}

function k_to_f($temp) {

    if (!is_numeric($temp)) {

        return false;
    }

    return round((($temp - 273.15) * 1.8) + 32);
}

function expiryTimestamp() {

    // 10 minutes
    return (time() + 10*60);
}

function encryptStringInMotion($string) {
    if (!isset($GLOBALS['currentlyOperatedDevice']) || empty($GLOBALS['currentlyOperatedDevice'])){
        $deviceCurrentlyUsed = '';
    }else{
        $deviceCurrentlyUsed = $GLOBALS['currentlyOperatedDevice'];
    }
    return encryptString($string, $GLOBALS['env_StringInMotionEncryptionKey'.$deviceCurrentlyUsed]);
}

function decryptStringInMotion($string) {
    if (!isset($GLOBALS['currentlyOperatedDevice']) || empty($GLOBALS['currentlyOperatedDevice'])){
        $deviceCurrentlyUsed = '';
    }else{
        $deviceCurrentlyUsed = $GLOBALS['currentlyOperatedDevice'];
    }
    return decryptString($string, $GLOBALS['env_StringInMotionEncryptionKey'.$deviceCurrentlyUsed]);
}

function decryptString($string, $key) {

    $parts = explode(':', $string);
    // JMD
    // json_error("AS_RAW", "", $string, 3, 1);
    // json_error("AS_NOBASE", "", $parts[0] . ' - ' . $parts[1], 3, 1);
    // json_error("AS_BASE", "", base64_decode($parts[0]) . ' - ' . base64_decode($parts[1]), 3, 1);

    return openssl_decrypt(base64_decode($parts[0]), AES_256_CBC, $key, OPENSSL_RAW_DATA, base64_decode($parts[1]));
}

function encryptString($string, $key) {

    // Generate an initialization vector
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));

    $encrypted = openssl_encrypt($string, AES_256_CBC, $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($encrypted) . ':' . base64_encode($iv);
}

function var_dump_ob($array) {

    ob_start();
    var_dump($array);
    $postVarsString = ob_get_clean();

    return $postVarsString;
}

function convertToBool($string) {

    $boolConversion = ($string === 'true');

    return $boolConversion;
}

function convertBoolToString($bool) {

    return ($bool) ? 'true' : 'false';
}

function convertToBoolFromInt($integer) {

    $boolConversion = (intval($integer) === 1);

    return $boolConversion;
}

function convertToIntFromBool($bool) {

    $intConversion = ($bool == true ? 1 : 0);

    return $intConversion;
}

function is_float_value($f) {

    if(strlen($f) > 16) {

        $f = substr($f, 0, 16);
    }

    ini_set('precision', 16);
    return ($f == (string)floatval($f));
}

function formatDateTimeRelative($timestamp) {

    $date = date('d/m/Y', $timestamp);

    if($date == date('d/m/Y')) {
        return "Today, " . date("g:i A", $timestamp);
    }
    else if($date == date('d/m/Y',time() - (24 * 60 * 60))) {
        return "Yesterday, " . date("g:i A", $timestamp);
    }
    else {
        return date("M j, g:i A", $timestamp);
    }
}

function emailFetchTemplateContent($templateFilePrefix, $templateSubstitutions, $templateSubstitutionsRepeat) {

    // Fetch template text content
    $templateContentText = emailFetchFileContent($templateFilePrefix . '.txt');

    // Fetch template HTML content
    $templateContentHTML = emailFetchFileContent($templateFilePrefix . '.html');

    // Parse content and return to caller
    return array(
        emailSubstituteTemplateTags($templateContentText, $templateSubstitutions, $templateSubstitutionsRepeat),
        emailSubstituteTemplateTags($templateContentHTML, $templateSubstitutions, $templateSubstitutionsRepeat)
    );
}

function emailFetchFileContent($templateFileName) {

    $handle = fopen($GLOBALS['env_HerokuSystemPath'] . $GLOBALS['env_EmailTemplatePath'] . $templateFileName, "r");
    $templateContent = fread($handle, filesize($GLOBALS['env_HerokuSystemPath'] . $GLOBALS['env_EmailTemplatePath'] . $templateFileName));
    fclose($handle);

    return $templateContent;
}

function emailSubstituteRepeatTags($templateContent, $templateSubstitutionsRepeat) {

    // Replace repeat content
    foreach($templateSubstitutionsRepeat as $key => $repeatArray) {

        // Regular expression
        $regex = "/(\[{repeat-" . $key . "}\])(.*?)(\[{\/repeat-" . $key . "}\])/s";
        $regexOpts = "/(\[{repeat-" . "options" . "}\])(.*?)(\[{\/repeat-" . "options" . "}\])/s";

        if(preg_match($regex, $templateContent, $matches) && preg_match($regexOpts, $templateContent, $optMatches)) {

            $replacedSingleRows = '';
            // Replace text for each row
            foreach($repeatArray as $repeatKey => $repeatValueArray) {
                $replacedSingleRows .= emailSubstituteSingleTags($matches[2], $repeatValueArray);

                if(isset($repeatValueArray["options"])){
                    foreach($repeatValueArray["options"] as $optKey => $optValueArray) {
                        $replacedSingleRows .= emailSubstituteSingleTags($optMatches[2], $optValueArray);
                    }
                }
            }

            // Update template content with repeated values inserted
            $templateContent = str_replace($matches[0], $replacedSingleRows, $templateContent);
            $templateContent = str_replace($optMatches[0], "", $templateContent);
        }
    }

    return $templateContent;
}

function emailSubstituteSingleTags($templateContent, $templateSubstitutions) {

    // Replace regular keywords
    foreach($templateSubstitutions as $key => $value) {

        if(is_array($value)) {

            continue;
        }

        $templateContent = str_replace("[{" . $key . "}]", $value, $templateContent);
    }

    return $templateContent;
}

function emailSubstituteTemplateTags($templateContent, $templateSubstitutions, $templateSubstitutionsRepeat) {

    // Replace repeat content
    if(count_like_php5($templateSubstitutionsRepeat) > 0) {

        $templateContent = emailSubstituteRepeatTags($templateContent, $templateSubstitutionsRepeat);
    }

    // Single tags
    $templateContent = emailSubstituteSingleTags($templateContent, $templateSubstitutions);

    return $templateContent;
}

function stripNonAlphaNumericSpaces($string) {

    return preg_replace("/[^a-z0-9]/i", "", $string);
}

function isOnesignalDeviceInvalid($oneSignalId) {

    $parameters['app_id'] = $GLOBALS['env_OneSignalAppId'];

    $response = \Httpful\Request::get($GLOBALS['env_OneSignalAddDeviceURL'] . '/' . $oneSignalId)
        ->sendsJson()
        ->body(json_encode($parameters))
        ->expectsJson()
        ->timeout(5)
        ->sendIt();

    $errorCode = -9999999;
    if(isset($response->code)) {

        $errorCode = intval($response->code);
    }

    // Error code 400
    if(in_array($errorCode, [400])) {

        return [$errorCode, true];
    }

    if(!isset($response->body->success)
        && isset($response->body->error)) {

        if(in_array($errorCode, [400])) {

            return [$errorCode, true];
        }
        else {

            // If pull of information failed
            return [$errorCode, false];
        }
    }

    // If $response->body->invalid_identifier is set to 1, then the id is no longer valid
    if(isset($response->body->invalid_identifier)
        && intval($response->body->invalid_identifier) == 1) {

        return [$errorCode, true];
    }

    // default response
    return [$errorCode, false];
}

function onesignalCreateDevice($parameters) {

    $parameters['app_id'] = $GLOBALS['env_OneSignalAppId'];

    $response = \Httpful\Request::post($GLOBALS['env_OneSignalAddDeviceURL'])
        ->sendsJson()
        ->body(json_encode($parameters))
        ->expectsJson()
        ->timeout(5)
        ->sendIt();

    if(!isset($response->body->success)) {

        if(isset($response->body->errors)
            && count_like_php5($response->body->errors) > 0) {

            return [false, "", json_encode($response->body->errors)];
        }
        else {

            return [false, "", json_encode(["unknown error"])];
        }
    }

    // json_error("AS_WWW", "", "Onesignal attempt - " . json_encode($response), 1, 1);

    return [true, $response->body->id, json_encode(array())];
}

function onesignalUpdateDevice($parameters, $oneSignalId) {

    $parameters['app_id'] = $GLOBALS['env_OneSignalAppId'];

    $response = \Httpful\Request::put($GLOBALS['env_OneSignalAddDeviceURL'] . '/' . $oneSignalId)
        ->sendsJson()
        ->body(json_encode($parameters))
        ->expectsJson()
        ->timeout(5)
        ->sendIt();

    if(!isset($response->body->success)
        && count_like_php5($response->body->errors) > 0) {

        return [false, json_encode($response->body->errors)];
    }

    return [true, json_encode(array())];
}

function html_entity_decode_walk(&$value,$key) {

    $value = html_entity_decode($value);
}

function sendMessageToPOSTablet($messageParamters) {

    json_error("AS_3009", "", "Sending message (" . json_encode($messageParamters) . ") to device id: " . $messageParamters['device_ids'], 3, 1);

    $response = \Httpful\Request::post($GLOBALS['env_MobiLockAPIURLPrefix'] . $GLOBALS['env_MobiLockAPIURLMessageSuffix'])
        ->addHeader('Authorization', 'Token ' . $GLOBALS['env_MobiLock_APIKey'])
        ->body(http_build_query($messageParamters))
        ->expectsJson()
        ->sendIt();

    if(isset($response->code)
        && $response->code == 200) {

        return true;
    }
    else {

        return false;
    }
}

function postTicketToZendesk($messageParamters, $internalTicketId) {

    if($GLOBALS['env_ZendeskCreateTickets'] == false) {

        return "";
    }

    $parameters = ['ticket' =>
        ['subject' => $messageParamters["subject"],
            'comment' => ['body' => $messageParamters["body"]],
            'requester' => ['name' => $messageParamters["customerName"], 'email' => $messageParamters["customerEmail"]],
            'external_id' => $internalTicketId
        ]
    ];

    $response = \Httpful\Request::post('https://atyourgate.zendesk.com/api/v2/tickets.json')
        ->authenticateWith($GLOBALS['env_ZendeskUsername'] . '/token', $GLOBALS['env_ZendeskAPIToken'])
        ->sendsJson()
        ->body(json_encode($parameters))
        ->expectsJson()
        ->sendIt();

    $ticketId = "";
    if($response->code == 201
        && isset($response->body->ticket->id)) {

        $ticketId = $response->body->ticket->id;
    }
    else {

        $error = (isset($response->body->error) ? $response->body->error : "No error provided");
        $error .= (isset($response->body->description) ? $response->body->description : "");
        $code  = (isset($response->body->code) ? $response->body->code : "No Code");;

        json_error("AS_3023", "", "Zendesk Ticket creation failed - " . $code . " - " . $error . " - " . json_encode($parameters));
    }

    return $ticketId;
}

function sendBuzzToPOSTablet($tabletMobilockId, $reasonForBuzz) {

    if($GLOBALS['env_TabletBuzzActive'] == false) {

        json_error("AS_3010", "", "Buzz requested (" . $reasonForBuzz . ") to device id: " . $tabletMobilockId . " but ignored as env_TabletBuzzActive was set to false", 3, 1);

        return true;
    }

    json_error("AS_3008", "", "Sending buzz (" . $reasonForBuzz . ") to device id: " . $tabletMobilockId, 3, 1);

    try {

        $response = \Httpful\Request::post($GLOBALS['env_MobiLockAPIURLPrefix'] . $tabletMobilockId . $GLOBALS['env_MobiLockAPIURLAlaramSuffix'])
            ->addHeader('Authorization', 'Token ' . $GLOBALS['env_MobiLock_APIKey'])
            ->sendsJson()
            ->expectsJson()
            ->sendIt();
    }
    catch (Exception $ex) {

        json_error("AS_1056", "", $ex->getMessage(), 2, 1);

        return false;
    }

    // Buzz failed
    if(isset($response->body->status)
        && strcasecmp($response->body->status, 'success')!=0) {

        return false;
    }

    return true;
}

function unserailize_all_array_items(&$value, $key) {

    $value = unserialize($value);
}

/*
function calctimeused() {

	$time_start = $GLOBALS['lastcheckin'];
	$time_end = microtime(true);

	$GLOBALS['lastcheckin'] = $time_end;
	return ($time_end-$time_start);
}
*/

function isAtAirport($tabletLocationCords, $airportCords, $distance=2) {

    $lon1 = $tabletLocationCords["lng"];
    $lon2 = $airportCords["lng"];

    $lat1 = $tabletLocationCords["lat"];
    $lat2 = $airportCords["lat"];

    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;

    // Away from airport?
    if($miles > $distance) {

        return "N";
    }
    else {

        return "Y";
    }
}

function sendTabletOrderCanceledToSlack($orderSequenceId, $order) {

    ///////////////////////////////////////////////////////////////////////////////////////
    // Prepare for Slack post
    ///////////////////////////////////////////////////////////////////////////////////////
    $slack = createOrderNotificationSlackMessage($order->getObjectId());
    //$slack = new SlackMessage($GLOBALS['env_SlackWH_orderNotifications'], 'env_SlackWH_orderNotifications');
    $slack->setText("Order: " . $orderSequenceId);

    $attachment = $slack->addAttachment();
    $attachment->addField("Order was marked canceled", "Yes", false);

    try {

        $slack->send();
    }
    catch (Exception $ex) {

    }
}

function formatSecondsIntoHumanIntervals($inputSeconds) {

    // If less than a minute
    // if($inputSeconds < 60) {

    // 	return $inputSeconds . " secs";
    // }

    $secondsInAMinute = 60;
    $secondsInAnHour  = 60 * $secondsInAMinute;
    $secondsInADay    = 24 * $secondsInAnHour;
    $secondsInAMonth  = 30 * $secondsInADay;

    // extract months
    $months = floor($inputSeconds / $secondsInAMonth);

    // extract days
    $daySeconds = $inputSeconds % $secondsInAMonth;
    $days = floor($daySeconds / $secondsInADay);

    // extract hours
    $hourSeconds = $inputSeconds % $secondsInADay;
    $hours = floor($hourSeconds / $secondsInAnHour);

    // extract minutes
    $minuteSeconds = $hourSeconds % $secondsInAnHour;
    $minutes = floor($minuteSeconds / $secondsInAMinute);

    // extract the remaining seconds
    $remainingSeconds = $minuteSeconds % $secondsInAMinute;
    $seconds = ceil($remainingSeconds);

    // return the final array
    $obj = array(
        0 => ["value" => (int) $months, "displayPlural" => "months", "displaySingle" => "month"],
        1 => ["value" => (int) $days, "displayPlural" => "days", "displaySingle" => "day"],
        2 => ["value" => (int) $hours, "displayPlural" => "hrs", "displaySingle" => "hr"],
        3 => ["value" => (int) $minutes, "displayPlural" => "mins", "displaySingle" => "min"],
        4 => ["value" => (int) $seconds, "displayPlural" => "secs", "displaySingle" => "sec"],
    );

    $firstValue = false;
    $stringToDisplay = "";
    foreach($obj as $categoryToDisplay) {

        if($categoryToDisplay["value"] == 0
            && $firstValue == false) {

            continue;
        }

        $firstValue = true;
        $stringToDisplay .= $categoryToDisplay["value"] . " " . ($categoryToDisplay["value"] > 1 ? $categoryToDisplay["displayPlural"] : $categoryToDisplay["displaySingle"]) . ", ";
    }

    return substr(trim($stringToDisplay), 0, -1);
}

function printLogTime() {

    return date("M-j-Y G:i:s T", time()) . " (" . time() . "): ";
}

function intval_external($string) {

    return intval(trim($string));
}

function count_like_php5($array) {

    if(is_null($array) || empty($array)) {

        return 0;
    }

    // Parse object
    else if(!is_array($array) && strcasecmp(get_class($array), ParseObject::class)==0) {

        return 1;
    }

    // Any other object
    else if(!is_array($array) && is_object($array)) {

        return 1;
    }

    else if(!is_array($array)) {

        return 0;
    }

    return count($array);
}

function addslashesfordoublequotes($string) {

    // Replace double quotes with two single quotes for CSV file storage
    return str_replace('"', "''", $string);
}

?>
