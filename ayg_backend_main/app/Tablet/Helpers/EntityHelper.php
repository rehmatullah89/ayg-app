<?php
namespace App\Tablet\Helpers;

/**
 * Class EntityHelper
 * @package App\Tablet\Helpers
 */
class EntityHelper
{
    /**
     * @param array $entityList
     * @return array
     */
    public static function listOfEntitiesIntoListOfIds(array $entityList)
    {
        $result = [];
        foreach ($entityList as $item) {
            $result[] = $item->getId();
        }
        return $result;
    }
}