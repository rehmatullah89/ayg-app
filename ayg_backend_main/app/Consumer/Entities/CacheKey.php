<?php
namespace App\Consumer\Entities;

/**
 * Class CacheKey
 * @package App\Consumer\Entities
 *
 * This class contains all cache keys that are used in the app
 * those values are set as constants
 */
class CacheKey extends Entity
{
    /**
     * type Route
     * used in \App\Consumer\Controllers\InfoController::helloWorld
     */
    const ROUTE_INFO_HELLO_WORLD = '__ROUTE_INFO_HW';

    /**
     * type Route
     * used in \App\Consumer\Controllers\InfoController::helloWorld
     */
    const ROUTE_INFO_HELLO_WORLD_ERROR = '__ROUTE_INFO_HW_ER';

    const ROUTE_INFO_AIRPORT_BY_CODE_PREFIX = '__ROUTE_INFO_AIR_CODE_';

    /**
     * type Route
     * @see \App\Consumer\Controllers\UserController::addPhoneWithTwilio
     */
    const ROUTE_USER_ADD_PHONE = '__ROUTE_USER_ADD_PHONE_';

    const USER_PHONE_VERIFY_CODE_PREFIX = '__USER_PHONE_';

    const USER_PHONE_VERIFY_ATTEMPT_COUNTER_PREFIX = '__USER_PHONE_VERIFY_ATTEMPT_COUNTER_';

    const USER_PHONE_ADD_ATTEMPT_COUNTER_PREFIX = '__USER_PHONE_ADD_ATTEMPT_COUNTER_';

    const LAST_ORDER_RATING = '__LAST_ORDER_RATING_';

    const ORDER_RATING = '__ORDER_RATING_';

    /**
     * @see \App\Consumer\Helpers\CacheKeyHelper::getCompletedDeliveryAssignmentWithDeliveryByOrderIdKey()
     */
    const ORDER_COMPLETE_DELIVERY_ASSIGNMENT = '__ORDER_COMPLETE_DELIVERY_ASSIGNMENT_';

    /**
     * @see \App\Consumer\Repositories\RetailerItemPropertiesCacheRepository::getActiveByUniqueRetailerItemIdAndDayOfWeek()
     */
    const RETAILER_ITEM_PROPERTIES_BY_RETAILER_AND_DAY = '__RETAILER_ITEM_PROPERTIES_';

    const RETAILER_PARTNER_BY_RETAILER_ID_KEY = '__RETAILER_PARTNER_BY_RETAILER_ID_KEY_';

    const RETAILER_PARTNER_BY_RETAILER_UNIQUE_ID_KEY = '__RETAILER_PARTNER_BY_RATAILER_UNIQUE_ID_KEY_';


    const DELIVERY_RESTRICTION = '__DELIVERY_RESTRICTION_';
    const PICKUP_RESTRICTION = '__PICKUP_RESTRICTION_';
}
