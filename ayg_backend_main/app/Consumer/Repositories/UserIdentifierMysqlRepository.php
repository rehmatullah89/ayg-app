<?php

namespace App\Consumer\Repositories;

use App\Consumer\Entities\UserIdentifier;

class UserIdentifierMysqlRepository
{
    /**
     * @var \PDO
     */
    private $pdoConnection;

    public function __construct(\PDO $pdoConnection)
    {
        $this->pdoConnection = $pdoConnection;
    }

    public function create(UserIdentifier $userIdentifier): void
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
    }

    public function doesExist(UserIdentifier $userIdentifier): bool
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
            return false;
        }

        return true;
    }
}
