<?php
namespace App\Background\Helpers;


class UpdateHelper
{
    public static function getAllPossibleValuesInCSV($csvExtracted, $columnIndex)
    {
        $returnList = [];
        foreach ($csvExtracted as $k => $v) {
            if ($k == 0) {
                continue;
            }

            $values = trim($v[$columnIndex]);
            if (empty($values)) {
                continue;
            }

            $values = explode(';', $values);
            $values = array_map('trim', $values);

            $returnList = array_merge($returnList, $values);
        }

        return array_unique($returnList);
    }


    public static function removeFromListByUniqueId($list, $uniqueId)
    {
        /* @todo change it to classes */
        foreach ($list as $k => $item) {
            if ($item['uniqueId'] == $uniqueId) {
                unset($list[$k]);
            }
        }
        return $list;
    }
}
