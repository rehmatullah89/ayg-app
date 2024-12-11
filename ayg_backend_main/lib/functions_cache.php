<?php

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;

function getNewFlightDuplicateCounter($flightId)
{

    // Ensure no duplicates are added
    $cacheKey = '__FLIGHTINFO__DUPINSERTCNT_' . $flightId;

    $counter = $GLOBALS['redis']->incr($cacheKey);

    setCacheExpire($cacheKey, 5 * 60);

    return $counter;
}

function addToCountOfAttempt($id, $uniqueId, $maxAttemptsAllowed, $timeInMinutesBwMaxFailedAttempts)
{

    $cacheKey = "__ATTEMPT_" . $id . "__" . md5($uniqueId);

    // If Cache doesn't exist, create one
    if (!doesCacheExist($cacheKey)) {

        // Set cache with expiry for X mins
        setCache($cacheKey, 0, 0, $timeInMinutesBwMaxFailedAttempts * 60);
    }
    // Else just update expiry time
    // As in the more attempts you make, the longer the wait period between batch of attempts you will have
    // Therefore once max attempts reached, then you should just wait before attempting again
    else {

        // Set cache expiry for X mins
        setCacheExpire($cacheKey, $timeInMinutesBwMaxFailedAttempts * 60);
    }

    $counter = $GLOBALS['redis']->incr($cacheKey);

    // Number of attempts is less than max attempts allowed
    // Return true
    if ($counter < $maxAttemptsAllowed) {

        return true;
    }

    // else max attempts reached, hence disallow
    return false;
}

function clearCountOfAttempt($id, $uniqueId)
{

    $cacheKey = "__ATTEMPT_" . $id . "__" . md5($uniqueId);

    delCacheByKey($cacheKey);
}

function setSlackFailureLogCache($orderId, $payloadDump)
{

    if (empty($orderId)) {

        $orderId = microtime(true) . '_' . rand(0, 100000000);
    }

    $cacheKey = "__SLACKFAILURE__" . $orderId;

    setCache($cacheKey, $payloadDump, 1, 7 * 24 * 60 * 60);

    return $orderId;
}

function setUnknownPaymentFailureLogCache($payloadDump)
{

    $uniqueId = microtime(true) . '_' . rand(0, 100000000);

    $cacheKey = "__PAYMENTFAIL__" . $uniqueId;

    setCache($cacheKey, $payloadDump, 1, 7 * 24 * 60 * 60);

    return $uniqueId;
}

function setDirectionsCache(
    $directionsResponse,
    $airportIataCode,
    $fromLocationId,
    $toRetailerLocationId,
    $referenceRetailerId,
    $expireInSeconds = 0
) {

    // If referenceRetailerId was set 0, then blank it out for key creation
    if (strcasecmp(strval($referenceRetailerId), "0") == 0) {

        $referenceRetailerId = "";
    }

    $cacheKey = "__DIRECTIONS_" . $airportIataCode . '-' . $fromLocationId . '-' . $toRetailerLocationId . '-' . $referenceRetailerId;

    setCache($cacheKey, $directionsResponse, 1, $expireInSeconds);
}

function getDirectionsSummarizedCache($airportIataCode, $fromLocationId, $toLocationId)
{

    // One cache key per airport
    // $keyPrefix = "__DIRECTIONS__";
    $keyPrefix = "__HASH__DIRECTIONS__";

    // Set global var first
    if (!isset($GLOBALS[$keyPrefix][$airportIataCode])) {

        $cacheKey = $keyPrefix . $airportIataCode;
        // $GLOBALS[$keyPrefix][$airportIataCode] = getCache($cacheKey, 1);
    }

    // Get index inside the cache name
    $cacheIndex = $fromLocationId . '-' . $toLocationId;

    $array = hGetCache($cacheKey, $cacheIndex, 1);

    // Cache not found for pair location
    // if(!isset($GLOBALS[$keyPrefix][$airportIataCode][$cacheIndex])) {
    if (!is_array($array)) {

        return "";
    } else {

        // return $GLOBALS[$keyPrefix][$airportIataCode][$cacheIndex]["totalDistanceMetricsForTrip"];
        return $array;
    }
}

function getDirectionsCache($airportIataCode, $fromLocationId, $toRetailerLocationId, $referenceRetailerId)
{

    // If referenceRetailerId was set 0, then blank it out for key creation
    if (strcasecmp(strval($referenceRetailerId), "0") == 0) {

        $referenceRetailerId = "";
    }

    $cacheKey = "__DIRECTIONS_" . $airportIataCode . '-' . $fromLocationId . '-' . $toRetailerLocationId . '-' . $referenceRetailerId;

    return getCache($cacheKey, 1);
}

function getDistanceMetricsCache(
    $toTerminal,
    $toConcourse,
    $toGate,
    $fromTerminal,
    $fromConcourse,
    $fromGate,
    $returnTotaled,
    $airportIataCode
) {

    // One cache key per airport
    // $keyPrefix = "__DISTANCEMETRICS__";

    $keyPrefix = "__HASH__DISTANCEMETRICS__";

    // Set global var first
    if (!isset($GLOBALS[$keyPrefix][$airportIataCode])) {

        $cacheKey = $keyPrefix . $airportIataCode;
        // $GLOBALS[$keyPrefix][$airportIataCode] = getCache($cacheKey, 1);
    }

    // Get index inside the cache name
    $cacheIndex = getDistanceMetricsCacheIndexName($toTerminal, $toConcourse, $toGate, $fromTerminal, $fromConcourse,
        $fromGate, $returnTotaled);

    $array = hGetCache($cacheKey, $cacheIndex, 1);

    // if(!isset($GLOBALS[$keyPrefix][$airportIataCode][$cacheIndex])) {
    if (!is_array($array)) {

        return "";
    } else {

        // return $GLOBALS[$keyPrefix][$airportIataCode][$cacheIndex];
        return $array;
    }
}

function getDistanceMetricsCacheIndexName(
    $toTerminal,
    $toConcourse,
    $toGate,
    $fromTerminal,
    $fromConcourse,
    $fromGate,
    $returnTotaled
) {

    return ($toTerminal . "|" . $toConcourse . "|" . $toGate . "|" . $fromTerminal . "|" . $fromConcourse . "|" . $fromGate . "|" . ($returnTotaled == true ? 1 : 0));
}

function setFlightNotifyTrackerCache($flightId, $flightInfo, $expireInSeconds = "EOD")
{

    $cacheKey = "__FLIGHTNOTIFYTRACKER_" . $flightId;

    setCache($cacheKey, $flightInfo, 1, $expireInSeconds);
}

function getFlightNotifyTrackerCache($flightId)
{

    $cacheKey = "__FLIGHTNOTIFYTRACKER_" . $flightId;

    return getCache($cacheKey, 1);
}

function setRetailerClosedEarly($uniqueId, $closeLevel, $closeForSecs)
{

    $cacheKey = "__RETAILERCLOSEDEARLY_" . $uniqueId;

    setCache($cacheKey, [time(), $closeLevel], 1, $closeForSecs);

    // Log event
    logRetailerEarlyCloseEvent($uniqueId, $closeLevel, 'Early Close');
}

function setRetailerCloseEarlyForNewOrders($uniqueId, $closeLevel)
{

    $cacheKey = "__RETAILERTEMPCLOSED_" . $uniqueId;

    setCache($cacheKey, [time(), $closeLevel], 1, $GLOBALS['env_RetailerEarlyCloseMinWaitInSecs'] * 2);

    // Log event
    logRetailerEarlyCloseEvent($uniqueId, $closeLevel, 'Early Close Acknowledged');
}

function getRetailerCloseEarlyForNewOrders($uniqueId)
{

    $cacheKey = "__RETAILERTEMPCLOSED_" . $uniqueId;

    return getCache($cacheKey, 1);
}

function delRetailerCloseEarlyForNewOrders($uniqueId)
{

    $cacheKey = "__RETAILERTEMPCLOSED_" . $uniqueId;

    delCacheByKey($cacheKey);
}

// JMD
function setRetailerOpenAfterClosedEarly($uniqueId, $openLevel)
{

    // Remove cache for no new orders (if it exists)
    delRetailerCloseEarlyForNewOrders($uniqueId);

    // Remove cache for closed early for the day
    $cacheKey = "__RETAILERCLOSEDEARLY_" . $uniqueId;

    delCacheByKey($cacheKey);

    // Log event
    logRetailerEarlyCloseEvent($uniqueId, $openLevel, 'Reopen after Early Close');
}

