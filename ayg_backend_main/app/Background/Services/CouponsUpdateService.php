<?php
namespace App\Background\Services;

use App\Background\Entities\CouponsUpdate;
use Parse\ParseQuery;

class CouponsUpdateService
{
    const STORAGE_PATH = __DIR__ . '/../../../storage/all_airports_update';

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


    public function update()
    {
        $this->slackService->sendMessage(':raised_hands: Coupons Update started');
        $couponsUpdate = new CouponsUpdate();

        $currentMd5 = $this->getCurrentLastModifiedMd5($couponsUpdate);
        $lastMd5 = $this->cacheService->getCouponsLoaderMd5ForAirport();

        if ($currentMd5 == $lastMd5) {
            $this->slackService->sendMessage(':ok_hand: No changes found');
            $this->slackService->sendMessage(':raised_hands: Coupons Update ended');
            return;
        }

        $this->slackService->sendMessage(':briefcase: Changes found');
        $this->slackService->sendMessage(':hourglass: Downloading files');
        $this->downloadCouponsDataToLocalStorage($couponsUpdate);
        $this->slackService->sendMessage(':+1: Files downloaded, update in progress...');


        $objectKeyIsArray = $couponsUpdate->getObjectKeysInArray();
        $imagesIndexesWithPaths = $couponsUpdate->getImagesIndexesWithPath(self::STORAGE_PATH);
        $referenceLookup = $couponsUpdate->getReferenceLookup();

        $fileArray = array_map('str_getcsv', file(self::STORAGE_PATH . '/' . $couponsUpdate->getDataCsvPath()));
        $objectKeys = array_map('trim', array_shift($fileArray));

        // check which ones we have and which ones we want to update, or archive and add new one

        try {
            // set all coupons as inActive, then update


            $this->slackService->sendMessage(':hourglass: Cleaning current coupons');
            $objParseQuery = new \Parse\ParseQuery('Coupons');
            $objParseQuery->limit(500);
            $objParseQuery->matches('couponCode', '^(?!_).*');
            $list = $objParseQuery->find();
            var_dump(count($list));
            foreach ($list as $item) {
                var_dump($item->get('couponCode'));
                $couponCode = $item->get('couponCode');
                $item->set('couponCode', '_' . $couponCode);
                $item->set('isActive', false);
                $item->save();
            }
            $this->slackService->sendMessage(':+1: Coupons cleaned');
            $this->slackService->sendMessage(':hourglass: Adding new coupons');

            $uniqueCodes = [];
            $groupIdKey = array_search("groupId", $objectKeys);
            $couponCodeKey = array_search("couponCode", $objectKeys);
            foreach ($fileArray as $index => $object) {
                // Check coupon code doesn't start with an Z
                if (preg_match("/^Z(.*)/si", $object[$couponCodeKey])) {
                    throw new \Exception($object[$couponCodeKey] . " - Coupon code cannot begin with an Z");
                }
                // Lower case the coupon code
                $object[$couponCodeKey] = strtolower($object[$couponCodeKey]);
                if (in_array($object[$couponCodeKey], $uniqueCodes)) {
                    throw new \Exception($object[$couponCodeKey] . " - duplicate");
                }
                $uniqueCodes[] = $object[$couponCodeKey];
                // List all coupons (unique list)
                $fileArrayCouponsUnique[$object[$couponCodeKey]] = 1;
                // Save it back to array
                $fileArray[$index] = $object;
                // Group Id list
                $groupIdList = explode(";", $object[$groupIdKey]);
                foreach ($groupIdList as $groupId) {
                    $groupId = trim($groupId);
                    if (empty($groupId)) {
                        continue;
                    }
                    // Save it back to array
                    $fileArrayCouponGroup[] = [$object[$couponCodeKey], $groupId];
                }
            }


            $totalReport = prepareAndPostToParse($GLOBALS['env_ParseApplicationId'], $GLOBALS['env_ParseRestAPIKey'],
                "Coupons", $fileArray, $objectKeyIsArray,
                $objectKeys, "N", [
                    'couponCode',
                ], $imagesIndexesWithPaths,
                $referenceLookup
                , true, [], true, true); // the second to last array lists the keys to combine to make a lookupkey


        } catch (\Exception $exception) {
            $this->slackService->sendMessage(':bangbang: ERROR ' . $exception->getMessage() . '. Script stopped');
            $this->slackService->sendMessage(':bangbang: ERROR ' . $exception->getFile() . '. Script stopped');
            $this->slackService->sendMessage(':bangbang: ERROR ' . $exception->getLine() . '. Script stopped');
            $this->slackService->sendMessage(':bangbang: ERROR ' . $exception->getTraceAsString() . '. Script stopped');
        }


        $this->cacheService->resetCouponsCache();
        $this->cacheService->setCouponsLoaderMd5($currentMd5);

        if (isset($totalReport) && is_array($totalReport)) {
            $this->slackService->sendMessage(':+1: total: *' . $totalReport['total'] . '*');
            $this->slackService->sendMessage(':+1: inserted: *' . $totalReport['inserted'] . '*');
            $this->slackService->sendMessage(':+1: updated: *' . $totalReport['updated'] . '*');
            $this->slackService->sendMessage(':+1: failed: *' . $totalReport['failed'] . '*');
        }
        $this->slackService->sendMessage('Coupons Update Ended');
    }


    private function getCurrentLastModifiedMd5(CouponsUpdate $CouponsUpdate): string
    {
        $lastModified = [];
        if ($this->s3Service->doesObjectExist($CouponsUpdate->getDataCsvPath())) {
            $lastModified[] = $this->s3Service->getLastModified($CouponsUpdate->getDataCsvPath());
        } else {
            $lastModified[] = '';
        }

        return md5(implode(',', $lastModified));
    }

    private function downloadCouponsDataToLocalStorage(CouponsUpdate $couponsUpdate)
    {
        $this->createCouponsDataDirPath($couponsUpdate);

        // copy csv files
        $fileNameAndPath = $couponsUpdate->getDataCsvPath();
        if ($this->s3Service->doesObjectExist($fileNameAndPath)) {
            $this->s3Service->downloadFile(
                $fileNameAndPath,
                self::STORAGE_PATH . '/' . $couponsUpdate->getDataCsvPath()
            );
        }

        $logosFiles = $this->s3Service->listFiles($couponsUpdate->getLogosDirPath());
        foreach ($logosFiles as $imageFile) {
            $this->s3Service->downloadFile(
                $couponsUpdate->getLogosDirPath() . '/' . $imageFile,
                self::STORAGE_PATH . '/' . $couponsUpdate->getLogosDirPath() . '/' . $imageFile
            );
        }

    }

    private function createCouponsDataDirPath(CouponsUpdate $couponsUpdate)
    {
        $this->createCouponsStartPath();

        if (!file_exists(self::STORAGE_PATH . '/' . $couponsUpdate->getDataDirPath())) {
            mkdir(self::STORAGE_PATH . '/' . $couponsUpdate->getDataDirPath());
        }

        if (!file_exists(self::STORAGE_PATH . '/' . $couponsUpdate->getLogosDirPath())) {
            mkdir(self::STORAGE_PATH . '/' . $couponsUpdate->getLogosDirPath());
        }
    }

    private function createCouponsStartPath()
    {
        if (!file_exists(self::STORAGE_PATH)) {
            mkdir(self::STORAGE_PATH);
        }
    }
}
