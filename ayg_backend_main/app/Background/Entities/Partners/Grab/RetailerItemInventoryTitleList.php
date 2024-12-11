<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;

class RetailerItemInventoryTitleList extends Entity implements \JsonSerializable
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerItemInventoryTitle $retailerItemInventoryTitle)
    {
        $this->list[] = $retailerItemInventoryTitle;
    }

    public static function createFromArray(array $array)
    {
        $list = new RetailerItemInventoryTitleList();

        foreach ($array as $item) {
            $list->addItem(new RetailerItemInventoryTitle(
                $item['detailsDescription'],
                $item['endTime'],
                $item['imageNameLong'],
                $item['imageNameWide'],
                "", // subtitles skipped
                $item['inventoryTitleDescription'],
                $item['inventoryTitleID'],
                $item['inventoryTitleOrder'],
                $item['startTime']
            ));
        }

        return $list;
    }

    public function getFirst():?RetailerItemInventoryTitle
    {
        if (!isset($this->list[0])) {
            return null;
        }
        return $this->list[0];
    }

    function jsonSerialize()
    {
        return $this->list;
    }
}
