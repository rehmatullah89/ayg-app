<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserSession;

interface UserCustomSessionRepositoryInterface
{
    public function add(UserSession $userSession): UserSession;

    public function deactivateSessionByUserDeviceIdentifier(string $deviceIdentifier): void;

    public function deactivateSessionByParseUserId(string $parseUserId): void;

    public function findActiveUserSessionByDeviceIdentifier(string $deviceIdentifier):?UserSession;

    public function findActiveUserSessionByDeviceIdentifierWithoutFullAccess($deviceIdentifier): ?UserSession;

    public function findActiveUserSessionBySessionToken(string $sessionToken):?UserSession;

    public function save(UserSession $userSession): void;

    public function deactivateSessionByUserIdentifierId(int $userIdentifierId): void;

    public function getBySessionToken(string $sessionToken):?UserSession;
}
