<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserIdentifier;
use App\Consumer\Entities\UserIdentifierList;
use App\Consumer\Entities\UserPhone;

interface UserCustomIdentifierRepositoryInterface
{

    public function add(UserIdentifier $userIdentifier): UserIdentifier;

    public function getId(UserIdentifier $userIdentifier): ?int;

    public function findByPhone(UserPhone $userPhone): UserIdentifierList;

    public function findActiveVerifiedByPhone(UserPhone $userPhone): UserIdentifierList;

    public function deactivateNotVerifiedUsersByUserDeviceIdentifier(string $deviceIdentifier);

    public function deactivateUserByUserIdentifierId(int $userIdentifierId);

    public function save(UserIdentifier $userIdentifier): void;
}
