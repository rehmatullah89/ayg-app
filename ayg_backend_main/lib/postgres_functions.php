<?php

///////////////////
// Config values
///////////////////


// Retailer Visits table
$retailerVisitsTableName = 'retailerVisits';

// Last run table
$lastRunLog = 'lastRunLog';

// Time Window (in minutes) within which duplicate calls to the API will be not logged
$timeWindowInMinsToBarDuplicateEntries = 1;

// Time Window (in minutes) within which duplicate Tally up runs should not be executed
$timeWindowInMinsToBarDuplicateTallyRuns = 50;

// Parse Class Name where to store Retailer's aggregated visit counts
$parseClassForSummaryOfVisits = 'RetailersVisitsTop';
	
function connectToDatabase() {
	
	global $env_HerokuPGDbHost, $env_HerokuPGDbUser, $env_HerokuPGDbPass, $env_HerokuPGDbName;
	
	// Connect to Heroku PG SQL
	$connectString = "pgsql:"
		. "host=$env_HerokuPGDbHost;"
		. "dbname=$env_HerokuPGDbName;"
		. "user=$env_HerokuPGDbUser;"
		. "port=5432;"
		. "sslmode=require;"
		. "password=$env_HerokuPGDbPass";

	$objQuery = new PDO($connectString);
	
	// Check Connection
	if (!$objQuery) {
		
		json_error("AS_006", "", "DB connection failed! Connect String: $connectString", 1);
	}
	
	return $objQuery;
}

function queryDatabase($objQuery, $query) {
	
	$result = $objQuery->query($query);
	
	if($result === false) {
		
		json_error("AS_007", "", "DB connection failed! Query execution failed. Query:: $query", 1);
	}
	
	return $result;
}

?>