<?php
namespace App\Background\Services;


use App\Background\Helpers\QueueMessageHelper;

class PartnerIntegrationService
{
    private $integrationService;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(
        SinglePartnerIntegrationServiceInterface $integrationService,
        CacheService $cacheService
    ) {
        $this->integrationService = $integrationService;
        $this->cacheService = $cacheService;
    }

    public function pingRetailers(string $partnerName)
    {
        $this->integrationService->pingRetailers($partnerName);
    }

    public function updateRetailersAndItems(string $partnerName, array $allAirportIataCodes)
    {
        $this->integrationService->updateRetailerPartners($partnerName, $allAirportIataCodes);
        $this->updateRetailers($partnerName, $allAirportIataCodes);
        $this->updateItems($partnerName, $allAirportIataCodes);
    }

    private function updateRetailers(string $partnerName, array $allAirportIataCodes)
    {
        $newRetailerRelatedFilesHash = $this->integrationService->getRetailerRelatedFilesHash(
            $partnerName,
            $allAirportIataCodes);
        $oldRetailerRelatedFilesHash = $this->cacheService->getLastRetailerRelatedFilesHash(
            $partnerName,
            $allAirportIataCodes);

        if ($newRetailerRelatedFilesHash == $oldRetailerRelatedFilesHash) {
            if (!in_array('BOS',$allAirportIataCodes)){
                return;
            }
        }

        $this->integrationService->saveAllRetailersIntoS3($partnerName, $allAirportIataCodes);
        $this->integrationService->notifyAboutNewRetailers($partnerName, $allAirportIataCodes);

        $this->cacheService->setLastRetailerRelatedFilesHash($partnerName, $allAirportIataCodes,
            $newRetailerRelatedFilesHash);


        $queueServiceRetailerUpdate = QueueServiceFactory::createRetailerUpdate();
        foreach ($allAirportIataCodes as $airportIataCode) {
            $queueServiceRetailerUpdate->sendMessage(QueueMessageHelper::getRetailersUpdateMessage($airportIataCode,
                true, true), 0);
        }
    }

    private function updateItems(string $partnerName, array $allAirportIataCodes)
    {
        $newItemsRelatedFilesHash = $this->integrationService->getItemsRelatedFilesHash(
            $partnerName,
            $allAirportIataCodes);
        $oldItemsRelatedFilesHash = $this->cacheService->getLastItemsRelatedFilesHash(
            $partnerName,
            $allAirportIataCodes);

        if ($newItemsRelatedFilesHash == $oldItemsRelatedFilesHash) {

            if (!in_array('BOS',$allAirportIataCodes)){
                return;
            }
        }
        $this->integrationService->saveAllItemsIntoS3($partnerName, $allAirportIataCodes);

        $this->cacheService->setLastItemsRelatedFilesHash($partnerName, $allAirportIataCodes,
            $newItemsRelatedFilesHash);

        $queueServiceMenuUpdate = QueueServiceFactory::createMenuUpdate();
        foreach ($allAirportIataCodes as $airportIataCode) {
            $queueServiceMenuUpdate->sendMessage(QueueMessageHelper::getMenuUpdateMessage($airportIataCode), 0);
        }

        $this->integrationService->notifyAboutNewItems($partnerName, $allAirportIataCodes);
    }

    public function emulateRetailerAcceptance()
    {
        $this->integrationService->emulateRetailerAcceptance();
    }

    public function handleCanceledOrders()
    {
        $this->integrationService->handleCanceledOrders();
    }

    public function getItemsTo86(string $partnerName)
    {
        $this->integrationService->getItemsTo86($partnerName);
    }
}