function getRetailerOpenAfterClosedEarly($uniqueId)
{

    $cacheKey = "__RETAILERCLOSEDEARLY_" . $uniqueId;

    return getCache($cacheKey, 1);
}

// How long is the retailer closed until
function getRetailerClosedEarlyUntil($uniqueId)
{

    $cacheKey = "__RETAILERCLOSEDEARLY_" . $uniqueId;

    return ($GLOBALS['redis']->ttl($cacheKey));
}

function setFlightOffScheduleMarker($flightId, $expireInSeconds = "EOD")
{

    $cacheKey = "__FLIGHTOFFSCHEDULE_" . $flightId;

    setCache($cacheKey, time(), 0, $expireInSeconds);
}

function getFlightOffScheduleMarker($flightId)
{

    $cacheKey = "__FLIGHTOFFSCHEDULE_" . $flightId;

    return getCache($cacheKey);
}

function delFlightOffScheduleMarker($flightId)
{

    $cacheKey = "__FLIGHTOFFSCHEDULE_" . $flightId;

    delCacheByKey($cacheKey);
}

function getFlightOffScheduleMarkerDuplicateCounter($flightId)
{

    $cacheKey = "__FLIGHTOFFSCHEDULECOUNTER_" . $flightId;

    $counter = $GLOBALS['redis']->incr($cacheKey);

    setCacheExpire($cacheKey, 5 * 60);

    return $counter;
}

function setFlightInfoCache($flightId, $flightInfo, $expireInSeconds = "EOD")
{

    $cacheKey = "__FLIGHTINFO_" . $flightId;

    setCache($cacheKey, $flightInfo, 1, $expireInSeconds);
}

function getFlightInfoCache($flightId)
{

    $cacheKey = "__FLIGHTINFO_" . $flightId;

    return getCache($cacheKey, 1);
}

function setRetailerInfoCache($retailerUniqueId, $retailerInfo)
{

    $cacheKey = "__RETAILERINFO_" . $retailerUniqueId;

    setCache($cacheKey, $retailerInfo, 1, $GLOBALS['parseClassAttributes']['Retailers']['ttl']);
}

function getRetailerInfoCache($retailerUniqueId)
{

    if (isset($GLOBALS["__RETAILERINFO_"][$retailerUniqueId])) {

        return $GLOBALS["__RETAILERINFO_"][$retailerUniqueId];
    }

    $cacheKey = "__RETAILERINFO_" . $retailerUniqueId;

    $GLOBALS["__RETAILERINFO_"][$retailerUniqueId] = getCache($cacheKey, 1);

    return $GLOBALS["__RETAILERINFO_"][$retailerUniqueId];
}

function getTerminalGateMapCache($airportIataCode, $byType)
{

    $cacheKey = "__TERMINALGATEMAP_" . $byType . "__" . $airportIataCode;

    // JMD
    return getCache($cacheKey, 1);
}

function setTerminalGateMapCache($airportIataCode, $terminalGateMapArray)
{

    foreach ($terminalGateMapArray as $byType => $valueArray) {

        $cacheKey = "__TERMINALGATEMAP_" . $byType . "__" . $airportIataCode;

        setCache($cacheKey, $valueArray, 1, $GLOBALS['parseClassAttributes']['TerminalGateMap']['ttl']);
    }
}

function getAirportsCache()
{

    $cacheKey = "__AIRPORTS__" . "allobjects";

    return getCache($cacheKey, 1);
}

function setAirlinesCache($airlinesArray)
{

    $cacheKey = "__AIRLINES__" . "allobjects";

    setCache($cacheKey, $airlinesArray, 1, $GLOBALS['parseClassAttributes']['Airlines']['ttl']);
}

function getAirlinesCache()
{

    $cacheKey = "__AIRLINES__" . "allobjects";

    return getCache($cacheKey, 1);
}

function setAirportsCache($airportsArray)
{

    $cacheKey = "__AIRPORTS__" . "allobjects";

    setCache($cacheKey, $airportsArray, 1, $GLOBALS['parseClassAttributes']['Airports']['ttl']);
}

function getFullfillmentInfoCache(&$retailer, &$location)
{

    $cacheKey = "__FULLFILLMENTINFO__" . $retailer->get('airportIataCode') . '_' . $retailer->getObjectId() . '_' . $location->getObjectId();

    return getCache($cacheKey, 1);
}

function setFullfillmentInfoCache(&$retailer, &$location, $responseArray)
{

    $cacheKey = "__FULLFILLMENTINFO__" . $retailer->get('airportIataCode') . '_' . $retailer->getObjectId() . '_' . $location->getObjectId();

    setCache($cacheKey, $responseArray, 1, 2 * 60);
}

function setRetailerPingTimestamp($retailerUniqueId, $timestamp)
{

    // $cacheKey = "__PING_" . $retailerUniqueId;

    // setCache($cacheKey, $timestamp);

    $cacheKey = "__PINGRETAILERS_";
    $GLOBALS[$cacheKey] = [];

    return hSetCache($cacheKey, $retailerUniqueId, $timestamp);
}

function getRetailerPingTimestamp($retailerUniqueId)
{

    // $cacheKey = "__PING_" . $retailerUniqueId;

    // return getCache($cacheKey);

    $cacheKey = "__PINGRETAILERS_";

    if (defined("WORKER")) {

        return hGetCache($cacheKey, $retailerUniqueId);
    } else {

        if (count_like_php5($GLOBALS[$cacheKey]) == 0) {

            $GLOBALS[$cacheKey] = hGetAllCache($cacheKey);
        }

        if (isset($GLOBALS[$cacheKey][$retailerUniqueId])) {

            return $GLOBALS[$cacheKey][$retailerUniqueId];
        }
    }

    return 0;
}

function setCacheBulkOverrideAdjustmentForDeliveryTimeInSeconds($airportIataCode, $keyValueArray, $keyValueArrayExpire)
{

    $cacheKey = "__OVERRIDEADJDELIVERYTIMES_SET_" . $airportIataCode;
    // JMD
    $cacheKeyExpire = "__OVERRIDEADJDELIVERYTIMES_SET_EXPIRE_" . $airportIataCode;

    $GLOBALS[$cacheKey] = [];
    // JMD
    hMemSetCache($cacheKey, $keyValueArray);
    hMemSetCache($cacheKeyExpire, $keyValueArrayExpire);
}

// JMD
function getCacheKeyListAdjustmentForDelivery()
{

    $cacheKey = "__OVERRIDEADJDELIVERYTIMES_SET_";

    return $GLOBALS['redis']->keys("*" . $cacheKey . "*");
}

function setCacheOverrideAdjustmentForDeliveryTimeInSeconds(
    $airportIataCode,
    $retailerUniqueId,
    $toLocationId,
    $overrideTimeInSeconds,
    $validTillTimestamp
) {

    $cacheKey = "__OVERRIDEADJDELIVERYTIMES_SET_" . $airportIataCode;
    // JMD
    $cacheKeyExpire = "__OVERRIDEADJDELIVERYTIMES_SET_EXPIRE" . $airportIataCode;

    $GLOBALS[$cacheKey] = [];
    // JMD
    hSetCache($cacheKey, $retailerUniqueId . '__' . $toLocationId, $overrideTimeInSeconds);
    hSetCache($cacheKeyExpire, $retailerUniqueId . '__' . $toLocationId, $validTillTimestamp);
}

// JMD
function getCacheOverrideAdjustmentForDeliveryTimeInSeconds($airportIataCode, $retailerUniqueId, $toLocationId)
{

    $cacheKey = "__OVERRIDEADJDELIVERYTIMES_SET_" . $airportIataCode;
    $cacheKeyExpire = "__OVERRIDEADJDELIVERYTIMES_SET_EXPIRE_" . $airportIataCode;

    if (!isset($GLOBALS[$cacheKey])
        || count_like_php5($GLOBALS[$cacheKey]) == 0
    ) {

        $GLOBALS[$cacheKey] = hGetAllCache($cacheKey);
        $GLOBALS[$cacheKeyExpire] = hGetAllCache($cacheKeyExpire);
    }

    if (isset($GLOBALS[$cacheKeyExpire][$retailerUniqueId . '__' . $toLocationId])
        && isset($GLOBALS[$cacheKey][$retailerUniqueId . '__' . $toLocationId])
    ) {

        // If valid
        if ($GLOBALS[$cacheKeyExpire][$retailerUniqueId . '__' . $toLocationId] > time()) {

            return $GLOBALS[$cacheKey][$retailerUniqueId . '__' . $toLocationId];
        }
    }

    return 0;
}

