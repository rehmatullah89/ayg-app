<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserIdentifier;
use App\Consumer\Entities\UserSession;
use App\Consumer\Entities\UserSessionList;
use App\Consumer\Exceptions\Exception;

class UserCustomSessionMysqlRepository implements UserCustomSessionRepositoryInterface
{
    /**
     * @var \PDO
     */
    private $pdoConnection;

    public function __construct(\PDO $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    public function add(UserSession $userSession): UserSession
    {
        $stmt = $this->pdoConnection->prepare("INSERT INTO user_sessions SET 
            `token` = :token,
            `user_identifier_id` = :userIdentifierId,
            `user_device_identifier` = :userDeviceIdentifier,
            `is_active` = :isActive,
            `has_full_access` = :hasFullAccess,
            `session_device_id` = :sessionDeviceId,
            `session_device_is_active` = :isSessionDeviceActive,
            `updated_at` = :updatedAt
        ");

        $isActive = 0;
        if ($userSession->isActive()) {
            $isActive = 1;
        }
        $hasFullAccess = 0;
        if ($userSession->hasFullAccess()) {
            $hasFullAccess = 1;
        }
        $isSessionDeviceActive = 0;
        if ($userSession->isSessionDeviceActive()) {
            $isSessionDeviceActive = 1;
        }

        $token = $userSession->getToken();
        $userDeviceId = $userSession->getUserIdentifier()->getDeviceIdentifier();
        $userDeviceIdentifierId = $userSession->getUserIdentifier()->getId();
        $sessionDeviceId = $userSession->getSessionDeviceId();
        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':token', $token, \PDO::PARAM_STR);
        $stmt->bindParam(':userIdentifierId', $userDeviceIdentifierId, \PDO::PARAM_STR);
        $stmt->bindParam(':userDeviceIdentifier', $userDeviceId, \PDO::PARAM_STR);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);
        $stmt->bindParam(':sessionDeviceId', $sessionDeviceId, \PDO::PARAM_STR);
        $stmt->bindParam(':isSessionDeviceActive', $isSessionDeviceActive, \PDO::PARAM_INT);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            throw new \Exception('problem with adding custom user session');
        }

