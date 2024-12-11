<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserSession;

class UserSessionMysqlRepository
{
    /**
     * @var \PDO
     */
    private $pdoConnection;

    public function __construct(\PDO $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    public function create(UserSession $userSession)
    {
        $stmt = $this->pdoConnection->prepare("INSERT INTO user_sessions SET 
            `token` = :token,
            `user_device_identifier` = :userDeviceIdentifier,
            `is_active` = :isActive,
            `has_full_access` = :hasFullAccess,
            `updated_at` = :updatedAt
        ");

        $isActive = 0;
        $hasFullAccess = 0;
        if ($userSession->isIsActive()) {
            $isActive = 1;
        }
        if ($userSession->isHasFullAccess()) {
            $hasFullAccess = 1;
        }

        $token = $userSession->getToken();
        $userDeviceId = $userSession->getUserIdentifier()->getDeviceIdentifier();
        $updateAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':token', $token, \PDO::PARAM_STR);
        $stmt->bindParam(':userDeviceIdentifier', $userDeviceId, \PDO::PARAM_STR);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);
        $stmt->bindParam(':updatedAt', $updateAt, \PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
        }
    }

    public function deactivateSessionByUserDeviceIdentifier(string $deviceIdentifier)
    {
        $stmt = $this->pdoConnection->prepare("UPDATE user_sessions SET 
            `is_active` = :isActive,
            `updated_at` = :updatedAt
            WHERE 
            `user_device_identifier` = :userDeviceIdentifier
        ");

        $isActive = 0;
        $updateAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':userDeviceIdentifier', $deviceIdentifier, \PDO::PARAM_STR);
        $stmt->bindParam(':updatedAt', $updateAt, \PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
        }
    }

    public function findActiveUserSessionBySessionToken(string $sessionToken):?UserSession
    {
        // checking if given session belongs to anybody and if it is active
        // in case it belongs but it is not active, then it means that someone is using old session token
        // even tho new one is generated (possible hack)

        $stmt = $this->pdoConnection->prepare("SELECT * FROM user_sessions WHERE 
            `token` = :token
        ");

        $stmt->bindParam(':token', $token, \PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
            logResponse(json_encode($result));
        }

    }
}
