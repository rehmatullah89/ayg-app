<?php

use Parse\ParseClient;
use Parse\ParseQuery;
use Parse\ParseObject;
use Parse\ParseFile;

function parseSetupQueryParams($objectValueArray, &$objParseQuery, $ascendingObjectName="", $descendingObjectName="", $includeKeys=array(), $limit=1000) {
	
	foreach($objectValueArray as $key => $value) {
		
		if(preg_match("/^__MATCHESQUERY__/i", $key)) {

			$objParseQuery->matchesQuery(str_replace('__MATCHESQUERY__', '', $key), $value);
		}

		else if(preg_match("/^__CONTAINEDIN__/i", $key)) {

			$objParseQuery->containedIn(str_replace('__CONTAINEDIN__', '', $key), $value);
		}

		else if(preg_match("/^__NOTCONTAINEDIN__/i", $key)) {

			$objParseQuery->notContainedIn(str_replace('__NOTCONTAINEDIN__', '', $key), $value);
		}

		else if(is_array($value)) {
			
			// $value is an Array here
			$objParseQuery->containedIn($key, $value);
		}
		else {
			
			if(preg_match("/^__NE__/i", $key)) {

				$key = str_replace('__NE__', '', $key);
				$parseFunction = 'notEqualTo';
			}
			else if(preg_match("/^__GT__/i", $key)) {

				$key = str_replace('__GT__', '', $key);
				$parseFunction = 'greaterThan';
			}
			else if(preg_match("/^__GTE__/i", $key)) {

				$key = str_replace('__GTE__', '', $key);
				$parseFunction = 'greaterThanOrEqualTo';
			}
			else if(preg_match("/^__LT__/i", $key)) {

				$key = str_replace('__LT__', '', $key);
				$parseFunction = 'lessThan';
			}
			else if(preg_match("/^__LTE__/i", $key)) {

				$key = str_replace('__LTE__', '', $key);
				$parseFunction = 'lessThanOrEqualTo';
			}
			else if(preg_match("/^__DNE__/i", $key)) {

				$key = str_replace('__DNE__', '', $key);
				$parseFunction = 'doesNotExist';
				$value = null;
			}
			else if(preg_match("/^__E__/i", $key)) {

				$key = str_replace('__E__', '', $key);
				$parseFunction = 'exists';
				$value = null;
			}
			else if(preg_match("/^__SW__/i", $key)) {

				$key = str_replace('__SW__', '', $key);
				$parseFunction = 'startsWith';
			}
			else if(preg_match("/^__EW__/i", $key)) {

				$key = str_replace('__EW__', '', $key);
				$parseFunction = 'endsWith';
			}
			else if(preg_match("/^__CONTAINS__/i", $key)) {

				$key = str_replace('__CONTAINS__', '', $key);
				$parseFunction = 'contains';
			}
			else {

				$parseFunction = 'equalTo';
			}

			// used for exists and doesNotExist
			if(is_null($value)) {

				$objParseQuery->$parseFunction($key);
			}
			else if(gettype($value) == 'double') {
				
				$objParseQuery->$parseFunction($key, (float)$value);
			}
			else if(gettype($value) == 'integer') {
			
				$objParseQuery->$parseFunction($key, (int)$value);
			}
			else if(gettype($value) == 'boolean') {
				
				$objParseQuery->$parseFunction($key, (bool)$value);
			}
			else {
			
				$objParseQuery->$parseFunction($key, $value);
			}
		}
	}

	if(!empty($ascendingObjectName)) {
		
		$objParseQuery->ascending($ascendingObjectName);
	}
	
	if(!empty($descendingObjectName)) {
		
		$objParseQuery->descending($descendingObjectName);
	}
	
	if(count_like_php5($includeKeys) > 0) {
		
		foreach($includeKeys as $keyName) {
			
			$objParseQuery->includeKey($keyName);
		}		
	}
	
	$objParseQuery->limit($limit);

	return $objParseQuery;
}


function __parseExecuteQuery($objectValueArray, $className, $ascendingObjectName="", $descendingObjectName="", $includeKeys=array(), $limit, $queryType, $useMasterKey) {
	$objParseQuery = new ParseQuery($className);

	parseSetupQueryParams($objectValueArray, $objParseQuery, $ascendingObjectName, $descendingObjectName, $includeKeys, $limit);

	try {
		
		if(strcasecmp($queryType, 'find')==0) {

			$objParseQueryResults = $objParseQuery->find($useMasterKey, true);
		}
		else {

			$objParseQueryResults[] = $objParseQuery->count($useMasterKey);
		}
	}
	catch(Exception $ex) {

		if(defined("WORKER")) {

			// return error
			// return json_error_return_array("AS_015", "", "__parseExecuteQuery failed: objectValueArray = " . json_encode($objectValueArray) . ", className = $className, ascendingObjectName = $ascendingObjectName, descendingObjectName = $descendingObjectName, limit = $limit, includeKeys = " . json_encode($includeKeys) . ", queryType = " . $queryType . " :: Error: " . $ex->getMessage() . " :: Backtrace: " . getBackTrace(), 1);
			json_error("AS_014", "", "__parseExecuteQuery failed: objectValueArray = " . json_encode($objectValueArray) . ", className = $className, ascendingObjectName = $ascendingObjectName, descendingObjectName = $descendingObjectName, limit = $limit, includeKeys = " . json_encode($includeKeys) . ", queryType = " . $queryType . " :: Error: " . $ex->getMessage() . " :: Backtrace: " . getBackTrace(), 1);
		}
		else {

			// exiting error
			json_error("AS_014", "", "__parseExecuteQuery failed: objectValueArray = " . json_encode($objectValueArray) . ", className = $className, ascendingObjectName = $ascendingObjectName, descendingObjectName = $descendingObjectName, limit = $limit, includeKeys = " . json_encode($includeKeys) . ", queryType = " . $queryType . " :: Error: " . $ex->getMessage() . " :: Backtrace: " . getBackTrace(), 1);
		}

		// return false;
	}
	
	return $objParseQueryResults;
}

