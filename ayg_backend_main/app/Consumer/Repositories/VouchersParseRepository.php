<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\Voucher;
use App\Consumer\Entities\VoucherList;
use Parse\ParseQuery;

class VouchersParseRepository extends ParseRepository implements VouchersRepositoryInterface
{
    public function getActiveVouchers(): VoucherList
    {
        $parseVoucherQuery = new ParseQuery("Vouchers");
        $parseVoucherQuery->equalTo("isActive", true);
        $parseVouchers = $parseVoucherQuery->find();

        $voucherList = new VoucherList();
        foreach ($parseVouchers as $parseVoucher) {
            $voucherList->addItem(new Voucher(
                (string)$parseVoucher->getObjectId(),
                (string)$parseVoucher->get('partnerName'),
                (int)$parseVoucher->get('limitInCents'),
                (bool)$parseVoucher->get('isActive')
            ));
        }

        return $voucherList;
    }

    public function getActiveVoucherById(string $voucherId): ?Voucher
    {
        $parseVoucherQuery = new ParseQuery("Vouchers");
        $parseVoucherQuery->equalTo("objectId", $voucherId);
        $parseVoucherQuery->equalTo("isActive", true);
        $parseVoucherQuery->limit(1);
        $parseVouchers = $parseVoucherQuery->find();

        if (empty($parseVouchers)) {
            return null;
        }

        $parseVoucher = $parseVouchers[0];

        return new Voucher(
            (string)$parseVoucher->getObjectId(),
            (string)$parseVoucher->get('partnerName'),
            (int)$parseVoucher->get('limitInCents'),
            (bool)$parseVoucher->get('isActive')
        );
    }
}
