<?php
namespace App\Background\Services;

use App\Background\Entities\MenuUpdateRetailerUniqueId;
use App\Background\Entities\MenuUpdateRetailerUniqueIdList;
use App\Background\Helpers\CsvHelper;
use App\Background\Helpers\MenuUpdateHelper;

class MenuUpdateService
{
    const MENU_FILES_POSTFIXES = [
        '-items.csv',
        '-itemTimes.csv',
        '-modifiers.csv',
        '-modifierOptions.csv',
    ];

    const ITEM_IMAGES_DIR = 'itemImages';

    const ROOT_PATH = __DIR__ . '/../../../';
    const STORAGE_PATH = __DIR__ . '/../../../storage/airport_specific_update';
    /**
     * @var MenuUpdateRetailerUniqueIdList
     */
    private $retailerUniqueIdLoadDataList;
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
        MenuUpdateRetailerUniqueIdList $retailerUniqueIdLoadDataList,
        S3Service $s3Service,
        CacheService $cacheService,
        SlackService $slackService
    ) {
        $this->retailerUniqueIdLoadDataList = $retailerUniqueIdLoadDataList;
        $this->s3Service = $s3Service;
        $this->cacheService = $cacheService;
        $this->slackService = $slackService;
    }

    private function getCurrentEtagMd5(MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData): string
    {
        $fileNameAndPathPrefix = $retailerUniqueIdLoadData->getRetailerDataPath() . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-' . $retailerUniqueIdLoadData->getRetailerDataDirName();

        $lastModified = [];
        foreach (self::MENU_FILES_POSTFIXES as $filePostfix) {
            if ($this->s3Service->doesObjectExist($fileNameAndPathPrefix . $filePostfix)) {
                $lastModified[] = $this->s3Service->getEtag($fileNameAndPathPrefix . $filePostfix);
            } else {
                $lastModified[] = '';
            }
        }

        return md5(implode(',', $lastModified));
    }


    public function updateByAirportIataCode(string $airportIataCode, bool $silentMode)
    {
        if (!$silentMode) {
            $this->slackService->sendMessage(':raised_hands: Menu Data Update for *' . $airportIataCode . ' started*');
        }
        $list = $this->retailerUniqueIdLoadDataList->getByAirportIataCode($airportIataCode);

        /** @var MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData */
        foreach ($list as $retailerUniqueIdLoadData) {
            $retailerShortName = $retailerUniqueIdLoadData->getAirportIataCode() . ' - ' . $retailerUniqueIdLoadData->getRetailerDataDirName();
            if (!$silentMode) {
                $this->slackService->sendMessage('Menu Data Update for *' . $retailerShortName . ' started*');
            }
            $fullDataDirectory = self::STORAGE_PATH . '/' . $retailerUniqueIdLoadData->getRetailerDataPath();

            $currentMd5 = $this->getCurrentEtagMd5($retailerUniqueIdLoadData);
            $lastMd5 = $this->cacheService->getMenuLoaderMd5ForRetailer($retailerUniqueIdLoadData->getRetailerUniqueId());

            if ($currentMd5 == $lastMd5) {
                if (!$silentMode) {
                    $this->slackService->sendMessage(':ok_hand: No changes found for ' . $retailerShortName);
                    $this->slackService->sendMessage('Menu Data Update for *' . $retailerShortName . ' ended*');
                }
                continue;
            }

            if (!$silentMode) {
                $this->slackService->sendMessage('Clearing files for ' . $retailerShortName);
            }
            //$this->clearLocalStorageForRetailer($retailerUniqueIdLoadData);

            if (!$silentMode) {
                $this->slackService->sendMessage('Downloading files for ' . $retailerShortName);
            }
            $this->downloadMenuToLocalStorage($retailerUniqueIdLoadData);

            $updateSuccess = false;
            for ($i = 0; $i < 2; $i++) {
                if (!$silentMode) {
                    $this->slackService->sendMessage(':heavy_check_mark: Menu Data Update for *' . $retailerShortName . ' check no. ' . ($i + 1) . '*');
                }
                if ($i == 0) {
                    // do not log when this is first call
                    $isEverythingUpToDate = $this->updateMenuForRetailer($retailerUniqueIdLoadData, $fullDataDirectory,
                        false, $silentMode);
                } else {
                    $isEverythingUpToDate = $this->updateMenuForRetailer($retailerUniqueIdLoadData, $fullDataDirectory,
                        true, $silentMode);
                }

                if ($isEverythingUpToDate) {
                    $updateSuccess = true;
                    break;
                }
            }

            if (!$updateSuccess) {
                $this->slackService->sendMessage(':x: Menu Data Update for *' . $retailerShortName . ' FAILED (2 tries)*');
                break;
            }


            $this->cacheService->resetRetailerMenuCache($retailerUniqueIdLoadData->getRetailerUniqueId());
            $this->cacheService->setMenuLoaderMd5ForRetailer($retailerUniqueIdLoadData->getRetailerUniqueId(),
                $currentMd5);
            //$this->slackService->sendMessage('Cache updated for ' . $retailerShortName);
            if (!$silentMode) {
                $this->slackService->sendMessage('Menu Data Update for *' . $retailerShortName . ' ended*');
            }
        }
        if (!$silentMode) {
            $this->slackService->sendMessage(':+1: Menu Data Update for *' . $airportIataCode . ' ended*');
        }
    }

    private function updateMenuForRetailer(
        MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData,
        $retailerDirectoryName,
        $informAboutChanges,
        bool $silentMode
    ) {
        $airportIataCode = $retailerUniqueIdLoadData->getAirportIataCode();
        $retailerUniqueId = $retailerUniqueIdLoadData->getRetailerUniqueId();
        $retailerDirName = $retailerUniqueIdLoadData->getRetailerDataDirName();

        $retailerShortName = $airportIataCode . '-' . $retailerDirName;

        echo 'getting menu from db' . PHP_EOL;
        $menuInDb = $this->getMenuFromDb($retailerUniqueId);

        echo 'getting menu from files' . PHP_EOL;
        $newMenu = $this->pullMenuFromFiles($retailerDirectoryName, $airportIataCode . '-' . $retailerDirName,
            $retailerUniqueId);


        $itemsToAdd = [];
        $itemsToModify = [];
        $itemsToLeaveLikeItIs = [];
        $itemsToDelete = $menuInDb;
        echo 'comparing' . PHP_EOL;

        foreach ($newMenu as $newItem) {
            $toAdd = true;
            foreach ($menuInDb as $existingItem) {
                if ($existingItem['uniqueId'] == $newItem['uniqueId']) {
                    // check if we should do anything

                    if (MenuUpdateHelper::getItemMeat($existingItem) == MenuUpdateHelper::getItemMeat($newItem)) {
                        $itemsToLeaveLikeItIs[] = $existingItem;
                    } else {
                        $itemsToModify[] = $existingItem;
                        if ($informAboutChanges) {
                            $this->slackService->sendMessage(
                                $retailerShortName . ' found item that should be updated, compared values looks like:' . "\n" .
                                'Existing: ' . MenuUpdateHelper::getItemMeat($existingItem) . "\n" .
                                'New: ' . MenuUpdateHelper::getItemMeat($newItem)
                            );

                        }
                        var_dump('item to edit found for ' . $retailerShortName);
                        var_dump(MenuUpdateHelper::getItemMeat($existingItem), MenuUpdateHelper::getItemMeat($newItem));
                    }

                    $itemsToDelete = MenuUpdateHelper::removeFromListByUniqueId($itemsToDelete, $newItem['uniqueId']);
                    $toAdd = false;
                    break;
                }
            }
            if ($toAdd) {
                $itemsToAdd[] = $newItem;
            }
        }

        $updateStats = $retailerShortName . ':' . "\n
        Menu in DB: " . count($menuInDb) . "\n
        Menu in Files: " . count($newMenu) . "\n
        Items to add: " . count($itemsToAdd) . "\n
        Items to delete: " . count($itemsToDelete) . "\n
        Items to update: " . count($itemsToModify) . "\n
        Items to leave like it is: " . count($itemsToLeaveLikeItIs) . "     
        ";
        if (!$silentMode) {
            $this->slackService->sendMessage($updateStats);
        }
        var_dump($updateStats);

        $imagePath = $this->getRetailerLocalImagesPath($retailerUniqueIdLoadData);

        $shouldRetailerBeOpenAfterUpdate = false;
        $isRetailerClosed = $this->isRetailerClosed($retailerUniqueIdLoadData->getRetailerUniqueId());
        if (!$isRetailerClosed) {
            $this->setRetailerClosedEarlyForNewOrders($retailerUniqueIdLoadData->getRetailerUniqueId());
            if (!$silentMode) {
                $this->slackService->sendMessage($retailerShortName . ' closed new orders');
            }
            $shouldRetailerBeOpenAfterUpdate = true;
        } else {
            if (!$silentMode) {
                $this->slackService->sendMessage($retailerShortName . ' already closed');
            }
        }

        if (!$silentMode) {
            $this->slackService->sendMessage($retailerShortName . ' deleting');
        }
        $this->manageDelete($retailerUniqueId, $itemsToDelete);
        if (!$silentMode) {
            $this->slackService->sendMessage($retailerShortName . ' updating');
        }
        $this->manageNewVersion($retailerUniqueId, $itemsToModify, $newMenu, $retailerDirectoryName, $airportIataCode,
            $imagePath);
        if (!$silentMode) {
            $this->slackService->sendMessage($retailerShortName . ' adding');
        }
        $this->manageAdd($retailerUniqueId, $itemsToAdd, $newMenu, $retailerDirectoryName, $airportIataCode,
            $imagePath);


        // @todo refresh cache
        if ($shouldRetailerBeOpenAfterUpdate) {
            $this->setRetailerOpen($retailerUniqueIdLoadData->getRetailerUniqueId());
            if (!$silentMode) {
                $this->slackService->sendMessage($retailerShortName . ' opened after update');
            }
        }

        if ((count($itemsToLeaveLikeItIs) == count($menuInDb)) && (count($itemsToLeaveLikeItIs) == count($newMenu))) {
            return true;
        }
        return false;
    }

    private function getMenuFromDb($retailerUniqueId)
    {
        // @todo - class structure
        $menuAsArray = pullMenuForCompare($retailerUniqueId);
        return $menuAsArray;
    }

    private function pullMenuFromFiles($dir, $retailerName, $retailerUniqueId)
    {
        // @todo - class structure
        $items = CsvHelper::arrayFromCSV($dir . '/' . $retailerName . '-items.csv', true);
        foreach ($items as $k => $v) {
            $items[$k]['uniqueRetailerId'] = str_replace(['UNIQUE_RETAILER_ID', 'unique_retailer_id'],
                $retailerUniqueId,
                $v['uniqueRetailerId']);
        }
        if (file_exists($dir . '/' . $retailerName . '-itemTimes.csv')) {
            $itemTimes = CsvHelper::arrayFromCSV($dir . '/' . $retailerName . '-itemTimes.csv', true);
        } else {
            $itemTimes = [];
        }
        if (file_exists($dir . '/' . $retailerName . '-modifiers.csv')) {
            $modifiers = CsvHelper::arrayFromCSV($dir . '/' . $retailerName . '-modifiers.csv', true);
        } else {
            $modifiers = [];
        }
        if (file_exists($dir . '/' . $retailerName . '-modifierOptions.csv')) {
            $modifierOptions = CsvHelper::arrayFromCSV($dir . '/' . $retailerName . '-modifierOptions.csv', true);
        } else {
            $modifierOptions = [];
        }

        return MenuUpdateHelper::modifyMenuToDbArrayForm($items, $itemTimes, $modifiers, $modifierOptions);
    }


    private function manageDelete($retailerUniqueId, $itemsToDelete)
    {
        $ids = [];
        foreach ($itemsToDelete as $item) {
            $ids[] = $item['itemId'];
        }
        $inactiveTimestamp = time();

        updateMenuDeleteItems($retailerUniqueId, $ids, $inactiveTimestamp);
    }

    private function manageAdd(
        $retailerUniqueId,
        $itemsToUpdate,
        $newMenu,
        $retailerDirectoryName,
        $airportIataCode,
        $imagePath
    ) {
        $ids = [];
        foreach ($itemsToUpdate as $item) {
            $ids[] = $item['itemId'];
        }
        $retailerInfo = getRetailerInfo($retailerUniqueId);


        updateMenuInsertItems($retailerUniqueId, $ids, $newMenu, '', $airportIataCode, $retailerDirectoryName,
            $retailerInfo, false, [], [], $imagePath);
    }

    private function manageNewVersion(
        $retailerUniqueId,
        $itemsToUpdate,
        $newMenu,
        $retailerDirectoryName,
        $airportIataCode,
        $imagePath
    ) {
        $ids = [];
        foreach ($itemsToUpdate as $item) {
            $ids[] = $item['itemId'];
        }

        $retailerInfo = getRetailerInfo($retailerUniqueId);
        updateMenuUpdateItems($retailerUniqueId, $ids, $newMenu, '', time(), $airportIataCode, $retailerDirectoryName,
            $retailerInfo,
            false, [],
            [], $imagePath
        );
    }

    private function clearLocalStorageForRetailer(MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData)
    {
        $retailerMenuPath = $this->getRetailerLocalMenuPath($retailerUniqueIdLoadData);

        if (!file_exists($retailerMenuPath)) {
            return true;
        }

        // item Images
        if (file_exists($retailerMenuPath . '/' . self::ITEM_IMAGES_DIR)) {
            $files = glob($retailerMenuPath . '/' . self::ITEM_IMAGES_DIR . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($retailerMenuPath . '/' . self::ITEM_IMAGES_DIR);
        }

        // csv files
        $files = glob($retailerMenuPath . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($retailerMenuPath);
    }

    private function downloadMenuToLocalStorage(MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData)
    {
        $retailerMenuPath = $this->getRetailerLocalMenuPath($retailerUniqueIdLoadData);
        if (!file_exists($retailerMenuPath)) {
            $this->createRetailerLocalMenuPath($retailerUniqueIdLoadData);
        }

        // copy csv files
        $fileNameAndPathPrefix = $retailerUniqueIdLoadData->getRetailerDataPath() . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-' . $retailerUniqueIdLoadData->getRetailerDataDirName();
        foreach (self::MENU_FILES_POSTFIXES as $filePostfix) {
            if ($this->s3Service->doesObjectExist($fileNameAndPathPrefix . $filePostfix)) {
                $this->s3Service->downloadFile(
                    $fileNameAndPathPrefix . $filePostfix,
                    $this->getRetailerLocalMenuPath($retailerUniqueIdLoadData) . '/' .
                    $retailerUniqueIdLoadData->getAirportIataCode() . '-' . $retailerUniqueIdLoadData->getRetailerDataDirName() . $filePostfix
                );
            }
        }

        if (!file_exists($this->getRetailerLocalMenuPath($retailerUniqueIdLoadData) . '/' . self::ITEM_IMAGES_DIR)) {
            mkdir($this->getRetailerLocalMenuPath($retailerUniqueIdLoadData) . '/' . self::ITEM_IMAGES_DIR);
        }

        $imageFiles = $this->s3Service->listFiles($retailerUniqueIdLoadData->getRetailerDataPath() . '/' . self::ITEM_IMAGES_DIR);

        foreach ($imageFiles as $imageFile) {
            $fileExists = $this->s3Service->doesObjectExist($retailerUniqueIdLoadData->getRetailerDataPath() . '/' . self::ITEM_IMAGES_DIR . '/' . $imageFile);
            if (!$fileExists) {
                continue;
            }

            $this->createAdditionalDirectoriesBasedOnFileName($this->getRetailerLocalMenuPath($retailerUniqueIdLoadData) . '/' . self::ITEM_IMAGES_DIR,
                $imageFile);

            $this->s3Service->downloadFile(
                $retailerUniqueIdLoadData->getRetailerDataPath() . '/' . self::ITEM_IMAGES_DIR . '/' . $imageFile,
                $this->getRetailerLocalMenuPath($retailerUniqueIdLoadData) . '/' . self::ITEM_IMAGES_DIR . '/' . $imageFile
            );
        }
    }

    private function createAdditionalDirectoriesBasedOnFileName(string $imageDir, string $fileName)
    {
        $fileName = explode('/', $fileName);
        for ($i = 0; $i < (count($fileName) - 1); $i++) {
            $additionalDir = array_slice($fileName, 0, ($i + 1));
            $additionalDir = implode('/', $additionalDir);
            if (!file_exists($imageDir . '/' . $additionalDir)) {
                mkdir($imageDir . '/' . $additionalDir);
            }
        }
    }

    private function createRetailerLocalMenuPath(MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData)
    {

        if (!file_exists(self::STORAGE_PATH)) {
            mkdir(self::STORAGE_PATH);
        }

        if (!file_exists(self::STORAGE_PATH . '/' . $retailerUniqueIdLoadData->getAirportIataCode())) {
            mkdir(self::STORAGE_PATH . '/' . $retailerUniqueIdLoadData->getAirportIataCode());
        }

        if (!file_exists(self::STORAGE_PATH . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-data')) {
            mkdir(self::STORAGE_PATH . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-data');
        }

        if (!file_exists(self::STORAGE_PATH . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-data' . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-Menus')) {
            mkdir(self::STORAGE_PATH . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-data' . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-Menus');
        }

        mkdir($this->getRetailerLocalMenuPath($retailerUniqueIdLoadData));
    }

    private function getRetailerLocalMenuPath(MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData)
    {
        $airportMenuPath = self::STORAGE_PATH . '/' .
            $retailerUniqueIdLoadData->getAirportIataCode() . '/' .
            $retailerUniqueIdLoadData->getAirportIataCode() . '-data/' .
            $retailerUniqueIdLoadData->getAirportIataCode() . '-Menus';


        return $airportMenuPath . '/' . $retailerUniqueIdLoadData->getAirportIataCode() . '-' . $retailerUniqueIdLoadData->getRetailerDataDirName();
    }

    private function getRetailerLocalImagesPath(MenuUpdateRetailerUniqueId $retailerUniqueIdLoadData)
    {
        return $this->getRetailerLocalMenuPath($retailerUniqueIdLoadData) . '/' . self::ITEM_IMAGES_DIR;
    }

    private function isRetailerClosed($retailerUniqueId)
    {
        // Is Retailer already closed?
        // Ensures lower level close request doesn't override a higher level
        if (isRetailerClosedEarly($retailerUniqueId) || isRetailerCloseEarlyForNewOrders($retailerUniqueId)) {
            return true;
        }
        return false;
    }

    private function setRetailerClosedEarlyForNewOrders($retailerUniqueId)
    {
        // Set cache so no new orders are accepted
        setRetailerCloseEarlyForNewOrders($retailerUniqueId, getTabletOpenCloseLevelFromSystem());
    }

    private function setRetailerOpen($retailerUniqueId)
    {
        setRetailerOpenAfterClosedEarly($retailerUniqueId, getTabletOpenCloseLevelFromSystem());
    }

}
