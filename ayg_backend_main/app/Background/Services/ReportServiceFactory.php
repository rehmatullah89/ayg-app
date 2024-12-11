<?php
namespace App\Background\Services;

use App\Background\Repositories\PingLogMysqlRepository;

/**
 * Class ReportServiceFactory
 * @package App\Backround\Services
 */
class ReportServiceFactory
{
    /**
     * @return ReportService
     */
    public static function create(\PDO $pdoConnection)
    {
        return new ReportService(
            new PingLogMysqlRepository($pdoConnection)
        );
    }
}