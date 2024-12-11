<?php
namespace App\Background\Repositories;

use App\Background\Services\S3Service;

/**
 * Class S3Repository
 * @package App\Background\Repositories
 */
class S3Repository
{
    const MAIN_DIRECTORY = 'logs';

    /**
     * @var S3Service
     */
    private $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    /**
     * @param $directory
     * @param $data
     */
    public function store($directory, $data, $filePrefix = '')
    {
        $date = new \DateTimeImmutable('now',new \DateTimeZone('UTC'));

        $this->s3Service->registerStreamWrapper();
        $stream = fopen('s3://' . $this->s3Service->getBucket(). '/'.self::MAIN_DIRECTORY."/{$directory}/".$filePrefix.$date->format('Y-m-d').'.txt' , 'a');
        fwrite($stream, json_encode($data)." \r\n");
        fclose($stream);
    }


}
