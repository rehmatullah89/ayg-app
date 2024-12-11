<?php
namespace App\Tablet\Helpers;

/**
 * Class S3Helper
 * @package App\Tablet\Helpers
 */
class S3Helper
{
    public static function preparePublicS3URL($fileName, $keyPath, $endPoint) {
        return preparePublicS3URL($fileName, $keyPath, $endPoint);
    }

    public static function extractFilenameFromS3URL($url) {
        return extractFilenameFromS3URL($url);
    }

    public static function getS3KeyPath_FilesInvoice() {
        return getS3KeyPath_FilesInvoice();
    }

    public static function getS3KeyPath_ImagesRetailerLogo($airportIataCode) {
        return getS3KeyPath_ImagesRetailerLogo($airportIataCode);
    }

    public static function getS3KeyPath_ImagesRetailerBackground($airportIataCode) {
        return getS3KeyPath_ImagesRetailerBackground($airportIataCode);
    }

    public static function getS3KeyPath_ImagesRetailerItem($airportIataCode) {
        return getS3KeyPath_ImagesRetailerItem($airportIataCode);
    }

    public static function getS3KeyPath_ImagesDirection($airportIataCode) {
        return getS3KeyPath_ImagesDirection($airportIataCode);
    }

    public static function getS3KeyPath_ImagesAirportBackground() {
        return getS3KeyPath_ImagesAirportBackground();
    }

    public static function getS3KeyPath_ImagesUserSubmittedBug() {
        return getS3KeyPath_ImagesUserSubmittedBug();
    }

    public static function getS3KeyPath_FilesAirEmployee() {
        return getS3KeyPath_FilesAirEmployee();
    }

    public static function getS3KeyPath_ImagesUserSubmittedProfile() {
        return getS3KeyPath_ImagesUserSubmittedProfile();
    }
}