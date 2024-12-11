<?php
namespace App\Background\Services;


use App\Background\Entities\Order;
use App\Background\Entities\OrderList;
use App\Background\Entities\Partners\Grab\OrderStatus;
use App\Background\Entities\Partners\Grab\Retailer;
use App\Background\Entities\Partners\Grab\RetailerCustomizationList;
use App\Background\Entities\Partners\Grab\RetailerItem;
use App\Background\Entities\Partners\Grab\RetailerItemCustomizationList;
use App\Background\Entities\Partners\Grab\RetailerItemInventorySub;
use App\Background\Entities\Partners\Grab\RetailerItemList;
use App\Background\Entities\Partners\Grab\RetailerList;
use App\Background\Entities\Partners\Grab\RetailerStatus;
use App\Background\Entities\Partners\Grab\RetailerStatusList;
use App\Background\Entities\Partners\Grab\RetailerWithItems;
use App\Background\Entities\RetailerPartner;
use App\Background\Entities\RetailerPartnerShortInfoList;
use App\Background\Exceptions\Exception;
use App\Background\Helpers\PartnerIntegration\Grab\RetailerItemHelper;
use App\Background\Helpers\QueueMessageHelper;
use App\Background\Helpers\SlackMessageHelper;
use App\Background\Repositories\OrderRepositoryInterface;
use App\Background\Repositories\RetailerPartnerRepositoryInterface;
use App\Background\Repositories\RetailerPOSConfigRepositoryInterface;
use App\Background\Repositories\RetailerRepositoryInterface;
use GuzzleHttp\Client;

class GrabIntegrationService implements SinglePartnerIntegrationServiceInterface
{
    const AIRPORT_LIST_PATH = 'Cursus/Cursus_PartnerDirect_GrabActiveAirports';

    const ORDER_STATUS_PATH = 'Cursus/Cursus_PartnerDirect_GetOrderStatus';

    const AIRPORT_WITH_STORES_PATH = 'Cursus/CursusPortalV2_PartnerDirect_GrabActiveAirportsWithStores';

    const RETAILER_INFO_PATH = 'Cursus/Cursus_PartnerDirect_GetStoreInventory';

    const RETAILER_IMAGE_URL_PREFIX = 'https://grabmobilewebtop.com/cursusmenuimages/';

    const RETAILER_ITEM_IMAGE_URL_PREFIX = 'https://grabmobilewebtop.com/cursusmenuimages/';

    const PARTNER_NAME = 'grab';

    /**
     * @var string
     */
    private $email;
    /**
     * @var string
     */
    private $secretKey;
    /**
     * @var S3Service
     */
    private $s3Service;
    /**
     * @var RetailerPartnerRepositoryInterface
     */
    private $retailerPartnerRepository;
    /**
     * @var RetailerPOSConfigRepositoryInterface
     */
    private $retailerPOSConfigRepository;
    /**
     * @var RetailerRepositoryInterface
     */
    private $retailerRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var SlackService
     */
    private $slackService;
    /**
     * @var CacheService
     */
    private $cacheService;
    /**
     * @var string
     */
    private $mainApiUrl;


    public function __construct(
        string $email,
        string $mainApiUrl,
        string $secretKey,
        S3Service $s3Service,
        RetailerPartnerRepositoryInterface $retailerPartnerRepository,
        RetailerPOSConfigRepositoryInterface $retailerPOSConfigRepository,
        RetailerRepositoryInterface $retailerRepository,
        OrderRepositoryInterface $orderRepository,
        SlackService $slackService,
        CacheService $cacheService
    ) {
        $this->email = $email;
        $this->secretKey = $secretKey;
        $this->s3Service = $s3Service;
        $this->retailerPartnerRepository = $retailerPartnerRepository;
        $this->retailerPOSConfigRepository = $retailerPOSConfigRepository;
        $this->retailerRepository = $retailerRepository;
        $this->orderRepository = $orderRepository;
        $this->slackService = $slackService;
        $this->cacheService = $cacheService;
        $this->mainApiUrl = $mainApiUrl;
    }

    public function handleCanceledOrders()
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName(self::PARTNER_NAME);
        $retailers = $this->retailerRepository->getRetailersByUniqueIdArray($retailerPartnerList->getRetailerUniqueIdArray());
        $retailerIds = $retailers->getRetailerIdArray();
        $orderList = $this->orderRepository->getActiveOrdersListByRetailerIdList($retailerIds);