        $userSession->setId($this->pdoConnection->lastInsertId());
        return $userSession;
    }

    public function deactivateSessionByUserDeviceIdentifier(string $deviceIdentifier): void
    {
        $stmt = $this->pdoConnection->prepare("UPDATE user_sessions SET 
            `is_active` = :isActive,
            `has_full_access` = :hasFullAccess,
            `updated_at` = :updatedAt
            WHERE 
            `user_device_identifier` = :userDeviceIdentifier
        ");

        $hasFullAccess = 0;
        $isActive = 0;
        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);
        $stmt->bindParam(':userDeviceIdentifier', $deviceIdentifier, \PDO::PARAM_STR);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
        }
    }

    public function deactivateSessionByParseUserId(string $parseUserId): void
    {
        $stmt = $this->pdoConnection->prepare("
            SELECT 
            us.id as user_session_id
            FROM 
            user_sessions AS us
            JOIN 
            user_identifiers as ui
            ON us.user_identifier_id = ui.id
            WHERE 
            ui.user_id = :userId AND
            us.is_active = :isActive
        ");

        $isActive = 1;
        $stmt->bindParam(':userId', $parseUserId, \PDO::PARAM_STR);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return;
        }

        foreach ($result as $userSession) {

            $stmt = $this->pdoConnection->prepare("UPDATE user_sessions SET 
            `is_active` = :isActive,
            `has_full_access` = :hasFullAccess,
            `updated_at` = :updatedAt
            WHERE 
            `id` = :userSessionId
        ");

            $userSessionId = $userSession['user_session_id'];
            $hasFullAccess = 0;
            $isActive = 0;
            $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

            $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
            $stmt->bindParam(':userSessionId', $userSessionId, \PDO::PARAM_INT);
            $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);
            $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);

            $stmt->execute();
        }
    }


    public function findActiveUserSessionByDeviceIdentifier(string $deviceIdentifier):?UserSession
    {
        // checking if given session belongs to anybody and if it is active
        // in case it belongs but it is not active, then it means that someone is using old session token
        // even tho new one is generated (possible hack)

        $stmt = $this->pdoConnection->prepare("
            SELECT 
            us.*,
            ui.*,
            us.id as user_session_id,
            ui.id as user_identifier_id
            FROM 
            user_sessions AS us
            JOIN 
            user_identifiers as ui
            ON us.user_identifier_id = ui.id
            WHERE 
            us.user_device_identifier = :userDeviceIdentifier AND
            us.is_active = :isActive
        ");

        $isActive = 1;
        $stmt->bindParam(':userDeviceIdentifier', $deviceIdentifier, \PDO::PARAM_STR);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }

        return new UserSession(
            $result[0]['user_session_id'],
            $result[0]['token'],
            new UserIdentifier(
                $result[0]['user_identifier_id'],
                $result[0]['user_device_identifier'],
                $result[0]['phone_country_code'],
                $result[0]['phone_number'],
                $result[0]['user_id'],
                $result[0]['is_active']
            ),
            (bool)$result[0]['is_active'],
            (bool)$result[0]['has_full_access'],
            $result[0]['session_device_id'],
            (bool)$result[0]['session_device_is_active']
        );
    }

    public function findActiveUserSessionByDeviceIdentifierWithoutFullAccess($deviceIdentifier): ?UserSession
    {
        // checking if given session belongs to anybody and if it is active
        // in case it belongs but it is not active, then it means that someone is using old session token
        // even tho new one is generated (possible hack)

        $stmt = $this->pdoConnection->prepare("
            SELECT 
            us.*,
            ui.*,
            us.id as user_session_id,
            ui.id as user_identifier_id
            FROM 
            user_sessions AS us
            JOIN 
            user_identifiers as ui
            ON us.user_identifier_id = ui.id
            WHERE 
            us.user_device_identifier = :userDeviceIdentifier AND
            us.is_active = :isActive AND
            us.has_full_access = :hasFullAccess
        ");

        $hasFullAccess = 0;
        $isActive = 1;
        $stmt->bindParam(':userDeviceIdentifier', $deviceIdentifier, \PDO::PARAM_STR);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }

        return new UserSession(
            null,
            $result[0]['token'],
            new UserIdentifier(
                null,
                $result[0]['user_device_identifier'],
                $result[0]['phone_country_code'],
                $result[0]['phone_number'],
                $result[0]['user_id'],
                $result[0]['is_active']
            ),
            (bool)$result[0]['is_active'],
            (bool)$result[0]['has_full_access'],
            $result[0]['session_device_id'],
            (bool)$result[0]['session_device_is_active']
        );
    }


    public function findActiveUserSessionBySessionToken(string $sessionToken):?UserSession
    {
        // checking if given session belongs to anybody and if it is active
        // in case it belongs but it is not active, then it means that someone is using old session token
        // even tho new one is generated (possible hack)

        $stmt = $this->pdoConnection->prepare("
            SELECT 
            us.*,
            ui.*,
            us.id as user_session_id,
            ui.id as user_identifier_id
            FROM 
            user_sessions AS us
            JOIN 
            user_identifiers as ui
            ON us.user_identifier_id = ui.id
            WHERE 
            us.token = :token AND
            us.is_active = :isActive
        ");

        $isActive = 1;
        $stmt->bindParam(':token', $sessionToken, \PDO::PARAM_STR);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }

        return new UserSession(
            $result[0]['user_session_id'],
            $result[0]['token'],
            new UserIdentifier(
                $result[0]['user_identifier_id'],
                $result[0]['user_device_identifier'],
                $result[0]['phone_country_code'],
                $result[0]['phone_number'],
                $result[0]['user_id'],
                $result[0]['is_active']
            ),
            (bool)$result[0]['is_active'],
            (bool)$result[0]['has_full_access'],
            $result[0]['session_device_id'],
            (bool)$result[0]['session_device_is_active']
        );
    }

    public function findActiveUserSessionsBySessionToken(string $sessionToken):UserSessionList
    {
        // checking if given session belongs to anybody and if it is active
        // in case it belongs but it is not active, then it means that someone is using old session token
        // even tho new one is generated (possible hack)

        $stmt = $this->pdoConnection->prepare("
            SELECT 
            us.*,
            ui.*,
            us.id as user_session_id,
            ui.id as user_identifier_id
            FROM 
            user_sessions AS us
            JOIN 
            user_identifiers as ui
            ON us.user_identifier_id = ui.id
            WHERE 
            us.token = :token AND
            us.is_active = :isActive
        ");

        $isActive = 1;
        $stmt->bindParam(':token', $sessionToken, \PDO::PARAM_STR);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $list = new UserSessionList();
        foreach ($result as $userSession){
            $list->addItem(
                new UserSession(
                    $userSession['user_session_id'],
                    $userSession['token'],
                    new UserIdentifier(
                        $userSession['user_identifier_id'],
                        $userSession['user_device_identifier'],
                        $userSession['phone_country_code'],
                        $userSession['phone_number'],
                        $userSession['user_id'],
                        $userSession['is_active']
                    ),
                    (bool)$userSession['is_active'],
                    (bool)$userSession['has_full_access'],
                    $userSession['session_device_id'],
                    (bool)$userSession['session_device_is_active']
                )
            );
        }

        return $list;
    }

    public function save(UserSession $userSession): void
    {
        $stmt = $this->pdoConnection->prepare("
            UPDATE 
            user_sessions as us
            SET
            has_full_access = :hasFullAccess,
            user_identifier_id = :userIdentifierId,
            is_active = :isActive,
            session_device_id = :sessionDeviceId,
            session_device_is_active = :isSessionDeviceActive,
            updated_at = :updatedAt
            WHERE 
            us.id = :userSessionId
        ");

        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $hasFullAccess = 0;
        if ($userSession->hasFullAccess()) {
            $hasFullAccess = 1;
        }
        $isActive = 0;
        if ($userSession->isActive()) {
            $isActive = 1;
        }
        $isSessionDeviceActive = 0;
        if ($userSession->isSessionDeviceActive()) {
            $isSessionDeviceActive = 1;
        }
        $userSessionId = $userSession->getId();
        $userIdentifierId = $userSession->getUserIdentifier()->getId();
        $sessionDeviceId = $userSession->getSessionDeviceId();

        $stmt->bindParam(':userIdentifierId', $userIdentifierId, \PDO::PARAM_INT);
        $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':userSessionId', $userSessionId, \PDO::PARAM_INT);
        $stmt->bindParam(':sessionDeviceId', $sessionDeviceId, \PDO::PARAM_STR);
        $stmt->bindParam(':isSessionDeviceActive', $isSessionDeviceActive, \PDO::PARAM_INT);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            json_error($stmt->errorInfo());
        }
    }


    public function deactivateSessionByUserIdentifierId(int $userIdentifierId): void
    {
        $stmt = $this->pdoConnection->prepare("UPDATE user_sessions SET 
            `is_active` = :isActive,
            `has_full_access` = :hasFullAccess,
            `updated_at` = :updatedAt
            WHERE 
            `user_identifier_id` = :userIdentifierId
        ");

        $isActive = 0;
        $hasFullAccess = 0;
        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);
        $stmt->bindParam(':userIdentifierId', $userIdentifierId, \PDO::PARAM_INT);
        $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);

        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
        }
    }


    public function deactivateSessionByToken(string $sessionToken): void
    {
        $stmt = $this->pdoConnection->prepare("UPDATE user_sessions SET 
            `is_active` = :isActive,
            `has_full_access` = :hasFullAccess,
            `updated_at` = :updatedAt
            WHERE 
            `token` = :token
        ");

        $isActive = 0;
        $hasFullAccess = 0;
        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);
        $stmt->bindParam(':token', $sessionToken, \PDO::PARAM_STR);
        $stmt->bindParam(':hasFullAccess', $hasFullAccess, \PDO::PARAM_INT);

        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
        }
    }

    public function getBySessionToken(string $sessionToken):?UserSession
    {

        $stmt = $this->pdoConnection->prepare("
            SELECT 
            us.*,
            ui.*,
            us.id as user_session_id,
            ui.id as user_identifier_id
            FROM 
            user_sessions AS us
            JOIN 
            user_identifiers as ui
            ON us.user_identifier_id = ui.id
            WHERE 
            us.token = :token
        ");

        $stmt->bindParam(':token', $sessionToken, \PDO::PARAM_STR);

        $stmt->execute();
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (empty($result)) {
            return null;
        }

        return new UserSession(
            $result[0]['user_session_id'],
            $result[0]['token'],
            new UserIdentifier(
                $result[0]['user_identifier_id'],
                $result[0]['user_device_identifier'],
                $result[0]['phone_country_code'],
                $result[0]['phone_number'],
                $result[0]['user_id'],
                $result[0]['is_active']
            ),
            (bool)$result[0]['is_active'],
            (bool)$result[0]['has_full_access'],
            $result[0]['session_device_id'],
            (bool)$result[0]['session_device_is_active']
        );
    }
}
