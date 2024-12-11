<?php
namespace App\Background\Entities;

class RetailersUpdate extends Entity
{
    private $dataDirPath;
    private $dataCsvPath;
    private $logosDirPath;
    private $backgroundsDirPath;
    private $airportIataCode;

    const CSV_FILE_NAME_POSTFIX = '-Retailers.csv';
    const LOGOS_DIR_NAME_POSTFIX = '-imageLogo';
    const BACKGROUND_DIR_NAME_POSTFIX = '-imageBackground';

    public function __construct(
        string $airportIataCode
    ) {
        $this->airportIataCode = $airportIataCode;
        $this->dataDirPath = $airportIataCode . '/' . $airportIataCode . '-data';
        $this->dataCsvPath = $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . self::CSV_FILE_NAME_POSTFIX;
        $this->logosDirPath = $airportIataCode . '/' . $airportIataCode . self::LOGOS_DIR_NAME_POSTFIX;
        $this->backgroundsDirPath = $airportIataCode . '/' . $airportIataCode . self::BACKGROUND_DIR_NAME_POSTFIX;
    }

    public function getAirportIataCode(): string
    {
        return $this->airportIataCode;
    }

    public function getDataDirPath(): string
    {
        return $this->dataDirPath;
    }

    /**
     * @return mixed
     */
    public function getDataCsvPath()
    {
        return $this->dataCsvPath;
    }

    /**
     * @return mixed
     */
    public function getLogosDirPath()
    {
        return $this->logosDirPath;
    }

    /**
     * @return mixed
     */
    public function getBackgroundsDirPath()
    {
        return $this->backgroundsDirPath;
    }

    public function getImagesIndexesWithPath($storagePath)
    {
        return [
            "imageLogo" => [
                "S3KeyPath" => getS3KeyPath_ImagesRetailerLogo($this->getAirportIataCode()),
                "useUniqueIdInName" => "Y",
                "maxWidth" => '',
                "maxHeight" => '',
                "createThumbnail" => false,
                "imagePath" => $storagePath . '/' . $this->getLogosDirPath()
            ],
            "imageBackground" => [
                "S3KeyPath" => getS3KeyPath_ImagesRetailerBackground($this->getAirportIataCode()),
                "useUniqueIdInName" => "Y",
                "maxWidth" => '',
                "maxHeight" => '',
                "createThumbnail" => false,
                "imagePath" => $storagePath . '/' . $this->getBackgroundsDirPath()
            ]
        ];
    }

    public function getObjectKeysInArray()
    {
// Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
        return [
            "airportIataCode" => "N",
            "retailerName" => "N",
            "terminal" => "X", // remove after location value is looked up
            "concourse" => "X", // remove after location value is looked up
            "gate" => "X", // remove after location value is looked up
            "retailerType" => "N",
            "retailerCategory" => "Y", // will be an array
            "retailerPriceCategory" => "I",
            "retailerFoodSeatingType" => "Y", // will be an array
            "isChain" => "N",
            "hasPickup" => "N",
            "hasDelivery" => "N",
            "searchTags" => "Y",
            "imageLogo" => "N",
            "imageBackground" => "N",
            "description" => "N",
            "openTimesMonday" => "N",
            "closeTimesMonday" => "N",
            "openTimesTuesday" => "N",
            "closeTimesTuesday" => "N",
            "openTimesWednesday" => "N",
            "closeTimesWednesday" => "N",
            "openTimesThursday" => "N",
            "closeTimesThursday" => "N",
            "openTimesFriday" => "N",
            "closeTimesFriday" => "N",
            "openTimesSaturday" => "N",
            "closeTimesSaturday" => "N",
            "openTimesSunday" => "N",
            "closeTimesSunday" => "N",
            "isActive" => "N",
            "employeeDiscountPCT"=> "F",
            "militaryDiscountPCT" => "F",
            "employeeDiscountAllowed" => "N",
            "militaryDiscountAllowed" => "N",
        ];
    }

    public function getReferenceLookup()
    {
        return [
            "location" => array(
                "className" => "TerminalGateMap",
                "isRequired" => true,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "airportIataCode" => "airportIataCode",
                    "terminal" => "terminal",
                    "concourse" => "concourse",
                    "gate" => "gate",
                ),
                // "lookupColsType" => array(
                // 					// Column in ClassName => Column in File
                // 					"airportIataCode" => "Y", // An array
                // 				)
            ),
            "retailerType" => array(
                "className" => "RetailerType",
                "isRequired" => true,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "retailerType" => "retailerType",
                )
            ),
            "retailerCategory" => array(
                "className" => "RetailerCategory",
                "isRequired" => true,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "retailerCategory" => "retailerCategory",
                )
            ),
            "retailerPriceCategory" => array(
                "className" => "RetailerPriceCategory",
                "isRequired" => false,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "retailerPriceCategory" => "retailerPriceCategory",
                )
            ),
            "retailerFoodSeatingType" => array(
                "className" => "RetailerFoodSeatingType",
                "isRequired" => false,
                "lookupCols" => array(
                    // Column in ClassName => Column in File
                    "retailerFoodSeatingType" => "retailerFoodSeatingType",
                )
            )
        ];
    }
}
