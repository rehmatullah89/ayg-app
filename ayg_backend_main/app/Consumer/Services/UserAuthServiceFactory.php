<?php
namespace App\Consumer\Services;

use App\Consumer\Repositories\UserCustomIdentifierMysqlRepository;
use App\Consumer\Repositories\UserCustomSessionMysqlRepository;
use App\Consumer\Repositories\UserSessionDeviceParseRepository;

class UserAuthServiceFactory extends Service
{
    public static function create(): UserAuthService
    {
        $sessionsPdoConnection = new \PDO('mysql:host=' . $GLOBALS['env_mysqlSessionsDataBaseHost'] . ';port=' . $GLOBALS['env_mysqlSessionsDataBasePort'] . ';dbname=' . $GLOBALS['env_mysqlSessionsDataBaseName'],
            $GLOBALS['env_mysqlSessionsDataBaseUser'], $GLOBALS['env_mysqlSessionsDataBasePassword']);

        return new UserAuthService(
            new UserCustomIdentifierMysqlRepository($sessionsPdoConnection),
            new UserCustomSessionMysqlRepository($sessionsPdoConnection),
            new UserSessionDeviceParseRepository()
        );
    }
}
