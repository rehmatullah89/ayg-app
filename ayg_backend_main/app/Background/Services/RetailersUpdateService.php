<?php
namespace App\Background\Services;

use App\Background\Entities\MenuUpdateRetailerUniqueId;
use App\Background\Entities\MenuUpdateRetailerUniqueIdList;
use App\Background\Entities\RetailersUpdate;
use App\Background\Exceptions\RetailerUpdateException;
use App\Background\Helpers\CsvHelper;
use App\Background\Helpers\MenuUpdateHelper;
use App\Background\Helpers\UpdateHelper;
use Parse\ParseQuery;

class RetailersUpdateService
{
    const STORAGE_PATH = __DIR__ . '/../../../storage/airport_specific_update';

    /**
     * @var S3Service
     */
    private $s3Service;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var SlackService
     */
    private $slackService;

    public function __construct(
        S3Service $s3Service,
        CacheService $cacheService,
        SlackService $slackService
    ) {
        $this->s3Service = $s3Service;
        $this->cacheService = $cacheService;
        $this->slackService = $slackService;
    }


    public function updateByAirportIataCode(string $airportIataCode, bool $skipUniqueIdGeneration, bool $silentMode)
    {
        if (!$silentMode) {
            $this->slackService->sendMessage(':raised_hands: Retailers Update for *' . $airportIataCode . ' started*');
        }
        $retailerUpdate = new RetailersUpdate($airportIataCode);

        $currentMd5 = $this->getCurrentEtagMd5($retailerUpdate);
        $lastMd5 = $this->cacheService->getRetailerLoaderMd5($retailerUpdate->getAirportIataCode());

        if ($currentMd5 == $lastMd5) {
            if (!$silentMode) {
                $this->slackService->sendMessage(':ok_hand: No changes found for ' . $retailerUpdate->getAirportIataCode());
                $this->slackService->sendMessage(':raised_hands: Retailers Update for *' . $retailerUpdate->getAirportIataCode() . ' ended*');
            }
            return;
        }

        if (!$silentMode) {
            $this->slackService->sendMessage(':briefcase: Changes found for ' . $retailerUpdate->getAirportIataCode());
            $this->slackService->sendMessage(':hourglass: Downloading files for ' . $retailerUpdate->getAirportIataCode());
        }
        $this->downloadRetailersDataToLocalStorage($retailerUpdate);

        if (!$silentMode) {
            $this->slackService->sendMessage(':+1: Files downloaded');
        }

        $fileArray = array_map('str_getcsv', file(self::STORAGE_PATH . '/' . $retailerUpdate->getDataCsvPath()));
        $imagesIndexesWithPaths = $retailerUpdate->getImagesIndexesWithPath(self::STORAGE_PATH);
        $objectKeys = array_map('trim', array_shift($fileArray));

        // Array lists which columns are Arrays (with semi-colon separated values) with a Y are columns, G for GeoPoints, I is integer, N neither, X don't use as data for upload
        $objectKeyIsArray = $retailerUpdate->getObjectKeysInArray();

        $referenceLookup = $retailerUpdate->getReferenceLookup();

        $missingValues['RetailerCategory'] = $this->verifyNewValues($fileArray, "RetailerCategory", "retailerCategory",
            "retailerCategory", array_search("retailerCategory", $objectKeys), $objectKeyIsArray);
        $missingValues['RetailerPriceCategory'] = $this->verifyNewValues($fileArray, "RetailerPriceCategory",
            "retailerPriceCategory", "retailerPriceCategory", array_search("retailerPriceCategory", $objectKeys),
            $objectKeyIsArray);
        $missingValues['RetailerFoodSeatingType'] = $this->verifyNewValues($fileArray, "RetailerFoodSeatingType",
            "retailerFoodSeatingType", "retailerFoodSeatingType", array_search("retailerFoodSeatingType", $objectKeys),
            $objectKeyIsArray);
        $missingValues['RetailerType'] = $this->verifyNewValues($fileArray, "RetailerType", "retailerType",
            "retailerType",
            array_search("retailerType", $objectKeys), $objectKeyIsArray);

        foreach ($missingValues as $key => $item) {
            if (count_like_php5($item) > 0) {
                foreach ($item as $k => $v) {
                    $this->slackService->sendMessage(':bangbang: Missed data for *' . $key . '* : missed: _' . $v . '_');
                }
                $this->slackService->sendMessage(':+1: script has not been stopped anyway');
            }
        }

        try {
            if ($skipUniqueIdGeneration) {
                $generateUniqueId = 'N';
                $objectKeyIsArray['uniqueId'] = 'N';
                $duplicateLookupKeyArray = array(
                    "uniqueId"
                );
            } else {
                $generateUniqueId = 'Y';
                $duplicateLookupKeyArray = array("airportIataCode", "retailerName", "terminal", "concourse", "gate");
            }

            $totalReport = prepareAndPostToParse($GLOBALS['env_ParseApplicationId'], $GLOBALS['env_ParseRestAPIKey'],
                "Retailers",
                $fileArray, $objectKeyIsArray, $objectKeys, $generateUniqueId, $duplicateLookupKeyArray,
                $imagesIndexesWithPaths,
                $referenceLookup, false, [], false,
                true); // the second to last array lists the keys to combine to make a lookupkey

        } catch (\Exception $exception) {

            $this->slackService->sendMessage(':bangbang: ERROR ' . $exception->getMessage() . '. Script stopped');
        }

        $this->cacheService->resetRetailersCache();
        $this->cacheService->setRetailerLoaderMd5ForAirport($retailerUpdate->getAirportIataCode(), $currentMd5);

        if (isset($totalReport) && is_array($totalReport)) {
            if (!$silentMode) {
                $this->slackService->sendMessage(':+1: total: *' . $totalReport['total'] . '*');
                $this->slackService->sendMessage(':+1: inserted: *' . $totalReport['inserted'] . '*');
                $this->slackService->sendMessage(':+1: updated: *' . $totalReport['updated'] . '*');
                $this->slackService->sendMessage(':+1: failed: *' . $totalReport['failed'] . '*');
            }
        }

        if (!$silentMode) {
            $this->slackService->sendMessage('Retailers Update for *' . $airportIataCode . ': ended*');
        }
    }


