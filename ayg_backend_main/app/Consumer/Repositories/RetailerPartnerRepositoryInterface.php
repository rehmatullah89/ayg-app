<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\RetailerPartner;

interface RetailerPartnerRepositoryInterface
{
    public function getPartnerNameByRetailerId($retailerId):?RetailerPartner;

    public function getRetailerPartnerByRetailerUniqueId($retailerUniqueId):?RetailerPartner;
}
