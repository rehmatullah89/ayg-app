<?php

namespace App\Delivery\Repositories;

use App\Delivery\Entities\UserPhone;
use App\Delivery\Services\CacheService;

class UserPhoneCacheRepository extends ParseRepository implements UserPhoneRepositoryInterface
{
    /**
     * @var UserPhoneRepositoryInterface
     */
    private $decorator;
    /**
     * @var CacheService
     */
    private $cacheService;

    public function __construct(UserPhoneRepositoryInterface $orderRepository, CacheService $cacheService)
    {
        $this->decorator = $orderRepository;
        $this->cacheService = $cacheService;
    }

    public function getActiveUserPhoneByUserId(string $userId): UserPhone
    {
        return $this->decorator->getActiveUserPhoneByUserId($userId);
    }
}
