<?php
namespace App\Background\Repositories;

use App\Background\Services\S3Service;

/**
 * Class S3Repository
 * @package App\Background\Repositories
 */
class LocalStorageRepository
{
    const MAIN_DIRECTORY = __DIR__ . '/../../../storage/logs/ping_logs/';

    /**
     * @param $directory
     * @param $data
     */
    public function store($directory, $data, $filePrefix = '')
    {
        $date = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        if (!file_exists(self::MAIN_DIRECTORY . '/' . $directory)) {
            mkdir(self::MAIN_DIRECTORY . '/' . $directory);
        }

        if (!file_exists(self::MAIN_DIRECTORY . '/' . $directory . '/' . $date->format('Y-m-d'))) {
            mkdir(self::MAIN_DIRECTORY . '/' . $directory . '/' . $date->format('Y-m-d'));
        }

        $fileName = $filePrefix . $date->format('Y-m-d') . '.txt';
        file_put_contents(
            self::MAIN_DIRECTORY . $directory . '/' . $date->format('Y-m-d') . '/' . $fileName,
            json_encode($data) . PHP_EOL,
            FILE_APPEND);
    }
}
