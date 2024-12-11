<?php
try {
    $logsPdoConenction = new PDO('mysql:host='.$env_mysqlLogsDataBaseHost.';port='.$env_mysqlLogsDataBasePort.';dbname='.$env_mysqlLogsDataBaseName, $env_mysqlLogsDataBaseUser, $env_mysqlLogsDataBasePassword,
        [PDO::MYSQL_ATTR_SSL_CA => __DIR__ . '/../cert/rds-combined-ca-bundle.pem']);
    $GLOBALS['logsPdoConnection'] = $logsPdoConenction;
} catch (Exception $e) {
    $GLOBALS['logsPdoConnection'] = null;
    // @todo logging lack of connection
}