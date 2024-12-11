<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserIdentifier;
use App\Consumer\Entities\UserIdentifierList;
use App\Consumer\Entities\UserPhone;

class UserCustomIdentifierMysqlRepository implements UserCustomIdentifierRepositoryInterface
{
    /**
     * @var \PDO
     */
    private $pdoConnection;

    public function __construct(\PDO $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    public function add(UserIdentifier $userIdentifier): UserIdentifier
    {
        $stmt = $this->pdoConnection->prepare("INSERT INTO user_identifiers SET 
            `device_identifier` = :userDeviceIdentifier,
            `user_id` = :userId,
            `phone_country_code` = :phoneCountryCode,
            `phone_number` = :phoneNumber,
            `is_active` = :isActive
        ");

        $deviceIdentifier = $userIdentifier->getDeviceIdentifier();
        $userId = $userIdentifier->getParseUserId();
        $phoneCountryCode = $userIdentifier->getPhoneCountryCode() == null ? null : intval($userIdentifier->getPhoneCountryCode());
        $phoneNumber = $userIdentifier->getPhoneNumber() == null ? null : intval($userIdentifier->getPhoneNumber());
        $isActive = 1;

        $stmt->bindParam(':userDeviceIdentifier', $deviceIdentifier, \PDO::PARAM_STR);
        $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
        $stmt->bindParam(':phoneCountryCode', $phoneCountryCode, \PDO::PARAM_INT);
        $stmt->bindParam(':phoneNumber', $phoneNumber, \PDO::PARAM_INT);
        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);

        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
        }


        $lastId = $this->pdoConnection->lastInsertId();
        $userIdentifier->setId($lastId);
        return $userIdentifier;
    }

    public function getId(UserIdentifier $userIdentifier): ?int
    {
        $stmt = $this->pdoConnection->prepare("SELECT * FROM user_identifiers WHERE 
            `device_identifier` = :userDeviceIdentifier AND
            `user_id` = :userId AND
            `phone_country_code` = :phoneCountryCode AND
            `phone_number` = :phoneNumber
            LIMIT 1
        ");

        $deviceIdentifier = $userIdentifier->getDeviceIdentifier();
        $userId = $userIdentifier->getParseUserId();
        $phoneCountryCode = $userIdentifier->getPhoneCountryCode() == null ? null : intval($userIdentifier->getPhoneCountryCode());
        $phoneNumber = $userIdentifier->getPhoneNumber() == null ? null : intval($userIdentifier->getPhoneNumber());

        $stmt->bindParam(':userDeviceIdentifier', $deviceIdentifier, \PDO::PARAM_STR);
        $stmt->bindParam(':userId', $userId, \PDO::PARAM_STR);
        $stmt->bindParam(':phoneCountryCode', $phoneCountryCode, \PDO::PARAM_INT);
        $stmt->bindParam(':phoneNumber', $phoneNumber, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return null;
        }

        return $result[0]['id'];
    }

    public function findByPhone(UserPhone $userPhone): UserIdentifierList
    {
        $stmt = $this->pdoConnection->prepare("SELECT * FROM user_identifiers WHERE 
            `phone_country_code` = :phoneCountryCode AND
            `phone_number` = :phoneNumber
        ");

        $phoneCountryCode = $userPhone->getPhoneCountryCode();
        $phoneNumber = $userPhone->getPhoneNumber();

        $stmt->bindParam(':phoneCountryCode', $phoneCountryCode, \PDO::PARAM_INT);
        $stmt->bindParam(':phoneNumber', $phoneNumber, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $userIdentifierList = new UserIdentifierList();
        foreach ($result as $item) {
            $userIdentifierList->addItem(
                new UserIdentifier(
                    $item['id'],
                    $item['device_identifier'],
                    $item['phone_country_code'],
                    $item['phone_number'],
                    $item['user_id'],
                    $item['is_active']
                )
            );
        }

        return $userIdentifierList;
    }

    public function findActiveVerifiedByPhone(UserPhone $userPhone): UserIdentifierList
    {
        $stmt = $this->pdoConnection->prepare("SELECT * FROM user_identifiers WHERE
            `is_active` = :isActive AND
            `phone_country_code` = :phoneCountryCode AND
            `phone_number` = :phoneNumber
        ");

        $isActive = 1;
        $phoneCountryCode = $userPhone->getPhoneCountryCode();
        $phoneNumber = $userPhone->getPhoneNumber();

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':phoneCountryCode', $phoneCountryCode, \PDO::PARAM_INT);
        $stmt->bindParam(':phoneNumber', $phoneNumber, \PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $userIdentifierList = new UserIdentifierList();
        foreach ($result as $item) {
            $userIdentifierList->addItem(
                new UserIdentifier(
                    $item['id'],
                    $item['device_identifier'],
                    $item['phone_country_code'],
                    $item['phone_number'],
                    $item['user_id'],
                    $item['is_active']
                )
            );
        }

        return $userIdentifierList;
    }

    public function deactivateNotVerifiedUsersByUserDeviceIdentifier(string $deviceIdentifier)
    {
        $stmt = $this->pdoConnection->prepare("UPDATE user_identifiers SET 
            `is_active` = :isActive,
            `updated_at` = :updatedAt
            WHERE 
            `device_identifier` = :deviceIdentifier AND 
            `phone_country_code` IS NULL AND 
            `phone_number` IS NULL 
        ");

        $isActive = 0;
        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':deviceIdentifier', $deviceIdentifier, \PDO::PARAM_STR);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);

        $result = $stmt->execute();

        if (!$result) {
            // log
        }
    }

    public function deactivateUserByUserIdentifierId(int $userIdentifierId)
    {
        $stmt = $this->pdoConnection->prepare("UPDATE user_identifiers SET 
            `is_active` = :isActive,
            `updated_at` = :updatedAt
            WHERE 
            `id` = :id
        ");

        $isActive = 0;
        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);
        $stmt->bindParam(':id', $userIdentifierId, \PDO::PARAM_INT);

        $result = $stmt->execute();

        if (!$result) {
            // log
        }
    }

    public function save(UserIdentifier $userIdentifier): void
    {

        $stmt = $this->pdoConnection->prepare("UPDATE user_identifiers SET 
            `is_active` = :isActive,
            `phone_country_code` = :phoneCountryCode,
            `phone_number` = :phoneNumber,
            `updated_at` = :updatedAt
            WHERE 
            `id` = :userIdentifierId
        ");

        $isActive = 0;
        if ($userIdentifier->isActive()) {
            $isActive = 1;
        }
        $updatedAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('c');
        $userIdentifierId = $userIdentifier->getId();
        $phoneCountryCode = $userIdentifier->getPhoneCountryCode();
        $phoneNumber = $userIdentifier->getPhoneNumber();

        $stmt->bindParam(':isActive', $isActive, \PDO::PARAM_INT);
        $stmt->bindParam(':phoneCountryCode', $phoneCountryCode, \PDO::PARAM_INT);
        $stmt->bindParam(':phoneNumber', $phoneNumber, \PDO::PARAM_INT);
        $stmt->bindParam(':updatedAt', $updatedAt, \PDO::PARAM_STR);
        $stmt->bindParam(':userIdentifierId', $userIdentifierId, \PDO::PARAM_STR);


        $result = $stmt->execute();

        if (!$result) {
            // log failed insert
        }
    }
}
