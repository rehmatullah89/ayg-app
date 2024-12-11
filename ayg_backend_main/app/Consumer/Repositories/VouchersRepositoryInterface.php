<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Voucher;
use App\Consumer\Entities\VoucherList;

interface VouchersRepositoryInterface
{
    public function getActiveVouchers(): VoucherList;

    public function getActiveVoucherById(string $voucherId): ?Voucher;
}
