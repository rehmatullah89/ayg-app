<?php

namespace App\Background\Repositories;

use App\Background\Entities\RetailerPartner;
use App\Background\Entities\RetailerPartnerList;
use App\Background\Entities\RetailerPartnerShortInfoList;
use App\Background\Services\CacheService;

class RetailerPartnerCacheRepository implements RetailerPartnerRepositoryInterface
{
    /**
     * @var RetailerPartnerRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(RetailerPartnerRepositoryInterface $flightTripRepository, CacheService $cacheService)
    {
        $this->decorator = $flightTripRepository;
        $this->cacheService = $cacheService;
    }


    public function getListByPartnerName(string $partnerName): RetailerPartnerList
    {
        return $this->decorator->getListByPartnerName($partnerName);
    }

    public function getListByPartnerNameAndAirportIataCode(
        string $partnerName,
        string $airportIataCode
    ): RetailerPartnerList {
        return $this->decorator->getListByPartnerNameAndAirportIataCode($partnerName, $airportIataCode);
    }

    public function updateItemsDirectoryName(RetailerPartner $retailerPartner): RetailerPartner
    {
        return $this->decorator->updateItemsDirectoryName($retailerPartner);
    }

    public function addNotExistingRetailerPartnerByShortInfoList(
        RetailerPartnerShortInfoList $retailerPartnerShortInfoList
    ) {
        return $this->decorator->addNotExistingRetailerPartnerByShortInfoList($retailerPartnerShortInfoList);
    }
}
