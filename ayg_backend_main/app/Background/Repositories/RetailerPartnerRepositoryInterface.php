<?php

namespace App\Background\Repositories;

use App\Background\Entities\RetailerPartner;
use App\Background\Entities\RetailerPartnerList;
use App\Background\Entities\RetailerPartnerShortInfoList;

interface RetailerPartnerRepositoryInterface
{
    public function getListByPartnerName(string $partnerName):RetailerPartnerList;

    public function getListByPartnerNameAndAirportIataCode(
        string $partnerName,
        string $airportIataCode
    ): RetailerPartnerList;

    public function updateItemsDirectoryName(RetailerPartner $retailerPartner): RetailerPartner;

    public function addNotExistingRetailerPartnerByShortInfoList(RetailerPartnerShortInfoList $retailerPartnerShortInfoList);
}
