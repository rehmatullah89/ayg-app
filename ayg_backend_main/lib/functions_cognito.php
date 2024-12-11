<?php

use AwsCognito\CognitoClient;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

function getCognitoClientObject() {

    if (empty($GLOBALS['cognito_client'])) {

        $GLOBALS['cognito_client'] = CongnitoConnect();
    }

    return $GLOBALS['cognito_client'];
}

function CongnitoConnect() {

    try {

        $cognito_credentials = [
            'region' => 'us-east-1',
            'version' => 'latest',
            'app_client_id' => $GLOBALS['env_CognitoAppClientId'],
            'app_client_secret' => $GLOBALS['env_CognitoAppClientSecret'],
            'user_pool_id' => $GLOBALS['env_CognitoUserPoolId'],
            // JMD
            'credentials' => [
                'key' => $GLOBALS['env_CognitoKey'],
                'secret' => $GLOBALS['env_CognitoSecret']
            ],
        ];

        // Instantiate the client
        $aws = new \Aws\Sdk($cognito_credentials);
        $cognitoClientProvider = $aws->createCognitoIdentityProvider();

        $cognito_client = new AwsCognito\CognitoClient($cognitoClientProvider);
        $cognito_client->setAppClientId($cognito_credentials['app_client_id']);
        $cognito_client->setAppClientSecret($cognito_credentials['app_client_secret']);
        $cognito_client->setRegion($cognito_credentials['region']);
        $cognito_client->setUserPoolId($cognito_credentials['user_pool_id']);

    }
    catch (Exception $ex) {

        return json_error_return_array("AS_1086", "", "AWS Cognito connection failed " . json_encode($ex->getMessage()), 1);
    }

    return $cognito_client;
}

?>