function setCacheOverrideAdjustmentForDeliveryRequest($cacheKeySuffix, $array)
{

    // JMD
    $cacheKey = "__OVERRIDEADJDELIVERYTIMES_REQ_" . $cacheKeySuffix;
    setCache($cacheKey, json_encode($array), 0, 60 * 60);
}

function getOverrideAdjustmentForDeliveryRequest($cacheKeySuffix)
{

    $cacheKey = "__OVERRIDEADJDELIVERYTIMES_REQ_" . $cacheKeySuffix;
    return json_decode(getCache($cacheKey), true);
}

function getAllCacheOverrideAdjustmentForDelivery($airportIataCode)
{

    // JMD
    $cacheKey = "__OVERRIDEADJDELIVERYTIMES_SET_" . $airportIataCode;
    $cacheKeyExpire = "__OVERRIDEADJDELIVERYTIMES_SET_EXPIRE_" . $airportIataCode;

    return [hGetAllCache($cacheKey), hGetAllCache($cacheKeyExpire)];
}

function setRetailerDualConfigPingTimestamp($retailerUniqueId, $timestamp)
{

    // ensure any out of sync messages are not processed
    $lastTimestamp = getRetailerDualConfigPingTimestamp($retailerUniqueId);
    if ($lastTimestamp > $timestamp) {
        return 1;
    }

    $cacheKey = "__PINGDUALCONFIGRETAILERS_";

    return hSetCache($cacheKey, $retailerUniqueId, $timestamp);
}

function getRetailerDualConfigPingTimestamp($retailerUniqueId)
{

    $cacheKey = "__PINGDUALCONFIGRETAILERS_";

    // If being run in a continous loop then we need to pull directly from redis
    if (defined("WORKER")) {

        return hGetCache($cacheKey, $retailerUniqueId);
    } else {

        if (count_like_php5($GLOBALS[$cacheKey]) == 0) {

            $GLOBALS[$cacheKey] = hGetAllCache($cacheKey);
        }

        if (isset($GLOBALS[$cacheKey][$retailerUniqueId])) {

            return $GLOBALS[$cacheKey][$retailerUniqueId];
        }
    }

    return 0;
}

function setSlackDeliveryPingTimestamp($deliveryUserId, $timestamp)
{
    return 1;

    // $cacheKey = "__PING_SLACKDELIVERY_" . $deliveryUserId;

    // setCache($cacheKey, $timestamp);

    $cacheKey = "__PING_SLACKDELIVERY_";
    $GLOBALS[$cacheKey] = [];

    return hSetCache($cacheKey, $deliveryUserId, $timestamp);
}


function getSlackDeliveryPingTimestamp($deliveryUserId)
{

    // $cacheKey = "__PING_SLACKDELIVERY_" . $deliveryUserId;

    // return getCache($cacheKey);

    $cacheKey = "__PING_SLACKDELIVERY_";

    if (defined("WORKER")) {

        return hGetCache($cacheKey, $deliveryUserId);
    } else {

        if (count_like_php5($GLOBALS[$cacheKey]) == 0) {

            $GLOBALS[$cacheKey] = hGetAllCache($cacheKey);
        }

        if (isset($GLOBALS[$cacheKey][$deliveryUserId])) {

            return $GLOBALS[$cacheKey][$deliveryUserId];
        }
    }

    return 0;
}

function setCacheCheckinInfo($userId, $sessionArray)
{

    $cacheKey = "__CHECKIN_" . $userId;

    setCache($cacheKey, $sessionArray, 1, 1);
}

function getCacheCheckinInfo($userId)
{

    $cacheKey = "__CHECKIN_" . $userId;

    return getCache($cacheKey, 1);
}

function delCacheCheckinInfo($userId)
{

    $cacheKey = "__CHECKIN_" . $userId;

    return delCacheByKey($cacheKey);
}

function doesCacheCheckinInfoExist($userId)
{

    $cacheKey = "__CHECKIN_" . $userId;

    return doesCacheExist($cacheKey);
}

function getCache($key, $unserializeCacheResponse = 0, $uncompress = false)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])
        || !doesCacheExist($key)
    ) {

        return false;
    }

    $value = $GLOBALS['redis']->get($key);

    // If the response needs to be uncompressed
    if ($uncompress) {

        $value = gzuncompress($value);
    }

    // If the response needs to be unserialize while pulling from the Cache
    if ($unserializeCacheResponse == 0) {

        // Check if the key exists in the cache, if get it, else execute DB query
        // If you are fetching from DB query then save it for future use
        return $value;
    } else {
        return unserialize($value);
    }
}

function hMemSetCache($key, $keyValueArray, $expireInSeconds = 0)
{

    $result = $GLOBALS['redis']->hmset($key, $keyValueArray);

    // Does expire need to set?
    if (intval($expireInSeconds) > 0
        || !empty($expireInSeconds)
    ) {

        setCacheExpire($key, $expireInSeconds);
    }

    return $result;
}

function hSetCache($key, $index, $value, $serialize = 0, $expireInSeconds = 0)
{

    if ($serialize == 1) {

        $result = $GLOBALS['redis']->hset($key, $index, serialize($value));
    } else {

        $result = $GLOBALS['redis']->hset($key, $index, $value);
    }

    // Does expire need to set?
    if (intval($expireInSeconds) > 0
        || !empty($expireInSeconds)
    ) {

        setCacheExpire($key, $expireInSeconds);
    }

    return $result;
}

function hGetCache($key, $index, $serialize = 0)
{

    if ($serialize == 1) {

        return unserialize($GLOBALS['redis']->hget($key, $index));
    } else {

        return $GLOBALS['redis']->hget($key, $index);
    }
}

function hGetAllCache($key)
{

    return $GLOBALS['redis']->hgetall($key);
}

function hDelCache($key, $index)
{

    return $GLOBALS['redis']->hdel($key, $index);
}

function incrCache($key)
{

    return $GLOBALS['redis']->incr($key);
}

function setCache($key, $value, $serializeCache = 0, $expireInSeconds = 0)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return $value;
    }

    // If NOC = No Cache is defined
    if (strcasecmp($expireInSeconds, "NOC") == 0) {

        return $value;
    }

    // If the response needs to be serialized while setting to the Cache
    if ($serializeCache == 0) {

        $GLOBALS['redis']->set($key, $value);
    } else {

        $GLOBALS['redis']->set($key, serialize($value));
    }

    // Does expire need to set?
    if (intval($expireInSeconds) > 0
        || !empty($expireInSeconds)
    ) {

        setCacheExpire($key, $expireInSeconds);
    }

    return $value;
}

// JMD
function setCacheExpire($key, $requstedExpireInSeconds)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return;
    }

    if ($requstedExpireInSeconds < 0) {

        return;
    }

    // Default
    $expireInSeconds = 1;

    // Current time
    $nowTimestamp = time();

    // If numeric value
    if (intval($requstedExpireInSeconds) > 0) {

        $expireInSeconds = intval($requstedExpireInSeconds);
    } // End of Day, ends 11:59:59 PM EST
    else {
        if (strcasecmp($requstedExpireInSeconds, "EOD") == 0) {

            $expireInSeconds = mktime(23, 59, 59, date("n", $nowTimestamp), date("j", $nowTimestamp),
                    date("Y", $nowTimestamp)) - $nowTimestamp;
        } // End of Week, ends Sunday 11:59:59 PM EST
        else {
            if (strcasecmp($requstedExpireInSeconds, "EOW") == 0) {

                // If today is Sunday, then use today's date to mktime
                if (date("N", $nowTimestamp) == 7) {

                    $expireInSeconds = mktime(23, 59, 59, date("n", $nowTimestamp), date("j", $nowTimestamp),
                            date("Y", $nowTimestamp)) - $nowTimestamp;
                } // else get next Sunday's date
                else {

                    $nextSundayTimestamp = strtotime('next Sunday');
                    $expireInSeconds = mktime(23, 59, 59, date("n", $nextSundayTimestamp),
                            date("j", $nextSundayTimestamp), date("Y", $nextSundayTimestamp)) - $nowTimestamp;
                }
            }
        }
    }

    $GLOBALS['redis']->expire($key, $expireInSeconds);

    // Get left expiry time
    // echo($GLOBALS['redis']->ttl($key));exit;
}

