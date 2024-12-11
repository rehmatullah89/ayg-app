<?php
namespace App\Tablet\Helpers;

use App\Tablet\Entities\CacheKey;
use App\Tablet\Entities\Retailer;

/**
 * Class CacheKeyHelper
 * @package App\Tablet\Helpers
 */
class CacheKeyHelper
{
    /**
     * @param Retailer $retailer
     * @return string
     */
    public static function getRetailerPingKey(Retailer $retailer)
    {
        return CacheKey::RETAILER_PING_PREFIX . $retailer->getUniqueId();
    }

    /**
     * @param $userId
     * @return string
     */
    public static function getRetailerListByTabletUserKey($userId)
    {
        return CacheKey::RETAILER_LIST_BY_TABLET_USER_PREFIX . $userId;
    }

    /**
     * @param $orderId
     * @return string
     */
    public static function getOrderModifiersByOrderIdKey($orderId)
    {
        return CacheKey::ORDER_MODS_BY_ORDER_ID_PREFIX . $orderId;
    }

    /**
     * @param $uniqueIdList
     * @return string
     */
    public static function getRetailerItemModifierListByUniqueIdListKey($uniqueIdList)
    {
        return CacheKey::RETAILER_ITEM_MODIFIER_LIST_BY_UNIQUE_IDS_PREFIX . implode('_', $uniqueIdList);
    }

    /**
     * @param $uniqueIdList
     * @return string
     */
    public static function getRetailerItemModifierOptionListByUniqueIdListKey($uniqueIdList)
    {
        return CacheKey::RETAILER_ITEM_MODIFIER_OPTION_LIST_BY_UNIQUE_IDS_PREFIX . implode('_', $uniqueIdList);
    }

}