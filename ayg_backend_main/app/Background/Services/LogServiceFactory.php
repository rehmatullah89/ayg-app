<?php
namespace App\Background\Services;

use App\Background\Repositories\CheckInLocalStorageRepository;
use App\Background\Repositories\CheckInS3Repository;
use App\Background\Repositories\PingLogLocalStorageRepository;
use App\Background\Repositories\PingLogS3Repository;
use App\Background\Repositories\QueueLogLocalStorageRepository;
use App\Background\Repositories\QueueLogS3Repository;
use App\Background\Repositories\UserActionLocalStorageRepository;
use App\Background\Repositories\UserActionLogS3Repository;
use App\Background\Repositories\PingLogMysqlRepository;
use App\Background\Repositories\DeliveryLogMysqlRepository;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

/**
 * Class LogServiceFactory
 * @package App\Backround\Services
 */
class LogServiceFactory
{
    /**
     * @return LogService
     */
    public static function create(): LogService
    {
        /*
        $s3Service = new S3Service(
            new S3Client([
                'version' => 'latest',
                'region' => $GLOBALS['env_S3LogsRegion'],
                'credentials' => new Credentials($GLOBALS['env_S3LogsAccessKey'],
                    $GLOBALS['env_S3LogsAccessSecret'])
            ]),
            $GLOBALS['env_S3LogsBucketName']);
        */

        return new LogService(
            new PingLogLocalStorageRepository(),
            new CheckInLocalStorageRepository(),
            new QueueLogLocalStorageRepository(),
            new UserActionLocalStorageRepository(),
            new DeliveryLogMysqlRepository($GLOBALS['logsPdoConnection'])
            //new PingLogS3Repository(
            //    $s3Service
            //),
            //new CheckInS3Repository(
            //    $s3Service
            //),
            //new QueueLogS3Repository(
            //    $s3Service
            //),
            //new UserActionLogS3Repository(
            //    $s3Service
            //)
        );


        /*$pdoConnection = $GLOBALS['logsPdoConnection'];
        return new LogService(
            new PingLogMysqlRepository($pdoConnection)
        );*/
    }

}
