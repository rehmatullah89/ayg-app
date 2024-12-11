<?php

	// Random Salt for token generation
	putenv('env_RandomTokenString=');

	// Base URL to API
	putenv('env_BaseURL=https://abcd.herokuapp.com/dashboard');

	// Ops API key
	putenv('env_OpsRestAPIKeySalt=');



	// Slack
	putenv('env_SlackClientId=');
	putenv('env_SlackClientSecret=');
	putenv('env_SlackAccessToken=');

	// Envrionment name
	putenv('env_EnvironmentDisplayCode=PROD'); // DEV, TEST, PROD

	// Mobilock
	putenv('env_MobiLock_APIKey=');

?>