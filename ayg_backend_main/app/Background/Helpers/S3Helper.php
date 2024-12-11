<?php
namespace App\Background\Helpers;

/**
 * Class S3Helper
 * @package App\Background\Helpers
 */
class S3Helper
{
    public static function preparePublicS3URL($fileName, $keyPath, $endPoint)
    {
        return preparePublicS3URL($fileName, $keyPath, $endPoint);
    }

    public static function extractFilenameFromS3URL($url)
    {
        return extractFilenameFromS3URL($url);
    }

    public static function getS3KeyPath_FilesInvoice()
    {
        return getS3KeyPath_FilesInvoice();
        //return ConfigHelper::get('env_S3Path_PrivateFiles') . '/' . ConfigHelper::get('env_S3Path_PrivateFilesInvoice');
    }

    public static function getS3KeyPath_ImagesRetailerLogo($airportIataCode)
    {
        return getS3KeyPath_ImagesRetailerLogo($airportIataCode);
        //return ConfigHelper::get('env_S3Path_PublicImagesAirportSpecific') . '/' . strtoupper($airportIataCode) . '/' . ConfigHelper::get('env_S3Path_PublicImagesRetailerLogo');
    }

    public static function getS3KeyPath_ImagesRetailerBackground($airportIataCode)
    {
        return getS3KeyPath_ImagesRetailerBackground($airportIataCode);
        //return ConfigHelper::get('env_S3Path_PublicImagesAirportSpecific') . '/' . strtoupper($airportIataCode) . '/' . ConfigHelper::get('env_S3Path_PublicImagesRetailerBackground');
    }

    public static function getS3KeyPath_ImagesRetailerItem($airportIataCode)
    {
        return getS3KeyPath_ImagesRetailerItem($airportIataCode);
        //return ConfigHelper::get('env_S3Path_PublicImagesAirportSpecific') . '/' . strtoupper($airportIataCode) . '/' . ConfigHelper::get('env_S3Path_PublicImagesRetailerItem');
    }

    public static function getS3KeyPath_ImagesDirection($airportIataCode)
    {
        return getS3KeyPath_ImagesDirection($airportIataCode);
        //return ConfigHelper::get('env_S3Path_PublicImagesAirportSpecific') . '/' . strtoupper($airportIataCode) . '/' . ConfigHelper::get('env_S3Path_PublicImagesDirection');
    }

    public static function getS3KeyPath_ImagesAirportBackground()
    {
        return getS3KeyPath_ImagesAirportBackground();
        //return ConfigHelper::get('env_S3Path_PublicImages') . '/' . ConfigHelper::get('env_S3Path_PublicImagesAirportBackground');
    }

    public static function getS3KeyPath_ImagesUserSubmittedBug()
    {
        return getS3KeyPath_ImagesUserSubmittedBug();
        //return ConfigHelper::get('env_S3Path_PublicImagesUserSubmitted') . '/' . ConfigHelper::get('env_S3Path_PublicImagesUserSubmittedBug');
    }

    public static function getS3KeyPath_FilesAirEmployee()
    {
        return getS3KeyPath_FilesAirEmployee();
        //return ConfigHelper::get('env_S3Path_PrivateFiles') . '/' . ConfigHelper::get('env_S3Path_PrivateFilesAirEmployee');
    }

    public static function getS3KeyPath_ImagesUserSubmittedProfile()
    {
        return getS3KeyPath_ImagesUserSubmittedProfile();
        //return ConfigHelper::get('env_S3Path_PublicImagesUserSubmitted') . '/' . ConfigHelper::get('env_S3Path_PublicImagesUserSubmittedProfile');
    }

    public static function getS3KeyPath_ImagesCouponLogo()
    {
        return getS3KeyPath_ImagesCouponLogo();
        //return ConfigHelper::get('env_S3Path_PublicImagesAirportSpecific') . '/' . strtoupper($airportIataCode) . '/' . ConfigHelper::get('env_S3Path_PublicImagesRetailerLogo');
    }
}
