<?php
namespace App\Delivery\Repositories;

use App\Delivery\Entities\UserPhone;

interface UserPhoneRepositoryInterface
{
    public function getActiveUserPhoneByUserId(string $userId): UserPhone;
}
