<?php
namespace App\Background\Services;

class CacheService
{
    function setOrderCancelNotificationMade($orderId)
    {
        $cacheKey = '__ORDER_CANCELED_NOTIFICATION' . $orderId;
        setCache($cacheKey, 1);
    }

    function getOrderCancelNotificationMade($orderId)
    {
        $cacheKey = '__ORDER_CANCELED_NOTIFICATION' . $orderId;
        return getCache($cacheKey);
    }

    function setMenuLoaderMd5ForRetailer($retailerUniqueId, $md5)
    {
        $cacheKey = '__MENULOADER__RETAILERS_MD5_' . $retailerUniqueId;
        setCache($cacheKey, $md5);
    }

    function getMenuLoaderMd5ForRetailer($retailerUniqueId)
    {
        $cacheKey = '__MENULOADER__RETAILERS_MD5_' . $retailerUniqueId;
        return getCache($cacheKey);
    }

    function setRetailerLoaderMd5ForAirport($airportIataCode, $md5)
    {
        $cacheKey = '__RETAILERLOADER_MD5__' . $airportIataCode;
        setCache($cacheKey, $md5);
    }

    function getRetailerLoaderMd5($airportIataCode)
    {
        $cacheKey = 'b' . $airportIataCode;
        return getCache($cacheKey);
    }

    function setCouponsLoaderMd5($md5)
    {
        $cacheKey = '__COUPONSLOADER_MD5';
        setCache($cacheKey, $md5);
    }

    function getCouponsLoaderMd5ForAirport()
    {
        $cacheKey = '__COUPONSLOADER_MD5';
        return getCache($cacheKey);
    }

    function resetRetailerMenuCache($retailerUniqueId)
    {
        $cacheKeyList[] = $GLOBALS['redis']->keys("PQ__RetailerItems*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("PQ__RetailerItemModifiers*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("PQ__RetailerItemModifierOptions*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("PQ__RetailerItemProperties*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("__RETAILER_ITEM_PROPERTIES_*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("NRC__menu*" . $retailerUniqueId);

        s3logMenuLoader(printLogTime() . "Resetting cache" . "\r\n");
        resetCache($cacheKeyList);
    }

    function resetRetailersCache()
    {
        $cacheKeyList[] = $GLOBALS['redis']->keys("PQ__Retailers*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("PQ__Retailer*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("RR__retailer*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("RR__retailer__info*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("RR__retailer__list*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("RR__retailer__bydistance*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("RR__retailer__fullfillmentinfo*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("__RETAILERINFO_*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("*curated*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("*fullfillmentinfo*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("*FULLFILLMENTINFO*");
        $cacheKeyList[] = $GLOBALS['redis']->keys("*RetailerPOSConfig*");
        s3logMenuLoader(printLogTime() . "Resetting cache" . "\r\n");
        resetCache($cacheKeyList);
    }

    function resetCouponsCache()
    {
        $cacheKeyList[] = $GLOBALS['redis']->keys("*Coupon*");
        s3logMenuLoader(printLogTime() . "Resetting cache" . "\r\n");
        resetCache($cacheKeyList);
    }

    public function setRetailerPingTimestamp(string $retailerUniqueId, int $timestamp)
    {
        setRetailerPingTimestamp($retailerUniqueId, $timestamp);
    }

    public function setDoNotDisplayRetailerCache(string $retailerUniqueId, int $currentTimestamp): void
    {
        $cacheKey = '__RETAILERS_DO_NOT_SHOW_DUE_TO_NO_MENU_ITEMS_' . $retailerUniqueId;
        setCache($cacheKey, 1);
    }

    public function getDoNotDisplayRetailerCache(string $retailerUniqueId):?int
    {
        $cacheKey = '__RETAILERS_DO_NOT_SHOW_DUE_TO_NO_MENU_ITEMS_' . $retailerUniqueId;
        $value = getCache($cacheKey);
        if ($value === false) {
            return null;
        }
        return intval($value);
    }

    public function clearDoNotDisplayRetailerCache(string $retailerUniqueId)
    {
        $cacheKey = '__RETAILERS_DO_NOT_SHOW_DUE_TO_NO_MENU_ITEMS_' . $retailerUniqueId;
        delCacheByKey($cacheKey);
    }

    public function getDoNotDisplayRetailerLastNotificationTimestampCache(string $retailerUniqueId):?int
    {
        $cacheKey = '__RETAILERS_DO_NOT_SHOW_DUE_TO_NO_MENU_ITEMS_LAST_NOTIFICATION_TIMESTAMP_' . $retailerUniqueId;
        $value = getCache($cacheKey);
        if ($value === false) {
            return null;
        }
        return intval($value);
    }

    public function setDoNotDisplayRetailerLastNotificationTimestampCache(
        string $retailerUniqueId,
        int $timestamp
    ): void {
        $cacheKey = '__RETAILERS_DO_NOT_SHOW_DUE_TO_NO_MENU_ITEMS_LAST_NOTIFICATION_TIMESTAMP_' . $retailerUniqueId;
        setCache($cacheKey, $timestamp);
    }

    public function clearDoNotDisplayRetailerLastNotificationTimestampCache($retailerUniqueId)
    {
        $cacheKey = '__RETAILERS_DO_NOT_SHOW_DUE_TO_NO_MENU_ITEMS_LAST_NOTIFICATION_TIMESTAMP_' . $retailerUniqueId;
        delCacheByKey($cacheKey);
    }

    public function getLastRetailerRelatedFilesHash($partnerName, $allAirportIataCodes)
    {
        $cacheKey = '__PARTNER_INTEGRATION_RETAILERS_RELATED_FILES_HASH__' . $partnerName;
        return hGetCache($cacheKey, implode('-', $allAirportIataCodes));
    }

    public function setLastRetailerRelatedFilesHash($partnerName, $allAirportIataCodes, $newRetailerRelatedFilesHash)
    {
        $cacheKey = '__PARTNER_INTEGRATION_RETAILERS_RELATED_FILES_HASH__' . $partnerName;
        hSetCache($cacheKey, implode('-', $allAirportIataCodes), $newRetailerRelatedFilesHash);
    }

    public function getLastItemsRelatedFilesHash($partnerName, $allAirportIataCodes)
    {
        $cacheKey = '__PARTNER_INTEGRATION_ITEMS_RELATED_FILES_HASH__' . $partnerName;
        return hGetCache($cacheKey, implode('-', $allAirportIataCodes));
    }

    public function setLastItemsRelatedFilesHash($partnerName, $allAirportIataCodes, $newItemsRelatedFilesHash)
    {
        $cacheKey = '__PARTNER_INTEGRATION_ITEMS_RELATED_FILES_HASH__' . $partnerName;
        hSetCache($cacheKey, implode('-', $allAirportIataCodes), $newItemsRelatedFilesHash);
    }
}
