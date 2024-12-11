<?php
namespace App\Background\Entities\Partners\Grab;

use App\Background\Entities\Entity;
use App\Background\Exceptions\Exception;

class RetailerOpeningHoursList extends Entity implements \JsonSerializable
{
    private $list;

    public function __construct()
    {
        $this->list = [];
    }

    public function addItem(RetailerOpeningHours $retailerOpeningHours)
    {
        $this->list[] = $retailerOpeningHours;
    }

    public static function createFromString(string $string): RetailerOpeningHoursList
    {
        // mo;12:01 AM;11:59 PM;tu;12:01 AM;11:59 PM;we;12:01 AM;11:59 PM;th;12:01 AM;11:59 PM;fr;12:01 AM;11:59 PM;sa;12:01 AM;11:59 PM;su;12:01 AM;11:59 PM;

        $array = explode(';', $string);
        if (count($array) !== 22) {
            throw  new Exception('opening hours format is incorrect');
        }

        $list = new RetailerOpeningHoursList();

        for ($i = 0; $i < 7; $i++) {
            $j = $i * 3;
            $list->addItem(new RetailerOpeningHours(
                ($i + 1),
                $array[$j + 1],
                $array[$j + 2]
            ));
        }

        return $list;
    }

    public function getByDayId(int $id):?RetailerOpeningHours
    {
        // 1 = monday
        /** @var RetailerOpeningHours $item */
        foreach ($this->list as $item) {
            if ($item->getDay() == $id) {
                return $item;
            }
        }
        return null;
    }

    function jsonSerialize()
    {
        return $this->list;
    }
}