function doesCacheExist($key)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return;
    }

    return $GLOBALS['redis']->exists($key);
}

function delCacheByKey($key)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return;
    }

    return $GLOBALS['redis']->del($key);
}

function getRouteCache($cacheSlimRouteNamedKey = '', $uncompress = false, $returnOutput = false)
{

    // If Named cache key is provided
    if (!empty($cacheSlimRouteNamedKey)) {

        $json_encoded_string = getCache(getNamedRouteCacheName($cacheSlimRouteNamedKey));
    } else {

        $json_encoded_string = getCache($GLOBALS['cacheSlimRouteKey']);
    }

    if ($uncompress) {

        $json_encoded_string = gzuncompress($json_encoded_string);
    }

    // If Cache is found, display and exit
    if (!is_bool($json_encoded_string)) {

        if ($returnOutput) {

            return $json_encoded_string;
        } else {

            json_echo($json_encoded_string);
        }
    }

    if ($returnOutput) {

        return json_encode([]);
    }
}

function getNamedRouteCacheName($cacheSlimRouteNamedKey)
{

    return "NRC__" . $cacheSlimRouteNamedKey;
}

function setRouteCache($params)
{

    // Set parameters
    $jsonEncodedString = $params["jsonEncodedString"];
    $expireInSeconds = isset($params["expireInSeconds"]) ? $params["expireInSeconds"] : 3600;
    $cacheSlimRouteNamedKey = isset($params["cacheSlimRouteNamedKey"]) ? $params["cacheSlimRouteNamedKey"] : '';
    $compressed = isset($params["compressed"]) ? $params["compressed"] : false;

    // Set route cache name
    // If no named cache is provided use default route cache key
    if (empty($cacheSlimRouteNamedKey)) {

        $cacheSlimRouteNamedKey = $GLOBALS['cacheSlimRouteKey'];
    } else {

        // If name is provided add a prefix
        $cacheSlimRouteNamedKey = "NRC__" . $cacheSlimRouteNamedKey;
    }

    if ($compressed) {

        $jsonEncodedString = gzcompress($jsonEncodedString, 3);
    }

    // Set default cache with provided ttl
    setCache($cacheSlimRouteNamedKey, $jsonEncodedString, 0, $expireInSeconds);

    // return the encoded array so it can be displayed
    return $jsonEncodedString;
}

function createDBQueryCacheKey(
    $objectValueArray,
    $className,
    $ascendingObjectName,
    $descendingObjectName,
    $includeKeys,
    $limit
) {


    $cacheKey = $className . "_" . $ascendingObjectName . "_" . $descendingObjectName . "_" . $limit;
    ksort($objectValueArray);
    sort($includeKeys);

    $objectIdIfAvailable = "";
    foreach ($objectValueArray as $key => $value) {

        if (strcasecmp($key, "objectId") == 0
            && !is_array($value)
        ) {

            $objectIdIfAvailable = '__' . $value;
        }

        if (is_array($value) || is_object($value)) {

            $cacheKey .= "_" . $key . "~" . serialize($value);
        } else {

            if (gettype($value) == 'boolean') {

                $value = (bool)$value;
                $cacheKey .= "_" . $key . "~" . ($value == true ? 1 : 0);
            } else {

                $cacheKey .= "_" . $key . "~" . $value;
            }
        }
    }

    foreach ($includeKeys as $value) {

        $cacheKey .= "#" . $value;
    }

    // Create final usable key name
    // PQ = ParseQuery
    $cacheKeyPrefix = 'PQ__';
    $usableCacheKey = $cacheKeyPrefix . $className . $objectIdIfAvailable . "__" . md5($cacheKey);

    return $usableCacheKey;
}

function createDBQueryCacheKeyWithProvidedName($className, $cacheKey)
{

    // Create final usable key name
    // PQ = ParseQuery
    $cacheKeyPrefix = 'PQ__';
    $usableCacheKey = $cacheKeyPrefix . $className . "__" . $cacheKey;

    return $usableCacheKey;
}

function pushItemOntoQueue($queueNameKey, $value)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return;
    }

    return $GLOBALS['redis']->rpush($queueNameKey, $value);
}

function trendingRetailerMarkOrderSubmission($airportIataCode, $retailerType, $retailerUniqueId)
{

    // Cache key
    $cacheKey = '__TRENDING_' . $airportIataCode . "-";
    $cacheKeyRetailer = '__TRENDING_' . $airportIataCode . "-" . $retailerType;

    // Check if cached key exists, if not build id
    if (!doesCacheExist($cacheKey)) {

        // Generate cache for Airport Level
        // Generate cache for Airport + RetailerType level
        seedCacheForOrderCounts($airportIataCode, $retailerType);
    } // Else increment in cache
    else {

        sortedListIncrementBy($cacheKey, $retailerUniqueId, 1);
        sortedListIncrementBy($cacheKeyRetailer, $retailerUniqueId, 1);
    }
}

function seedCacheForOrderCounts($airportIataCode, $retailerType)
{

    $obj = new ParseQuery("Retailers");
    $objRetailersInnerQuery = parseSetupQueryParams(array("airportIataCode" => $airportIataCode, "isActive" => true),
        $obj);
    $objOrderCountsByRetailers = parseExecuteQuery(array("__MATCHESQUERY__retailer" => $objRetailersInnerQuery),
        "OrderCountsByRetailer", "", "", array("retailer.retailerType"));

    // Cache keys
    $cacheKeyAirport = '__TRENDING_' . $airportIataCode . "-";
    delCacheByKey($cacheKeyAirport);

    foreach ($objOrderCountsByRetailers as $obj) {

        sortedListIncrementBy($cacheKeyAirport, $obj->get('retailer')->get('uniqueId'), $obj->get('orderCount'));

        // Cache key for retailerType
        $cacheKeyAirportRetailerType = '__TRENDING_' . $airportIataCode . "-" . $obj->get('retailer')->get('retailerType')->get('retailerType');
        sortedListDeleteElement($cacheKeyAirportRetailerType, $obj->get('retailer')->get('uniqueId'));

        sortedListIncrementBy($cacheKeyAirportRetailerType, $obj->get('retailer')->get('uniqueId'),
            $obj->get('orderCount'));
    }
}

function trendingRetailerTop($airportIataCode, $retailerType, $topX)
{

    $cacheKey = '__TRENDING_' . $airportIataCode . "-" . $retailerType;

    if (!doesCacheExist($cacheKey)) {

        seedCacheForOrderCounts($airportIataCode, $retailerType);
    }

    if ($topX == 0) {

        $topX = 10;
    }

    return sortedListRange($cacheKey, 0, $topX);
}

function sortedListIncrementBy($listName, $key, $counter)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return;
    }

    $GLOBALS['redis']->zincrby($listName, $counter, $key);
}

function sortedListDeleteElement($listName, $key)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return;
    }

    $GLOBALS['redis']->zrem($listName, $key);
}

function sortedListRange($listName, $lowCounter, $highCounter)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return;
    }

    return $GLOBALS['redis']->zrevrange($listName, $lowCounter, $highCounter);
}

function getAirportWeatherFromCache($airportIataCode, $geoLatLong)
{

    // Cache key
    $cacheKey = '__AIRPORTWEATHER__' . $airportIataCode;

    // Check if cached key exists, if not build id
    if (!doesCacheExist($cacheKey)) {

        // Generate cache
        $airportWeather = getAirportWeather($airportIataCode, $geoLatLong);

        // Set cache
        setAirportWeatherFromCache($airportIataCode, $airportWeather);

        return $airportWeather;
    } // Else increment in cache
    else {

        return getCache($cacheKey, 1);
    }
}

function setAirportWeatherFromCache($airportIataCode, $airportWeather)
{

    // Cache key
    $cacheKey = '__AIRPORTWEATHER__' . $airportIataCode;

    // Cache for 15 mins
    setCache($cacheKey, $airportWeather, 1, 15 * 60);
}

