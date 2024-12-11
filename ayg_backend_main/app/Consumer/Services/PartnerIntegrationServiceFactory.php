<?php

namespace App\Consumer\Services;

use App\Consumer\Entities\RetailerPartner;
use App\Consumer\Exceptions\Exception;
use App\Consumer\Helpers\ConfigHelper;
use App\Consumer\Repositories\RetailerPartnerRepositoryInterface;
use GuzzleHttp\Client;

class PartnerIntegrationServiceFactory
{
    /**
     * @var RetailerPartnerRepositoryInterface
     */
    private $retailerPartnerRepository;

    public function __construct(
        RetailerPartnerRepositoryInterface $retailerPartnerRepository
    ) {
        $this->retailerPartnerRepository = $retailerPartnerRepository;
    }

    public function createByRetailerId($retailerId):?PartnerIntegrationService
    {
        $partnerName = $this->getPartnerNameByRetailerId($retailerId);
        if ($partnerName === null) {
            return null;
        }
        return $this->createByPartnerName($partnerName->getPartnerName());
    }

    public function createByRetailerUniqueId($retailerUniqueId):?PartnerIntegrationService
    {
        $partnerName = $this->getRetailerPartnerNameByRetailerUniqueId($retailerUniqueId);
        if ($partnerName === null) {
            return null;
        }
        return $this->createByPartnerName($partnerName->getPartnerName());
    }

    private function createByPartnerName(string $partnerName):?PartnerIntegrationService
    {
        switch ($partnerName) {
            case 'grab':
                return new PartnerIntegrationService(
                    new GrabIntegrationService(
                        ConfigHelper::get('env_GrabEmail'),
                        ConfigHelper::get('env_GrabMainApiUrl'),
                        ConfigHelper::get('env_GrabSecretKey'),
                        new Client()
                    ),
                    $this->retailerPartnerRepository
                );
                break;
            default:
                throw new Exception('There is no such partner (' . $partnerName . ')');
        }
    }

    public function getPartnerNameByRetailerId($retailerId):?RetailerPartner
    {
        return $this->retailerPartnerRepository->getPartnerNameByRetailerId($retailerId);
    }

    private function getRetailerPartnerNameByRetailerUniqueId($retailerUniqueId):?RetailerPartner
    {
        return $this->retailerPartnerRepository->getRetailerPartnerByRetailerUniqueId($retailerUniqueId);
    }
}
