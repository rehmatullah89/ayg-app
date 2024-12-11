<?php
namespace App\Background\Repositories;

use App\Background\Entities\RetailerPingLog;

/**
 * Interface CheckInRepositoryInterface
 * @package App\Background\Repositories
 */
interface CheckInRepositoryInterface
{

    /**
     * @param $userId
     * @param $sessionObjectId
     */
    public function logUserCheckin(string $objectId, string $sessionObjectId): void;

}