function getAirportWeather($airportIataCode, $geoLatLong)
{

    $currentTimeZone = date_default_timezone_get();

    $airportTimeZone = fetchAirportTimeZone($airportIataCode, $currentTimeZone);

    date_default_timezone_set($airportTimeZone);

    $weatherData = getJSONAirportWeatherForecast($geoLatLong);

    $lastDayKey = "";
    $i = -1;
    foreach ($weatherData as $counter => $weatherItem) {

        $dayKey = date('N', $weatherItem->dt);

        // Do this in two conditions:
        // 1) Date keys don't match, e.g. when Day changes as we need only one row per day
        // 2) If it's the first day and its the 2nd key (as sometimes wind value is not available in 0th element)
        if ($dayKey != $lastDayKey || ($dayKey == $lastDayKey && $counter == 1)) {

            // Update only for this condition
            // This ensures 0th element is overwritten if the 1st element (counter) is also for the first day
            if ($dayKey != $lastDayKey) {

                $i++;
            }

            $lastDayKey = $dayKey;

            $responseArrayWeather[$i]["date"] = date("l, M-j-Y", $weatherItem->dt);
            $responseArrayWeather[$i]["timestampUTC"] = $weatherItem->dt;
            $responseArrayWeather[$i]["tempFahrenheit"] = k_to_f($weatherItem->main->temp);
            $responseArrayWeather[$i]["tempMinFahrenheit"] = k_to_f($weatherItem->main->temp_min);
            $responseArrayWeather[$i]["tempMaxFahrenheit"] = k_to_f($weatherItem->main->temp_max);
            $responseArrayWeather[$i]["weatherText"] = $weatherItem->weather[0]->main;
            $responseArrayWeather[$i]["iconURL"] = 'http://openweathermap.org/img/w/' . $weatherItem->weather[0]->icon . '.png';
            $responseArrayWeather[$i]["windSpeed"] = isset($weatherItem->wind->speed) ? $weatherItem->wind->speed * 2.24 : "";
        }
    }

    date_default_timezone_set($currentTimeZone);

    return $responseArrayWeather;
}

function setTripItSessionToCache($tripItSessionInfo, $expireInSeconds = 60 * 60)
{

    $cacheKey = "__TRIPITSESSION_" . $GLOBALS['user']->getObjectId();

    setCache($cacheKey, $tripItSessionInfo, 1, $expireInSeconds);
}

function getTripItSessionToCache()
{

    $cacheKey = "__TRIPITSESSION_" . $GLOBALS['user']->getObjectId();

    return getCache($cacheKey, 1);
}

function setFlightSearchFromFlightAware($resultArray, $cacheKey, $expireInSeconds = "EOD")
{

    $cacheKey = "__FLIGHTAWARESEARCH_" . $cacheKey;

    setCache($cacheKey, $resultArray, 1, $expireInSeconds);
}

function getFlightSearchFromFlightAware($cacheKey)
{

    $cacheKey = "__FLIGHTAWARESEARCH_" . $cacheKey;

    return getCache($cacheKey, 1);
}

function deleteTripItSessionToCache()
{

    $cacheKey = "__TRIPITSESSION_" . $GLOBALS['user']->getObjectId();

    return delCacheByKey($cacheKey);
}

function getAPIKeyCacheKey($userObjectId, $apikey)
{

    $cacheKeyPrefix = "APL__";

    return $cacheKeyPrefix . $userObjectId . "__" . $apikey;
}

function generateRouteCacheName(&$route)
{

    // Route's parent name, e.g. retailer, user, i.e folder name
    // Find the position of apikey parameter, capture everything before it and replace slashes with underscores
    $pos = strpos($_SERVER['SCRIPT_NAME'], '/', 1);
    $parentRouteName = substr($_SERVER['SCRIPT_NAME'], 1, $pos - 1);

    // Route's call name, e.g. trending, info, i.e method name
    $pos = strpos($route->getPattern(), '/a/', 1);
    $callRouteName = str_replace('/', '_', substr($route->getPattern(), 1, $pos - 1));

    // Get Auth parameters
    $params = $route->getParams();

    // Generate unique cache key for call
    $cacheKey = "";
    $routeVars = "";

    // Get input parameters values for the route call, but skip apikey, epoch, and userid parameters
    foreach ($params as $key => $value) {

        if (!in_array($key, array('apikey', 'epoch', 'sessionToken'))) {

            $cacheKey .= $key . '/' . $value . '/';
            $routeVars .= '_' . $key;
        }
    }

    $cacheKeyPrefix = 'RR__';

    // Name the generated route cache
    return $cacheKeyPrefix . $parentRouteName . '__' . $callRouteName . '__' . md5($cacheKey);
}

function emailVerifyGetToken($emailVerifyToken)
{

    $cacheKey = "__EVTKN__" . $emailVerifyToken;


    // Fetch token value
    return getCache($cacheKey);
}

function emailVerifyGenerateAndSaveToken($objectId)
{

    // Generate a token
    $emailVerifyToken = md5($objectId . '~' . generateToken() . '~' . $GLOBALS['env_PasswordHashSalt']);

    $cacheKey = "__EVTKN__" . $emailVerifyToken;

    // Save it; with 7 day expiry
    setCache($cacheKey, $objectId, 0, 7 * 24 * 60 * 60);

    return $emailVerifyToken;
}

function emailVerifyRemoveToken($emailVerifyToken)
{

    $cacheKey = "__EVTKN__" . $emailVerifyToken;

    // If the token already exists
    if (doesCacheExist($cacheKey)) {

        delCacheByKey($cacheKey);
    }
}

function forgotGetTokenName($email)
{

    $cacheKeyPrefix = '__FTKN__';

    return $cacheKeyPrefix . md5($email);
}

function forgotGenerateAndSaveToken($email)
{

    $cacheKey = forgotGetTokenName($email);

    // Generate a token
    $forgotToken = generateToken();

    // Save it
    setCache($cacheKey, $forgotToken, 0, 60 * 60);

    return $forgotToken;
}

function forgotGetToken($email)
{

    $cacheKey = forgotGetTokenName($email);

    return getCache($cacheKey);
}

function forgotRemoveToken($email)
{

    $cacheKey = forgotGetTokenName($email);

    // If the token already exists
    if (doesCacheExist($cacheKey)) {

        delCacheByKey($cacheKey);
    }
}

function setQueueWorkerStartTimestamp($queueName = '')
{
    $cacheKey = '__QUEUE_WORKER_START_TIMESTAMP_' . $queueName;
    return setCache($cacheKey, time());
}

function getQueueWorkerStartTimestamp($queueName = '')
{
    $cacheKey = '__QUEUE_WORKER_START_TIMESTAMP_' . $queueName;

    $value = getCache($cacheKey);
    if ($value === false) {
        return null;
    }
    return (int)$value;
}

function delQueueWorkerStartTimestamp($queueName = '')
{
    $cacheKey = '__QUEUE_WORKER_START_TIMESTAMP_' . $queueName;

    $value = delCacheByKey($cacheKey);
}


function getCacheKeyForSessionDevice($userObjectId, $sessionToken)
{

    return "u-" . $userObjectId . '-' . md5($sessionToken);
}

function getCacheKeyForUserPhone($userObjectId)
{

    return "uphone-" . $userObjectId;
}

function getCacheKeyForUserDevice($userObjectId, $uniqueId)
{

    return "u-" . $userObjectId . '-' . $uniqueId;
}

function getCacheAPI9001Status()
{

    $cacheKey = "__API9001";

    return getCache($cacheKey);
}

