<?php

// If being run from Heroku
// Setting debug => false turns ensure our error handler is called

if(strcasecmp($env_InHerokuRun, "Y")==0) {
	
	$app = new \Slim\Slim(array(
		'log.enabled' => true,
		'debug' => false,
        'log.writer' => new \App\Common\Service\LogWriter()
	));
}
// Else
else {
	
	$app = new \Slim\Slim(array(
		'log.enabled' => true,
        'log.writer' => new \App\Common\Service\LogWriter()
	));
}
	
if($env_SlimLogLevel == "WARN") {
	
	$app->log->setLevel(\Slim\Log::WARN);
}
else if($env_SlimLogLevel == "ERROR") {
	
	$app->log->setLevel(\Slim\Log::ERROR);
}
else {
	
	$app->log->setLevel(\Slim\Log::DEBUG);
}

?>
