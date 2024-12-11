<?php

// Initialize redis connector
$GLOBALS['redis'] = "";

// If redis caching is enabled
// Download Redis Certificates from AWS and cache locally
if($env_CacheEnabled) {
	
	// If SSL certificates required for this environment
	if(!empty($env_CacheSSLCA)) {
		
		// Generate local file names for storage in the temporary storage
		$localSSLCA = rtrim(sys_get_temp_dir(), '/') . '/' . basename(parse_url($env_CacheSSLCA, PHP_URL_PATH));
		$localSSLCert = rtrim(sys_get_temp_dir(), '/') . '/' . basename(parse_url($env_CacheSSLCert, PHP_URL_PATH));
		$localSSLPK = rtrim(sys_get_temp_dir(), '/') . '/' . basename(parse_url($env_CacheSSLPK, PHP_URL_PATH));
		
		// If files don't exist in the temporary storage, pull from Cloudfront Signed URLs
		if(!file_exists($localSSLCA)
			|| !file_exists($localSSLCert)
			|| !file_exists($localSSLPK)) {

			// CA
			$fp = fopen($localSSLCA, 'w');
			fwrite($fp, getpage(trim($env_CacheSSLCA)));
			fclose($fp);

			// Cert
			$fp = fopen($localSSLCert, 'w');
			fwrite($fp, getpage(trim($env_CacheSSLCert)));
			fclose($fp);

			// Private Key
			$fp = fopen($localSSLPK, 'w');
			fwrite($fp, getpage(trim($env_CacheSSLPK)));
			fclose($fp);
		}
	}

	// Connect to Redis
	try {
		
		// If this redis database requires SSL
		if(!empty($env_CacheSSLCA)) {

			$GLOBALS['redis'] = new Predis\Client([
				'scheme' => 'tls',
				'ssl' => ['cafile' => $localSSLCA, 'local_cert' => $localSSLCert, 'local_pk' => $localSSLPK, 'verify_peer' => true],
				'host' => parse_url($env_CacheRedisURL, PHP_URL_HOST),
				'port' => parse_url($env_CacheRedisURL, PHP_URL_PORT),
				'password' => parse_url($env_CacheRedisURL, PHP_URL_PASS),
				// 'persistent' => '1' // Add Persistence with PHP version > 7
			]);
		}
		
		// If no SSL is required
		else {
			
			$GLOBALS['redis'] = new Predis\Client([
				'host' => parse_url($env_CacheRedisURL, PHP_URL_HOST),
				'port' => parse_url($env_CacheRedisURL, PHP_URL_PORT),
				'password' => parse_url($env_CacheRedisURL, PHP_URL_PASS),
				// 'persistent' => '1' // Add Persistence with PHP version > 7
			]);
		}
	}
	catch (Exception $ex) {
		
		// Redis connection failed
		$GLOBALS['redis'] = "";
		json_error("AS_017", "", "Redis Cloud Connection failed" . $ex->getMessage(), 1);
	}
}