function setCacheAPI9001WorkerQueue()
{

    $cacheKey = "__API9001_worker_queue";

    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerQueue()
{

    $cacheKey = "__API9001_worker_queue";

    return getCache($cacheKey);
}

function delCacheAPI9001WorkerQueue()
{

    $cacheKey = "__API9001_worker_queue";

    return delCacheByKey($cacheKey);
}

function setCacheAPI9001WorkerQueueMidPriorityAsynch()
{

    $cacheKey = "__API9001_worker_queue_mid_priority_asynch";

    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerQueueMidPriorityAsynch()
{
    $cacheKey = "__API9001_worker_queue_mid_priority_asynch";
    return getCache($cacheKey);
}

function delCacheAPI9001WorkerQueueMidPriorityAsynch()
{
    $cacheKey = "__API9001_worker_queue_mid_priority_asynch";
    return delCacheByKey($cacheKey);
}


function setCacheAPI9001WorkerQueueEmail()
{
    $cacheKey = "__API9001_worker_queue_email";
    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerQueueEmail()
{
    $cacheKey = "__API9001_worker_queue_email";
    return getCache($cacheKey);
}

function delCacheAPI9001WorkerQueueEmail()
{
    $cacheKey = "__API9001_worker_queue_email";
    return delCacheByKey($cacheKey);
}


function setCacheAPI9001WorkerQueueFlight()
{
    $cacheKey = "__API9001_worker_queue_flight";
    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerQueueFlight()
{
    $cacheKey = "__API9001_worker_queue_flight";
    return getCache($cacheKey);
}

function delCacheAPI9001WorkerQueueFlight()
{
    $cacheKey = "__API9001_worker_queue_flight";
    return delCacheByKey($cacheKey);
}


function setCacheAPI9001WorkerQueuePushAndSms()
{
    $cacheKey = "__API9001_worker_queue_push_and_sms";
    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerQueuePushAndSms()
{
    $cacheKey = "__API9001_worker_queue_push_and_sms";
    return getCache($cacheKey);
}

function delCacheAPI9001WorkerQueuePushAndSms()
{
    $cacheKey = "__API9001_worker_queue_push_and_sms";
    return delCacheByKey($cacheKey);
}


function setCacheAPI9001WorkerQueueSlackNotification()
{
    $cacheKey = "__API9001_worker_queue_slack_notification";
    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerQueueSlackNotification()
{
    $cacheKey = "__API9001_worker_queue_slack_notification";
    return getCache($cacheKey);
}

function delCacheAPI9001WorkerQueueSlackNotification()
{
    $cacheKey = "__API9001_worker_queue_slack_notification";
    return delCacheByKey($cacheKey);
}


function setCacheAPI9001WorkerMenuUpdate()
{

    $cacheKey = "__API9001_worker_menu_update";

    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerMenuUpdate()
{

    $cacheKey = "__API9001_worker_menu_update";

    return getCache($cacheKey);
}

function delCacheAPI9001WorkerMenuUpdate()
{

    $cacheKey = "__API9001_worker_menu_update";

    return delCacheByKey($cacheKey);
}

function setCacheAPI9001RetailersUpdate()
{

    $cacheKey = "__API9001_worker_retailers_update";

    setCache($cacheKey, time());
}

function getCacheAPI9001RetailersUpdate()
{

    $cacheKey = "__API9001_worker_retailers_update";

    return getCache($cacheKey);
}

function delCacheAPI9001RetailersUpdate()
{

    $cacheKey = "__API9001_worker_retailers_update";

    return delCacheByKey($cacheKey);
}

function setCacheAPI9001CouponsUpdate()
{

    $cacheKey = "__API9001_worker_retailers_update";

    setCache($cacheKey, time());
}

function getCacheAPI9001CouponsUpdate()
{

    $cacheKey = "__API9001_worker_retailers_update";

    return getCache($cacheKey);
}

function delCacheAPI9001CouponsUpdate()
{

    $cacheKey = "__API9001_worker_retailers_update";

    return delCacheByKey($cacheKey);
}

function setCacheAPI9001WorkerLooper()
{

    $cacheKey = "__API9001_worker_looper";

    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerLooper()
{

    $cacheKey = "__API9001_worker_looper";

    return getCache($cacheKey);
}

function delCacheAPI9001WorkerLooper()
{

    $cacheKey = "__API9001_worker_looper";

    return delCacheByKey($cacheKey);
}

function setCacheAPI9001WorkerLooper2()
{

    $cacheKey = "__API9001_worker_looper2";

    setCache($cacheKey, time());
}

function getCacheAPI9001WorkerLooper2()
{

    $cacheKey = "__API9001_worker_looper2";

    return getCache($cacheKey);
}

function delCacheAPI9001WorkerLooper2()
{

    $cacheKey = "__API9001_worker_looper2";

    return delCacheByKey($cacheKey);
}

function setCacheAPI9001WorkerLooper3()
{

    $cacheKey = "__API9001_worker_looper3";

    setCache($cacheKey, time());
}

function getCacheKeyForCacheOneSignalQueueMessageSend($userDeviceObjectId)
{

    return "__ONE_SIGNAL_QUEUE_MESSAGE_SEND_" . $userDeviceObjectId;
}

function getCacheKeyList($keySearch)
{

    // If Redis connection failed
    if (empty($GLOBALS['redis'])) {

        return false;
    }

    return $GLOBALS['redis']->keys($keySearch);
}

function setCacheOneSignalQueueMessageSend($userDeviceObjectId)
{

    $cacheKey = getCacheKeyForCacheOneSignalQueueMessageSend($userDeviceObjectId);

    setCache($cacheKey, 1, 1, 60);
}

function getCacheOneSignalQueueMessageSend($userDeviceObjectId)
{

    $cacheKey = getCacheKeyForCacheOneSignalQueueMessageSend($userDeviceObjectId);

    return getCache($cacheKey);
}

function isItem86isedFortheDay($uniqueItemId)
{

    // $cacheKey = "__86ITEM__" . md5($uniqueItemId);

    // return doesCacheExist($cacheKey);

    $cacheKey = "__86ITEM__LIST";
    if (empty(hGetCache($cacheKey, $uniqueItemId))) {

        return false;
    }

    return true;
}

function setItem86isedFortheDay($uniqueItemId, $itemDetails)
{

    // $cacheKey = "__86ITEM__" . md5($uniqueItemId);
    // return setCache($cacheKey, $itemDetails, 1, "EOD");

    $cacheKey = "__86ITEM__LIST";
    return hSetCache($cacheKey, $uniqueItemId, json_encode($itemDetails), 0, "EOD");
}

function delItem86isedFortheDay($uniqueItemId)
{

    // $cacheKey = "__86ITEM__" . md5($uniqueItemId);
    // return delCacheByKey($cacheKey);

    $cacheKey = "__86ITEM__LIST";
    return hDelCache($cacheKey, $uniqueItemId);
}

function fetchAll86Items()
{

    // $cacheKey = "__86ITEM__";
    // return getCacheKeyList($cacheKey . "*");

    $cacheKey = "__86ITEM__LIST";
    return hGetAllCache($cacheKey);
}

function getDynoLastSeenByTypeFromCache($dynoType)
{

    $cacheKey = "__MAINT_" . 'DYNO_STATE';

    $dynoStatuses = getCache($cacheKey, 1);

    // If non found send 0
    if (empty($dynoStatuses) || count_like_php5($dynoStatuses) == 0) {

        return 0;
    }

    $lastSeenOfType = [];
    foreach ($dynoStatuses as $dynoTypeKey => $dynoTypeStatus) {

        if (strcasecmp($dynoTypeKey, $dynoType) == 0) {

            foreach ($dynoTypeStatus as $dynoStatus) {

                $lastSeenOfType[] = $dynoStatus["lastSeen"];
            }
        }
    }

    // Send the earliest seen dyno's timestamp
    if (count_like_php5($lastSeenOfType) > 0) {

        return min($lastSeenOfType);
    }

    // If non found send 0
    return 0;

}

function setMobilockDeviceDataCache($deviceData)
{

    $cacheKey = "__MOBILOCKDEVICES";

    setCache($cacheKey, $deviceData, 1, "EOW");
}

function getMobilockDeviceDataCache()
{

    $cacheKey = "__MOBILOCKDEVICES";

    return getCache($cacheKey, 1);
}

function resetCache($keyList)
{

    $clearedCache = [];
    foreach ($keyList as $keyArray) {

        foreach ($keyArray as $keyName) {

            $clearedCache[$keyName][] = delCacheByKey($keyName);
        }
    }

    return $clearedCache;
}

function getNewBraintreeCustomerCreateCounter($userId)
{

    // Ensure no duplicates are added
    $cacheKey = '__BTCUSTOMERCT__DUPINSERTCNT_' . $userId;

    $counter = $GLOBALS['redis']->incr($cacheKey);

    setCacheExpire($cacheKey, 5 * 60);

    return $counter;
}

function getNewReferralCodeCounter($code)
{

    // Ensure no duplicates are added
    $cacheKey = '__REFERRALCODE__DUPINSERTCNT_' . $code;

    $counter = $GLOBALS['redis']->incr($cacheKey);

    setCacheExpire($cacheKey, 5 * 60);

    return $counter;
}

function getCreditCodeAppliedCounter($userId)
{

    // Ensure no duplicates are added
    $cacheKey = '__CREDITCODE__DUPINSERTCNT_' . $userId;

    $counter = $GLOBALS['redis']->incr($cacheKey);

    setCacheExpire($cacheKey, 60 * 60);

    return $counter;
}

function setCacheForLogging($email, $action)
{

    $cacheKey = '__USRACTION_' . $action . '_' . md5($email) . strval(time());

    $value = ["email" => $email, "IPAddress" => getenv('HTTP_X_FORWARDED_FOR') . ' ~ ' . getenv('REMOTE_ADDR')];

    setCache($cacheKey, $value, 1, 5 * 60 * 60);

    return $cacheKey;
}

function generateReferStatusCacheKey($user)
{

    return 'referStatus' . '__u__' . $user->getObjectId();
}

function setCacheForDeliveryAvailable($airportIataCode, $array)
{

    setCache('__globalsForDeliveryAvailable_' . $airportIataCode, $array, 1,
        intval($GLOBALS['env_PingSlackDeliveryIntervalInSecs']));
}

function getCacheForDeliveryAvailable($airportIataCode)
{

    setCache('__globalsForDeliveryAvailable_' . $airportIataCode, 1);
}


function getCacheKeyHMSHostTaxForOrder($orderId)
{

    return '__cartv2_hmshost_tax_' . $orderId;
}

function setCacheHMSHostTaxForOrder($orderId, $taxes)
{

    setCache(getCacheKeyHMSHostTaxForOrder($orderId), $taxes, 1, 2 * 60);
}

function getCacheHMSHostTaxForOrder($orderId)
{

    return getCache(getCacheKeyHMSHostTaxForOrder($orderId), 1);
}

function getRetailerFullfillmentInfoFromCache($airportIataCode, $locationId, $retailerUniqueId)
{

    // Orignal fullfillment info
    $namedCacheKey = '__FULLFILLMENTINFO__' . $airportIataCode . '__' . $locationId;

    $fullfillmentInfo = getCache(getNamedRouteCacheName($namedCacheKey));

    if (empty($fullfillmentInfo)) {

        return getFullfillmentInfoEmpty($retailerUniqueId);
    } else {

        $json_decoded = json_decode($fullfillmentInfo, true);

        if (isset($json_decoded[$retailerUniqueId])) {

            return $json_decoded[$retailerUniqueId];
        }

        return getFullfillmentInfoEmpty($retailerUniqueId);
    }
}

function getAllRetailerFullfillmentInfoFromCache($airportIataCode, $locationId)
{

    // Orignal fullfillment info
    $namedCacheKey = '__FULLFILLMENTINFO__sq__' . $airportIataCode . '__' . $locationId;

    $fullfillmentInfo = getCache(getNamedRouteCacheName($namedCacheKey), 0, true);

    if (empty($fullfillmentInfo)) {

        return [];
    }

    return json_decode($fullfillmentInfo, true);
}

function getCuratedListCacheKeyName(
    $airportIataCode,
    $listId,
    $locationId,
    $requestedFullFillmentTimestamp,
    $futureFullfillment
) {

    $namedCacheKey = '__curated__' . $airportIataCode . '__' . $listId . '__' . $locationId;

    if ($futureFullfillment == true) {

        $namedCacheKey .= '__' . $requestedFullFillmentTimestamp;
    }

    return $namedCacheKey;
}

function hasUserIdNotifiedForDeliveryUpTime($todayDate, $objectId)
{

    $cacheKey = "__DELIVERYUPNOTIFICATION_" . $todayDate . "_";

    return empty(hGetCache($cacheKey, $objectId)) ? false : true;
}

function logUserIdForDeliveryUpTimeNotification($todayDate, $objectId)
{

    $cacheKey = "__DELIVERYUPNOTIFICATION_" . $todayDate . "_";

    return hSetCache($cacheKey, $objectId, time(), 0, 24 * 60 * 60);
}

function setIPLocationToCache($ipAddress, $locationInfo)
{

    $cacheKey = "__LOCINFO__" . md5($ipAddress);

    return setCache($cacheKey, $locationInfo, 1, 24 * 60 * 60);
}

function getIPLocationFromCache($ipAddress)
{

    $cacheKey = "__LOCINFO__" . md5($ipAddress);

    return getCache($cacheKey, 1);
}

function getDashboardTokenCounter($token)
{

    $cacheKey = '__OPSTOKEN__' . $token;

    if (!doesCacheExist($cacheKey)) {

        return -1;
    }

    return getCache($cacheKey);
}

function incrDashboardTokenCounter($token)
{

    $cacheKey = '__OPSTOKEN__' . $token;

    $counter = $GLOBALS['redis']->incr($cacheKey);

    setCacheExpire($cacheKey, 2 * 60 * 60);

    return $counter;
}

function getMenuLoaderVersion()
{

    $cacheKey = '__MENULOADER__VERSION';

    $timestamp = getCache($cacheKey);
    $resumeRun = true;

    // If no version found, generate one
    if (empty($timestamp)) {

        $timestamp = time();
        setMenuLoaderVersion($timestamp);
        $resumeRun = false;
    }

    return [$timestamp, $resumeRun];
}

function setMenuLoaderVersion($timestamp)
{

    $cacheKey = '__MENULOADER__VERSION';

    setCache($cacheKey, $timestamp, 0, -1);
}

function delMenuLoaderVersion()
{

    $cacheKey = '__MENULOADER__VERSION';

    delCacheByKey($cacheKey);
}

function setMenuLoaderVersionForRetailer($timestamp, $retailerUniqueId, $state)
{

    $cacheKey = '__MENULOADER__RETAILERS_' . $timestamp;
    hSetCache($cacheKey, $retailerUniqueId, $state, 0, 10 * 60 * 60);
}

function delMenuLoaderVersionForRetailer($timestamp)
{

    $cacheKey = '__MENULOADER__RETAILERS_' . $timestamp;
    delCacheByKey($cacheKey);
}

function isPartialRunStatusMenuLoadedForVersionForRetailerAvailable($timestamp)
{

    $cacheKey = '__MENULOADER__RETAILERS_' . $timestamp;

    return doesCacheExist($cacheKey);
}

function getStatusMenuLoadedForVersionForRetailer($timestamp, $retailerUniqueId)
{

    $cacheKey = '__MENULOADER__RETAILERS_' . $timestamp;

    $timestampFound = hGetCache($cacheKey, $retailerUniqueId);

    if (empty($timestampFound)) {

        return 0;
    } else {
        if ($timestampFound == -1) {

            return -1;
        } else {

            return 1;
        }
    }
}

function isTimeToRunMenuLoader($gapInSeconds)
{

    $timestamp = getMenuLoaderLastVersion();

    // Do not run between 10 pm and 5 am EST
    if (isPartialRunStatusMenuLoadedForVersionForRetailerAvailable($timestamp)) {

        return true;
    } else {
        if (date("G") >= 22 || date("G") <= 5) {

            return false;
        } else {
            if (empty($timestamp)) {

                return true;
            } else {
                if (($timestamp + $gapInSeconds) < time()) {

                    return true;
                } else {

                    return false;
                }
            }
        }
    }
}

function getMenuLoaderLastVersion()
{

    $cacheKey = '__MENULOADER__RUN';

    return getCache($cacheKey);
}

function setMenuLoaderLastVersion($timestamp)
{

    $cacheKey = '__MENULOADER__RUN';

    setCache($cacheKey, $timestamp, 0, -1);
}

function getMenuLoaderNewCategoryHash()
{

    $cacheKey = '__MENULOADER__RUNTIME_DAILY_HASH';
    // $cacheKey = '__MENULOADER__NEW_CATOG_HASH';

    return hGetCache($cacheKey, "Category");
}

function setMenuLoaderNewCategoryHash($hash)
{

    $cacheKey = '__MENULOADER__RUNTIME_DAILY_HASH';

    hSetCache($cacheKey, "Category", intval($hash), 0, "EOD");
    // setCache($cacheKey, $hash, 0, "EOD");
}

function getMenuLoaderPendingHash($retailerUniqueId)
{

    $cacheKey = '__MENULOADER__PENDING_ITEMS_HASH';

    return hGetCache($cacheKey, $retailerUniqueId);
}

function setMenuLoaderPendingHash($retailerUniqueId, $hash)
{

    $cacheKey = '__MENULOADER__PENDING_ITEMS_HASH';

    hSetCache($cacheKey, $retailerUniqueId, $hash, 0, "EOD");
}

function getMenuLoaderCustomizableLoadTime($retailerUniqueId)
{

    $cacheKey = '__MENULOADER__CUSTOMIZABLE_LOAD_TIME';

    return hGetCache($cacheKey, $retailerUniqueId);
}

function setMenuLoaderCustomizableLoadTime($retailerUniqueId, $timestamp)
{

    $cacheKey = '__MENULOADER__CUSTOMIZABLE_LOAD_TIME';

    hSetCache($cacheKey, $retailerUniqueId, $timestamp, 0, -1);
}

function doesDailyCouponUsageCacheExist($cacheKeySuffix)
{

    $cacheKey = '__COUPON_USAGE_BYUSER' . '_' . $cacheKeySuffix;

    $byUserExists = doesCacheExist($cacheKey);

    $cacheKey = '__COUPON_USAGE_BYCODE' . '_' . $cacheKeySuffix;

    $byCodeExists = doesCacheExist($cacheKey);

    return [$byUserExists, $byCodeExists];
}

function setCouponUsageByUser($usageByUsers, $cacheKeySuffix)
{

    $cacheKey = '__COUPON_USAGE_BYUSER' . '_' . $cacheKeySuffix;

    foreach ($usageByUsers as $userId => $codeUsage) {

        hSetCache($cacheKey, $userId, json_encode($codeUsage), 0, -1);
    }
}

function getCouponUsageByUser($userId)
{

    $cacheKeyHistory = '__COUPON_USAGE_BYUSER' . '_' . 'history';
    $historyCodeUsage = hGetCache($cacheKeyHistory, $userId);


    if (!empty($historyCodeUsage)) {

        $historyCodeUsage = json_decode($historyCodeUsage, true);
    } else {

        $historyCodeUsage = [];
    }

    $cacheKeyToday = '__COUPON_USAGE_BYUSER' . '_' . date("Y-m-d", strtotime("today 12:00:01 am EST"));
    $todayCodeUsage = hGetCache($cacheKeyToday, $userId);

    if (!empty($todayCodeUsage)) {

        $todayCodeUsage = json_decode($todayCodeUsage, true);
    } else {

        $todayCodeUsage = [];
    }

    $sums = mergeArraysAndSumValues($historyCodeUsage, $todayCodeUsage);

    return $sums;
}

function mergeCouponUsageByUser($cacheKeySuffix)
{

    $cacheKeyHistory = '__COUPON_USAGE_BYUSER' . '_' . 'history';
    $historyCodeUsageByUser = hGetAllCache($cacheKeyHistory);

    // Yesterday's usage by users
    $cacheKeyYesterday = '__COUPON_USAGE_BYUSER' . '_' . $cacheKeySuffix;
    $yesterdayCodeUsage = hGetAllCache($cacheKeyYesterday);


    if (!empty($yesterdayCodeUsage)) {

        if (!empty($historyCodeUsageByUser)) {
            // The ones that are already in the history
            foreach ($historyCodeUsageByUser as $userId => $byUserUsageHistory) {

                // Do we have any usage for this user yesterday
                if (isset($yesterdayCodeUsage[$userId])) {

                    $byUserUsageHistory = json_decode($byUserUsageHistory, true);
                    $byUserUsageYesterday = json_decode($yesterdayCodeUsage[$userId], true);

                    // Merge the two arrays
                    $sums = mergeArraysAndSumValues($byUserUsageHistory, $byUserUsageYesterday);

                    // Save the updated array in history
                    hSetCache($cacheKeyHistory, $userId, json_encode($sums), 0, -1);
                }
            }
        }

        // New ones
        foreach ($yesterdayCodeUsage as $userId => $byUserUsageYesterday) {

            // Do we have any usage for this user yesterday
            // If not then let's add it
            if (!isset($historyCodeUsageByUser[$userId])) {

                // Save the updated array in history
                hSetCache($cacheKeyHistory, $userId, $byUserUsageYesterday, 0, -1);
            }
        }

        // Delete the cache for yesterday
        delCacheByKey($cacheKeyYesterday);
    }
}

function mergeCouponUsageByCode($cacheKeySuffix)
{

    $cacheKeyHistory = '__COUPON_USAGE_BYCODE' . '_' . 'history';
    $historyCodeUsageByCode = hGetAllCache($cacheKeyHistory);

    // Yesterday's usage by code
    $cacheKeyYesterday = '__COUPON_USAGE_BYCODE' . '_' . $cacheKeySuffix;
    $yesterdayCodeUsage = hGetAllCache($cacheKeyYesterday);

    if (!empty($yesterdayCodeUsage)) {

        // Merge the two arrays
        if (!empty($historyCodeUsageByCode)) {
            $sums = mergeArraysAndSumValues($historyCodeUsageByCode, $yesterdayCodeUsage);
        } else {
            $sums = $yesterdayCodeUsage;
        }

        // Save the updated array in history
        foreach ($sums as $code => $value) {

            hSetCache($cacheKeyHistory, $code, $value, 0, -1);
        }

        // Delete the cache for yesterday
        delCacheByKey($cacheKeyYesterday);
    }
}

function mergeArraysAndSumValues($array1, $array2)
{

    // Combine the two
    $sums = array();
    foreach (array_keys($array1 + $array2) as $key) {

        $sums[$key] = (isset($array1[$key]) ? $array1[$key] : 0)
            + (isset($array2[$key]) ? $array2[$key] : 0);
    }

    return $sums;
}

function addCouponUsageByUser($userId, $couponCode, $increment = true)
{

    $cacheKey = '__COUPON_USAGE_BYUSER' . '_' . date("Y-m-d", strtotime("today 12:00:01 am EST"));
    $todayCodeUsage = hGetCache($cacheKey, $userId);

    if (!empty($todayCodeUsage)) {

        $todayCodeUsage = json_decode($todayCodeUsage, true);
    } else {

        $todayCodeUsage = [$couponCode => 0];
    }

    if ($increment) {

        if (!isset($todayCodeUsage[$couponCode])) {
            $todayCodeUsage[$couponCode] = 1;
        } else {
            $todayCodeUsage[$couponCode] = $todayCodeUsage[$couponCode] + 1;
        }
    } else {

        $todayCodeUsage[$couponCode] = $todayCodeUsage[$couponCode] - 1;
    }

    hSetCache($cacheKey, $userId, json_encode($todayCodeUsage), 0, -1);
}

function setCouponUsageByCode($usageByCoupons, $cacheKeySuffix)
{

    $cacheKey = '__COUPON_USAGE_BYCODE' . '_' . $cacheKeySuffix;

    foreach ($usageByCoupons as $couponCode => $codeUsage) {

        hSetCache($cacheKey, $couponCode, $codeUsage, 0, -1);
    }
}

function addCouponUsageByCode($couponCode, $increment = true)
{

    $cacheKey = '__COUPON_USAGE_BYCODE' . '_' . date("Y-m-d", strtotime("today 12:00:01 am EST"));

    if ($increment) {

        $GLOBALS['redis']->hincrby($cacheKey, $couponCode, 1);
    } else {

        $GLOBALS['redis']->hincrby($cacheKey, $couponCode, -1);
    }
}

function getCouponUsageByCode($couponCode)
{

    $cacheKeyHistory = '__COUPON_USAGE_BYCODE' . '_' . 'history';
    $historyCodeUsage = hGetCache($cacheKeyHistory, $couponCode);

    $cacheKeyToday = '__COUPON_USAGE_BYCODE' . '_' . date("Y-m-d", strtotime("today 12:00:01 am EST"));
    $todayCodeUsage = hGetCache($cacheKeyToday, $couponCode);

    return (intval($historyCodeUsage) + intval($todayCodeUsage));
}


function getShouldRetailerBeShownDueItemCount($retailerUniqueId)
{
    $cacheKey = '__RETAILERS_DO_NOT_SHOW_DUE_TO_NO_MENU_ITEMS_' . $retailerUniqueId;
    $value = getCache($cacheKey);
    if ($value === false) {
        return true;
    }
    return false;
}

?>
