<?php

use Aws\S3\S3Client;

function getS3ClientObject($forceNewInstance=true, $storeIndex=0) {

    if($forceNewInstance == true) {

        return S3Connect();
    }

    if (!isset($GLOBALS['s3_client'][$storeIndex])) {

        $GLOBALS['s3_client'][$storeIndex] = S3Connect();
    }

    return $GLOBALS['s3_client'][$storeIndex];
}

function S3Connect()
{

    try {

        $s3_credentials = array(
            'region' => 'us-east-2',
            'version' => 'latest',
            'use_accelerate_endpoint' => true,
            'credentials' => array(
                'key' => $GLOBALS['env_S3AccessKey'],
                'secret' => $GLOBALS['env_S3AccessSecret']
            )
        );

        // Instantiate the client
        $s3_client = new S3Client($s3_credentials);
    } catch (Exception $ex) {

        return json_error_return_array("AS_1033", "", "AWS S3 connection failed " . json_encode($ex->getMessage()), 1);
    }

    return $s3_client;
}

function S3FileExsists($s3_client, $bucketName, $keyWithFolderPath, $localFilePath)
{
    $result = $s3_client->doesObjectExist($bucketName, $keyWithFolderPath . '/' . $localFilePath);

    return $result;
}

function S3UploadFileWithPath($s3_client, $bucketName, $keyWithFolderPath, $localFilePath, $allowPublicRead)
{
    $fp = fopen($localFilePath, 'r+');
    $result = S3UploadFile($s3_client, $bucketName, $keyWithFolderPath, $fp, $allowPublicRead);
    fclose($fp);

    return $result;
}

function S3UploadFileWithContents($s3_client, $bucketName, $keyWithFolderPath, $fileContents, $allowPublicRead)
{

    return S3UploadFile($s3_client, $bucketName, $keyWithFolderPath, $fileContents, $allowPublicRead);
}

function S3UploadFile($s3_client, $bucketName, $keyWithFolderPath, $fileContents, $allowPublicRead)
{

    try {

        $objectProperties = [
            'Bucket' => $bucketName,
            'Key' => $keyWithFolderPath,
            'Body' => $fileContents
        ];

        if ($allowPublicRead == true) {

            $objectProperties['ACL'] = 'public-read';
        }

        $result = $s3_client->putObject($objectProperties);

        $publicUrl = $result->get('ObjectURL');
    } catch (Exception $ex) {

        return json_error_return_array("AS_1034", "", "AWS S3 upload failed " . $keyWithFolderPath . json_encode($ex->getMessage()), 1);
    }

    return $publicUrl;
}

function S3GetPrivateFile($s3_client, $bucketName, $key, $minsToExpire = '30')
{

    try {

        $cmd = $s3_client->getCommand('GetObject', [
            'Bucket' => $bucketName,
            'Key' => $key
        ]);

        $result = $s3_client->createPresignedRequest($cmd, '+' . $minsToExpire . ' minutes');

        $presignedUrl = strval($result->getUri());
    } catch (Exception $ex) {

        return json_error_return_array("AS_1037", "", "AWS S3 private object fetch failed " . $key . json_encode($ex->getMessage()), 1);
    }

    // JMD
    return $presignedUrl;
}

function S3GetLastModifiedTimeFile($s3_client, $bucketName, $key) {

    try {

        $result = $s3_client->getObject(array(
            'Bucket' => $bucketName,
            'Key' => $key
        ));

        if(isset($result['LastModified'])) {

            return $result['LastModified']->getTimestamp();
        }
        else {

            throw new Exception("File time not found");
        }
    }
    catch (Exception $ex) {

        return json_error_return_array("AS_1037", "", "AWS S3 get object fetch failed " . $key . json_encode($ex->getMessage()), 1);
    }
}

function S3DeleteFile($s3_client, $bucket, $key)
{

    try {

        $result = $s3_client->deleteObject(array(
            'Bucket' => $bucket,
            'Key' => $key
        ));
    } catch (Exception $ex) {

        return json_error_return_array("AS_1035", "", "AWS S3 delete failed " . $key . json_encode($ex->getMessage()), 1);
    }

    return S3IsFileDeleted($s3_client, $bucket, $key);
}

function S3IsFileDeleted($s3_client, $bucket, $key)
{

    $fileInfo = array();
    try {

        $fileInfo = $s3_client->getObject(['Bucket' => $bucket, 'Key' => $key]);
    } catch (Exception $ex) {
    }

    if (count_like_php5($fileInfo) > 0) {

        // File Not deleted
        return false;
    }

    return true;
}

function preparePublicS3URL($fileName, $keyPath, $endPoint)
{

    if (empty($fileName)) {

        return "";
    }

    return $endPoint . '/' . $keyPath . '/' . $fileName;
}

function extractFilenameFromS3URL($url)
{

    return substr($url, strrpos($url, '/') + 1, strlen($url));
}

function getS3KeyPath_FilesInvoice()
{

    return $GLOBALS['env_S3Path_PrivateFiles'] . '/' . $GLOBALS['env_S3Path_PrivateFilesInvoice'];
}

function getS3KeyPath_ImagesRetailerLogo($airportIataCode)
{

    return $GLOBALS['env_S3Path_PublicImagesAirportSpecific'] . '/' . strtoupper($airportIataCode) . '/' . $GLOBALS['env_S3Path_PublicImagesRetailerLogo'];
}

