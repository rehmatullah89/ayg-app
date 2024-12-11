<?php

namespace App\Consumer\Repositories;


use App\Consumer\Entities\Payment;

interface PaymentRepositoryInterface
{
    public function getPaymentByUserId(string $userId): ? Payment;
}
