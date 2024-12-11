<?php

namespace App\Consumer\Repositories;

use App\Consumer\Services\CacheService;
use App\Consumer\Entities\Payment;

class PaymentCacheRepository implements PaymentRepositoryInterface
{

    private $decorator;
    private $cacheService;

    public function __construct(PaymentRepositoryInterface $paymentRepository, CacheService $cacheService)
    {
        $this->decorator = $paymentRepository;
        $this->cacheService = $cacheService;
    }

    public function getPaymentByUserId(string $userId): ? Payment
    {
        return $this->decorator->getPaymentByUserId($userId);
    }
}
