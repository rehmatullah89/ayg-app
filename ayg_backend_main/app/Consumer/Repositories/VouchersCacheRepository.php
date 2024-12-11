<?php

namespace App\Consumer\Repositories;

use App\Consumer\Services\CacheService;
use App\Consumer\Entities\Voucher;
use App\Consumer\Entities\VoucherList;

class VouchersCacheRepository implements VouchersRepositoryInterface
{
    private $decorator;
    private $cacheService;

    public function __construct(VouchersRepositoryInterface $userPhoneRepository, CacheService $cacheService)
    {
        $this->decorator = $userPhoneRepository;
        $this->cacheService = $cacheService;
    }

    public function getActiveVouchers(): VoucherList
    {
        return $this->decorator->getActiveVouchers();
    }


    public function getActiveVoucherById(string $voucherId): ?Voucher
    {
        return $this->decorator->getActiveVoucherById($voucherId);
    }
}
