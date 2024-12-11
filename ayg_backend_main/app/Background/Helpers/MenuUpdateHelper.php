<?php
namespace App\Background\Helpers;


class MenuUpdateHelper
{
    public static function modifyMenuToDbArrayForm($items, $itemTimes, $modifiers, $modifierOptions)
    {
        $return = [];
        foreach ($items as $item) {
            if ($item['isActive'] != 'Y') {
                continue;
            }

            foreach ($itemTimes as $itemTime) {
                if ($itemTime['isActive'] != 'Y') {
                    continue;
                }
                if ($itemTime['uniqueRetailerItemId'] == $item['uniqueId']) {
                    $item['__itemTimes'][] = $itemTime;
                }
            }
            foreach ($modifiers as $modifier) {
                if ($modifier['isActive'] != 'Y') {
                    continue;
                }
                if ($modifier['uniqueRetailerItemId'] == $item['uniqueId']) {

                    foreach ($modifierOptions as $modifierOption) {
                        if ($modifierOption['isActive'] != 'Y') {
                            continue;
                        }
                        if ($modifierOption['uniqueRetailerItemModifierId'] == $modifier['uniqueId']) {
                            $modifier['__options'][$modifierOption['optionId']] = $modifierOption;
                        }
                    }
                    $item['__modifiers'][$modifier['modifierId']] = $modifier;
                }
            }
            $return[$item['itemId']] = $item;
        }
        return $return;
    }


    public static function removeFromListByUniqueId($list, $uniqueId)
    {
        /* @todo change it to classes */
        foreach ($list as $k => $item) {
            if ($item['uniqueId'] == $uniqueId) {
                unset($list[$k]);
                break;
            }
        }
        return $list;
    }


    public static function getItemMeat($item)
    {
        /* @todo change it to classes */
        $return = [];
        $return['itemPrice'] = intval($item['itemPrice']);
        $return['itemPOSName'] = trim($item['itemPOSName']);
        $return['itemDisplayDescription'] = stripslashes(str_replace(["\n", "\r", "\n\r", "\r\n"], '',
            trim($item['itemDisplayDescription'])));
        $return['itemCategoryName'][] = trim($item['itemCategoryName']);
        $return['itemDisplaySequence'][] = intval($item['itemDisplaySequence']);
        $return['allowedThruSecurity'][] = strtoupper($item['allowedThruSecurity']);

        $return['itemTimes']['restrictOrderTimes'] = [];
        if (isset($item['__itemTimes'])) {
            foreach ($item['__itemTimes'] as $v) {
                $entry = trim(trim($v['restrictOrderTimes']), '0');
                $entry = str_replace(' - 0', ' - ', $entry);
                $return['itemTimes']['restrictOrderTimes'][] = $entry;
            }
        }
        sort($return['itemTimes']['restrictOrderTimes']);


        $return['modifiers']['modifierId'] = [];
        $return['modifiers']['modifierPOSName'] = [];
        $return['modifiers']['maxQuantity'] = [];
        $return['modifiers']['minQuantity'] = [];
        $return['modifiers']['isRequired'] = [];
        $return['modifiers']['options'] = [];

        if (isset($item['__modifiers'])) {
            foreach ($item['__modifiers'] as $v) {
                $return['modifiers']['modifierId'][] = $v['modifierId'];
                $return['modifiers']['modifierPOSName'][] = trim($v['modifierPOSName']);
                $return['modifiers']['maxQuantity'][] = intval($v['maxQuantity']);
                $return['modifiers']['minQuantity'][] = intval($v['minQuantity']);
                $return['modifiers']['isRequired'][] = strtoupper($v['isRequired']);
                $return['modifiers']['modifierDisplaySequence'][] = intval($v['modifierDisplaySequence']);

                if (isset($v['__options'])) {
                    foreach ($v['__options'] as $vv) {
                        unset($option);
                        $option[] = trim($vv['optionId']);
                        $option[] = trim($vv['optionDisplayName']);
                        $option[] = intval(trim($vv['pricePerUnit']));
                        $option[] = intval(trim($vv['optionDisplaySequence']));
                        $return['modifiers']['options'][] = implode('--|||||--', str_replace(["\t"], '',$option));
                    }
                }
            }
        }
        sort($return['modifiers']['modifierId']);
        sort($return['modifiers']['modifierPOSName']);
        sort($return['modifiers']['maxQuantity']);
        sort($return['modifiers']['minQuantity']);
        sort($return['modifiers']['isRequired']);
        sort($return['modifiers']['options']);


        return (json_encode($return));
    }
}
