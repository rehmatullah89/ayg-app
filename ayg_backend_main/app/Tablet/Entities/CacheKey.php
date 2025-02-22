<?php
namespace App\Tablet\Entities;

/**
 * Class CacheKey
 * @package App\Tablet\Entities
 */
class CacheKey extends Entity
{
    const RETAILER_PING_PREFIX = '__PING_';

    const RETAILER_LIST_BY_TABLET_USER_PREFIX = '__RETAILER_LIST_BY_TABLET_USER_';

    const ORDER_MODS_BY_ORDER_ID_PREFIX = '__ORDER_MODS_BY_ORDER_ID_';

    const RETAILER_ITEM_MODIFIER_LIST_BY_UNIQUE_IDS_PREFIX = '__RETAILER_ITEM_MODIFIER_LIST_BY_UNIQUE_IDS_';

    const RETAILER_ITEM_MODIFIER_OPTION_LIST_BY_UNIQUE_IDS_PREFIX = '__RETAILER_ITEM_MODIFIER_OPTION_LIST_BY_UNIQUE_IDS_';
}