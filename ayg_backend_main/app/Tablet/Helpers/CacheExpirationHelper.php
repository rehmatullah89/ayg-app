<?php
namespace App\Tablet\Helpers;


/**
 * Class CacheExpirationHelper
 * @package App\Consumer\Helpers
 */
class CacheExpirationHelper
{
    /**
     * @param $cachePrefix
     * @return string
     */
    public static function getExpirationTimestampByMethodName($cachePrefix)
    {
        switch ($cachePrefix) {
            case 'App\Tablet\Repositories\RetailerCacheRepository::getByTabletUserId':
                return DateTimeHelper::getEndOfCurrentDayTimestamp();
                break;
            case 'App\Tablet\Repositories\OrderModifierCacheRepository::getOrderModifiersByOrderId':
                return DateTimeHelper::getEndOfCurrentMonthTimestamp();
                break;
            case 'App\Tablet\Repositories\RetailerItemModifierOptionCacheRepository::getListByUniqueIdList':
                return DateTimeHelper::getEndOfCurrentDayTimestamp();
                break;
            case 'App\Tablet\Repositories\RetailerItemModifierCacheRepository::getListByUniqueIdList':
                return DateTimeHelper::getEndOfCurrentDayTimestamp();
                break;
            default:
                return DateTimeHelper::getXMinutesTimestamp(5);
        }

    }
}