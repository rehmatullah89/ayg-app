<?php

namespace App\Consumer\Repositories;
use App\Consumer\Entities\UserDevice;
use App\Consumer\Entities\SessionDeviceList;

use App\Consumer\Entities\SessionDevice;
use App\Consumer\Entities\UserSessionList;
use App\Consumer\Repositories\UserSessionDeviceRepositoryInterface;
use Parse\ParseQuery;

/**
 * Class UserSessionDeviceParseRepository
 * @package App\Consumer\Repositories
 */
class UserSessionDeviceParseRepository extends ParseRepository implements UserSessionDeviceRepositoryInterface
{

    public function getUserActiveSessionsList(string $userObjectId): SessionDeviceList
    {
        // list sessions for an active user
        $userInnerQuery = new ParseQuery('_User');
        $userInnerQuery->equalTo('objectId', $userObjectId);

        $query = new ParseQuery('SessionDevices');
        $query->matchesQuery('user', $userInnerQuery);
        $query->includeKey('userDevice');
        $query->equalTo('isActive', true);
        $sessionDevices = $query->find();

        // new class (entity)
        $list = new SessionDeviceList();
        foreach ($sessionDevices as $sessionDevice){
            $list->addItem(
                new SessionDevice(
                    $sessionDevice->getObjectId(),
                    new UserDevice(
                        $sessionDevice->get('userDevice')->getObjectId(),
                        $sessionDevice->get('userDevice')->get('deviceType'),
                        $sessionDevice->get('userDevice')->get('deviceId'),
                        $sessionDevice->get('userDevice')->get('deviceModel'),
                        $sessionDevice->get('userDevice')->get('appVersion'),
                        $sessionDevice->get('userDevice')->get('deviceOS')
                    ),
                    $sessionDevice->get('IPAddress'),
                    $sessionDevice->get('checkinTimestamp')
                )
            );
        }
        return $list;
    }

    public function getUserActiveSessionsListBySessions(UserSessionList $userSessionList): SessionDeviceList
    {
        // @todo use
        $list = $userSessionList->getTokens();

        $query = new ParseQuery('SessionDevices');
        $query->containedIn('sessionTokenRecall', $list);
        $query->equalTo('isActive', true);
        $query->includeKey('userDevice');
        $sessionDevices = $query->find();

        // new class (entity)
        $list = new SessionDeviceList();
        foreach ($sessionDevices as $sessionDevice){
            $list->addItem(
                new SessionDevice(
                    $sessionDevice->getObjectId(),
                    new UserDevice(
                        $sessionDevice->get('userDevice')->getObjectId(),
                        $sessionDevice->get('userDevice')->get('deviceType'),
                        $sessionDevice->get('userDevice')->get('deviceId'),
                        $sessionDevice->get('userDevice')->get('deviceModel'),
                        $sessionDevice->get('userDevice')->get('appVersion'),
                        $sessionDevice->get('userDevice')->get('deviceOS')
                    ),
                    $sessionDevice->get('IPAddress'),
                    $sessionDevice->get('checkinTimestamp')
                )
            );
        }

        return $list;
    }
}