    private function getCurrentEtagMd5(RetailersUpdate $retailersUpdate): string
    {
        $etag = [];
        if ($this->s3Service->doesObjectExist($retailersUpdate->getDataCsvPath())) {
            $etag[] = $this->s3Service->getEtag($retailersUpdate->getDataCsvPath());
        } else {
            $etag[] = '';
        }

        $logos = $this->s3Service->listFiles($retailersUpdate->getLogosDirPath());
        $background = $this->s3Service->listFiles($retailersUpdate->getBackgroundsDirPath());

        foreach ($logos as $k => $v) {
            $etag[] = $this->s3Service->getEtag($retailersUpdate->getLogosDirPath() . '/' . $v);
        }

        foreach ($background as $k => $v) {
            $etag[] = $this->s3Service->getEtag($retailersUpdate->getBackgroundsDirPath() . '/' . $v);
        }

        return md5(implode(',', $etag));
    }

    private function downloadRetailersDataToLocalStorage(RetailersUpdate $retailerUpdate)
    {
        $this->createAirportRetailerDataDirPath($retailerUpdate);
        $this->createAirportRetailerLogoDirPath($retailerUpdate);
        $this->createAirportRetailerBackgroundDirPath($retailerUpdate);

        // copy csv files
        $fileNameAndPath = $retailerUpdate->getDataCsvPath();
        if ($this->s3Service->doesObjectExist($fileNameAndPath)) {
            $this->s3Service->downloadFile(
                $fileNameAndPath,
                self::STORAGE_PATH . '/' . $retailerUpdate->getDataCsvPath()
            );
        }

        $logosFiles = $this->s3Service->listFiles($retailerUpdate->getLogosDirPath());
        foreach ($logosFiles as $imageFile) {
            $this->s3Service->downloadFile(
                $retailerUpdate->getLogosDirPath() . '/' . $imageFile,
                self::STORAGE_PATH . '/' . $retailerUpdate->getLogosDirPath() . '/' . $imageFile
            );
        }

        $backgroundFiles = $this->s3Service->listFiles($retailerUpdate->getBackgroundsDirPath());
        foreach ($backgroundFiles as $imageFile) {
            $this->s3Service->downloadFile(
                $retailerUpdate->getBackgroundsDirPath() . '/' . $imageFile,
                self::STORAGE_PATH . '/' . $retailerUpdate->getBackgroundsDirPath() . '/' . $imageFile
            );
        }
    }

    private function createAirportRetailerDataDirPath(RetailersUpdate $retailerUpdate)
    {
        $this->createAirportRetailerStartPath($retailerUpdate);

        if (!file_exists(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode() . '/' . $retailerUpdate->getAirportIataCode() . '-data')) {
            mkdir(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode() . '/' . $retailerUpdate->getAirportIataCode() . '-data');
        }
    }

    private function createAirportRetailerLogoDirPath(RetailersUpdate $retailerUpdate)
    {
        $this->createAirportRetailerStartPath($retailerUpdate);

        if (!file_exists(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode() . '/' . $retailerUpdate->getAirportIataCode() . '-imageLogo')) {
            mkdir(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode() . '/' . $retailerUpdate->getAirportIataCode() . '-imageLogo');
        }
    }

    private function createAirportRetailerBackgroundDirPath(RetailersUpdate $retailerUpdate)
    {
        $this->createAirportRetailerStartPath($retailerUpdate);

        if (!file_exists(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode() . '/' . $retailerUpdate->getAirportIataCode() . '-imageBackground')) {
            mkdir(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode() . '/' . $retailerUpdate->getAirportIataCode() . '-imageBackground');
        }
    }

    private function createAirportRetailerStartPath(RetailersUpdate $retailerUpdate)
    {
        if (!file_exists(self::STORAGE_PATH)) {
            mkdir(self::STORAGE_PATH);
        }

        if (!file_exists(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode())) {
            mkdir(self::STORAGE_PATH . '/' . $retailerUpdate->getAirportIataCode());
        }
    }


    function verifyNewValues(
        $fileArray,
        $className,
        $classColumnName,
        $keyName,
        $keyForValue,
        $objectKeyIsArray
    ) {
        $uniqueValues = UpdateHelper::getAllPossibleValuesInCSV($fileArray, $keyForValue);

        $missingValues = array();
        foreach ($uniqueValues as $uniqueValueOne) {

            if ($objectKeyIsArray[$keyName] == "I") {

                $uniqueValueOne = intval($uniqueValueOne);
            }

            $objParseQuery = new ParseQuery($className);
            $objParseQuery->equalTo($classColumnName, $uniqueValueOne);

            $objParseQueryResults = $objParseQuery->find();

            if (count_like_php5($objParseQueryResults) == 0) {

                if (!in_array($uniqueValueOne, $missingValues)) {

                    $missingValues[] = $uniqueValueOne;
                }
            }
        }

        return $missingValues;
    }


}
