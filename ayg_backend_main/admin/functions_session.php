<?php
use Parse\ParseQuery;

/**
 * @param $userId
 * @return \Parse\ParseObject[]
 *
 * gets active session devices for a given user
 * deleted cache related with those session devices
 * sets isActive property to false
 */
function deactivateSessionDeviceByUserId($userId)
{

    $userInnerQuery = new ParseQuery('_User');
    $userInnerQuery->equalTo('objectId', $userId);

    $query = new ParseQuery('SessionDevices');
    $query->matchesQuery('user', $userInnerQuery);
    $query->equalTo('isActive', true);
    $sessionDevices = $query->find();

    foreach ($sessionDevices as $sessionDevice) {
        $sessionDeviceId = $sessionDevice->getObjectId();
        $cacheKey = getCacheKeyForSessionDevice($userId, $sessionDeviceId);
        delCacheByKey($cacheKey);
        $sessionDevice->set('isActive', false);
        $sessionDevice->save();
    }

    return $sessionDevices;
}


?>