<?php
namespace App\Background\Services;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class S3Service
{
    private $s3Client;

    private $bucket;

    public function __construct(S3Client $s3Client, string $bucket)
    {
        $this->s3Client = $s3Client;
        $this->bucket = $bucket;
    }

    public function doesObjectExist($filePath)
    {
        return $this->s3Client->doesObjectExist($this->bucket, $filePath);
    }

    public function getLastModified($filePath)
    {
        $result = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $filePath
        ]);
        return (string)$result['LastModified'];
    }

    public function getEtag($filePath)
    {
        $result = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $filePath
        ]);
        return (string)$result['@metadata']['headers']['etag'];
    }

    public function getFileMd5($filePath)
    {
        $result = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $filePath
        ]);
        return (string)$result['LastModified'];
    }


    public function downloadFile($filePath, $localStoragePath)
    {
        $result = $this->s3Client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $filePath
        ]);
        $bodyAsString = (string)$result['Body'];
        file_put_contents($localStoragePath, $bodyAsString);
    }

    public function listFiles($prefix)
    {
        $list = $this->s3Client->listObjects([
            'Bucket' => $this->bucket,
            'Prefix' => $prefix,
        ]);

        if (!isset($list['Contents'])) {
            return [];
        }

        $return = [];
        foreach ($list['Contents'] as $item) {
            $fullPath = $item['Key'];

            $key = '';
            if (substr($fullPath, 0, strlen($prefix)) == $prefix) {
                $key = substr($fullPath, strlen($prefix));
            }

            $key = trim($key,'/');
            if (!empty($key)) {
                $return[] = $key;
            }
        }
        return $return;
    }

    public function getBucket()
    {
        return $this->bucket;
    }

    public function listBuckets()
    {
        return $this->s3Client->listBuckets();
    }

    public function registerStreamWrapper()
    {
        return $this->s3Client->registerStreamWrapper();
    }

    public function copyObjectFromLocal(string $localPath, string $destinationPath)
    {
        $this->s3Client->putObject([
            'Bucket' => $this->getBucket(),
            'Key' => $destinationPath,
            'SourceFile' => $localPath,
        ]);
    }

    public function getFileContent($files3Path)
    {
        $objectResult = $this->s3Client->getObject(array(
            'Bucket' => $this->getBucket(),
            'Key' => $files3Path
        ));
        /** @var \GuzzleHttp\Psr7\Stream $body */
        $body = $objectResult->get('Body');
        return $body->getContents();
    }
}
