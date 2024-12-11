<?php
namespace App\Consumer\Helpers;

use App\Consumer\Entities\CacheKey;
use DateInterval;
use DateTime;
use Slim\Route;

/**
 * Class CacheExpirationHelper
 * @package App\Consumer\Helpers
 */
class CacheExpirationHelper
{
    /**
     * @param Route $route
     * @return string
     *
     * gets cache expiration value - timestamp based on route
     * it is using DateTimeHelper static methods to generate timestamps
     */
    public static function getExpirationTimestampByRoute(Route $route)
    {
        $name = $route->getName();

        switch ($name) {
            case 'info-hello-world':
                return DateTimeHelper::getEndOfCurrentDayTimestamp();
                break;
            case 'user-add-phone-twilio':
                return DateTimeHelper::getXSecondsTimestamp(10);
                break;
            default:
                return DateTimeHelper::getEndOfCurrentDayTimestamp();
        }

    }

    /**
     * @param $cachePrefix
     * @return int
     */
    public static function getExpirationTimestampByMethodName($cachePrefix)
    {
        switch ($cachePrefix) {
            case 'App\Consumer\Services\UserService::setUserVerifyPhoneCodeIntoCache':
                return DateTimeHelper::getXMinutesTimestamp(10);
                break;
            case 'App\Consumer\Repositories\OrderRatingCacheRepository::addOrderRatingWithFeedback':
                return DateTimeHelper::getEndOfCurrentWeekTimestamp();
                break;
            case 'App\Consumer\Repositories\OrderRatingCacheRepository::getLastRating':
                return DateTimeHelper::getEndOfCurrentWeekTimestamp();
                break;
            case 'App\Consumer\Repositories\DeliveryAssignmentCacheRepository::getCompletedDeliveryAssignmentWithDeliveryByOrderId':
                return DateTimeHelper::getEndOfCurrentWeekTimestamp();
                break;
            case 'App\Consumer\Repositories\RetailerPartnerCacheRepository::getPartnerNameByRetailerId':
                return DateTimeHelper::getEndOfCurrentMonthTimestamp();
                break;
            case 'App\Consumer\Repositories\RetailerPartnerCacheRepository::getPartnerNameByRetailerUniqueId':
                return DateTimeHelper::getEndOfCurrentMonthTimestamp();
                break;

            case 'App\Consumer\Repositories\TerminalGateMapRetailerRestrictionsCacheRepository::getDeliveryRestriction':
                return DateTimeHelper::getEndOfCurrentDayTimestamp();
                break;


            default:
                return DateTimeHelper::getXMinutesTimestamp(5);
        }
    }

    /**
     * @param $cachePrefix
     * @return int
     */
    public static function getExpirationInSecondsByMethodName($cachePrefix)
    {
        switch ($cachePrefix) {
            case 'App\Consumer\Services\UserService::verifyPhoneWithTwilio':
                return 60*60;
                break;
            case 'App\Consumer\Services\UserService::addPhoneWithTwilio':
                return 60*60;
                break;
            default:
                return 60*60;
        }
    }
}