function getS3KeyPath_ImagesRetailerAds($airportIataCode)
{

    return $GLOBALS['env_S3Path_PublicImagesAirportSpecific'] . '/' . strtoupper($airportIataCode) . '/' . $GLOBALS['env_S3Path_PublicImagesRetailerAds'];
}

function getS3KeyPath_ImagesRetailerBackground($airportIataCode)
{

    return $GLOBALS['env_S3Path_PublicImagesAirportSpecific'] . '/' . strtoupper($airportIataCode) . '/' . $GLOBALS['env_S3Path_PublicImagesRetailerBackground'];
}

function getS3KeyPath_ImagesRetailerItem($airportIataCode)
{

    return $GLOBALS['env_S3Path_PublicImagesAirportSpecific'] . '/' . strtoupper($airportIataCode) . '/' . $GLOBALS['env_S3Path_PublicImagesRetailerItem'];
}

function getS3KeyPath_ImagesDirection($airportIataCode)
{

    return $GLOBALS['env_S3Path_PublicImagesAirportSpecific'] . '/' . strtoupper($airportIataCode) . '/' . $GLOBALS['env_S3Path_PublicImagesDirection'];
}

function getS3KeyPath_RetailerMenuPartnerPath($partner, $retailer)
{

    if(empty($partner)) {

        return $GLOBALS['env_S3Path_PrivateMenuInternal'] . '/' . $retailer;
    }

    return $GLOBALS['env_S3Path_PrivateMenuPartner'] . '/' . $partner . '/' . $retailer;
}

function getS3KeyPath_RetailerMenuImagesPreLoad($airportIataCode, $partner, $retailer) {

    return $GLOBALS['env_S3Path_PrivateFiles'] . '/' . $GLOBALS['env_S3Path_PrivateMenu'] . '/' . strtoupper($airportIataCode) . '/' . getS3KeyPath_RetailerMenuPartnerPath($partner, $retailer) . '/' . $GLOBALS['env_S3Path_PrivateMenuImages'] . '/';
}

function getS3KeyPath_RetailerMenuLoaderLog() {

    return $GLOBALS['env_S3BucketName'] . '/' . $GLOBALS['env_S3Path_PrivateFiles'] . '/' . $GLOBALS['env_S3Path_PrivateMenu'] . '/';
}

function getS3KeyPath_RetailerMenuLoaderNewCategories() {

    return $GLOBALS['env_S3Path_PrivateFiles'] . '/' . $GLOBALS['env_S3Path_PrivateMenu'] . '/';
}

function getS3KeyPath_RetailerMenuLog($airportIataCode, $partner, $retailer) {

    return $GLOBALS['env_S3Path_PrivateFiles'] . '/' . $GLOBALS['env_S3Path_PrivateMenu'] . '/' . strtoupper($airportIataCode) . '/' . getS3KeyPath_RetailerMenuPartnerPath($partner, $retailer) . '/' .  $GLOBALS['env_S3Path_PrivateMenuLog'] . '/';
}

function getS3KeyPath_RetailerMenuFiles($airportIataCode, $partner, $retailer) {

    return $GLOBALS['env_S3Path_PrivateFiles'] . '/' . $GLOBALS['env_S3Path_PrivateMenu'] . '/' . strtoupper($airportIataCode) . '/' . getS3KeyPath_RetailerMenuPartnerPath($partner, $retailer) . '/';
}

function getS3KeyPath_ImagesAirportBackground()
{

    return $GLOBALS['env_S3Path_PublicImages'] . '/' . $GLOBALS['env_S3Path_PublicImagesAirportBackground'];
}

function getS3KeyPath_ImagesUserSubmittedBug()
{

    return $GLOBALS['env_S3Path_PublicImagesUserSubmitted'] . '/' . $GLOBALS['env_S3Path_PublicImagesUserSubmittedBug'];
}

function getS3KeyPath_FilesAirEmployee()
{

    return $GLOBALS['env_S3Path_PrivateFiles'] . '/' . $GLOBALS['env_S3Path_PrivateFilesAirEmployee'];
}

function getS3KeyPath_ImagesUserSubmittedProfile()
{

    return $GLOBALS['env_S3Path_PublicImagesUserSubmitted'] . '/' . $GLOBALS['env_S3Path_PublicImagesUserSubmittedProfile'];
}

function getS3KeyPath_ImagesCouponLogo()
{
    return $GLOBALS['env_S3Path_PublicImages'] . '/' . $GLOBALS['env_S3Path_PublicImagesCouponLogo'];
}

function getS3KeyPath_ImagesPartnerLogo()
{
    return $GLOBALS['env_S3Path_PublicImages'] . '/' . $GLOBALS['env_S3Path_PublicImagesPartnerLogo'];
}

function getS3KeyPath_ImagesAppIcon()
{

    return $GLOBALS['env_S3Path_PublicImagesAppIconsFontAwesome'];
}

function getS3KeyPath_PartnerExtractFile($getOpsCSVTimestamp, $groupName) {

    return ['extracts/' . $groupName, date("Ymd", $getOpsCSVTimestamp) . '-daily' . '.csv'];
}

function getS3KeyPath_Logs()
{

    return $GLOBALS['env_S3BucketName'] . '/' . $GLOBALS['env_S3Path_Logs'];
}

?>
