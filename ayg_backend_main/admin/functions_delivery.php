<?php

use App\Background\Services\ReportServiceFactory;
use Parse\ParseObject;
use Parse\ParseUser;

/**
 * @param $day - day in a form of YYYY-MM-DD
 * @param $deliveryId
 * @return float
 */
function identifyDeliveryAvailablePercentageByDay($startDateTimeTimestamp, $endDateTimeTimestamp, $deliveryId)
{
    $expectedPingInterval = $GLOBALS['env_PingSlackDeliveryIntervalInSecs']*$GLOBALS['env_PingSlackGraceMultiplier'];

    $reportServiceFactory = ReportServiceFactory::create($GLOBALS['logsPdoConnection']);
    try {
        list($lostPercentage, $missedPings) = $reportServiceFactory->getLostDeliveryPingPercentageForAGivenTimeRange($deliveryId, $startDateTimeTimestamp, $endDateTimeTimestamp, $expectedPingInterval);
    } catch (\App\Background\Exceptions\PingNotFoundException $exception) {
        // there is no pings in a given range
        return [0.0, []];
    }

    // when there is more pings then expected lost percentage will be less then zero
    if ($lostPercentage < 0) {
        $lostPercentage = 0;
    }

    return [100.0 - $lostPercentage, $missedPings];
}


?>