        /** @var Order $order */
        foreach ($orderList as $order) {
            $orderStatus = new OrderStatus(
                $order->getId(),
                $order->getPartnerOrderId(),
                null,
                null
            );
            $this->fetchOrderStatusInfo($orderStatus);
            if ($orderStatus->isCanceled()) {
                $alreadyNotified = $this->cacheService->getOrderCancelNotificationMade($order->getId());
                if ($alreadyNotified == false) {
                    $slackService = SlackServiceFactory::createSlackServiceByAirportIataCode($order->getRetailer()->getAirportIataCode());
                    $slackService->sendMessage('*Order canceled by partner tablet* OrderId: ' . $order->getOrderSequenceId());
                    $this->cacheService->setOrderCancelNotificationMade($order->getId());
                }
            }
        }
    }

    public function getRetailerRelatedFilesHash($partnerName, array $allAirportIataCodes)
    {
        $airportHashes = [];
        foreach ($allAirportIataCodes as $airportIataCode) {
            $airportHashes[$airportIataCode] = json_encode(
                $this->getRetailerPartnerShortInfoList($airportIataCode, $partnerName)
            );
        }

        return md5(json_encode($airportHashes));
    }

    public function getItemsRelatedFilesHash($partnerName, array $allAirportIataCodes)
    {
        $returnHashArray = [];
        foreach ($allAirportIataCodes as $airportIataCode) {
            $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerNameAndAirportIataCode(
                self::PARTNER_NAME,
                $airportIataCode
            );
            /** @var RetailerPartner $retailerPartner */
            foreach ($retailerPartnerList as $retailerPartner) {

                $json = $this->callGetEndpoint(self::RETAILER_INFO_PATH, [
                    'storeWaypointID' => $retailerPartner->getPartnerId()
                ]);

                $returnHashArray[$airportIataCode][$retailerPartner->getPartnerId()] = json_encode(
                    new RetailerWithItems(
                        Retailer::createFromGrabRetailerInfoJson($json, $retailerPartner->getDateTimeZone()),
                        RetailerItemList::createFromGrabRetailerInfoJson($json, $retailerPartner->getDateTimeZone())
                    )
                );
            }
        }

        return md5(json_encode($returnHashArray));
    }

    public function emulateRetailerAcceptance()
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName(self::PARTNER_NAME);
        $retailers = $this->retailerRepository->getRetailersByUniqueIdArray($retailerPartnerList->getRetailerUniqueIdArray());
        $retailerIds = $retailers->getRetailerIdArray();
        $orderList = $this->orderRepository->getActiveOrdersListByRetailerIdList($retailerIds);
        $orderList = $this->pushToRetailerOrdersThatHasSatusPaymentConfirmed($orderList);
        $this->confirmByRetailerOrdersThatHasStatusPushToRetailer($orderList);
    }

    public function pingRetailers(string $partnerName)
    {
        $airportIataCodesArray = $this->getAllAirportIataCodesByPartner($partnerName);
        foreach ($airportIataCodesArray as $airportIataCode) {
            $retailerStatusList = $this->getRetailerStatusList($airportIataCode);

            // @todo check if it is online
            /** @var RetailerStatus $retailerStatus */
            foreach ($retailerStatusList as $retailerStatus) {

                if ($retailerStatus->isBStoreIsCurrentlyOpen()) {
                    $this->cacheService->setRetailerPingTimestamp($retailerStatus->getUniqueId(),
                        (new \DateTime('now'))->getTimestamp());
                }
            }
        }
    }

    public function getItemsTo86($partnerName)
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName($partnerName);
        $airportIataCodesArray = $this->getAllAirportIataCodesByPartner($partnerName);
        foreach ($airportIataCodesArray as $airportIataCode) {
            $listByAirport = $retailerPartnerList->filterByAirportIataCode($airportIataCode);
            var_dump($airportIataCode);

            $retailerInfoList = new RetailerList();
            /** @var RetailerPartner $item */
            foreach ($listByAirport as $item) {
                $retailerInfoList->addItem(
                    $this->getRetailerInfo(
                        $item->getPartnerId(),
                        $item->getDateTimeZone()
                    )
                );
            }

            /** @var Retailer $retailer */
            foreach ($retailerInfoList as $retailer) {
                var_dump($retailer->getStoreName());
                var_dump($retailer->getStoreWaypointID());
                // update only retailers that exists and are active (so accepted)

                $retailerPartner = $retailerPartnerList->findByPartnerNameAndPartnerId(self::PARTNER_NAME,
                    $retailer->getStoreWaypointID());
                if ($retailerPartner == null) {
                    continue;
                }

                if (empty($retailerPartner->getItemsDirectoryName())) {
                    // it means it was not generated, it will be with next iteration
                    continue;
                }


                $existingRetailers = $this->retailerRepository->getRetailersByUniqueIdArray([$retailerPartner->getRetailerUniqueId()]);
                $existingRetailer = $existingRetailers->getFirst();
                if ($existingRetailer == null || $existingRetailer->getIsActive() !== true) {
                    continue;
                }

                try {
                    $retailerItemList = $this->getRetailerItemList(
                        $retailer->getStoreWaypointID(),
                        $retailer->getDateTimeZone()
                    );
                } catch (\Exception $exception) {
                    var_dump('not active store ' . $retailer->getStoreWaypointID());
                    continue;
                }

                /** RetailerItemList $retailerItemList */

                /** @var RetailerItem $retailerItem */
                foreach ($retailerItemList as $retailerItem) {
                    $is86d = $retailerItem->is86d(new \DateTime('now', $item->getDateTimeZone()));
                    if ($is86d) {
                        var_dump(RetailerItemHelper::getAllPossibleUniqueIds($retailerItem));
                    } else {
                    }
                }

            }
        }


    }

    private function getAllAirportIataCodesByPartner(string $partnerName): array
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName($partnerName);
        return $retailerPartnerList->getAllAirportIataCodes();
    }


    public function updateRetailerPartners(string $partnerName, array $allAirportIataCodes)
    {
        foreach ($allAirportIataCodes as $airportIataCode) {
            $retailerPartnerShortInfoList = $this->getRetailerPartnerShortInfoList($airportIataCode, $partnerName);

            $retailerList = $this->retailerRepository->getRetailersByUniqueIdArray($retailerPartnerShortInfoList->getUniqueIdArray());

            $this->retailerPOSConfigRepository->addNotExistingRetailerPOSConfigByRetailerList(
                $retailerList
            );

            $this->retailerPartnerRepository->addNotExistingRetailerPartnerByShortInfoList($retailerPartnerShortInfoList);
        }

        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName($partnerName);


        /** @var RetailerPartner $retailerPartner */
        foreach ($retailerPartnerList as $retailerPartner) {
            if (empty($retailerPartner->getItemsDirectoryName())) {
                $retailer = $this->getRetailerInfo(
                    $retailerPartner->getPartnerId(),
                    $retailerPartner->getDateTimeZone()
                );
                $directoryName = $retailer->generateMenuDirName($retailerPartner->getAirportIataCode());
                $retailerPartner->setItemsDirectoryName($directoryName);
                $this->retailerPartnerRepository->updateItemsDirectoryName($retailerPartner);
            }
        }
    }


    private function getRetailerInfo(int $retailerId, \DateTimeZone $dateTimeZone): Retailer
    {
        $json = $this->callGetEndpoint(self::RETAILER_INFO_PATH, [
            'storeWaypointID' => $retailerId
        ]);
        return Retailer::createFromGrabRetailerInfoJson($json, $dateTimeZone);
    }

    private function fetchOrderStatusInfo(OrderStatus $orderStatus)
    {
        $json = $this->callGetEndpoint(self::ORDER_STATUS_PATH, [
            'orderID' => $orderStatus->getPartnerId()
        ]);
        $array = json_decode($json, true);

        $orderStatus->setPartnerStatusCode($array['orderStatus']);
        $orderStatus->setPartnerStatusDisplay($array['orderStatusDisplay']);

        return $orderStatus;
    }

    private function getRetailerPartnerShortInfoList(
        string $airportIataCode,
        string $partnerName
    ): RetailerPartnerShortInfoList {
        $json = $this->callGetEndpoint(self::AIRPORT_WITH_STORES_PATH, [
            'airportident' => $airportIataCode,
            'sessionId' => $this->secretKey,
        ]);
        return RetailerPartnerShortInfoList::createFromGrabAirportInfoJson($json, $partnerName);
    }

    private function getRetailerItemList(int $retailerId, \DateTimeZone $dateTimeZone)
    {
        $json = $this->callGetEndpoint(self::RETAILER_INFO_PATH, [
            'storeWaypointID' => $retailerId
        ]);
        return RetailerItemList::createFromGrabRetailerInfoJson($json, $dateTimeZone);
    }

    private function getRetailerStatusList(
        string $airportIataCode
    ): RetailerStatusList {
        $json = $this->callGetEndpoint(self::AIRPORT_WITH_STORES_PATH, [
            'airportident' => $airportIataCode,
            'sessionId' => $this->secretKey,
        ]);
        return RetailerStatusList::createFromGrabAirportInfoJson($json);
    }

    public function notifyAboutNewRetailers(string $partnerName, $allAirportIataCodes)
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName($partnerName);
        //$allAirportIataCodes = $retailerPartnerList->getAllAirportIataCodes();

        foreach ($allAirportIataCodes as $airportIataCode) {
            $specificDir = $airportIataCode . '/' . $airportIataCode . '-data';
            $customFileName = $airportIataCode . '-Retailers-customize-from-' . $partnerName . '.csv';

            $files3Path = $specificDir . '/' . $customFileName;
            if ($this->s3Service->doesObjectExist($files3Path)) {
                $retailerItemCustomizationList = $this->getRetailerCustomizationListFromS3($files3Path);

                if ($retailerItemCustomizationList->isThereUnverifiedItem()) {
                    $this->slackService->sendMessage(SlackMessageHelper::getNewNotVerifiedRetailersMessage($customFileName));
                }
            } else {
                $this->slackService->sendMessage(SlackMessageHelper::getNoCustomRetailersFileMessage($customFileName));
            }
        }
    }

    public function notifyAboutNewItems(string $partnerName, $allAirportIataCodes)
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName($partnerName);
        /** @var RetailerPartner $retailerPartner */
        foreach ($retailerPartnerList as $retailerPartner) {
            if (empty($retailerPartner->getItemsDirectoryName())) {
                // it means it was not generated, it will be with next iteration
                continue;
            }


            $existingRetailers = $this->retailerRepository->getRetailersByUniqueIdArray([$retailerPartner->getRetailerUniqueId()]);
            $existingRetailer = $existingRetailers->getFirst();
            if ($existingRetailer == null || $existingRetailer->getIsActive() !== true) {
                continue;
            }


            $specificDir = $this->getFullPathForRetailer($retailerPartner->getAirportIataCode(),
                $retailerPartner->getItemsDirectoryName());
            $customFileName = $this->getItemCustomizeFileName($retailerPartner->getItemsDirectoryName(),
                self::PARTNER_NAME);

            $files3Path = $specificDir . '/' . $customFileName;
            if ($this->s3Service->doesObjectExist($files3Path)) {
                $retailerItemCustomizationList = $this->getRetailerItemCustomizationListFromS3($files3Path);

                if ($retailerItemCustomizationList->isThereUnverifiedItem()) {
                    // $this->slackService->sendMessage(SlackMessageHelper::getNewNotVerifiedItemsMessage($customFileName));
                }
            } else {
                // $this->slackService->sendMessage(SlackMessageHelper::getNoCustomFileMessage($customFileName));
            }
        }
    }

    public function saveAllItemsIntoS3(string $partnerName, $allAirportIataCodes)
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName($partnerName);
        //$allAirportIataCodes = $retailerPartnerList->getAllAirportIataCodes();
        foreach ($allAirportIataCodes as $airportIataCode) {
            $listByAirport = $retailerPartnerList->filterByAirportIataCode($airportIataCode);

            $retailerInfoList = new RetailerList();
            /** @var RetailerPartner $item */
            foreach ($listByAirport as $item) {
                $retailerInfoList->addItem(
                    $this->getRetailerInfo(
                        $item->getPartnerId(),
                        $item->getDateTimeZone()
                    )
                );
            }

            /** @var Retailer $retailer */
            foreach ($retailerInfoList as $retailer) {
                // update only retailers that exists and are active (so accepted)

                $retailerPartner = $retailerPartnerList->findByPartnerNameAndPartnerId(self::PARTNER_NAME,
                    $retailer->getStoreWaypointID());
                if ($retailerPartner == null) {
                    continue;
                }

                if (empty($retailerPartner->getItemsDirectoryName())) {
                    // it means it was not generated, it will be with next iteration
                    continue;
                }


                $existingRetailers = $this->retailerRepository->getRetailersByUniqueIdArray([$retailerPartner->getRetailerUniqueId()]);
                $existingRetailer = $existingRetailers->getFirst();
                if ($existingRetailer == null || $existingRetailer->getIsActive() !== true) {
                    continue;
                }

                $retailerItemList = $this->getRetailerItemList(
                    $retailer->getStoreWaypointID(),
                    $retailer->getDateTimeZone()
                );

                $retailerItemList = $this->removeItemsImageNameIfNotExistsRemotly($retailerItemList);

                $this->saveSingleRetailerAllItemsDataToFiles(
                    self::PARTNER_NAME,
                    $retailer,
                    $retailerItemList,
                    $retailerPartner->getItemsDirectoryName()
                );

                $this->storeRetailerItemImagesOnS3(
                    $retailer,
                    $retailerItemList,
                    self::PARTNER_NAME,
                    $retailerPartner->getItemsDirectoryName()
                );
            }
        }
    }

    public function saveAllRetailersIntoS3(string $partnerName, array $allAirportIataCodes)
    {
        $retailerPartnerList = $this->retailerPartnerRepository->getListByPartnerName($partnerName);

        //$allAirportIataCodes = $retailerPartnerList->getAllAirportIataCodes();


        foreach ($allAirportIataCodes as $airportIataCode) {
            $listByAirport = $retailerPartnerList->filterByAirportIataCode($airportIataCode);

            $retailerList = new RetailerList();
            /** @var RetailerPartner $item */
            foreach ($listByAirport as $item) {
                $retailerList->addItem($this->getRetailerInfo($item->getPartnerId(), $item->getDateTimeZone()));
            }

            $retailerList = $this->removeRetailersImageNamesIfNotExistsRemotly($retailerList);

            // save retailer from airport as csv

            $fileName = $airportIataCode . '-Retailers-from-' . $partnerName . '.csv';
            $this->storeRetailerListAsS3Csv($retailerList, $airportIataCode, $partnerName, $fileName, false);
            $this->storeRetailerCustomFilesIntoS3Csv($retailerList, $airportIataCode, $partnerName);
            $this->applyCustomizationsToRetailers($retailerList, $airportIataCode, $partnerName);
            $fileName = $airportIataCode . '-Retailers.csv';
            var_dump($retailerList, $airportIataCode, $partnerName, $fileName, true);
            $this->storeRetailerListAsS3Csv($retailerList, $airportIataCode, $partnerName, $fileName, true);

            // save retailer from airport as csv
            $this->storeRetailersLogosOnS3($retailerList, $airportIataCode, $partnerName);

        }
    }

    public function saveSingleRetailerAllItemsDataToFiles(
        string $partnerName,
        Retailer $retailer,
        RetailerItemList $retailerItemList,
        string $retailerDirName
    ) {
        $this->createLocalFilesDir($partnerName);
        $this->createRetailerLocalMenuPath($partnerName, $retailer->getAirportIdent(), $retailerDirName);
        $airportIataCode = $retailer->getAirportIdent();

        $tempDir = __DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update';
        $specificDir = $this->getFullPathForRetailer($airportIataCode, $retailerDirName);

        $itemsFileName = $this->getItemsFileNameWithPartnerPostfix($retailerDirName, $partnerName);
        $itemTimesFileName = $this->getItemTimesFileName($retailerDirName);
        $modifiersFileName = $this->getItemModifiersFileName($retailerDirName);
        $modifierOptionsFileName = $this->getItemModifierOptionsFileName($retailerDirName);
        $customizeFileName = $this->getItemCustomizeFileName($retailerDirName, $partnerName);

        $this->saveSingleRetailerItemsToFile($tempDir, $specificDir, $itemsFileName, $retailerItemList, false);

        $this->saveSingleRetailerItemTimesToFile($tempDir, $specificDir, $itemTimesFileName, $retailerItemList);
        $this->saveSingleRetailerModifiersToFile($tempDir, $specificDir, $modifiersFileName, $retailerItemList);
        $this->saveSingleRetailerModifierOptionsToFile($tempDir, $specificDir, $modifierOptionsFileName,
            $retailerItemList);
        $this->saveSingleRetailerItemsCustomizeToFile($tempDir, $specificDir, $customizeFileName, $retailerItemList);


        // update by customization data and save again
        $customFileName = $this->getItemCustomizeFileName($retailerDirName, self::PARTNER_NAME);
        $files3Path = $specificDir . '/' . $customFileName;
        if ($this->s3Service->doesObjectExist($files3Path)) {
            $retailerItemCustomizationList = $this->getRetailerItemCustomizationListFromS3($files3Path);
            /** @var RetailerItem $retailerItem */
            foreach ($retailerItemList as $retailerItem) {
                /** @var RetailerItemInventorySub $retailerItemInventorySub */
                foreach ($retailerItem->getRetailerItemInventorySubList() as $retailerItemInventorySub) {
                    $retailerItemInventorySub->setRetailerItemCustomization($retailerItemCustomizationList->findVerifiedByUniqueId($retailerItem->getUniqueId($retailerItemInventorySub)));
                }
            }
        }

        $itemsFileName = $this->getItemsFileName($retailerDirName);
        $this->saveSingleRetailerItemsToFile($tempDir, $specificDir, $itemsFileName, $retailerItemList, true);
    }

    private function saveSingleRetailerItemsToFile(
        string $tempDir,
        string $specificDir,
        string $fileName,
        RetailerItemList $retailerItemList,
        bool $withCustomizationOnly
    ) {
        $firstLine = 'itemCategoryName,itemSecondCategoryName,itemThirdCategoryName,itemPOSName,itemDisplayName,itemDisplayDescription,itemId,itemPrice,priceLevelId,isActive,uniqueRetailerId,uniqueId,itemImageURL,itemTags,itemDisplaySequence,taxCategory,allowedThruSecurity,version';
        $firstLine = explode(',', $firstLine);

        $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $fileName;
        $fp = fopen($localFileFullPath, 'w');

        fputcsv($fp, $firstLine);

        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            /** @var RetailerItemInventorySub $selectedSub */
            foreach ($retailerItem->getRetailerItemInventorySubList() as $selectedSub) {
                if ($withCustomizationOnly === false || $selectedSub->getRetailerItemCustomization() !== null) {
                    fputcsv($fp, array_values($retailerItem->asArrayForCsvLine($selectedSub)));
                }
            }
        }

        fclose($fp);

        $this->s3Service->copyObjectFromLocal(
            $localFileFullPath,
            $specificDir . '/' . $fileName
        );
        //unlink($localFileFullPath);
    }


    private function saveSingleRetailerItemTimesToFile(
        string $tempDir,
        string $specificDir,
        string $fileName,
        RetailerItemList $retailerItemList
    ) {
        $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $fileName;


        // we can get information only on today, so we need to grab existing file and improve it by todays data
        if ($this->s3Service->doesObjectExist($specificDir . '/' . $fileName)) {
            $this->s3Service->downloadFile($specificDir . '/' . $fileName, $localFileFullPath);
            $csv = array_map('str_getcsv', file($localFileFullPath));
        } else {
            $firstLine = 'uniqueRetailerItemId,dayOfWeek,restrictOrderTimes,prepRestrictTimesGroup1,prepTimeCategoryIdGroup1,prepRestrictTimesGroup2,prepTimeCategoryIdGroup2,prepRestrictTimesGroup3,prepTimeCategoryIdGroup3,isActive';
            $csv[0] = explode(',', $firstLine);
        }

        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            /** @var RetailerItemInventorySub $selectedSub */
            foreach ($retailerItem->getRetailerItemInventorySubList() as $selectedSub) {
                $newItemTimeEntryArray = $retailerItem->asArrayForCsvLineItemTimes($selectedSub);
                $itemHasBeenAdded = false;
                foreach ($csv as $key => $existingItemTimeEntryArray) {
                    if ($csv[$key][0] == $newItemTimeEntryArray[0] && $csv[$key][1] == $newItemTimeEntryArray[1]
                    ) {
                        $csv[$key] = $newItemTimeEntryArray;
                        $itemHasBeenAdded = true;
                    }
                }

                if ($itemHasBeenAdded === false) {
                    $csv[] = $newItemTimeEntryArray;
                }
            }
        }

        $fp = fopen($localFileFullPath, 'w');
        foreach ($csv as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        $this->s3Service->copyObjectFromLocal(
            $localFileFullPath,
            $specificDir . '/' . $fileName
        );
        //unlink($localFileFullPath);
    }


    private function saveSingleRetailerModifiersToFile(
        string $tempDir,
        string $specificDir,
        string $fileName,
        RetailerItemList $retailerItemList
    ) {
        $firstLine = 'modifierPOSName,modifierDisplayName,modifierDisplayDescription,modifierId,maxQuantity,minQuantity,isRequired,isActive,uniqueRetailerItemId,uniqueId,modifierDisplaySequence,version';
        $firstLine = explode(',', $firstLine);

        $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $fileName;
        $fp = fopen($localFileFullPath, 'w');

        fputcsv($fp, $firstLine);

        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            /** @var RetailerItemInventorySub $selectedSub */
            foreach ($retailerItem->getRetailerItemInventorySubList() as $selectedSub) {
                foreach ($retailerItem->asArrayForCsvLineModifiersFromChoices($selectedSub) as $item) {
                    fputcsv($fp, $item);
                }
                foreach ($retailerItem->asArrayForCsvLineModifiersFromOptions($selectedSub) as $item) {
                    fputcsv($fp, $item);
                }
            }
        }

        fclose($fp);

        $this->s3Service->copyObjectFromLocal(
            $localFileFullPath,
            $specificDir . '/' . $fileName
        );
        //unlink($localFileFullPath);
    }


    private function saveSingleRetailerModifierOptionsToFile(
        string $tempDir,
        string $specificDir,
        string $fileName,
        RetailerItemList $retailerItemList
    ) {
        $firstLine = 'optionPOSName,optionDisplayName,optionDisplayDescription,optionId,pricePerUnit,priceLevelId,isActive,uniqueRetailerItemModifierId,uniqueId,optionDisplaySequence,version';
        $firstLine = explode(',', $firstLine);

        $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $fileName;
        $fp = fopen($localFileFullPath, 'w');

        fputcsv($fp, $firstLine);

        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            /** @var RetailerItemInventorySub $selectedSub */
            foreach ($retailerItem->getRetailerItemInventorySubList() as $selectedSub) {
                foreach ($retailerItem->asArrayForCsvLineModifierOptionsFromChoices($selectedSub) as $item) {
                    fputcsv($fp, $item);
                }
                foreach ($retailerItem->asArrayForCsvLineModifierOptionsFromOptions($selectedSub) as $item) {
                    fputcsv($fp, $item);
                }
            }
        }

        fclose($fp);

        $this->s3Service->copyObjectFromLocal(
            $localFileFullPath,
            $specificDir . '/' . $fileName
        );
        //unlink($localFileFullPath);
    }


    private function callGetEndpoint(
        string $path,
        array $additionalParams
    ): string {
        $url = $this->mainApiUrl . $path . '?email=' . $this->email . '&kobp=' . $this->secretKey;

        foreach ($additionalParams as $key => $value) {
            $url = $url . '&' . $key . '=' . $value;
        }

        $guzzleClient = new Client();

        try{
            $response = $guzzleClient->get($url);
        }catch (\Exception $exception){
            throw new Exception('GRAB problem with url ' . $this->mainApiUrl . $path);
        }

        if ($response->getStatusCode() !== 200) {
            throw new Exception('GRAB problem with url ' . $this->mainApiUrl . $path);
        }

        return (string)$response->getBody();
    }


    private function storeRetailerListAsS3Csv(
        RetailerList $retailerList,
        string $airportIataCode,
        string $partnerName,
        string $fileName,
        bool $verifiedOnly
    ) {
        $this->createLocalFilesDir($partnerName);
        $this->createRetailerLocalRetailerPath($partnerName, $airportIataCode);

        $tempDir = __DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update';
        $specificDir = $airportIataCode . '/' . $airportIataCode . '-data';

        $firstLine = 'airportIataCode,retailerName,description,retailerType,retailerCategory,retailerPriceCategory,retailerFoodSeatingType,searchTags,terminal,concourse,gate,hasDelivery,hasPickup,imageBackground,imageLogo,isActive,isChain,openTimesMonday,openTimesTuesday,openTimesWednesday,openTimesThursday,openTimesFriday,openTimesSaturday,openTimesSunday,closeTimesMonday,closeTimesTuesday,closeTimesWednesday,closeTimesThursday,closeTimesFriday,closeTimesSaturday,closeTimesSunday,uniqueId';
        $firstLine = explode(',', $firstLine);

        $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $fileName;
        $fp = fopen($localFileFullPath, 'w');

        fputcsv($fp, $firstLine);

        /** @var Retailer $retailer */
        foreach ($retailerList as $retailer) {
            if ($verifiedOnly === false || ($retailer->getRetailerCustomization() !== null && $retailer->getRetailerCustomization()->getVerified() === true)) {
                fputcsv($fp, array_values($retailer->asArrayForCsvLine()));
            }
        }

        fclose($fp);

        // store it in s3
        $this->s3Service->copyObjectFromLocal(
            $localFileFullPath,
            $specificDir . '/' . $fileName
        );

        //unlink($localFileFullPath);
    }

    private function createLocalFilesDir(
        string $partnerName
    ) {
        if (!file_exists(__DIR__ . '/../../../storage/partner')) {
            mkdir(__DIR__ . '/../../../storage/partner');
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName)) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName);
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update')) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update');
        }
    }

    private function createRetailerLocalRetailerPath(
        string $partnerName,
        string $airportIataCode
    ) {
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode)) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode);
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data')) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data');
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-imageLogo')) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-imageLogo');
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-imageBackground')) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-imageBackground');
        }
    }

    private function createRetailerLocalMenuPath(
        string $partnerName,
        string $airportIataCode,
        string $retailerDir
    ) {
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode)) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode);
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data')) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data');
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus')) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus');
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDir)) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDir);
        }
        if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDir . '/itemImages')) {
            mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDir . '/itemImages');
        }
    }

    private function createRetailerLocalMenuPathAdditionalDirs(
        string $fileName,
        $partnerName,
        $airportIataCode,
        $retailerDir
    ) {
        $fileName = trim($fileName, '/');
        $fileName = explode('/', $fileName);
        if (count($fileName) > 1) {
            var_dump($fileName);
        }
        for ($i = 0; $i < (count($fileName) - 1); $i++) {
            $additionalDir = array_slice($fileName, 0, ($i + 1));
            $additionalDir = implode('/', $additionalDir);
            if (!file_exists(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDir . '/itemImages/' . $additionalDir)) {
                mkdir(__DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update/' . $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDir . '/itemImages/' . $additionalDir);
            }
        }
    }

    private
    function storeRetailersLogosOnS3(
        RetailerList $retailerInfoList,
        string $airportIataCode,
        string $partnerName
    ) {
        $this->createLocalFilesDir($partnerName);
        $this->createRetailerLocalRetailerPath($partnerName, $airportIataCode);

        $tempDir = __DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update';
        $specificDir = $airportIataCode . '/' . $airportIataCode . '-imageLogo';

        /** @var Retailer $retailer */
        foreach ($retailerInfoList as $retailer) {

            if (empty($retailer->getStoreImageName())) {
                continue;
            }

            $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $retailer->getStoreImageName();

            (new Client())->request('GET', self::RETAILER_IMAGE_URL_PREFIX . $retailer->getStoreImageName(),
                ['sink' => $localFileFullPath]);

            $this->s3Service->copyObjectFromLocal(
                $localFileFullPath,
                $specificDir . '/' . $retailer->getStoreImageName()
            );

            //unlink($localFileFullPath);
        }
    }

    private function storeRetailerItemImagesOnS3(
        Retailer $retailer,
        RetailerItemList $retailerItemList,
        string $partnerName,
        string $retailerDirName
    ) {
        $airportIataCode = $retailer->getAirportIdent();
        $this->createLocalFilesDir($partnerName);
        $this->createRetailerLocalRetailerPath($partnerName, $airportIataCode);

        $tempDir = __DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update';
        $specificDir = $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDirName . '/itemImages';

        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            if (empty($retailerItem->getInventoryItemImageName())) {
                continue;
            }
            $this->createRetailerLocalMenuPathAdditionalDirs($retailerItem->getInventoryItemImageName(), $partnerName,
                $airportIataCode, $retailerDirName);

            $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $retailerItem->getInventoryItemImageName();

            (new Client())->request('GET',
                self::RETAILER_ITEM_IMAGE_URL_PREFIX . $retailerItem->getInventoryItemImageName(),
                ['sink' => $localFileFullPath]);

            $this->s3Service->copyObjectFromLocal(
                $localFileFullPath,
                $specificDir . '/' . $retailerItem->getInventoryItemImageName()
            );

            //unlink($localFileFullPath);
        }
    }

    private function saveSingleRetailerItemsCustomizeToFile(
        $tempDir,
        $specificDir,
        $fileName,
        $retailerItemList
    ) {
        $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $fileName;


        // we can get information only on today, so we need to grab existing file and improve it by todays data
        if ($this->s3Service->doesObjectExist($specificDir . '/' . $fileName)) {
            $this->s3Service->downloadFile($specificDir . '/' . $fileName, $localFileFullPath);
            $csv = array_map('str_getcsv', file($localFileFullPath));
        } else {
            $firstLine = 'itemPOSName,uniqueId,isActive,itemDisplaySequence,allowedThruSecurity,verified';
            $csv[0] = explode(',', $firstLine);
        }

        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            /** @var RetailerItemInventorySub $selectedSub */
            foreach ($retailerItem->getRetailerItemInventorySubList() as $selectedSub) {
                $itemFoundInCustom = false;
                $newItemTimeEntryArray = $retailerItem->asArrayForCsvLineItemCustom($selectedSub);
                foreach ($csv as $key => $existingItemTimeEntryArray) {
                    // comparing Ids
                    if ($csv[$key][1] == $newItemTimeEntryArray[1]) {
                        $itemFoundInCustom = true;
                        break;
                    }
                }
                if (!$itemFoundInCustom) {
                    // check if there is an item with the same name, if so, copy its already filled data

                    $itemWithSameNameFound = false;
                    $itemWithSameName = null;
                    foreach ($csv as $key => $existingItemTimeEntryArray) {
                        // comparing Names
                        if ($csv[$key][0] == $newItemTimeEntryArray[0]) {
                            $itemWithSameNameFound = true;
                            $itemWithSameName = $csv[$key];
                            break;
                        }
                    }

                    if ($itemWithSameNameFound) {
                        // get all data from item with the same name, but put new Id there
                        $id = $newItemTimeEntryArray[1];
                        $newItemTimeEntryArray = $itemWithSameName;
                        $newItemTimeEntryArray[1] = $id;
                    }
                    $csv[] = $newItemTimeEntryArray;
                }
            }
        }

        $fp = fopen($localFileFullPath, 'w');
        foreach ($csv as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        $this->s3Service->copyObjectFromLocal(
            $localFileFullPath,
            $specificDir . '/' . $fileName
        );
        //unlink($localFileFullPath);
    }

    private function getItemsFileNameWithPartnerPostfix(
        string $retailerDirName,
        string $partnerName
    ) {
        return $retailerDirName . '-items-from-' . $partnerName . '.csv';
    }

    private function getItemsFileName(
        string $retailerDirName
    ) {
        return $retailerDirName . '-items.csv';
    }

    private function getItemTimesFileName(
        string $retailerDirName
    ) {
        return $retailerDirName . '-itemTimes.csv';
    }

    private
    function getItemModifiersFileName(
        string $retailerDirName
    ) {
        return $retailerDirName . '-modifiers.csv';
    }

    private
    function getItemModifierOptionsFileName(
        string $retailerDirName
    ) {
        return $retailerDirName . '-modifierOptions.csv';
    }

    private
    function getItemCustomizeFileName(
        string $retailerDirName,
        string $partnerName
    ) {
        return $retailerDirName . '-customize-from-' . $partnerName . '.csv';
    }

    private function getFullPathForRetailer(
        string $airportIataCode,
        string $retailerDirName
    ) {
        return $airportIataCode . '/' . $airportIataCode . '-data/' . $airportIataCode . '-Menus/' . $retailerDirName;
    }

    private function getRetailerItemCustomizationListFromS3(
        string $files3Path
    ): RetailerItemCustomizationList {
        $customizeFileContent = $this->s3Service->getFileContent($files3Path);
        $customizeFileContent = trim($customizeFileContent, "\n");
        $csv = array_map('str_getcsv', explode("\n", $customizeFileContent));

        // first and last new line
        unset($csv[0]);
        return RetailerItemCustomizationList::createFromArray($csv);
    }

    private function getRetailerCustomizationListFromS3(
        string $files3Path
    ): RetailerCustomizationList {
        $customizeFileContent = $this->s3Service->getFileContent($files3Path);
        $customizeFileContent = trim($customizeFileContent, "\n");
        $csv = array_map('str_getcsv', explode("\n", $customizeFileContent));

        // first and last new line
        unset($csv[0]);
        return RetailerCustomizationList::createFromArray($csv);
    }

    /**
     * @param RetailerList $retailerInfoList
     * @param string $airportIataCode
     * @param string $partnerName
     */
    private function storeRetailerCustomFilesIntoS3Csv(
        RetailerList $retailerInfoList,
        string $airportIataCode,
        string $partnerName
    ) {
        $this->createLocalFilesDir($partnerName);
        $this->createRetailerLocalRetailerPath($partnerName, $airportIataCode);

        $tempDir = __DIR__ . '/../../../storage/partner/' . $partnerName . '/airport_specific_update';
        $specificDir = $airportIataCode . '/' . $airportIataCode . '-data';
        $fileName = $airportIataCode . '-Retailers-customize-from-' . $partnerName . '.csv';

        $localFileFullPath = $tempDir . '/' . $specificDir . '/' . $fileName;

        // we can get information only on today, so we need to grab existing file and improve it by todays data
        if ($this->s3Service->doesObjectExist($specificDir . '/' . $fileName)) {
            $this->s3Service->downloadFile($specificDir . '/' . $fileName, $localFileFullPath);
            $csv = array_map('str_getcsv', file($localFileFullPath));
        } else {
            $firstLine = 'retailerName,retailerId,terminal,concourse,gate,hasDelivery,hasPickup,isActive,isVerified';
            $csv[0] = explode(',', $firstLine);
        }

        /** @var Retailer $retailer */
        foreach ($retailerInfoList as $retailer) {
            $itemFoundInCustom = false;
            $newItemTimeEntryArray = $retailer->asArrayForCsvLineItemCustom();
            foreach ($csv as $key => $existingItemTimeEntryArray) {
                if ($csv[$key][0] == $newItemTimeEntryArray[0] && $csv[$key][1] == $newItemTimeEntryArray[1]) {
                    $itemFoundInCustom = true;
                }
            }
            if (!$itemFoundInCustom) {
                $csv[] = $newItemTimeEntryArray;
            }
        }

        $fp = fopen($localFileFullPath, 'w');
        foreach ($csv as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        $this->s3Service->copyObjectFromLocal(
            $localFileFullPath,
            $specificDir . '/' . $fileName
        );
        //unlink($localFileFullPath);
    }

    private function applyCustomizationsToRetailers(
        RetailerList $retailerInfoList,
        string $airportIataCode,
        string $partnerName
    ) {
        $specificDir = $airportIataCode . '/' . $airportIataCode . '-data';
        $customFileName = $airportIataCode . '-Retailers-customize-from-' . $partnerName . '.csv';

        $files3Path = $specificDir . '/' . $customFileName;
        if ($this->s3Service->doesObjectExist($files3Path)) {
            $retailerItemCustomizationList = $this->getRetailerCustomizationListFromS3($files3Path);
            /** @var Retailer $retailer */
            foreach ($retailerInfoList as $retailer) {
                $retailer->setRetailerCustomization($retailerItemCustomizationList->findVerifiedByUniqueRetailer($retailer->getStoreWaypointID()));
            }
        }
    }

    private function removeItemsImageNameIfNotExistsRemotly(RetailerItemList $retailerItemList): RetailerItemList
    {
        /** @var RetailerItem $retailerItem */
        foreach ($retailerItemList as $retailerItem) {
            if (empty($retailerItem->getInventoryItemImageName())) {
                continue;
            }

            if (!$this->fileExistsRemotely(self::RETAILER_ITEM_IMAGE_URL_PREFIX . $retailerItem->getInventoryItemImageName())) {
                //echo 'remote file does not exist (image for item: ' . $retailerItem->getUniqueId() . '): ' . self::RETAILER_IMAGE_URL_PREFIX . $retailerItem->getInventoryItemImageName() . PHP_EOL;
                $retailerItem->setInventoryItemImageName("");
                continue;
            } else {
                //echo 'found (image for item: ' . $retailerItem->getUniqueId() . '): ' . self::RETAILER_IMAGE_URL_PREFIX . $retailerItem->getInventoryItemImageName() . PHP_EOL;

            }
        }
        return $retailerItemList;
    }

    private function removeRetailersImageNamesIfNotExistsRemotly(RetailerList $retailerList): RetailerList
    {
        /** @var Retailer $retailer */
        foreach ($retailerList as $retailer) {
            if (empty($retailer->getStoreImageName())) {
                continue;
            }

            if (!$this->fileExistsRemotely(self::RETAILER_ITEM_IMAGE_URL_PREFIX . $retailer->getStoreImageName())) {
                echo 'remote file does not exist (image for item: ' . $retailer->getStoreWaypointID() . '): ' . self::RETAILER_IMAGE_URL_PREFIX . $retailer->getStoreImageName() . PHP_EOL;
                $retailer->setStoreImageName("");
                continue;
            } else {
                //echo 'found (image for retailer: ' . $retailer->getStoreWaypointID() . '): ' . self::RETAILER_IMAGE_URL_PREFIX . $retailer->getStoreImageName() . PHP_EOL;

            }
        }
        return $retailerList;
    }


    private function fileExistsRemotely($url)
    {
        $guzzleClient = new Client();
        try {
            $response = $guzzleClient->get($url);
            if ($response->getStatusCode() == 200) {
                return true;
            }
        } catch (\Exception $exception) {

        }
        return false;
    }

    private function pushToRetailerOrdersThatHasSatusPaymentConfirmed(OrderList $orderList)
    {
        /** @var Order $order */
        foreach ($orderList as $order) {
            if ($order->getStatus() === Order::STATUS_PAYMENT_ACCEPTED) {
                $this->orderRepository->changeStatusToPushedToRetailer($order);
            }
        }
        return $orderList;
    }

    private function confirmByRetailerOrdersThatHasStatusPushToRetailer($orderList)
    {
        /** @var Order $order */
        foreach ($orderList as $order) {
            if ($order->getStatus() !== Order::STATUS_PUSHED_TO_RETAILER) {
                continue;
            }
            $order = $this->orderRepository->changeStatusToAcceptedByRetailer($order);
            $orderEmailReceiptMessage = QueueMessageHelper::getOrderEmailReceiptMessage($order);

            $queueService = QueueServiceFactory::create();
            $queueServiceForEmail = QueueServiceFactory::createEmailQueueService();
            $queueServiceForLogs = QueueServiceFactory::createMidPriorityAsynch();
            $queueServiceForNotifications = QueueServiceFactory::createSmsAndPushNotificationQueueService();

            $queueServiceForEmail->sendMessage($orderEmailReceiptMessage, 0);


            if (strcasecmp($order->getFullfillmentType(), "p") == 0) {
                $orderPickupMarkCompleteMessage = QueueMessageHelper::getOrderPickupMarkCompleteMessage($order);
                $messageDelayInSeconds = $queueService->getWaitTimeForDelay($order->getEtaTimestamp());
                $queueService->sendMessage($orderPickupMarkCompleteMessage, $messageDelayInSeconds);
                $orderPickupMarkCompleteMessage = QueueMessageHelper::getSendNotificationOrderPickupAccepted($order);
                $queueServiceForNotifications->sendMessage($orderPickupMarkCompleteMessage, 0);
                $logPickupOrderStatus = QueueMessageHelper::getLogOrderDeliveryStatuses($order->getRetailer()->getLocation()->getAirportIataCode(),
                    'retailer_accepted', time(), $order->getOrderSequenceId());
                $queueServiceForLogs->sendMessage($logPickupOrderStatus, 0);
            } else {
                if (strcasecmp($order->getFullfillmentType(), "d") == 0) {
                    // Assign for delivery for orders progressed to STATUS_ACCEPTED_BY_RETAILER
                    $orderDeliveryAssignDeliveryMessage = QueueMessageHelper::getOrderDeliveryAssignDeliveryMessage($order);
                    $queueService->sendMessage($orderDeliveryAssignDeliveryMessage, 0);
                    $logDeliveryOrderStatus = QueueMessageHelper::getLogOrderDeliveryStatuses($order->getRetailer()->getLocation()->getAirportIataCode(),
                        'retailer_accepted', time(), $order->getOrderSequenceId());
                    $queueServiceForLogs->sendMessage($logDeliveryOrderStatus, 0);
                }
            }
        }

        return $orderList;
    }

}
