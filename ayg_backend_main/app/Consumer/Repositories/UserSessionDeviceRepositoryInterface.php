<?php

namespace App\Consumer\Repositories;
use App\Consumer\Entities\SessionDeviceList;
use App\Consumer\Entities\UserSessionList;

/**
 * Interface UserSessionDeviceRepositoryInterface
 * @package App\Consumer\Repositories
 */
interface UserSessionDeviceRepositoryInterface
{
    /**
     * @param string $userObjectId
     * @return SessionDeviceList|null
     */
    public function getUserActiveSessionsList(string $userObjectId):?SessionDeviceList;



    public function getUserActiveSessionsListBySessions(UserSessionList $userSessionList):?SessionDeviceList;
}
