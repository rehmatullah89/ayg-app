<?php
namespace App\Consumer\Helpers;

use App\Consumer\Entities\CacheKey;
use Slim\Route;

/**
 * Class CacheKeyHelper
 * @package App\Consumer\Helpers
 */
class CacheKeyHelper
{
    /**
     * @param Route $route
     * @return string
     *
     * gets cache Key for a given route,
     * for every route name, the cache key can be taken directly from CacheKey Class
     * or can be generated based on params and prefix is taken from CacheKey Class
     */
    public static function getCacheKeyByRoute(Route $route)
    {
        $name = $route->getName();
        $params = $route->getParams();

        switch ($name) {
            case 'info-hello-world':
                return CacheKey::ROUTE_INFO_HELLO_WORLD;
                break;
            case 'info-hello-world-with-params':
                return CacheKey::ROUTE_INFO_AIRPORT_BY_CODE_PREFIX . '_' . $params['code'];
                break;
            case 'user-add-phone-twilio':
                return CacheKey::ROUTE_USER_ADD_PHONE . '_' . $params[0]->getId() . $params['phoneCountryCode'] . $params['phoneNumber'];
                break;
            default:
                return null;
        }

    }

    /**
     * @param $phoneId
     * @return string
     */
    public static function getUserVerifyPhoneKey($phoneId)
    {
        return CacheKey::USER_PHONE_VERIFY_CODE_PREFIX . $phoneId;
    }

    /**
     * @param $phoneId
     * @return string
     */
    public static function getUserVerifyPhoneAttemptCounterKey($phoneId)
    {
        return CacheKey::USER_PHONE_VERIFY_ATTEMPT_COUNTER_PREFIX . $phoneId;
    }

    /**
     * @param $phoneId
     * @return string
     */
    public static function getUserAddPhoneAttemptCounterKey($phoneId)
    {
        return CacheKey::USER_PHONE_ADD_ATTEMPT_COUNTER_PREFIX . $phoneId;
    }

    /**
     * @param $orderId
     * @param $userId
     * @return string
     */
    public static function getLastRatingByOrderIdAndUserIdKey($orderId, $userId)
    {
        return CacheKey::LAST_ORDER_RATING . $orderId . '_' . $userId;
    }

    /**
     * @param $orderId
     * @param $userId
     * @return string
     */
    public static function getOrderRatingWithFeedbackKey($orderId, $userId, $overAllRating, $feedback)
    {
        return CacheKey::ORDER_RATING . $orderId . '_' . $userId;
    }

    /**
     * @param $orderId
     * @return string
     */
    public static function getCompletedDeliveryAssignmentWithDeliveryByOrderIdKey($orderId)
    {
        return CacheKey::ORDER_COMPLETE_DELIVERY_ASSIGNMENT . $orderId;
    }

    public static function getDeliveryRestrictionKey($retailerId, $locationId)
    {
        return CacheKey::DELIVERY_RESTRICTION . $retailerId . $locationId;
    }

    public static function getPickupRestrictionKey($retailerId, $locationId)
    {
        return CacheKey::PICKUP_RESTRICTION . $retailerId . $locationId;
    }

    public static function getActiveByUniqueRetailerItemIdAndDayOfWeekKey($uniqueRetailerItemId, $dayOfWeekAtAirport)
    {
        return CacheKey::RETAILER_ITEM_PROPERTIES_BY_RETAILER_AND_DAY . $uniqueRetailerItemId . $dayOfWeekAtAirport;
    }

    public static function getRetailerPartnerByRetailerIdKey($retailerId): string
    {
        return CacheKey::RETAILER_PARTNER_BY_RETAILER_ID_KEY . $retailerId;
    }

    public static function getRetailerPartnerByRetailerUniqueIdKey($retailerUniqueId): string
    {
        return CacheKey::RETAILER_PARTNER_BY_RETAILER_UNIQUE_ID_KEY . $retailerUniqueId;
    }
}