function parseExecuteQuery($objectValueArray, $className, $ascendingObjectName="", $descendingObjectName="", $includeKeys=array(), $limit=10000, $doNotUseCache = false, $overrideClassAttrAndCache = array(), $queryType='find', $useMasterKey=false) {
	
	if($limit == 0) {
		
		$limit = 1000;
	}
	
	// Cache if: doNotUseCache == false and parseClassAttributes != NOC
	// Or Cache if: overrideClassAttrAndCache = array count > 0
	if((strcasecmp($GLOBALS['parseClassAttributes'][$className]['ttl'], "NOC") !=0 && $doNotUseCache == false)
		|| count_like_php5($overrideClassAttrAndCache) > 0) {
		
		// If no override cache key name is provided
		if(count_like_php5($overrideClassAttrAndCache) > 0) {

			// Use provided name
			$cacheKey = createDBQueryCacheKeyWithProvidedName($className, $overrideClassAttrAndCache['cacheKey']);
		}
		else {

			// create cache key
			$cacheKey = createDBQueryCacheKey($objectValueArray, $className, $ascendingObjectName, $descendingObjectName, $includeKeys, $limit);
            //logResponse(json_encode([['cacheKey',$cacheKey],'list',[$objectValueArray, $className, $ascendingObjectName, $descendingObjectName, $includeKeys, $limit]]));
		}
		
		// Get cache with unserialization
		$obj = getCache($cacheKey, 1);
		
		// If cache was found
		if(!is_bool($obj)
			&& !isset($obj["error_code"])) {
			
			return parseReturnObjectBasedOnLimit($obj, $limit);
		}
	}

	// Run 3 times trying to fetch Parse data
	$i = 0;
	$obj = false;
	
	while(is_bool($obj) && $i < 3) {
		
		if($i > 0) {
			
			// Info Log 
			//json_error("AS_2005", "", "Multiple Parse Fetch Required ($i) - $className", 3, 1);

			// 1/10th of second
			usleep(100000);
		}
		
		$obj = __parseExecuteQuery($objectValueArray, $className, $ascendingObjectName, $descendingObjectName, $includeKeys, $limit, $queryType, $useMasterKey);
		$i++;
	}
	
	// After 3 attempts if usable object still not found
	if(is_bool($obj)) {
		
		// Throw Fatal Error and exit
		json_error("AS_1000", "", "AS_2004 - Multiple Fetch Failed for $className objectValueArray: " . base64_encode(serialize($objectValueArray)), 1);
	}
	
	// Cache if: doNotUseCache == false and parseClassAttributes != NOC
	// Or Cache if: overrideClassAttrAndCache = array count > 0
	if((strcasecmp($GLOBALS['parseClassAttributes'][$className]['ttl'], "NOC") !=0 && $doNotUseCache == false)
		|| count_like_php5($overrideClassAttrAndCache) > 0) {
		
		if(count_like_php5($overrideClassAttrAndCache) > 0) {

			$cacheOnlyWhenResult = $overrideClassAttrAndCache['cacheOnlyWhenResult'];
			$ttl = $overrideClassAttrAndCache['ttl'];
		}
		else {

			$cacheOnlyWhenResult = $GLOBALS['parseClassAttributes'][$className]['cacheOnlyWhenResult'];
			$ttl = $GLOBALS['parseClassAttributes'][$className]['ttl'];
		}

		// Check if we should cache only when results are found
		// If so then cache with object count > 0
		// Or cahche if this rule is not set
		if(!isset($overrideClassAttrAndCache['cacheOnlyWhenResult'])
			|| ($overrideClassAttrAndCache['cacheOnlyWhenResult'] == true && count_like_php5($obj) > 0)
				|| ($overrideClassAttrAndCache['cacheOnlyWhenResult'] == false)) {

			// Set cache with serialization
			setCache($cacheKey, $obj, 1, $ttl);	
		}

		/*
		// Set expire time if required
		if($GLOBALS['cacheExpireParseQuery'] != 0) {
			
			setCacheExpire($cacheKey, $GLOBALS['cacheExpireParseQuery']);
		}
		*/
		
		// reset the flag for next use
		// $GLOBALS['cacheParseQuery'] = false;
		// $GLOBALS['cacheExpireParseQuery'] = 0;
	}
	
	return parseReturnObjectBasedOnLimit($obj, $limit);
}

function parseReturnObjectBasedOnLimit($obj, $limit) {

	if($limit == 1
		&& count_like_php5($obj) > 0
		&& isset($obj[0])) {

		return $obj[0];
	}
	else {

		return $obj;
	}
}

/*
function parseExecuteFindFunction(&$objLoaded) {
	
	$i = 0;
	$obj = false;
	
	// Run 3 times trying to fetch Parse data
	while(is_bool($obj) && $i < 3) {
		
		if($i > 0) {
			
			// Info Log 
			//json_error("AS_2005", "", "Multiple Parse Function Required ($i)", 3, 1);

			// 1/10th of second
			usleep(100000);
		}
		
		$obj = $objLoaded->find();
		$i++;
	}
	
	// After 3 attempts if usable object still not found
	if(is_bool($obj)) {
		
		// Throw Fatal Error and exit
		json_error("AS_2004", "", "Multiple Parse Function Failed for objLoaded: " . base64_encode(serialize($objLoaded)), 1);
	}
	
	return $obj;
}
*/

?>
