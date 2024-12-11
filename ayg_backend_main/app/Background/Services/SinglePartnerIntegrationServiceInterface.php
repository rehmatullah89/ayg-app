<?php
namespace App\Background\Services;

use App\Background\Entities\Partners\Grab\Retailer;
use App\Background\Entities\Partners\Grab\RetailerItemList;

interface SinglePartnerIntegrationServiceInterface
{

    public function saveAllRetailersIntoS3(string $partnerName, array $allAirportIataCodes);

    public function saveSingleRetailerAllItemsDataToFiles(
        string $partnerName,
        Retailer $retailer,
        RetailerItemList $retailerItemList,
        string $retailerDirName
    );

    public function saveAllItemsIntoS3(string $partnerName, $allAirportIataCodes);

    public function notifyAboutNewItems(string $partnerName, $allAirportIataCodes);

    public function notifyAboutNewRetailers(string $partnerName, $allAirportIataCodes);

    public function updateRetailerPartners(string $partnerName, array $allAirportIataCodes);

    public function pingRetailers(string $partnerName);

    public function emulateRetailerAcceptance();

    public function handleCanceledOrders();

    public function getRetailerRelatedFilesHash($partnerName, array $allAirportIataCodes);

    public function getItemsRelatedFilesHash($partnerName, array $allAirportIataCodes);

    public function getItemsTo86($partnerName);
}
